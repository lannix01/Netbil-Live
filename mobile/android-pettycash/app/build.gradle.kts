plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.kotlin.serialization)
    alias(libs.plugins.hilt.android)
    alias(libs.plugins.ksp)
}

android {
    namespace = "com.marcep.pettycash"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.marcep.pettycash"
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
        vectorDrawables {
            useSupportLibrary = true
        }

        buildConfigField("String", "API_BASE_URL", "\"https://netbil.marcepagency.com/api/petty/v1/\"")
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    kotlinOptions {
        jvmTarget = "17"
    }
    buildFeatures {
        compose = true
        buildConfig = true
    }
    splits {
        abi {
            isEnable = true
            reset()
            include("armeabi-v7a", "arm64-v8a", "x86_64")
            isUniversalApk = false
        }
    }
    bundle {
        abi { enableSplit = true }
        density { enableSplit = true }
        language { enableSplit = true }
    }
    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
    }
}

val appVersionCodeProvider = providers.provider {
    android.defaultConfig.versionCode ?: 1
}

tasks.register("renameDebugApk") {
    doLast {
        val outDir = layout.buildDirectory.dir("outputs/apk/debug").get().asFile
        val sources = outDir.listFiles()
            ?.filter { it.isFile && it.extension == "apk" && it.name.startsWith("app") }
            .orEmpty()
        sources.forEach { source ->
            val suffix = source.name.removePrefix("app-").removePrefix("app")
            val targetName = "pettycashv${appVersionCodeProvider.get()}-$suffix"
            val target = outDir.resolve(targetName)
            source.copyTo(target, overwrite = true)
            if (source.name != target.name) source.delete()
        }
    }
}

tasks.register("renameReleaseApk") {
    doLast {
        val outDir = layout.buildDirectory.dir("outputs/apk/release").get().asFile
        val sources = outDir.listFiles()
            ?.filter { it.isFile && it.extension == "apk" && it.name.startsWith("app") }
            .orEmpty()
        sources.forEach { source ->
            val suffix = source.name.removePrefix("app-").removePrefix("app")
            val targetName = "pettycashv${appVersionCodeProvider.get()}-$suffix"
            val target = outDir.resolve(targetName)
            source.copyTo(target, overwrite = true)
            if (source.name != target.name) source.delete()
        }
    }
}

tasks.matching { it.name == "assembleDebug" }.configureEach {
    finalizedBy("renameDebugApk")
}

tasks.matching { it.name == "assembleRelease" }.configureEach {
    finalizedBy("renameReleaseApk")
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.lifecycle.runtime.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.activity.compose)

    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)
    implementation(libs.androidx.compose.icons)
    implementation(libs.androidx.navigation.compose)
    implementation("com.google.android.material:material:1.12.0")

    implementation(libs.retrofit)
    implementation(libs.okhttp)
    implementation(libs.okhttp.logging)
    implementation(libs.serialization.json)
    implementation(libs.retrofit.serialization.converter)

    implementation(libs.hilt.android)
    ksp(libs.hilt.compiler)
    implementation(libs.androidx.hilt.navigation.compose)
    implementation(libs.androidx.work.runtime.ktx)
    implementation(libs.androidx.hilt.work)
    ksp(libs.androidx.hilt.compiler)

    implementation(libs.androidx.datastore.preferences)
    implementation(libs.kotlinx.coroutines.android)
    implementation("com.google.zxing:core:3.5.3")

    debugImplementation(platform(libs.androidx.compose.bom))
    debugImplementation("androidx.compose.ui:ui-tooling")
    debugImplementation("androidx.compose.ui:ui-test-manifest")
}
