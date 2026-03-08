package com.marcep.pettycash.feature.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.marcep.pettycash.core.model.ModuleSummary
import com.marcep.pettycash.core.repository.HomeRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class HomeUiState(
    val loading: Boolean = false,
    val modules: List<ModuleSummary> = emptyList(),
    val error: String? = null,
)

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val homeRepository: HomeRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow(HomeUiState())
    val uiState: StateFlow<HomeUiState> = _uiState.asStateFlow()

    fun refreshAll() {
        viewModelScope.launch {
            _uiState.update { it.copy(loading = true, error = null) }
            try {
                val modules = homeRepository.loadAllSummaries()
                _uiState.update { it.copy(loading = false, modules = modules, error = null) }
            } catch (t: Throwable) {
                _uiState.update { it.copy(loading = false, error = t.message ?: "Unable to load dashboard.") }
            }
        }
    }
}
