package com.marcep.pettycash.feature.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.marcep.pettycash.core.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class AuthUiState(
    val email: String = "",
    val password: String = "",
    val loading: Boolean = false,
    val error: String? = null,
    val welcomeMessage: String? = null,
)

@HiltViewModel
class AuthViewModel @Inject constructor(
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow(AuthUiState())
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    fun onEmailChanged(value: String) {
        _uiState.update { it.copy(email = value, error = null) }
    }

    fun onPasswordChanged(value: String) {
        _uiState.update { it.copy(password = value, error = null) }
    }

    fun consumeWelcomeMessage() {
        _uiState.update { it.copy(welcomeMessage = null) }
    }

    fun login() {
        val snapshot = _uiState.value
        if (snapshot.email.isBlank() || snapshot.password.isBlank()) {
            _uiState.update { it.copy(error = "Email and password are required.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(loading = true, error = null) }
            val result = authRepository.login(snapshot.email, snapshot.password)
            if (!result.ok) {
                _uiState.update { it.copy(loading = false, error = result.error ?: "Login failed") }
                return@launch
            }
            authRepository.hydrateUserFromMe()
            val session = result.data
            val identity = session?.userName
                ?.takeIf { it.isNotBlank() }
                ?: session?.userEmail
                ?.takeIf { it.isNotBlank() }
                ?: snapshot.email
            _uiState.update {
                it.copy(
                    loading = false,
                    password = "",
                    error = null,
                    welcomeMessage = "Welcome, $identity.",
                )
            }
        }
    }
}
