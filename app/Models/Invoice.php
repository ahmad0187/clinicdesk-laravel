<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number', 'patient_id', 'appointment_id', 'issued_at', 'due_at', 'status', 'subtotal', 'tax_rate', 'tax', 'total', 'paid_at', 'payment_method', 'notes'])]
class Invoice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Unpaid && $this->due_at->isPast() && ! $this->due_at->isToday();
    }

    /** Next sequential number for the year, e.g. INV-2026-0042. */
    public static function nextNumber(?int $year = null): string
    {
        $year ??= now()->year;
        $last = static::where('number', 'like', "INV-{$year}-%")->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('INV-%d-%04d', $year, $seq);
    }
}
