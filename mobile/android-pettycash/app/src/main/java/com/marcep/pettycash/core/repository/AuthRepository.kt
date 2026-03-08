package com.marcep.pettycash.core.repository

import com.marcep.pettycash.core.data.SessionStore
import com.marcep.pettycash.core.model.ActionResult
import com.marcep.pettycash.core.model.AuthSessionListing
import com.marcep.pettycash.core.model.AuthSessionRecord
import com.marcep.pettycash.core.model.SessionState
import com.marcep.pettycash.core.network.LoginRequest
import com.marcep.pettycash.core.network.LogoutAllRequest
import com.marcep.pettycash.core.network.PettyApiService
import com.marcep.pettycash.core.network.RefreshRequest
import com.marcep.pettycash.core.network.SessionPayloadParser
import com.marcep.pettycash.core.network.arr
import com.marcep.pettycash.core.network.asObj
import com.marcep.pettycash.core.network.int
import com.marcep.pettycash.core.network.obj
import com.marcep.pettycash.core.network.str
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val api: PettyApiService,
    private val sessionStore: SessionStore,
) {

    suspend fun login(email: String, password: String): ActionResult<SessionState> {
        return try {
            val envelope = api.login(LoginRequest(email = email.trim(), password = password))
            if (!envelope.success || envelope.data == null) {
                return ActionResult.failure(envelope.message.ifBlank { "Login failed." })
            }

            val session = SessionPayloadParser.fromAuthData(envelope.data)
            if (!session.isLoggedIn) {
                return ActionResult.failure("Login response missing token.")
            }

            sessionStore.saveSession(session)
            ActionResult.success(session)
        } catch (t: Throwable) {
            ActionResult.failure(t.message ?: "Unable to login.")
        }
    }

    suspend fun refreshToken(): ActionResult<SessionState> {
        val current = sessionStore.currentSession()
        if (!current.isLoggedIn) return ActionResult.failure("Not logged in.")

        return try {
            val envelope = api.refresh(RefreshRequest())
            if (!envelope.success || envelope.data == null) {
                return ActionResult.failure(envelope.message.ifBlank { "Token refresh failed." })
            }

            val session = SessionPayloadParser.fromAuthData(envelope.data, current)
            if (!session.isLoggedIn) return ActionResult.failure("Refresh response missing token.")
            sessionStore.saveSession(session)
            ActionResult.success(session)
        } catch (t: Throwable) {
            ActionResult.failure(t.message ?: "Unable to refresh token.")
        }
    }

    suspend fun hydrateUserFromMe(): ActionResult<SessionState> {
        val current = sessionStore.currentSession()
        if (!current.isLoggedIn) return ActionResult.failure("No active session.")

        return try {
            val envelope = api.me()
            if (!envelope.success || envelope.data == null) {
                return ActionResult.failure(envelope.message.ifBlank { "Failed to load profile." })
            }

            val merged = SessionPayloadParser.mergeMeData(current, envelope.data)
            sessionStore.saveSession(merged)
            ActionResult.success(merged)
        } catch (t: Throwable) {
            ActionResult.failure(t.message ?: "Unable to load profile.")
        }
    }

    suspend fun logoutCurrent(clearLocalEvenOnFailure: Boolean = true): ActionResult<Unit> {
        return try {
            val envelope = api.logoutCurrent()
            if (clearLocalEvenOnFailure || envelope.success) {
                sessionStore.clearSession()
            }
            if (!envelope.success) return ActionResult.failure(envelope.message.ifBlank { "Logout failed." })
            ActionResult.success(Unit)
        } catch (t: Throwable) {
            if (clearLocalEvenOnFailure) sessionStore.clearSession()
            ActionResult.failure(t.message ?: "Unable to logout current session.")
        }
    }

    suspend fun logoutAll(includeCurrent: Boolean): ActionResult<Unit> {
        return try {
            val envelope = api.logoutAll(LogoutAllRequest(includeCurrent = includeCurrent))
            if (!envelope.success) return ActionResult.failure(envelope.message.ifBlank { "Logout-all failed." })
            if (includeCurrent) {
                sessionStore.clearSession()
            }
            ActionResult.success(Unit)
        } catch (t: Throwable) {
            ActionResult.failure(t.message ?: "Unable to logout all sessions.")
        }
    }

    suspend fun revokeSession(tokenId: Int): ActionResult<Boolean> {
        return try {
            val envelope = api.revokeSession(tokenId)
            if (!envelope.success) return ActionResult.failure(envelope.message.ifBlank { "Failed to revoke session." })
            val revokedCurrent = envelope.data?.str("revoked_current").toBooleanSafe()
            if (revokedCurrent) {
                sessionStore.clearSession()
            }
            ActionResult.success(revokedCurrent)
        } catch (t: Throwable) {
            ActionResult.failure(t.message ?: "Unable to revoke session.")
        }
    }

    suspend fun listSessions(allUsers: Boolean = false, userId: Int? = null): ActionResult<AuthSessionListing> {
        return try {
            val envelope = api.sessions(
                allUsers = if (allUsers) 1 else null,
                userId = userId,
            )
            if (!envelope.success || envelope.data == null) {
                return ActionResult.failure(envelope.message.ifBlank { "Failed to list sessions." })
            }

            val scope = envelope.data.str("scope").orEmpty().ifBlank { "current_user" }
            val sessions = envelope.data.arr("tokens")
                ?.mapNotNull { it.asObj() }
                ?.mapNotNull { row ->
                    val id = row.int("id") ?: return@mapNotNull null
                    val userObj = row.obj("user")
                    AuthSessionRecord(
                        id = id,
                        name = row.str("name").orEmpty(),
                        deviceId = row.str("device_id"),
                        devicePlatform = row.str("device_platform"),
                        isCurrent = row.str("is_current").toBooleanSafe(),
                        lastIp = row.str("last_ip"),
                        lastUserAgent = row.str("last_user_agent"),
                        lastUsedAt = row.str("last_used_at"),
                        expiresAt = row.str("expires_at"),
                        createdAt = row.str("created_at"),
                        userId = userObj?.int("id"),
                        userName = userObj?.str("name"),
                        userEmail = userObj?.str("email"),
                        userRole = userObj?.str("role"),
                    )
                }
                .orEmpty()

            ActionResult.success(AuthSessionListing(scope = scope, sessions = sessions))
        } catch (t: Throwable) {
            ActionResult.failure(t.message ?: "Unable to list sessions.")
        }
    }

    suspend fun currentSession(): SessionState = sessionStore.currentSession()
    suspend fun clearLocalSession() = sessionStore.clearSession()
}

private fun String?.toBooleanSafe(): Boolean {
    val raw = this?.trim()?.lowercase() ?: return false
    return raw == "1" || raw == "true" || raw == "yes"
}
