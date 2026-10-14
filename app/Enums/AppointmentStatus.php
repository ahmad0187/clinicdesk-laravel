<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::NoShow => 'No-show',
            default => ucfirst($this->value),
        };
    }

    /** Tailwind classes for the status badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Cancelled => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            self::NoShow => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        };
    }

    /** Statuses that occupy the doctor's calendar. */
    public function blocksCalendar(): bool
    {
        return in_array($this, [self::Scheduled, self::Completed], true);
    }
}
