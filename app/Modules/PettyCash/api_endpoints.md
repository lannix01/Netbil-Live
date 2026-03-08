# PettyCash API Endpoints

Reference for PettyCash API v1 endpoints to share with other developers.

- Production Base URL: `https://netbil.marcepagency.com/api/petty/v1`
- If integrating from a different environment/system, keep the same path and replace only the host.
  Example: `https://<your-domain>/api/petty/v1`
- Auth: `Authorization: Bearer <access_token>` for protected endpoints
- Response envelope: `success`, `message`, `data`, `meta`
- Write-role note: current backend allows `admin` and `finance` (`accountant` legacy) on most write endpoints unless marked admin-only.

## Authentication and Sessions
| Method | Endpoint | Auth | What it does | When to use | Where used |
|---|---|---|---|---|---|
| POST | `https://netbil.marcepagency.com/api/petty/v1/auth/login` | No | Authenticate user and issue access token/session | User signs in | Mobile app login screen |
| GET | `https://netbil.marcepagency.com/api/petty/v1/auth/me` | Yes | Return current authenticated user profile | App boot / refresh user context | Mobile app session bootstrap |
| POST | `https://netbil.marcepagency.com/api/petty/v1/auth/logout` | Yes | Revoke current session token | User logs out current device | Account/session menu |
| POST | `https://netbil.marcepagency.com/api/petty/v1/auth/logout-all` | Yes | Revoke all user sessions (`include_current` optional) | Force sign-out all devices | Security/account actions |
| POST | `https://netbil.marcepagency.com/api/petty/v1/auth/refresh` | Yes | Rotate current token and return a fresh token/session | Token refresh or session hardening | Background auth refresh |
| GET | `https://netbil.marcepagency.com/api/petty/v1/auth/tokens` | Yes | List active sessions for current user (admin can request all users via query) | Session management UI | Security/session page |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/auth/tokens/current` | Yes | Revoke current session only | "Log out this device" action | Session/device controls |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/auth/tokens/{tokenId}` | Yes | Revoke one specific session token | Remove a specific device session | Session/device controls |

## Funding and Batches
| Method | Endpoint | Auth | What it does | When to use | Where used |
|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/batches/available` | Yes | List batches with available net balances | Need funding source options | Spendings/credits flows |
| GET | `https://netbil.marcepagency.com/api/petty/v1/tokens/batches/available` | Yes | Alias of available batches for token module clients | Token module funding source lookup | Token payment flow |

## Master Data - Bikes
| Method | Endpoint | Auth | Roles | What it does | When to use | Where used |
|---|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/masters/bikes` | Yes | All authenticated users | List bikes (filterable) | Load bike pickers or bike master list | Master data screens |
| POST | `https://netbil.marcepagency.com/api/petty/v1/masters/bikes` | Yes | admin, finance/accountant | Create bike record | Add new bike/vehicle | Admin/master setup |
| PUT/PATCH | `https://netbil.marcepagency.com/api/petty/v1/masters/bikes/{bike}` | Yes | admin, finance/accountant | Update bike record | Edit bike details/status | Admin/master setup |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/masters/bikes/{bike}` | Yes | admin only | Delete bike (blocked if linked data exists) | Cleanup invalid bike records | Admin/master setup |

## Master Data - Respondents
| Method | Endpoint | Auth | Roles | What it does | When to use | Where used |
|---|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/masters/respondents` | Yes | All authenticated users | List respondents (filterable) | Load respondent pickers/lists | Spendings + reports filters |
| POST | `https://netbil.marcepagency.com/api/petty/v1/masters/respondents` | Yes | admin, finance/accountant | Create respondent | Add new respondent | Admin/master setup |
| PUT/PATCH | `https://netbil.marcepagency.com/api/petty/v1/masters/respondents/{respondent}` | Yes | admin, finance/accountant | Update respondent | Edit respondent details | Admin/master setup |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/masters/respondents/{respondent}` | Yes | admin only | Delete respondent (blocked if linked spendings exist) | Cleanup invalid respondent data | Admin/master setup |

## Credits
| Method | Endpoint | Auth | Roles | What it does | When to use | Where used |
|---|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/credits` | Yes | All authenticated users | List credits with summary and pagination | Review incoming funds | Finance/ledger views |
| POST | `https://netbil.marcepagency.com/api/petty/v1/credits` | Yes | admin, finance/accountant | Create a credit and its batch entry | Record new incoming money | Credit capture flow |
| GET | `https://netbil.marcepagency.com/api/petty/v1/credits/{credit}` | Yes | All authenticated users | Get one credit detail | Open credit detail | Credit detail view |
| PUT/PATCH | `https://netbil.marcepagency.com/api/petty/v1/credits/{credit}` | Yes | admin, finance/accountant | Update credit | Correct/update recorded credit | Credit edit flow |

