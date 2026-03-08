package com.marcep.pettycash.core.network

import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.contentOrNull

fun JsonObject.obj(key: String): JsonObject? = this[key] as? JsonObject
fun JsonObject.arr(key: String): JsonArray? = this[key] as? JsonArray
fun JsonObject.primitive(key: String): JsonPrimitive? = this[key] as? JsonPrimitive
fun JsonObject.str(key: String): String? = primitive(key)?.contentOrNull
fun JsonObject.int(key: String): Int? = primitive(key)?.contentOrNull?.toIntOrNull()
fun JsonObject.double(key: String): Double? = primitive(key)?.contentOrNull?.toDoubleOrNull()

fun JsonElement?.asObj(): JsonObject? = this as? JsonObject
fun JsonElement?.asArr(): JsonArray? = this as? JsonArray
fun JsonElement?.asPrimitive(): JsonPrimitive? = this as? JsonPrimitive
