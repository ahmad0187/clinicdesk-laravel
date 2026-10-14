@extends('layouts.app')
@section('title', 'Dashboard')
@section('actions')
    @can('manage-appointments')
        <a href="{{ route('patients.create') }}" class="btn">New patient</a>
        <a href="{{ route('appointments.create') }}" class="btn btn-primary">Book appointment</a>
    @endcan
@endsection

@section('content')
<p class="mb-5 text-sm text-slate-500">{{ now()->format('l, F j') }} · Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ strtok(auth()->user()->name, ' ') === 'Dr.' ? auth()->user()->name : strtok(auth()->user()->name, ' ') }}.</p>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kpi label="Appointments today" :value="$kpis['appointments_today']" :sub="$today->where('status.value', 'scheduled')->count().' still to come'" />
    <x-kpi label="Revenue this month" :value="'$'.number_format($kpis['revenue_month'])" sub="Paid invoices" />
    <x-kpi label="Outstanding" :value="'$'.number_format($kpis['outstanding'])" :sub="$kpis['overdue_count'].' overdue invoice'.($kpis['overdue_count'] === 1 ? '' : 's')" />
    <x-kpi label="No-show rate (30 days)" :value="$kpis['no_show_rate'].'%'" :sub="$kpis['new_patients_month'].' new patients this month'" />
</div>

<div class="mt-6 grid gap-4 xl:grid-cols-3">
    <section class="card p-5 xl:col-span-2">
        <h2 class="mb-4 font-semibold">Revenue, last 6 months</h2>
        <div class="h-64"><canvas id="revenueChart" aria-label="Revenue by month"></canvas></div>
    </section>
    <section class="card p-5">
        <h2 class="mb-4 font-semibold">Appointments by status</h2>
        <div class="h-64"><canvas id="statusChart" aria-label="Appointments by status"></canvas></div>
    </section>
</div>

<div class="mt-6 grid gap-4 xl:grid-cols-3">
    <section class="card xl:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold">Today's schedule</h2>
            <a href="{{ route('appointments.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Open calendar</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($today as $a)
                <li class="flex items-center gap-4 px-5 py-3">
                    <span class="w-24 shrink-0 text-sm font-semibold tabular-nums">{{ $a->starts_at->format('H:i') }}–{{ $a->ends_at->format('H:i') }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('patients.show', $a->patient) }}" class="font-semibold hover:text-brand-700">{{ $a->patient->full_name }}</a>
                        <p class="truncate text-sm text-slate-500">{{ $a->service?->name ?? 'Consultation' }} · {{ $a->doctor->name }}</p>
                    </div>
                    <span class="badge {{ $a->status->badge() }}">{{ $a->status->label() }}</span>
                    @can('update-appointment-status', $a)
                        @if ($a->status->value === 'scheduled')
                            <form method="POST" action="{{ route('appointments.status', $a) }}">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="completed"><button class="btn px-2.5 py-1 text-xs">Complete</button>
                            </form>
                        @endif
                    @endcan
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">No appointments today.</li>
            @endforelse
        </ul>
    </section>
    <section class="card">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold">Overdue invoices</h2></div>
        <ul class="divide-y divide-slate-100">
            @forelse ($overdue as $inv)
                <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                    <div class="min-w-0">
                        @can('view-invoices')<a href="{{ route('invoices.show', $inv) }}" class="font-semibold hover:text-brand-700">{{ $inv->number }}</a>@else<span class="font-semibold">{{ $inv->number }}</span>@endcan
                        <p class="truncate text-slate-500">{{ $inv->patient->full_name }} · due {{ $inv->due_at->diffForHumans() }}</p>
                    </div>
                    <span class="font-semibold tabular-nums">${{ number_format($inv->total, 2) }}</span>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">Nothing overdue.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection

@push('scripts')
<script type="module">
    const revenue = @json($revenue_by_month);
    const statuses = @json($status_counts);
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: { labels: revenue.map(r => r.label), datasets: [{ data: revenue.map(r => r.value), backgroundColor: '#14a38b', borderRadius: 6, maxBarThickness: 46 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => '$' + c.raw.toLocaleString() } } },
            scales: { y: { ticks: { callback: v => '$' + (v >= 1000 ? (v / 1000) + 'k' : v) }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } },
    });
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: Object.keys(statuses), datasets: [{ data: Object.values(statuses), backgroundColor: ['#38bdf8', '#14a38b', '#cbd5e1', '#fb7185'], borderWidth: 0 }] },
        options: { maintainAspectRatio: false, cutout: '64%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } } },
    });
</script>
@endpush
