package com.marcep.pettycash

import android.widget.Toast
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import com.marcep.pettycash.core.notifications.DueAlertsScheduler
import androidx.hilt.navigation.compose.hiltViewModel
import com.marcep.pettycash.feature.auth.AuthScreen
import com.marcep.pettycash.feature.auth.AuthViewModel
import com.marcep.pettycash.feature.home.SessionViewModel
import com.marcep.pettycash.feature.shell.ShellScreen
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text

@Composable
fun AppRoot(
    authViewModel: AuthViewModel = hiltViewModel(),
    sessionViewModel: SessionViewModel = hiltViewModel(),
) {
    val authState by authViewModel.uiState.collectAsState()
    val session by sessionViewModel.session.collectAsState()
    val sessionGateLoading by sessionViewModel.sessionGateLoading.collectAsState()
    val activeSessionsCount by sessionViewModel.activeSessionsCount.collectAsState()
    val activeSessionsError by sessionViewModel.activeSessionsError.collectAsState()
    val sessionScope by sessionViewModel.sessionScope.collectAsState()
    val sessionRows by sessionViewModel.sessionRows.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(authState.welcomeMessage) {
        val message = authState.welcomeMessage ?: return@LaunchedEffect
        Toast.makeText(context, message, Toast.LENGTH_SHORT).show()
        authViewModel.consumeWelcomeMessage()
    }

    LaunchedEffect(session.isLoggedIn, sessionGateLoading) {
        if (session.isLoggedIn && !sessionGateLoading) {
            DueAlertsScheduler.schedule(context)
        } else {
            DueAlertsScheduler.cancel(context)
        }
    }

    if (!session.isLoggedIn) {
        AuthScreen(
            state = authState,
            onEmailChanged = authViewModel::onEmailChanged,
            onPasswordChanged = authViewModel::onPasswordChanged,
            onLogin = authViewModel::login,
        )
        return
    }

    if (sessionGateLoading) {
        SessionGateScreen()
        return
    }

    LaunchedEffect(session.accessToken) {
        if (session.isLoggedIn && !sessionGateLoading) {
            sessionViewModel.refreshSessionStats()
        }
    }

    ShellScreen(
        userName = session.userName,
        userRole = session.userRole,
        activeSessionsCount = activeSessionsCount,
        activeSessionsError = activeSessionsError,
        sessionScope = sessionScope,
        sessionRows = sessionRows,
        onRefreshSessionStats = sessionViewModel::refreshSessionStats,
        onRevokeSession = sessionViewModel::revokeSession,
        onLogoutCurrent = sessionViewModel::logoutCurrent,
        onLogoutAll = sessionViewModel::logoutAllAndCurrent,
    )
}

@Composable
private fun SessionGateScreen() {
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center,
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            CircularProgressIndicator()
            Text(
                text = "Validating session...",
                style = MaterialTheme.typography.bodyMedium,
            )
        }
    }
}
