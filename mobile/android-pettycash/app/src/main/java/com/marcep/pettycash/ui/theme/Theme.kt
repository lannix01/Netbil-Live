package com.marcep.pettycash.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable

private val LightColors = lightColorScheme(
    primary = BrandPrimary,
    secondary = BrandSecondary,
    background = BrandBackground,
    surface = BrandSurface,
    error = BrandError,
)

private val DarkColors = darkColorScheme(
    primary = BrandPrimary,
    secondary = BrandSecondary,
)

@Composable
fun PettyCashTheme(
    darkTheme: Boolean = false,
    content: @Composable () -> Unit,
) {
    val colors = if (darkTheme) DarkColors else LightColors

    MaterialTheme(
        colorScheme = colors,
        typography = Typography,
        content = content,
    )
}
