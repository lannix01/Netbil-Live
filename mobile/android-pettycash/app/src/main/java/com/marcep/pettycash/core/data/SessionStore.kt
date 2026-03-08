package com.marcep.pettycash.core.data

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.emptyPreferences
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.marcep.pettycash.core.model.SessionState
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import java.io.IOException

private val Context.sessionDataStore: DataStore<Preferences> by preferencesDataStore(name = "petty_session")

@Singleton
class SessionStore @Inject constructor(
    @ApplicationContext private val context: Context,
) {
    private val dataStore = context.sessionDataStore

    private val tokenKey = stringPreferencesKey("access_token")
    private val tokenTypeKey = stringPreferencesKey("token_type")
    private val expiresAtKey = stringPreferencesKey("expires_at")
    private val sessionStartedAtKey = stringPreferencesKey("session_started_at")
    private val userIdKey = intPreferencesKey("user_id")
    private val userNameKey = stringPreferencesKey("user_name")
    private val userEmailKey = stringPreferencesKey("user_email")
    private val userRoleKey = stringPreferencesKey("user_role")

    val sessionFlow: Flow<SessionState> = dataStore.data
        .catch { e ->
            if (e is IOException) emit(emptyPreferences()) else throw e
        }
        .map { prefs ->
            SessionState(
                accessToken = prefs[tokenKey].orEmpty(),
                tokenType = prefs[tokenTypeKey] ?: "Bearer",
                expiresAt = prefs[expiresAtKey],
                sessionStartedAt = prefs[sessionStartedAtKey],
                userId = prefs[userIdKey],
                userName = prefs[userNameKey],
                userEmail = prefs[userEmailKey],
                userRole = prefs[userRoleKey],
            )
        }

    suspend fun currentSession(): SessionState = sessionFlow.first()

    suspend fun saveSession(session: SessionState) {
        dataStore.edit { prefs ->
            prefs[tokenKey] = session.accessToken
            prefs[tokenTypeKey] = session.tokenType
            if (session.expiresAt.isNullOrBlank()) prefs.remove(expiresAtKey) else prefs[expiresAtKey] = session.expiresAt
            if (session.sessionStartedAt.isNullOrBlank()) prefs.remove(sessionStartedAtKey) else prefs[sessionStartedAtKey] = session.sessionStartedAt
            if (session.userId == null) prefs.remove(userIdKey) else prefs[userIdKey] = session.userId
            if (session.userName.isNullOrBlank()) prefs.remove(userNameKey) else prefs[userNameKey] = session.userName
            if (session.userEmail.isNullOrBlank()) prefs.remove(userEmailKey) else prefs[userEmailKey] = session.userEmail
            if (session.userRole.isNullOrBlank()) prefs.remove(userRoleKey) else prefs[userRoleKey] = session.userRole
        }
    }

    suspend fun clearSession() {
        dataStore.edit { it.clear() }
    }
}
