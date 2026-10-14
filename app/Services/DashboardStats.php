<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;

class DashboardStats
{
    public function __construct(private ?CarbonImmutable $now = null)
    {
        $this->now ??= CarbonImmutable::now();
    }

    public function forUser(User $user): array
    {
        $now = $this->now;
        $today = Appointment::query()
            ->with(['patient', 'doctor', 'service'])
            ->whereBetween('starts_at', [$now->startOfDay(), $now->endOfDay()])
            ->when($user->isDoctor(), fn ($q) => $q->where('doctor_id', $user->id))
            ->orderBy('starts_at')
            ->get();

        $last30 = Appointment::query()
            ->whereBetween('starts_at', [$now->subDays(30), $now])
            ->whereIn('status', [AppointmentStatus::Completed, AppointmentStatus::NoShow])
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $attended = (int) ($last30[AppointmentStatus::Completed->value] ?? 0);
        $noShows = (int) ($last30[AppointmentStatus::NoShow->value] ?? 0);

        $revenueByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->startOfMonth()->subMonths($i);
            $revenueByMonth[] = [
                'label' => $month->format('M'),
                'value' => (float) Invoice::where('status', InvoiceStatus::Paid)
                    ->whereBetween('paid_at', [$month, $month->endOfMonth()])->sum('total'),
            ];
        }

        $statusCounts = Appointment::query()
            ->whereBetween('starts_at', [$now->subDays(30), $now->addDays(14)])
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'today' => $today,
            'kpis' => [
                'appointments_today' => $today->where('status', '!=', AppointmentStatus::Cancelled)->count(),
                'revenue_month' => (float) Invoice::where('status', InvoiceStatus::Paid)->where('paid_at', '>=', $now->startOfMonth())->sum('total'),
                'outstanding' => (float) Invoice::where('status', InvoiceStatus::Unpaid)->sum('total'),
                'overdue_count' => Invoice::where('status', InvoiceStatus::Unpaid)->whereDate('due_at', '<', $now)->count(),
                'new_patients_month' => Patient::where('created_at', '>=', $now->startOfMonth())->count(),
                'patients_total' => Patient::count(),
                'no_show_rate' => ($attended + $noShows) ? round($noShows / ($attended + $noShows) * 100, 1) : 0.0,
            ],
            'revenue_by_month' => $revenueByMonth,
            'status_counts' => collect(AppointmentStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => (int) ($statusCounts[$s->value] ?? 0)]),
            'overdue' => Invoice::with('patient')->where('status', InvoiceStatus::Unpaid)->whereDate('due_at', '<', $now)
                ->orderBy('due_at')->limit(5)->get(),
        ];
    }
}
