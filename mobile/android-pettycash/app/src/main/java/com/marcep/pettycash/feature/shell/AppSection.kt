package com.marcep.pettycash.feature.shell

enum class AppSection(val title: String, val subtitle: String) {
    DASHBOARD("Dashboard", "Live module health + balances"),
    CREDITS("Credits", "List and record credits"),
    SPENDINGS("Spendings", "List and add bike/meal/other spendings"),
    TOKENS("Token Hostels", "Hostels + token payments"),
    MAINTENANCE("Maintenance", "Schedule, history, and services"),
    BIKES("Bikes Master", "Manage bike records"),
    RESPONDENTS("Respondents", "Manage respondents"),
    NOTIFICATIONS("Notifications", "In-app notices and reminders"),
    REPORTS("Reports & Lookups", "Reference IDs and available batches"),
    SESSION("Session", "Device sessions and logout controls"),
}
