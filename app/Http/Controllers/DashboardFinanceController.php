<?php

namespace App\Http\Controllers;

use App\Models\MegaPayment;
use Illuminate\Http\JsonResponse;

class DashboardFinanceController extends Controller
{
    public function amountThisMonth(): JsonResponse
    {
        $startOfToday = now()->startOfDay();
        $endOfToday = now()->endOfDay();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthQuery = MegaPayment::query()
            ->where('status', 'completed')
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($fallback) use ($startOfMonth, $endOfMonth) {
                        $fallback->whereNull('completed_at')
                            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                    });
            });

        $todayQuery = MegaPayment::query()
            ->where('status', 'completed')
            ->where(function ($query) use ($startOfToday, $endOfToday) {
                $query->whereBetween('completed_at', [$startOfToday, $endOfToday])
                    ->orWhere(function ($fallback) use ($startOfToday, $endOfToday) {
                        $fallback->whereNull('completed_at')
                            ->whereBetween('created_at', [$startOfToday, $endOfToday]);
                    });
            });

        $monthAmount = (float) (clone $monthQuery)->sum('amount');
        $monthTransactions = (int) (clone $monthQuery)->count();
        $todayAmount = (float) (clone $todayQuery)->sum('amount');
        $todayTransactions = (int) (clone $todayQuery)->count();

        return response()->json([
            'ok' => true,
            'currency' => 'KES',
            'amount' => round($monthAmount, 2),
            'formatted_amount' => 'KES ' . number_format($monthAmount, 2),
            'transactions' => $monthTransactions,
            'month' => $startOfMonth->format('F Y'),
            'today' => $startOfToday->format('Y-m-d'),
            'today_amount' => round($todayAmount, 2),
            'today_formatted_amount' => 'KES ' . number_format($todayAmount, 2),
            'today_transactions' => $todayTransactions,
            'month_amount' => round($monthAmount, 2),
            'month_formatted_amount' => 'KES ' . number_format($monthAmount, 2),
            'month_transactions' => $monthTransactions,
            'range' => [
                'start' => $startOfMonth->toDateString(),
                'end' => $endOfMonth->toDateString(),
            ],
            'today_range' => [
                'start' => $startOfToday->toDateTimeString(),
                'end' => $endOfToday->toDateTimeString(),
            ],
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
