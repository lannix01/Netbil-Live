package com.marcep.pettycash.core.network

import com.marcep.pettycash.core.model.SessionState
import kotlinx.serialization.json.JsonObject
import java.time.OffsetDateTime

object SessionPayloadParser {
    fun fromAuthData(data: JsonObject, fallback: SessionState? = null): SessionState {
        val userObj = data.obj("user")

        return SessionState(
            accessToken = data.str("access_token") ?: fallback?.accessToken.orEmpty(),
            tokenType = data.str("token_type") ?: fallback?.tokenType ?: "Bearer",
            expiresAt = data.str("expires_at") ?: fallback?.expiresAt,
            // Persist a parse-safe local timestamp for strict 24h session gating.
            sessionStartedAt = fallback?.sessionStartedAt ?: OffsetDateTime.now().toString(),
            userId = userObj?.int("id") ?: fallback?.userId,
            userName = userObj?.str("name") ?: fallback?.userName,
            userEmail = userObj?.str("email") ?: fallback?.userEmail,
            userRole = userObj?.str("role") ?: fallback?.userRole,
        )
    }

    fun mergeMeData(base: SessionState, data: JsonObject): SessionState {
        val userObj = data.obj("user") ?: return base
        return base.copy(
            userId = userObj.int("id") ?: base.userId,
            userName = userObj.str("name") ?: base.userName,
            userEmail = userObj.str("email") ?: base.userEmail,
            userRole = userObj.str("role") ?: base.userRole,
        )
    }
}
