<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InvoiceBillingService
{
    private static array $columnCache = [];

    public function ensurePublicToken(Invoice $invoice): Invoice
    {
        if (!$this->hasInvoiceColumn('public_token')) {
            return $invoice;
        }

        if (!$invoice->public_token) {
            $invoice->public_token = Str::random(48);
            if ($this->hasInvoiceColumn('public_token_expires_at')) {
                $invoice->public_token_expires_at = now()->addMonths(6);
            }
            $invoice->save();
        }

        return $invoice->refresh();
    }

    public function publicUrl(Invoice $invoice): string
    {
        if (!$this->hasInvoiceColumn('public_token')) {
            return url('/pay/invoices/' . rawurlencode((string)$invoice->invoice_number));
        }

        $invoice = $this->ensurePublicToken($invoice);
        return url('/pay/invoices/' . $invoice->public_token);
    }

    public function recalculate(Invoice $invoice): Invoice
    {
        $subtotal = (float)($invoice->subtotal_amount ?: $invoice->amount ?: 0);
        $taxPercent = (float)($invoice->tax_percent ?: 0);
        $penaltyPercent = (float)($invoice->penalty_percent ?: 0);

        $taxAmount = (float)$invoice->tax_amount;
        if ($taxAmount <= 0 && $taxPercent > 0) {
            $taxAmount = round($subtotal * ($taxPercent / 100), 2);
        }

        $penaltyAmount = (float)$invoice->penalty_amount;
        if ($penaltyAmount <= 0 && $penaltyPercent > 0) {
            $penaltyAmount = round($subtotal * ($penaltyPercent / 100), 2);
        }

        $total = round($subtotal + $taxAmount + $penaltyAmount, 2);

        $paid = 0;
        if ($this->hasPaymentColumn('invoice_id')) {
            $paid = round((float)$invoice->payments()->sum('amount'), 2);
        }
        if ($this->hasInvoiceColumn('paid_amount') && $invoice->paid_amount > 0 && $invoice->paid_amount > $paid) {
            $paid = round((float)$invoice->paid_amount, 2);
        }

        $balance = round(max(0, $total - $paid), 2);
        $status = $this->resolveStatus(
            $balance,
            $this->hasInvoiceColumn('due_date') ? $invoice->due_date : null,
            $paid,
            (string)($invoice->invoice_status ?? '')
        );

        $updates = [];
        foreach ([
            'subtotal_amount' => $subtotal,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'penalty_percent' => $penaltyPercent,
            'penalty_amount' => $penaltyAmount,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => $balance,
        ] as $column => $value) {
            if ($this->hasInvoiceColumn($column)) {
                $updates[$column] = $value;
            }
        }

        if ($this->hasInvoiceColumn('invoice_status')) {
            $updates['invoice_status'] = $status;
        }
        if ($this->hasInvoiceColumn('status')) {
            $updates['status'] = $status === 'paid' ? 'paid' : 'unpaid';
        }

        if ($this->hasInvoiceColumn('issued_at') && !$invoice->issued_at) {
            $invoice->issued_at = $invoice->created_at ?: now();
        }

        if (empty($updates)) {
            return $invoice;
        }

        $invoice->fill($updates);
        $invoice->save();

        return $invoice->refresh();
    }

    public function applyPayment(Invoice $invoice, float $amount): Invoice
    {
        $amount = max(0, round($amount, 2));
        $invoice->paid_amount = round((float)$invoice->paid_amount + $amount, 2);
        return $this->recalculate($invoice);
    }

    public function markOverdue(Invoice $invoice): Invoice
    {
        if (in_array($invoice->invoice_status, ['paid', 'cancelled'], true)) {
            return $invoice;
        }

        if ($this->hasInvoiceColumn('due_date') && $invoice->due_date && Carbon::parse($invoice->due_date)->isPast()) {
            if (!$this->hasInvoiceColumn('invoice_status')) {
                return $this->recalculate($invoice);
            }
            $invoice->invoice_status = 'overdue';
            $invoice->status = 'unpaid';
            $invoice->save();
            return $invoice->refresh();
        }

        return $this->recalculate($invoice);
    }

    private function resolveStatus(float $balance, $dueDate, float $paidAmount, string $currentStatus = ''): string
    {
        if ($currentStatus === 'cancelled') {
            return 'cancelled';
        }

        if ($balance <= 0) {
            return 'paid';
        }

        if ($paidAmount > 0 && $balance > 0) {
            return 'partial';
        }

        if ($dueDate && Carbon::parse($dueDate)->isPast()) {
            return 'overdue';
        }

        if ($dueDate) {
            return 'due';
        }

        return 'unpaid';
    }

    private function hasInvoiceColumn(string $column): bool
    {
        return $this->hasColumn('invoices', $column);
    }

    private function hasPaymentColumn(string $column): bool
    {
        return $this->hasColumn('payments', $column);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }
}
