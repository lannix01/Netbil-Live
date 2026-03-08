package com.marcep.pettycash.core.model

data class ActionResult<T>(
    val data: T? = null,
    val error: String? = null,
) {
    val ok: Boolean get() = error == null

    companion object {
        fun <T> success(data: T? = null): ActionResult<T> = ActionResult(data = data)
        fun <T> failure(message: String): ActionResult<T> = ActionResult(error = message)
    }
}
