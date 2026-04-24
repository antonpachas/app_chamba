import java.util.Properties

plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

val keystorePropertiesFile = rootProject.file("key.properties")
val keystoreProperties = Properties()
val hasReleaseKeystore: Boolean = keystorePropertiesFile.exists().also { exists ->
    if (exists) {
        keystorePropertiesFile.inputStream().use { keystoreProperties.load(it) }
    }
}

android {
    namespace = "com.example.chamba_app"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.example.chamba_app"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    signingConfigs {
        if (hasReleaseKeystore) {
            create("release") {
                keyAlias = keystoreProperties.getProperty("keyAlias")
                    ?: error("key.properties: falta keyAlias")
                keyPassword = keystoreProperties.getProperty("keyPassword")
                    ?: error("key.properties: falta keyPassword")
                storePassword = keystoreProperties.getProperty("storePassword")
                    ?: error("key.properties: falta storePassword")
                val storePath = keystoreProperties.getProperty("storeFile")
                    ?: error("key.properties: falta storeFile")
                // Ruta absoluta o relativa al proyecto `android/` (no a `android/app/`).
                storeFile = rootProject.file(storePath)
            }
        }
    }

    buildTypes {
        release {
            signingConfig = if (hasReleaseKeystore) {
                signingConfigs.getByName("release")
            } else {
                signingConfigs.getByName("debug")
            }
        }
    }
}

flutter {
    source = "../.."
}
