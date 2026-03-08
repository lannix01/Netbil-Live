package com.marcep.pettycash.feature.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.marcep.pettycash.core.data.SessionStore
import com.marcep.pettycash.core.model.AuthSessionRecord
import com.marcep.pettycash.core.model.SessionState
import com.marcep.pettycash.core.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

@HiltViewModel
class SessionViewModel @Inject constructor(
    private val sessionStore: SessionStore,
    private val authRepository: AuthRepository,
) : ViewModel() {
    private var sessionTimeoutHandled = false
    private var lastValidatedAccessToken: String? = null
    private var validatingAccessToken: String? = null

    val session: StateFlow<SessionState> = sessionStore.sessionFlow
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), SessionState())

    private val _sessionGateLoading = MutableStateFlow(true)
    val sessionGateLoading: StateFlow<Boolean> = _sessionGateLoading.asStateFlow()

    private val _activeSessionsCount = MutableStateFlow<Int?>(null)
    val activeSessionsCount: StateFlow<Int?> = _activeSessionsCount.asStateFlow()

    private val _activeSessionsError = MutableStateFlow<String?>(null)
    val activeSessionsError: StateFlow<String?> = _activeSessionsError.asStateFlow()

    private val _sessionScope = MutableStateFlow("current_user")
    val sessionScope: StateFlow<String> = _sessionScope.asStateFlow()

    private val _sessionRows = MutableStateFlow<List<AuthSessionRecord>>(emptyList())
    val sessionRows: StateFlow<List<AuthSessionRecord>> = _sessionRows.asStateFlow()

    init {
        viewModelScope.launch {
            session.collect { current ->
                if (!current.isLoggedIn) {
                    sessionTimeoutHandled = false
                    lastValidatedAccessToken = null
                    validatingAccessToken = null
                    _sessionGateLoading.value = false
                    return@collect
                }

                if (current.isTokenExpired() || current.isPastLocalSessionWindow(hours = 24)) {
                    expireSession("Session expired after 24 hours. Please sign in again.")
                    return@collect
                }

                val token = current.accessToken
                if (token.isBlank() || token == lastValidatedAccessToken || token == validatingAccessToken) {
                    return@collect
                }

                validateActiveSession(token)
            }
        }

        viewModelScope.launch {
            while (true) {
                enforceLocalSessionWindow()
                delay(60_000)
            }
        }
    }

    fun hydrateProfileIfNeeded() {
        if (!session.value.isLoggedIn) return
        viewModelScope.launch {
            authRepository.hydrateUserFromMe()
            refreshSessionStats()
        }
    }

    fun refreshSessionStats() {
        if (!session.value.isLoggedIn) return
        viewModelScope.launch {
            val includeAllUsers = session.value.userRole.equals("admin", ignoreCase = true)
            val result = authRepository.listSessions(allUsers = includeAllUsers)
            if (result.ok) {
                val listing = result.data!!
                _sessionScope.value = listing.scope
                _sessionRows.value = listing.sessions
                _activeSessionsCount.value = listing.total
                _activeSessionsError.value = null
            } else {
                _activeSessionsError.value = result.error
            }
        }
    }

    fun logoutCurrent() {
        viewModelScope.launch {
            authRepository.logoutCurrent(clearLocalEvenOnFailure = true)
            resetSessionUi()
        }
    }

    fun logoutAllAndCurrent() {
        viewModelScope.launch {
            val result = authRepository.logoutAll(includeCurrent = true)
            if (!result.ok) {
                authRepository.clearLocalSession()
            }
            resetSessionUi()
        }
    }

    fun revokeSession(tokenId: Int, isCurrent: Boolean) {
        if (!session.value.isLoggedIn) return
        viewModelScope.launch {
            val result = authRepository.revokeSession(tokenId)
            if (!result.ok) {
                _activeSessionsError.value = result.error
                return@launch
            }

            val revokedCurrent = result.data == true || isCurrent
            if (revokedCurrent) {
                resetSessionUi()
                return@launch
            }

            refreshSessionStats()
        }
    }

    private suspend fun enforceLocalSessionWindow() {
        val current = session.value
        if (!current.isLoggedIn || sessionTimeoutHandled) return
        if (!current.isPastLocalSessionWindow(hours = 24) && !current.isTokenExpired()) return

        expireSession("Session expired after 24 hours. Please sign in again.")
    }

    private suspend fun validateActiveSession(accessToken: String) {
        if (!session.value.isLoggedIn) return
        validatingAccessToken = accessToken
        _sessionGateLoading.value = true
        val result = authRepository.hydrateUserFromMe()
        if (result.ok) {
            lastValidatedAccessToken = accessToken
            validatingAccessToken = null
            _sessionGateLoading.value = false
            return
        }
        validatingAccessToken = null
        _sessionGateLoading.value = false
        expireSession(result.error ?: "Session validation failed. Please sign in again.")
    }

    private suspend fun expireSession(message: String) {
        if (sessionTimeoutHandled) return
        sessionTimeoutHandled = true
        authRepository.logoutCurrent(clearLocalEvenOnFailure = true)
        lastValidatedAccessToken = null
        validatingAccessToken = null
        _sessionGateLoading.value = false
        resetSessionUi(message)
    }

    private fun resetSessionUi(errorMessage: String? = null) {
        _activeSessionsCount.value = null
        _activeSessionsError.value = errorMessage
        _sessionScope.value = "current_user"
        _sessionRows.value = emptyList()
    }
}
