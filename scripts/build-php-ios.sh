#!/bin/bash

#####################################################################
# Build PHP for iOS (arm64 and simulator)
#
# This script cross-compiles PHP and required extensions for iOS
# Based on PMMP build scripts and iOS compilation practices
#####################################################################

set -e

# Configuration
PHP_VERSION="8.3.14"
IOS_MIN_VERSION="14.0"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD_DIR="${SCRIPT_DIR}/../build/ios"
OUTPUT_DIR="${SCRIPT_DIR}/../binaries"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check prerequisites
check_prerequisites() {
    log_info "Checking prerequisites..."

    if ! command -v xcrun &> /dev/null; then
        log_error "Xcode command line tools not found. Install with: xcode-select --install"
        exit 1
    fi

    if ! command -v automake &> /dev/null; then
        log_error "automake not found. Install with: brew install automake"
        exit 1
    fi

    if ! command -v pkg-config &> /dev/null; then
        log_error "pkg-config not found. Install with: brew install pkg-config"
        exit 1
    fi

    log_info "Prerequisites check passed"
}

# Setup build environment for iOS
setup_ios_env() {
    local PLATFORM=$1
    local ARCH=$2

    log_info "Setting up iOS environment for $PLATFORM ($ARCH)..."

    # Get Xcode paths
    export DEVELOPER_DIR=$(xcode-select -print-path)
    export SDK_PATH=$(xcrun --sdk $PLATFORM --show-sdk-path)
    export SDK_VERSION=$(xcrun --sdk $PLATFORM --show-sdk-version)

    # Set architecture and platform
    export ARCH=$ARCH
    export PLATFORM=$PLATFORM

    # Compiler flags
    # Force ucontext-based fiber implementation (not assembly) for iOS compatibility
    # _XOPEN_SOURCE=600 for SUSv3/POSIX.1-2001 - provides ucontext while keeping more APIs visible
    # _DARWIN_C_SOURCE ensures Darwin/BSD APIs (including DNS resolver) remain available
    export CFLAGS="-arch $ARCH -isysroot $SDK_PATH -miphoneos-version-min=$IOS_MIN_VERSION -fembed-bitcode -DZEND_FIBER_UCONTEXT -D_XOPEN_SOURCE=600 -D_DARWIN_C_SOURCE"
    export CXXFLAGS="-arch $ARCH -isysroot $SDK_PATH -miphoneos-version-min=$IOS_MIN_VERSION -fembed-bitcode -DZEND_FIBER_UCONTEXT -D_XOPEN_SOURCE=600 -D_DARWIN_C_SOURCE"
    export LDFLAGS="-arch $ARCH -isysroot $SDK_PATH -miphoneos-version-min=$IOS_MIN_VERSION"

    # Toolchain
    export CC="$(xcrun --sdk $PLATFORM --find clang)"
    export CXX="$(xcrun --sdk $PLATFORM --find clang++)"
    export AR="$(xcrun --sdk $PLATFORM --find ar)"
    export RANLIB="$(xcrun --sdk $PLATFORM --find ranlib)"
    export STRIP="$(xcrun --sdk $PLATFORM --find strip)"
    export LD="$(xcrun --sdk $PLATFORM --find ld)"

    # Host triplet for cross-compilation
    if [[ "$PLATFORM" == "iphonesimulator" ]]; then
        export HOST="$ARCH-apple-darwin"
    else
        export HOST="$ARCH-apple-ios"
    fi

    # Bypass autoconf header check for ucontext.h during cross-compilation
    # iOS SDK has ucontext.h but autoconf can't detect it properly in cross-compile mode
    export ac_cv_header_ucontext_h=yes

    log_info "Environment configured for $PLATFORM ($ARCH)"
}

# Download PHP source
download_php() {
    log_info "Downloading PHP ${PHP_VERSION}..."

    mkdir -p "${BUILD_DIR}"
    cd "${BUILD_DIR}"

    if [ ! -f "php-${PHP_VERSION}.tar.gz" ]; then
        curl -L "https://www.php.net/distributions/php-${PHP_VERSION}.tar.gz" -o "php-${PHP_VERSION}.tar.gz"
        log_info "PHP source downloaded"
    else
        log_info "PHP source already downloaded"
    fi

    if [ ! -d "php-${PHP_VERSION}" ]; then
        tar -xzf "php-${PHP_VERSION}.tar.gz"
        log_info "PHP source extracted"
    fi
}

