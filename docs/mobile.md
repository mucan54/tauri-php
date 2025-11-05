# Mobile Development Guide

Complete guide to building Android and iOS applications with Tauri-PHP.

## Overview

Tauri-PHP supports building native mobile applications for Android and iOS using the same Laravel codebase. Your Laravel app runs embedded with FrankenPHP, providing a true native mobile experience.

## Prerequisites

### For Android Development

**Required:**
- Java JDK 11 or higher ([Adoptium](https://adoptium.net/))
- Android Studio with Android SDK
- Android NDK (install via Android Studio SDK Manager)
- Set `ANDROID_HOME` environment variable

**Recommended:**
- Android device or emulator for testing

### For iOS Development (macOS only)

**Required:**
- macOS (iOS development only works on Mac)
- Xcode 14+ from Mac App Store
- Xcode Command Line Tools
- CocoaPods: `sudo gem install cocoapods`
- Apple Developer account (for device testing & distribution)

**Recommended:**
- iOS device or simulator for testing

## Getting Started

### Step 1: Initialize Desktop Project

First, initialize Tauri for desktop:

```bash
php artisan tauri:init
```

### Step 2: Initialize Mobile Platform

Initialize Android, iOS, or both:

```bash
# Android only
php artisan tauri:mobile-init android

# iOS only (macOS required)
php artisan tauri:mobile-init ios --team-id=YOUR_TEAM_ID

# Both platforms
php artisan tauri:mobile-init both --team-id=YOUR_TEAM_ID
```

**Options:**
- `--package-name`: Custom package name (default: uses app identifier)
- `--team-id`: iOS Team ID (required for iOS, get from developer.apple.com)

This creates:
- Android: `src-tauri/gen/android/` - Android Studio project
- iOS: `src-tauri/gen/apple/` - Xcode project

### Step 3: Configure Mobile Settings

Edit `.env.tauri` to customize mobile settings:

```env
# Android
TAURI_ANDROID_PACKAGE_NAME=com.example.myapp
TAURI_ANDROID_MIN_SDK=24
TAURI_ANDROID_TARGET_SDK=33

# iOS
TAURI_IOS_BUNDLE_IDENTIFIER=com.example.myapp
TAURI_IOS_DEPLOYMENT_TARGET=13.0
TAURI_IOS_TEAM_ID=YOUR_TEAM_ID
```

## Development

### Running on Android

#### Option 1: Using Emulator

```bash
# Start emulator automatically and run app
php artisan tauri:mobile-dev android --emulator
```

#### Option 2: Using Physical Device

1. Enable USB debugging on your Android device
2. Connect device via USB
3. Run:

```bash
php artisan tauri:mobile-dev android
```

#### Option 3: Using Specific Device

```bash
# List devices
adb devices

# Run on specific device
php artisan tauri:mobile-dev android --device=DEVICE_ID
```

**Important:** Use `--host=0.0.0.0` to make the dev server accessible from mobile device:

```bash
php artisan tauri:mobile-dev android --host=0.0.0.0 --port=8080
```

### Running on iOS

#### Using iOS Simulator

```bash
php artisan tauri:mobile-dev ios
```

This automatically starts the iOS Simulator.

#### Using Physical iOS Device

1. Open Xcode project: `src-tauri/gen/apple/[YourApp].xcodeproj`
2. Configure signing in Xcode:
   - Select your project
   - Go to "Signing & Capabilities"
   - Select your development team
   - Enable "Automatically manage signing"
3. Connect iOS device
4. Run from Xcode or:

```bash
php artisan tauri:mobile-dev ios --device=YOUR_DEVICE_NAME
```

## Building for Production

### Building for Android

#### Build APK (for testing)

```bash
php artisan tauri:build --platform=android --apk
```

Output: `src-tauri/gen/android/app/build/outputs/apk/`

#### Build AAB (for Play Store)

```bash
php artisan tauri:build --platform=android
```

Output: `src-tauri/gen/android/app/build/outputs/bundle/`

#### Debug Build

```bash
php artisan tauri:build --platform=android --debug
```

### Building for iOS

```bash
php artisan tauri:build --platform=ios
```

Output: `src-tauri/gen/apple/build/`

#### Open in Xcode After Build

```bash
php artisan tauri:build --platform=ios --open
```

## Code Signing

### Android Code Signing

1. **Generate Keystore:**

```bash
keytool -genkey -v -keystore my-release-key.keystore -alias my-key-alias -keyalg RSA -keysize 2048 -validity 10000
```

2. **Configure in `.env.tauri`:**

```env
TAURI_SIGN_ANDROID=true
TAURI_ANDROID_KEYSTORE=/path/to/my-release-key.keystore
TAURI_ANDROID_KEYSTORE_PASSWORD=your-keystore-password
TAURI_ANDROID_KEY_ALIAS=my-key-alias
TAURI_ANDROID_KEY_PASSWORD=your-key-password
```

3. **Build signed APK/AAB:**

```bash
php artisan tauri:build --platform=android
```

### iOS Code Signing

1. **Configure in Xcode:**
   - Open `src-tauri/gen/apple/[YourApp].xcodeproj`
   - Select project → Signing & Capabilities
   - Select your Team
   - Choose provisioning profile

2. **Or configure in `.env.tauri`:**

```env
TAURI_SIGN_IOS=true
TAURI_IOS_DEVELOPMENT_TEAM=YOUR_TEAM_ID
TAURI_IOS_PROVISIONING_PROFILE=YOUR_PROFILE_UUID
```

3. **Build:**

```bash
php artisan tauri:build --platform=ios
```

## Mobile-Specific Considerations

### 1. Database

Use SQLite for mobile apps:

```env
# .env (embedded)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### 2. File Storage

Mobile has limited storage. Use Laravel's storage system:

```php
Storage::disk('local')->put('file.txt', $contents);
```

### 3. Network Access

Check if app is running on mobile:

```php
if (env('TAURI_EMBEDDED') && request()->header('User-Agent') // contains mobile keywords) {
    // Mobile-specific logic
}
```

### 4. Performance

- Minimize asset sizes (images, CSS, JS)
- Use lazy loading
- Optimize database queries
- Cache aggressively

### 5. Permissions

Mobile apps need explicit permissions. Configure in:

**Android:** `src-tauri/gen/android/app/src/main/AndroidManifest.xml`

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
```

**iOS:** `src-tauri/gen/apple/[YourApp]/Info.plist`

```xml
<key>NSCameraUsageDescription</key>
<string>We need camera access to scan QR codes</string>
```

## Publishing

### Publishing to Google Play Store

1. Build signed AAB:
   ```bash
   php artisan tauri:build --platform=android
   ```

2. Create Google Play Console account

3. Create new app in Play Console

4. Upload AAB file

5. Complete store listing

6. Submit for review

### Publishing to Apple App Store

1. Build for iOS:
   ```bash
   php artisan tauri:build --platform=ios
   ```

2. Open in Xcode:
   ```bash
   php artisan tauri:build --platform=ios --open
   ```

3. Archive app (Product → Archive)

4. Upload to App Store Connect

5. Complete app information in App Store Connect

6. Submit for review

## Troubleshooting

### Android Issues

**"ANDROID_HOME not set"**
- Set environment variable to Android SDK location
- Example: `export ANDROID_HOME=$HOME/Android/Sdk`

**"No device found"**
- Check: `adb devices`
- Enable USB debugging on device
- Try: `adb kill-server && adb start-server`

**Build fails with NDK error**
- Install NDK via Android Studio SDK Manager
- Set NDK version in `.env.tauri`: `TAURI_ANDROID_NDK_VERSION=25.2.9519653`

### iOS Issues

**"Development team not found"**
- Open Xcode project
- Configure signing in Signing & Capabilities
- Select your team

**"Provisioning profile doesn't match"**
- In Xcode: Preferences → Accounts
- Download Manual Profiles
- Or use automatic signing

**Simulator doesn't start**
- Open Xcode → Preferences → Locations
- Ensure Command Line Tools are selected
- Try: `xcrun simctl list devices`

### General Mobile Issues

**App crashes on startup**
- Check logs:
  - Android: `adb logcat`
  - iOS: Console.app or Xcode logs
- Verify PHP errors in Laravel logs

**Server not accessible from device**
- Use `--host=0.0.0.0` in dev command
- Check firewall allows port 8080
- Ensure device is on same network

**Large APK/IPA size**
- Enable code obfuscation: `--obfuscate`
- Remove unused PHP extensions in `.env.tauri`
- Optimize assets (compress images)

## Best Practices

1. **Test on Real Devices** - Emulators don't catch all issues
2. **Handle Offline Mode** - Mobile devices lose connectivity
3. **Optimize for Touch** - Ensure UI elements are touch-friendly
4. **Respect Battery** - Minimize background tasks
5. **Follow Platform Guidelines** - Material Design (Android), Human Interface Guidelines (iOS)
6. **Test Different Screen Sizes** - Use responsive design
7. **Implement Error Handling** - Mobile environments are unpredictable

## Next Steps

- Review [Configuration Reference](configuration.md)
- Check [Troubleshooting Guide](troubleshooting.md)
- Read Tauri Mobile docs: https://v2.tauri.app/develop/
