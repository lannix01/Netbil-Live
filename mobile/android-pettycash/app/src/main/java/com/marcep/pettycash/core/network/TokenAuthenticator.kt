package com.marcep.pettycash.core.network

import com.marcep.pettycash.core.data.SessionStore
import javax.inject.Inject
import kotlinx.coroutines.runBlocking
import okhttp3.Authenticator
import okhttp3.Request
import okhttp3.Response
import okhttp3.Route

class TokenAuthenticator @Inject constructor(
    private val sessionStore: SessionStore,
    private val refreshService: TokenRefreshService,
) : Authenticator {

    override fun authenticate(route: Route?, response: Response): Request? {
        if (responseCount(response) >= 2) return null
        if (response.request.header("X-Auth-Retry") == "1") return null

        val current = runBlocking { sessionStore.currentSession() }
        val currentHeader = current.authHeaderValue() ?: return null

        val refreshResponse = try {
            refreshService.refresh(currentHeader, RefreshRequest()).execute()
        } catch (_: Throwable) {
            runBlocking { sessionStore.clearSession() }
            return null
        }

        val body = refreshResponse.body()
        if (!refreshResponse.isSuccessful || body?.success != true || body.data == null) {
            runBlocking { sessionStore.clearSession() }
            return null
        }

        val refreshed = SessionPayloadParser.fromAuthData(body.data, current)
        if (!refreshed.isLoggedIn) {
            runBlocking { sessionStore.clearSession() }
            return null
        }

        runBlocking { sessionStore.saveSession(refreshed) }

        return response.request.newBuilder()
            .header("Authorization", refreshed.authHeaderValue() ?: return null)
            .header("X-Auth-Retry", "1")
            .build()
    }

    private fun responseCount(response: Response): Int {
        var count = 1
        var r = response.priorResponse
        while (r != null) {
            count++
            r = r.priorResponse
        }
        return count
    }
}
