package com.marcep.pettycash.feature.home

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.ScrollableTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.marcep.pettycash.core.model.ModuleKey
import com.marcep.pettycash.core.model.ModuleSummary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    userName: String?,
    uiState: HomeUiState,
    onRefresh: () -> Unit,
    onLogoutCurrent: () -> Unit,
    onLogoutAll: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var selectedTab by remember { mutableIntStateOf(0) }
    val keys = ModuleKey.entries
    val summaries = uiState.modules.associateBy { it.key }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Skybrix PettyCash")
                        Text(
                            text = userName ?: "Signed in",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                },
                actions = {
                    IconButton(onClick = onRefresh) {
                        Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                    }
                    IconButton(onClick = onLogoutCurrent) {
                        Icon(Icons.AutoMirrored.Filled.ExitToApp, contentDescription = "Logout current")
                    }
                },
            )
        },
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding),
        ) {
            ScrollableTabRow(selectedTabIndex = selectedTab) {
                keys.forEachIndexed { index, key ->
                    Tab(
                        selected = selectedTab == index,
                        onClick = { selectedTab = index },
                        text = { Text(key.title) },
                    )
                }
            }

            val selectedKey = keys[selectedTab]
            val selected = summaries[selectedKey]

            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                item {
                    Text(
                        text = selectedKey.title,
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.SemiBold,
                    )
                }

                if (selected != null) {
                    item { SummaryCard(selected) }
                }

                item {
                    HorizontalDivider()
                    Spacer(Modifier.height(8.dp))
                    Text(
                        "All module health",
                        style = MaterialTheme.typography.titleMedium,
                    )
                }

                items(keys) { key ->
                    SummaryRow(summaries[key])
                }

                item {
                    Spacer(Modifier.height(8.dp))
                    Text(
                        text = "Tip: long-press app icon to update branding after you drop your logo file.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Spacer(Modifier.height(8.dp))
                    Text(
                        text = if (uiState.loading) "Refreshing data..." else "Data loaded from live API endpoints.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }

                item {
                    Spacer(Modifier.height(16.dp))
                    Text(
                        text = "Logout all devices",
                        style = MaterialTheme.typography.labelLarge,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier
                            .padding(vertical = 4.dp),
                    )
                    androidx.compose.material3.TextButton(onClick = onLogoutAll) {
                        Text("Revoke all sessions")
                    }
                }
            }
        }
    }
}

@Composable
private fun SummaryCard(summary: ModuleSummary) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = if (summary.ok) {
                MaterialTheme.colorScheme.surface
            } else {
                MaterialTheme.colorScheme.errorContainer
            },
        ),
    ) {
        Column(Modifier.padding(16.dp)) {
            Text(
                text = summary.value,
                style = MaterialTheme.typography.headlineSmall,
            )
            Spacer(Modifier.height(4.dp))
            Text(
                text = summary.subtitle,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun SummaryRow(summary: ModuleSummary?) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(summary?.key?.title ?: "--", style = MaterialTheme.typography.bodyMedium)
        Text(
            text = summary?.value ?: "--",
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium,
        )
    }
}
