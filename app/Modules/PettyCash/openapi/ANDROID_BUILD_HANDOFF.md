# Android Build Handoff (PettyCash)

Use this package for Android app generation:

1. OpenAPI contract: `app/Modules/PettyCash/openapi/petty_v1.openapi.yaml`
2. Human contract notes: `app/Modules/PettyCash/API_V1.md`

## Backend assumptions

- Base URL: `https://<your-domain>/api/petty/v1`
- Auth: Bearer token in `Authorization` header
- Standard envelope:
  - success: `{ success, message, data, meta }`
  - error: `{ success, message, errors, meta }`
- `meta.request_id` and `meta.api_version` should be surfaced in debug/error logs.

## Required Android modules

- Auth module:
  - login, me, refresh, logout current, logout all, sessions list, revoke single session
- Dashboard module:
  - insights dashboard, ledger
- Spendings module:
  - list/create/update/delete spendings
- Token hostels module:
  - hostels list/details/create/update/delete
  - payments create/update/delete
- Maintenance module:
  - schedule/history/unroadworthy, bike profile, services CRUD
- Masters module:
  - bikes/respondents CRUD
- Credits module:
  - credits list/create/show/update

## Recommended stack

- Kotlin + Jetpack Compose
- Retrofit + OkHttp + Moshi/Kotlinx Serialization
- Paging3 for paginated lists
- Hilt for DI
- DataStore for token/session persistence
- Coroutines + Flow + ViewModel

## Prompt you can give another AI (if needed)

"Build an Android app (Kotlin, Jetpack Compose) from this OpenAPI file: `petty_v1.openapi.yaml`.
Use clean architecture, repository pattern, Retrofit, Paging3, Hilt, and DataStore.
Implement bearer auth with automatic token refresh using `/auth/refresh` on 401 once, then retry original request.
Support session management using `/auth/tokens`, `/auth/tokens/current`, `/auth/tokens/{tokenId}`, and `/auth/logout-all`.
Render server envelope errors from `message` and field errors from `errors`.
Generate production-ready code with feature modules: Auth, Dashboard, Spendings, TokenHostels, Maintenance, Masters, Credits." 

## What to share when asking for app generation

- `petty_v1.openapi.yaml`
- Base API host (dev/staging/prod)
- Desired app name/logo/colors
- Android min SDK target
- Preferred package name (e.g. `com.marcep.pettycash`)
