@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-3xl w-full px-6">
        <div class="bg-white rounded-xl shadow-sm p-8 text-center">

            <!-- Heading -->
            <h1 class="text-3xl font-semibold text-gray-800 mb-4">
                ISP Billing & Network Management System
            </h1>

            <!-- Intro -->
            <p class="text-gray-600 max-w-2xl mx-auto mb-6">
                A simple, reliable platform designed for Internet Service Providers to manage
                <span class="font-medium text-gray-800">metered billing</span>,
                <span class="font-medium text-gray-800">hotspot users</span>, and
                <span class="font-medium text-gray-800">PPPoE subscribers</span> efficiently.
            </p>

            <!-- Features -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left mb-8">
                <div class="border rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-1">Metered Billing</h3>
                    <p class="text-sm text-gray-600">
                        Automatically track data usage and bill customers accurately based on consumption.
                    </p>
                </div>

                <div class="border rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-1">Hotspot Management</h3>
                    <p class="text-sm text-gray-600">
                        Create vouchers, manage hotspot users, and control access with ease.
                    </p>
                </div>

                <div class="border rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-1">PPPoE Billing</h3>
                    <p class="text-sm text-gray-600">
                        Manage PPPoE clients, automate subscriptions, and monitor active connections.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col md:flex-row items-center justify-center gap-4">
                <!-- Existing ISP -->
                <a href="{{ route('login') }}"
                   class="w-full md:w-auto px-6 py-3 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900 transition">
                    Existing ISP? Login
                </a>

                <!-- New ISP -->
                <a href="{{ route('register') }}"
                   class="w-full md:w-auto px-6 py-3 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-100 transition">
                    New Here? Register
                </a>
            </div>

        </div>

        <!-- Footer note -->
        <p class="text-center text-xs text-gray-500 mt-6">
            Built to simplify ISP operations, billing, and customer management.
        </p>
    </div>
</div>
@endsection
