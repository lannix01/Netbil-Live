package com.marcep.pettycash.core.model

enum class ModuleKey(val title: String, val endpoint: String) {
    DASHBOARD("Dashboard", "GET /insights/dashboard"),
    CREDITS("Credits", "GET /credits"),
    SPENDINGS("Spendings", "GET /spendings"),
    TOKENS("Token Hostels", "GET /tokens/hostels"),
    MAINTENANCE("Maintenance", "GET /maintenances/schedule"),
    MASTERS("Bikes", "GET /masters/bikes"),
    RESPONDENTS("Respondents", "GET /masters/respondents"),
}

data class ModuleSummary(
    val key: ModuleKey,
    val value: String,
    val subtitle: String,
    val ok: Boolean,
)
