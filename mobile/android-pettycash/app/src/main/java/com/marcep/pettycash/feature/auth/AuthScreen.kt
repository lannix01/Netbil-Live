package com.marcep.pettycash.feature.auth

import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.foundation.rememberScrollState
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import com.marcep.pettycash.R

@Composable
fun AuthScreen(
    state: AuthUiState,
    onEmailChanged: (String) -> Unit,
    onPasswordChanged: (String) -> Unit,
    onLogin: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Box(
        modifier = modifier
            .fillMaxSize()
            .imePadding(),
        contentAlignment = Alignment.Center,
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .verticalScroll(rememberScrollState())
                .padding(20.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Spacer(Modifier.height(12.dp))
            Card(
                shape = RoundedCornerShape(18.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White),
            ) {
                Image(
                    painter = painterResource(id = R.drawable.app_logo),
                    contentDescription = "App logo",
                    modifier = Modifier
                        .padding(12.dp)
                        .size(124.dp),
                )
            }
            Text("Skybrix PettyCash", style = MaterialTheme.typography.headlineSmall)
            Text(
                "Sign in to continue",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )

            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.surface,
                ),
            ) {
                Column(Modifier.padding(16.dp)) {
                    OutlinedTextField(
                        value = state.email,
                        onValueChange = onEmailChanged,
                        label = { Text("Email") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Next),
                        modifier = Modifier.fillMaxWidth(),
                    )
                    Spacer(Modifier.height(12.dp))
                    OutlinedTextField(
                        value = state.password,
                        onValueChange = onPasswordChanged,
                        label = { Text("Password") },
                        visualTransformation = PasswordVisualTransformation(),
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Done),
                        modifier = Modifier.fillMaxWidth(),
                    )

                    if (!state.error.isNullOrBlank()) {
                        Spacer(Modifier.height(8.dp))
                        Text(
                            text = state.error,
                            color = MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodySmall,
                        )
                    }

                    Spacer(Modifier.height(16.dp))

                    Button(
                        onClick = onLogin,
                        enabled = !state.loading,
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        if (state.loading) {
                            CircularProgressIndicator(
                                strokeWidth = 2.dp,
                                modifier = Modifier.height(18.dp),
                            )
                            Spacer(Modifier.width(8.dp))
                        }
                        Text(if (state.loading) "Signing in..." else "Sign In")
                    }

                    TextButton(
                        onClick = {},
                        enabled = false,
                        modifier = Modifier.align(Alignment.End),
                    ) {
                        Text("API-based Android build")
                    }
                }
            }
        }
    }
}
