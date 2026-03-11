# PettyCash API v1 (Mobile Contract)

Base prefix: `/api/petty/v1`

All responses use this envelope:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {
    "request_id": "a4ef...",
    "api_version": "v1",
    "timestamp": "2026-02-19T12:34:56+00:00"
  }
}
```

Error envelope:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {},
  "meta": {
    "request_id": "a4ef...",
    "api_version": "v1",
    "timestamp": "2026-02-19T12:34:56+00:00"
  }
}
```

Paginated endpoints include `meta.pagination` with:

- `current_page`, `last_page`, `per_page`, `total`, `from`, `to`, `has_more_pages`

## Authentication

- `POST /auth/login`
  - body: `email`, `password`, optional `device_name`, `device_id`, `device_platform`, `revoke_other_sessions`
  - returns: `data.access_token`, `data.token_type`, `data.expires_at`, `data.user`, `data.session`
- `GET /auth/me` (Bearer token required)
- `POST /auth/logout` (Bearer token required)
- `POST /auth/logout-all` (Bearer token required)
  - optional body/query: `include_current` (default `true`)
- `POST /auth/refresh` (Bearer token required)
  - rotates current bearer token and returns a fresh token/session payload
  - body: optional `device_name`, `device_id`, `device_platform`, `revoke_other_sessions`
- `GET /auth/tokens` (Bearer token required)
  - returns active sessions for the current user
- `DELETE /auth/tokens/current` (Bearer token required)
  - revokes only the current session token
- `DELETE /auth/tokens/{tokenId}` (Bearer token required)
  - revokes one session token owned by the current user

Authorization header for protected routes:

`Authorization: Bearer <access_token>`

Response headers on `petty/v1`:

- `X-Petty-Api-Version`: current API version (default `v1`)
- `X-Request-Id`: request trace id (echoed from incoming header or generated)
- `Deprecation`: present as `true` when API deprecation mode is enabled
- `Sunset`: present when a sunset date is configured

Token lifecycle hardening:

- Tokens may be revoked per-session or all at once.
- Login is device-scoped (`device_id` when provided, otherwise `device_name`).
- Idle expiry can be enabled via `PETTY_API_TOKEN_IDLE_TTL_MINUTES`.
- Sliding expiry can be enabled via `PETTY_API_TOKEN_REFRESH_ON_USE=true`.
- Maximum active sessions per user is configurable with `PETTY_API_MAX_ACTIVE_TOKENS_PER_USER`.

Rate limits:

- Login: `throttle:petty-api-login` (config `PETTY_API_LOGIN_RATE_LIMIT_PER_MINUTE`, default `20`)
- Authenticated API: `throttle:petty-api` (config `PETTY_API_RATE_LIMIT_PER_MINUTE`, default `120`)

OpenAPI spec file (for Android/client generation):

- `app/Modules/PettyCash/openapi/petty_v1.openapi.yaml`

## Token Hostel: Batches

- `GET /tokens/batches/available`
  - returns batch list with available net balances

- `GET /batches/available`
  - same response as `/tokens/batches/available` (generic alias for mobile clients)

## Master Data

- `GET /masters/bikes`
  - query: `q`, `status`, `per_page`
- `POST /masters/bikes` (roles: `admin`, `accountant`)
  - body: `plate_no` (required), `model`, `status`
- `PUT/PATCH /masters/bikes/{bike}` (roles: `admin`, `accountant`)
- `DELETE /masters/bikes/{bike}` (role: `admin`, blocked if related spendings/services exist)

- `GET /masters/respondents`
  - query: `q`, `category`, `per_page`
- `POST /masters/respondents` (roles: `admin`, `accountant`)
  - body: `name` (required), `phone`, `category`
- `PUT/PATCH /masters/respondents/{respondent}` (roles: `admin`, `accountant`)
- `DELETE /masters/respondents/{respondent}` (role: `admin`, blocked if related spendings exist)

## Credits

- `GET /credits`
  - query: `from`, `to`, `batch_id`, `q`, `per_page`
  - returns: `data.credits`, `data.summary`, `meta.pagination`
- `POST /credits` (roles: `admin`, `accountant`)
  - body: `amount`, `date` required; `reference`, `transaction_cost`, `description` optional
  - behavior: creates new batch and credit entry together
- `GET /credits/{credit}`
- `PUT/PATCH /credits/{credit}` (roles: `admin`, `accountant`)

## Spendings (Bike/Meal/Other)

- `GET /spendings`
  - query:
    - `type` (`bike`, `meal`, `other`) optional
    - `sub_type`, `batch_id`, `respondent_id`, `bike_id`, `from`, `to`, `q`, `per_page`
  - returns:
    - `data.spendings`
    - `data.summary` (principal, transaction cost, net totals)
    - `meta.pagination`

- `POST /spendings` (roles: `admin`, `accountant`)
  - body:
    - funding: `funding` required (`auto` or `single`)
    - if `single`: `batch_id` required
    - common: `type` (`bike|meal|other`), `amount`, `date`
    - optional: `reference`, `transaction_cost`, `description`, `respondent_id`
    - bike-specific: `sub_type` (`fuel|maintenance`), `bike_id` (or `related_id`), `particulars` required for `maintenance`
    - meal behavior: `sub_type` forced to `lunch`
  - returns spending + allocation breakdown

- `GET /spendings/{spending}`
- `PUT/PATCH /spendings/{spending}` (roles: `admin`, `accountant`)
  - requires `funding`; supports partial field updates
- `DELETE /spendings/{spending}` (role: `admin`)
  - token spendings are blocked here; use token endpoints

## Maintenance and Services

