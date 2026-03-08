# Netbil PettyCash Android App (Starter)

Default setup applied:

- `applicationId`: `com.marcep.pettycash`
- `minSdk`: `26`
- UI stack: Jetpack Compose
- Networking: Retrofit + OkHttp
- DI: Hilt
- Auth/session: DataStore + bearer auto-refresh (`/auth/refresh`)

## Project path

- `mobile/android-pettycash`

## Before first run

1. Open project in Android Studio: `mobile/android-pettycash`
2. Update API base URL in `app/build.gradle.kts`:
   - `BuildConfig.API_BASE_URL = "https://your-domain.example/api/petty/v1/"`
3. Sync Gradle and run.
4. APK output names are auto-versioned as:
   - `pettycashv<versionCode>-debug.apk`
   - `pettycashv<versionCode>-release.apk`

## Logo handoff

Logo has been applied from:

- `/root/Marcep/netbil/skybrix-logo.png`

Current logo resources:

- In-app logo: `app/src/main/res/drawable-nodpi/app_logo.png`
- Launcher foreground: `app/src/main/res/drawable/ic_launcher_foreground.xml` (points to `@drawable/app_logo`)

To replace logo later:

- Replace `app/src/main/res/drawable-nodpi/app_logo.png` with a new PNG (keep same filename).

## What is already wired

- Auth endpoints:
  - `/auth/login`
  - `/auth/me`
  - `/auth/refresh`
  - `/auth/tokens/current`
  - `/auth/logout-all`
  - `/auth/tokens`
- Home summaries from live API endpoints:
  - dashboard, credits, spendings, token hostels, maintenance, masters
- Token refresh on 401 once, then request retry.

## Mobile module menu (current)

- Dashboard
- Credits: list + create
- Spendings: list + create
- Token Hostels: list + create hostel + add payment
- Maintenance: schedule/history/unroadworthy + add service
- Bikes Master: list + create
- Respondents: list + create
- Notifications: list + send + mark read/all read
- Reports & Lookups: quick IDs and available batches
- Session: logout current / logout all

## Contract files used

- `app/Modules/PettyCash/openapi/petty_v1.openapi.yaml`
- `app/Modules/PettyCash/API_V1.md`
