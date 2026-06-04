<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\SubscriptionPayment;
use App\Models\User;

final class LedgerService
{
    public function recordSubscriptionIncome(SubscriptionPayment $payment, User $admin): LedgerEntry
    {
        return LedgerEntry::create([
            'type' => 'ingreso',
            'category' => 'suscripcion',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'description' => 'Membresía '.$payment->subscription?->plan?->name.' · '.$payment->user?->email,
            'reference_type' => 'subscription_payment',
            'reference_id' => $payment->id,
            'occurred_at' => ($payment->confirmed_at ?? now())->toDateString(),
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function recordManualExpense(array $data, User $admin): LedgerEntry
    {
        return LedgerEntry::create([
            'type' => 'egreso',
            'category' => $data['category'] ?? 'general',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'PEN',
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'],
            'created_by_user_id' => $admin->id,
        ]);
    }
}