## Spendings (Bike, Meal, Other)
| Method | Endpoint | Auth | Roles | What it does | When to use | Where used |
|---|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/spendings` | Yes | All authenticated users | List spendings with summaries and filters | Browse/filter spending data | Spendings + reports |
| POST | `https://netbil.marcepagency.com/api/petty/v1/spendings` | Yes | admin, finance/accountant | Create spending with allocation logic | Record new spending | Spending entry screens |
| GET | `https://netbil.marcepagency.com/api/petty/v1/spendings/{spending}` | Yes | All authenticated users | Get one spending item | Spending detail | Spendings module |
| PUT/PATCH | `https://netbil.marcepagency.com/api/petty/v1/spendings/{spending}` | Yes | admin, finance/accountant | Update spending | Correct existing records | Spending edit flow |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/spendings/{spending}` | Yes | admin only | Delete spending (token spendings excluded) | Remove invalid entries | Admin correction flow |

## Maintenance and Services
| Method | Endpoint | Auth | Roles | What it does | When to use | Where used |
|---|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/maintenances/schedule` | Yes | All authenticated users | Return maintenance schedule/health buckets | Check due/overdue servicing | Maintenance dashboard |
| GET | `https://netbil.marcepagency.com/api/petty/v1/maintenances/history` | Yes | All authenticated users | List service history records | Review historical servicing | Maintenance history |
| GET | `https://netbil.marcepagency.com/api/petty/v1/maintenances/unroadworthy` | Yes | All authenticated users | List bikes marked unroadworthy | Quick unroadworthy tracking | Operations/maintenance |
| GET | `https://netbil.marcepagency.com/api/petty/v1/maintenances/bikes/{bike}` | Yes | All authenticated users | Return maintenance profile for one bike | Open bike maintenance profile | Maintenance detail |
| POST | `https://netbil.marcepagency.com/api/petty/v1/maintenances/bikes/{bike}/services` | Yes | admin, finance/accountant | Record a new service entry | Add completed service | Maintenance record entry |
| PUT/PATCH | `https://netbil.marcepagency.com/api/petty/v1/maintenances/services/{service}` | Yes | admin, finance/accountant | Update service entry | Correct service record | Maintenance edit |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/maintenances/services/{service}` | Yes | admin only | Delete service entry (audited where supported) | Remove invalid service log | Admin correction flow |
| POST | `https://netbil.marcepagency.com/api/petty/v1/maintenances/bikes/{bike}/unroadworthy` | Yes | admin, finance/accountant | Set/clear unroadworthy status | Update operational bike state | Maintenance controls |

## Insights
| Method | Endpoint | Auth | What it does | When to use | Where used |
|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/insights/dashboard` | Yes | Dashboard-level financial + service widgets | Load KPI summary | Dashboard cards/charts |
| GET | `https://netbil.marcepagency.com/api/petty/v1/insights/ledger` | Yes | Unified ledger dataset with totals | Analyze ledger trends and exports | Analytics/reporting UIs |

## Reports
| Method | Endpoint | Auth | What it does | When to use | Where used |
|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/reports/lookups` | Yes | Return report filter lookup values | Build report filter controls | Report filter forms |
| GET | `https://netbil.marcepagency.com/api/petty/v1/reports/general` | Yes | Return general report totals/charts/rows | Build combined financial reports | Reports module |

## Token Hostels
| Method | Endpoint | Auth | Roles | What it does | When to use | Where used |
|---|---|---|---|---|---|---|
| GET | `https://netbil.marcepagency.com/api/petty/v1/tokens/hostels` | Yes | All authenticated users | List hostels with token-related summaries | Browse hostels and due states | Token hostels list |
| POST | `https://netbil.marcepagency.com/api/petty/v1/tokens/hostels` | Yes | admin, finance/accountant | Create hostel record | Add new hostel for token tracking | Token setup flow |
| GET | `https://netbil.marcepagency.com/api/petty/v1/tokens/hostels/{hostel}` | Yes | All authenticated users | Get one hostel plus payment history | Open hostel profile/payments | Token hostel detail |
| PUT/PATCH | `https://netbil.marcepagency.com/api/petty/v1/tokens/hostels/{hostel}` | Yes | admin, finance/accountant | Update hostel profile fields | Edit hostel details | Token hostel edit |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/tokens/hostels/{hostel}` | Yes | admin only | Delete hostel (blocked if transactions exist) | Cleanup invalid hostel data | Admin correction flow |

## Token Payments
| Method | Endpoint | Auth | Roles | What it does | When to use | Where used |
|---|---|---|---|---|---|---|
| POST | `https://netbil.marcepagency.com/api/petty/v1/tokens/hostels/{hostel}/payments` | Yes | admin, finance/accountant | Record hostel token payment + spending/allocation | Capture new hostel payment | Token payment capture |
| PUT/PATCH | `https://netbil.marcepagency.com/api/petty/v1/tokens/payments/{payment}` | Yes | admin, finance/accountant | Update token payment (only linked records) | Correct token payment | Token payment edit |
| DELETE | `https://netbil.marcepagency.com/api/petty/v1/tokens/payments/{payment}` | Yes | admin only | Delete token payment (only linked records) | Remove invalid payment | Admin correction flow |

## Quick Handoff Notes for Developers
- Most read endpoints are safe for any authenticated role.
- Most write endpoints require `admin` or `finance` (`accountant` legacy).
- Some delete endpoints are strictly admin-only.
- For payment update/delete support, ensure migrations enabling `spending_id` linkage are applied.
- Use `X-Request-Id` in logs to trace requests end-to-end.
