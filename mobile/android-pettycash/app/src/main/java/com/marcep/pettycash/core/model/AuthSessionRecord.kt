package com.marcep.pettycash.core.model

data class AuthSessionRecord(
    val id: Int,
    val name: String,
    val deviceId: String? = null,
    val devicePlatform: String? = null,
    val isCurrent: Boolean = false,
    val lastIp: String? = null,
    val lastUserAgent: String? = null,
    val lastUsedAt: String? = null,
    val expiresAt: String? = null,
    val createdAt: String? = null,
    val userId: Int? = null,
    val userName: String? = null,
    val userEmail: String? = null,
    val userRole: String? = null,
)

data class AuthSessionListing(
    val scope: String = "current_user",
    val sessions: List<AuthSessionRecord> = emptyList(),
) {
    val total: Int
        get() = sessions.size
}