- `GET /maintenances/schedule`
  - query: `q`, `status` (`overdue|due_soon|ok|never|unroadworthy`), `soon_days`, `per_page`
  - returns bike schedule status summary + paginated bikes

- `GET /maintenances/history`
  - query: `q`, `bike_id`, `from`, `to`, `per_page`
  - returns service records with totals

- `GET /maintenances/unroadworthy`
  - query: `q`, `per_page`
  - returns bikes currently marked unroadworthy

- `GET /maintenances/bikes/{bike}`
  - query: `services_per_page`, `maintenances_per_page`
  - returns bike profile, service history, maintenance spendings, totals

- `POST /maintenances/bikes/{bike}/services` (roles: `admin`, `accountant`)
  - body: `service_date` required; optional `next_due_date`, `reference`, `work_done`, `amount`, `transaction_cost`

- `PUT/PATCH /maintenances/services/{service}` (roles: `admin`, `accountant`)
  - partial updates supported

- `DELETE /maintenances/services/{service}` (role: `admin`)
  - also writes audit log when `petty_bike_service_logs` table exists

- `POST /maintenances/bikes/{bike}/unroadworthy` (roles: `admin`, `accountant`)
  - body: `is_unroadworthy` (boolean), optional `unroadworthy_notes`

## Insights

- `GET /insights/dashboard`
  - query: `from`, `to`
  - returns dashboard financial summary + service widgets (`overdue`, `due_soon`, `never_serviced`)

- `GET /insights/ledger`
  - query: `from`, `to`, `batch_id`, `type`, `source`, `q`, `sort`, `per_page`
  - returns unified ledger entries and totals for filtered set

## Reports

- `GET /reports/lookups`
  - returns report filter data: `batches`, `bikes`, `respondents`, `hostels`, and default bucket list
  - query: `batch_limit` (default `100`, max `500`)

- `GET /reports/general`
  - query:
    - `batch_ids[]`, `from`, `to`
    - `bike_id`, `respondent_id`
    - `include[]` bucket filters (e.g. `bike:fuel`, `bike:maintenance`, `meal:lunch`, `token:hostel`, `other`)
    - `view` (`combined|split`)
    - `q` (search)
    - `include_chart` (`0|1`)
    - `include_rows` (`0|1`)
    - `max_rows` (default `500`, max `2000`)
  - returns:
    - net totals (`credited`, `debit`, `balance`)
    - net totals by bucket
    - optional chart image base64 (`chart_b64`)
    - optional credits and spendings detail rows

## Token Hostel: Hostels

- `GET /tokens/onts`
  - returns ONT/site directory options used for hostel creation and merge flows
  - returns:
    - `data.onts[]` with `key`, `hostel_name`, `site_id`, `router_count_suggestion`
    - `data.source_count`, `data.fetched_at`
  - notes:
    - provider sync failures return `503`

- `GET /tokens/hostels`
  - query:
    - `q` (search name, meter, phone)
    - `sort_due` (`asc` or `desc`)
    - `per_page` (`15`, `25`, `30`, `50`, `100`)
  - returns:
    - `data.hostels`
    - `data.summary_current_page`
    - `meta.pagination`

- `POST /tokens/hostels` (roles: `admin`, `accountant`)
  - body:
    - `hostel_name` (required if `ont_key` omitted)
    - `ont_key` (optional but recommended, from `GET /tokens/onts`)
    - `meter_no`, `phone_no` (optional)
    - `no_of_routers` (optional integer)
    - `stake` (`monthly` or `semester`)
    - `amount_due` (required numeric)
  - validation:
    - hostel name must match a directory ONT/site name when strict ONT validation is enabled

- `GET /tokens/hostels/{hostel}`
  - query: `payments_per_page` (`10`, `20`, `50`, `100`)
  - returns `data.hostel`, `data.payments`, `meta.pagination`

- `PUT/PATCH /tokens/hostels/{hostel}` (roles: `admin`, `accountant`)
  - same fields as create, all optional (`sometimes`)
  - if `hostel_name` or `ont_key` is supplied, name is revalidated against ONT directory

- `POST /tokens/hostels/{hostel}/merge-ont` (roles: `admin`, `accountant`)
  - body:
    - `ont_key` (required, from `GET /tokens/onts`)
    - `no_of_routers` (optional override; if omitted, suggested count is used)
  - purpose:
    - sync existing hostel name (and optionally router count) from ONT directory

- `DELETE /tokens/hostels/{hostel}` (role: `admin`)
  - blocked with `409` if transactions exist

## Token Hostel: Payments

- `POST /tokens/hostels/{hostel}/payments` (roles: `admin`, `accountant`)
  - body:
    - `funding`: `auto` or `single` (required)
    - `batch_id`: required when `funding=single`
    - `amount` (required)
    - `transaction_cost` (optional)
    - `date` (required, `Y-m-d`)
    - `reference`, `receiver_name`, `receiver_phone`, `notes`, `meter_no` (optional)
  - returns `data.hostel`, `data.payment`, `data.spending`, `data.allocations`, `data.supports_spending_link`

- `PUT/PATCH /tokens/payments/{payment}` (roles: `admin`, `accountant`)
  - same payload as payment create
  - only supported for payments linked to `spending_id`
  - returns `409` for legacy records without `spending_id`

- `DELETE /tokens/payments/{payment}` (role: `admin`)
  - only supported for payments linked to `spending_id`
  - returns `409` for legacy records without `spending_id`

## Migrations Required for Full API Update/Delete Support

- `2026_02_19_000001_create_petty_api_tokens_table.php`
- `2026_02_19_000002_add_spending_id_to_petty_payments_table.php`

If migration `000002` is skipped, payment creation still works, but update/delete APIs for payments return `409` because linking to `spending_id` is unavailable.
