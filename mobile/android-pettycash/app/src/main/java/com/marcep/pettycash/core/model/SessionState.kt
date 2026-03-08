package com.marcep.pettycash.core.model

import java.time.Instant
import java.time.LocalDate
import java.time.LocalDateTime
import java.time.OffsetDateTime
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter

data class SessionState(
    val accessToken: String = "",
    val tokenType: String = "Bearer",
    val expiresAt: String? = null,
    val sessionStartedAt: String? = null,
    val userId: Int? = null,
    val userName: String? = null,
    val userEmail: String? = null,
    val userRole: String? = null,
) {
    val isLoggedIn: Boolean
        get() = accessToken.isNotBlank()

    fun authHeaderValue(): String? {
        if (accessToken.isBlank()) return null
        val normalizedType = if (tokenType.isBlank()) "Bearer" else tokenType
        return "$normalizedType $accessToken"
    }

    fun isPastLocalSessionWindow(hours: Long = 24): Boolean {
        val startedAt = parseFlexibleDateTime(sessionStartedAt) ?: return false
        return startedAt.plusHours(hours).isBefore(OffsetDateTime.now())
    }

    fun isTokenExpired(now: OffsetDateTime = OffsetDateTime.now()): Boolean {
        val expiry = parseFlexibleDateTime(expiresAt) ?: return false
        return !expiry.isAfter(now)
    }
}

private fun parseFlexibleDateTime(raw: String?): OffsetDateTime? {
    if (raw.isNullOrBlank()) return null
    val value = raw.trim()

    fun parse(block: () -> OffsetDateTime?): OffsetDateTime? = runCatching { block() }.getOrNull()

    parse { OffsetDateTime.parse(value) }?.let { return it }
    parse { Instant.parse(value).atOffset(ZoneOffset.UTC) }?.let { return it }
    parse { LocalDateTime.parse(value).atOffset(ZoneOffset.UTC) }?.let { return it }
    parse {
        LocalDateTime.parse(
            value,
            DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"),
        ).atOffset(ZoneOffset.UTC)
    }?.let { return it }
    parse { LocalDate.parse(value).atStartOfDay().atOffset(ZoneOffset.UTC) }?.let { return it }

    val epochRaw = value.toLongOrNull() ?: return null
    val epochMillis = if (value.length > 10) epochRaw else epochRaw * 1000L
    return runCatching { Instant.ofEpochMilli(epochMillis).atOffset(ZoneOffset.UTC) }.getOrNull()
}