# Build PHP for specific platform/architecture
build_php() {
    local PLATFORM=$1
    local ARCH=$2
    local OUTPUT_NAME=$3

    log_info "Building PHP for $PLATFORM ($ARCH)..."

    # Setup environment
    setup_ios_env "$PLATFORM" "$ARCH"

    # Build directory
    local BUILD_PHP_DIR="${BUILD_DIR}/php-${PHP_VERSION}-${OUTPUT_NAME}"
    mkdir -p "${BUILD_PHP_DIR}"

    # Copy source to build directory
    if [ ! -d "${BUILD_PHP_DIR}/php-src" ]; then
        cp -R "${BUILD_DIR}/php-${PHP_VERSION}" "${BUILD_PHP_DIR}/php-src"
    fi

    cd "${BUILD_PHP_DIR}/php-src"

    # Remove entire fiber assembly directory (ELF format incompatible with iOS Mach-O)
    # We use ucontext implementation instead via ZEND_FIBER_UCONTEXT
    if [ -d "Zend/asm" ]; then
        log_info "Removing incompatible fiber assembly directory..."
        rm -rf Zend/asm
    fi

    # Apply iOS compatibility modifications
    log_info "Applying iOS compatibility modifications..."

    # Disable DNS resolver functions (iOS doesn't expose HEADER, C_IN, etc.)
    # Since this script is iOS-only, we use __APPLE__ check (simpler than TARGET_OS_IOS)
    if ! grep -q "iOS does not expose BSD resolver" ext/standard/dns.c; then
        log_info "Disabling DNS resolver functions for iOS..."
        # Add iOS check before HAVE_FULL_DNS_FUNCS
        sed -i.bak '/^\/\* }}} \*\/$/,/#ifdef HAVE_FULL_DNS_FUNCS/ {
            /#ifdef HAVE_FULL_DNS_FUNCS/i\
\
/* iOS does not expose BSD resolver internals (HEADER, C_IN, etc.). */\
/* This build script is iOS-only, so __APPLE__ check is sufficient. */\
#ifdef __APPLE__\
#undef HAVE_FULL_DNS_FUNCS\
#endif
        }' ext/standard/dns.c
    fi

    # Disable chroot function (not available on iOS)
    # Since this script is iOS-only, we use __APPLE__ check
    if ! grep -q "chroot() is not supported on iOS" ext/standard/dir.c; then
        log_info "Disabling chroot function for iOS..."
        # Wrap chroot function with iOS check
        sed -i.bak '/^PHP_FUNCTION(chroot)$/,/^}$/ {
            /^{$/a\
#ifdef __APPLE__\
\	php_error_docref(NULL, E_WARNING, "chroot() is not supported on iOS");\
\	RETURN_FALSE;\
#else
            /^}$/i\
#endif
        }' ext/standard/dir.c
    fi

    # Clean previous builds
    make clean 2>/dev/null || true

    # Configure PHP
    log_info "Configuring PHP..."

    ./buildconf --force

    ./configure \
        --host=$HOST \
        --enable-static \
        --disable-shared \
        --disable-all \
        --with-libxml \
        --enable-mysqlnd \
        --enable-pdo \
        --with-pdo-mysql=mysqlnd \
        --with-pdo-sqlite \
        --with-sqlite3 \
        --enable-mbstring \
        --disable-mbregex \
        --enable-bcmath \
        --enable-ctype \
        --enable-fileinfo \
        --enable-tokenizer \
        --enable-xml \
        --enable-simplexml \
        --enable-dom \
        --without-openssl \
        --with-zlib \
        --enable-posix \
        --disable-opcache \
        --disable-phpdbg \
        --without-pcre-jit \
        --disable-fiber-asm \
        --prefix="${BUILD_PHP_DIR}/install"

    # Build
    log_info "Compiling PHP (this may take a while)..."
    make -j$(sysctl -n hw.ncpu)

    # Install to prefix
    make install

    # Copy binary to output
    mkdir -p "${OUTPUT_DIR}"
    cp "${BUILD_PHP_DIR}/install/bin/php" "${OUTPUT_DIR}/php-${OUTPUT_NAME}"

    # Strip binary
    ${STRIP} "${OUTPUT_DIR}/php-${OUTPUT_NAME}"

    local SIZE=$(ls -lh "${OUTPUT_DIR}/php-${OUTPUT_NAME}" | awk '{print $5}')
    log_info "PHP binary created: php-${OUTPUT_NAME} (${SIZE})"
}

# Create universal binary
create_universal_binary() {
    log_info "Creating universal binary..."

    # For simulator, create universal binary from arm64 and x86_64
    if [ -f "${OUTPUT_DIR}/php-iphonesimulator-arm64" ] && [ -f "${OUTPUT_DIR}/php-iphonesimulator-x86_64" ]; then
        lipo -create \
            "${OUTPUT_DIR}/php-iphonesimulator-arm64" \
            "${OUTPUT_DIR}/php-iphonesimulator-x86_64" \
            -output "${OUTPUT_DIR}/php-iphonesimulator"

        log_info "Universal simulator binary created"
    fi
}

# Main build process
main() {
    log_info "Starting PHP iOS build process..."
    log_info "PHP Version: ${PHP_VERSION}"
    log_info "iOS Min Version: ${IOS_MIN_VERSION}"

    check_prerequisites
    download_php

    # Build for iOS device (arm64 only)
    build_php "iphoneos" "arm64" "iphoneos-arm64"

    # Build for iOS simulator (arm64 for Apple Silicon Macs)
    build_php "iphonesimulator" "arm64" "iphonesimulator-arm64"

    # Optional: Build for iOS simulator (x86_64 for Intel Macs)
    # build_php "iphonesimulator" "x86_64" "iphonesimulator-x86_64"
    # create_universal_binary

    log_info ""
    log_info "========================================="
    log_info "PHP iOS Build Complete!"
    log_info "========================================="
    log_info "Binaries location: ${OUTPUT_DIR}"
    log_info ""
    log_info "Device binary:    php-iphoneos-arm64"
    log_info "Simulator binary: php-iphonesimulator-arm64"
    log_info ""
    log_info "Next steps:"
    log_info "1. Test the binaries: ${OUTPUT_DIR}/php-iphonesimulator-arm64 -v"
    log_info "2. Copy to your Tauri project binaries directory"
    log_info "3. Update tauri.conf.json to bundle for iOS"
    log_info ""
}

# Run main
main "$@"
