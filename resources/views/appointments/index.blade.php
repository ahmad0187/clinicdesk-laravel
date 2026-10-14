@extends('layouts.app')
@section('title', 'Appointments')
@section('actions')
    @can('manage-appointments')<a href="{{ route('appointments.create') }}" class="btn btn-primary">Book appointment</a>@endcan
@endsection

@php
    $open = \App\Http\Requests\AppointmentRequest::OPEN_HOUR;
    $close = \App\Http\Requests\AppointmentRequest::CLOSE_HOUR;
    $ppm = 1.4; // pixels per minute
    // With all doctors shown, each doctor gets their own lane inside the day column.
    $lanes = $doctorId ? 1 : max(1, $doctors->count());
    $laneOf = fn ($id) => $doctorId ? 0 : max(0, (int) $doctors->search(fn ($d) => $d->id === $id));
    $palette = ['bg-sky-100 border-sky-400 text-sky-900', 'bg-violet-100 border-violet-400 text-violet-900', 'bg-amber-100 border-amber-400 text-amber-900', 'bg-emerald-100 border-emerald-400 text-emerald-900', 'bg-rose-100 border-rose-400 text-rose-900'];
    $colorFor = fn ($doctorId) => $palette[$doctors->search(fn ($d) => $d->id === $doctorId) % count($palette)] ?? $palette[0];
@endphp

@section('content')
<div class="mb-4 flex flex-wrap items-center gap-2">
    <a class="btn px-2.5" href="{{ route('appointments.index', ['week' => $week->subWeek()->toDateString(), 'doctor' => $doctorId]) }}" aria-label="Previous week">‹</a>
    <a class="btn" href="{{ route('appointments.index', ['doctor' => $doctorId]) }}">This week</a>
    <a class="btn px-2.5" href="{{ route('appointments.index', ['week' => $week->addWeek()->toDateString(), 'doctor' => $doctorId]) }}" aria-label="Next week">›</a>
    <h2 class="ml-2 text-lg font-bold">{{ $week->format('M j') }} – {{ $week->addDays(4)->format('M j, Y') }}</h2>
    <form method="GET" class="ml-auto flex items-center gap-2">
        <input type="hidden" name="week" value="{{ $week->toDateString() }}">
        <label for="doctor" class="text-sm font-semibold text-slate-500">Doctor</label>
        <select id="doctor" name="doctor" class="input w-auto" onchange="this.form.submit()">
            <option value="">All doctors</option>
            @foreach ($doctors as $d)<option value="{{ $d->id }}" @selected($doctorId === $d->id)>{{ $d->name }}</option>@endforeach
        </select>
    </form>
</div>

<div class="mb-3 flex flex-wrap gap-3 text-xs">
    @foreach ($doctors as $d)
        <span class="inline-flex items-center gap-1.5 font-medium text-slate-600"><span class="h-2.5 w-2.5 rounded-sm border {{ $colorFor($d->id) }}"></span>{{ $d->name }}</span>
    @endforeach
</div>

<div class="card overflow-x-auto" x-data="{ selected: null }">
    <div class="grid min-w-[860px]" style="grid-template-columns: 56px repeat(5, minmax(0, 1fr));">
        <div class="border-b border-slate-100"></div>
        @foreach ($days as $day)
            <div class="border-b border-l border-slate-100 px-3 py-2 text-center {{ $day->isToday() ? 'bg-brand-50' : '' }}">
                <p class="text-xs font-semibold uppercase text-slate-400">{{ $day->format('D') }}</p>
                <p class="text-lg font-bold {{ $day->isToday() ? 'text-brand-700' : '' }}">{{ $day->format('j') }}</p>
            </div>
        @endforeach

        <div class="relative" style="height: {{ ($close - $open) * 60 * $ppm }}px">
            @for ($h = $open; $h < $close; $h++)
                <span class="absolute right-2 -translate-y-1/2 text-[11px] font-medium text-slate-400" style="top: {{ ($h - $open) * 60 * $ppm }}px">{{ sprintf('%02d:00', $h) }}</span>
            @endfor
        </div>
        @foreach ($days as $day)
            <div class="relative border-l border-slate-100 {{ $day->isToday() ? 'bg-brand-50/40' : '' }}" style="height: {{ ($close - $open) * 60 * $ppm }}px">
                @for ($h = $open; $h < $close; $h++)
                    <div class="absolute inset-x-0 border-t border-slate-100" style="top: {{ ($h - $open) * 60 * $ppm }}px"></div>
                @endfor
                @can('manage-appointments')
                    <a href="{{ route('appointments.create', ['date' => $day->toDateString(), 'doctor' => $doctorId]) }}" class="absolute inset-0" aria-label="Book on {{ $day->format('M j') }}"></a>
                @endcan
                @foreach ($appointments->get($day->toDateString(), []) as $a)
                    @php
                        $top = ($a->starts_at->hour * 60 + $a->starts_at->minute - $open * 60) * $ppm;
                        $height = max(22, $a->starts_at->diffInMinutes($a->ends_at) * $ppm - 2);
                        $muted = in_array($a->status->value, ['cancelled', 'no_show']);
                    @endphp
                    <button type="button" @click="selected = {{ $a->id }}"
                        class="absolute overflow-hidden rounded-md border-l-4 px-1.5 py-1 text-left text-xs leading-tight shadow-sm transition hover:z-10 hover:shadow-md {{ $colorFor($a->doctor_id) }} {{ $muted ? 'opacity-45 line-through' : '' }}"
                        style="top: {{ $top }}px; height: {{ $height }}px; left: calc({{ $laneOf($a->doctor_id) / $lanes * 100 }}% + 2px); width: calc({{ 100 / $lanes }}% - 4px)"
                        title="{{ $a->starts_at->format('H:i') }} {{ $a->patient->full_name }} · {{ $a->service?->name ?? 'Consultation' }} · {{ $a->doctor->name }}">
                        <p class="truncate font-semibold">{{ $a->starts_at->format('H:i') }}</p>
                        <p class="truncate">{{ $a->patient->full_name }}</p>
                        @if ($height > 56)<p class="truncate opacity-70">{{ $a->service?->name ?? 'Consultation' }}</p>@endif
                    </button>

                    <div x-show="selected === {{ $a->id }}" x-cloak x-transition @click.outside="selected = null" @keydown.escape.window="selected = null"
                        class="fixed inset-0 z-40 grid place-items-center bg-slate-900/30 p-4">
                        <div class="card w-full max-w-sm p-5" @click.stop>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-lg font-bold">{{ $a->patient->full_name }}</p>
                                    <p class="text-sm text-slate-500">{{ $a->starts_at->format('l, M j · H:i') }}–{{ $a->ends_at->format('H:i') }}</p>
                                </div>
                                <span class="badge {{ $a->status->badge() }}">{{ $a->status->label() }}</span>
                            </div>
                            <dl class="mt-4 space-y-1.5 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Service</dt><dd class="font-medium">{{ $a->service?->name ?? 'Consultation' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Doctor</dt><dd class="font-medium">{{ $a->doctor->name }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $a->patient->phone }}</dd></div>
                            </dl>
                            @if ($a->notes)<p class="mt-3 rounded-lg bg-slate-50 p-2.5 text-sm text-slate-600">{{ $a->notes }}</p>@endif
                            @can('update-appointment-status', $a)
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach (\App\Enums\AppointmentStatus::cases() as $s)
                                        @continue($s === $a->status)
                                        <form method="POST" action="{{ route('appointments.status', $a) }}">@csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $s->value }}"><button class="btn px-2.5 py-1 text-xs">Mark {{ strtolower($s->label()) }}</button></form>
                                    @endforeach
                                </div>
                            @endcan
                            <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
                                <a href="{{ route('patients.show', $a->patient) }}" class="btn flex-1">Patient file</a>
                                @can('manage-appointments')<a href="{{ route('appointments.edit', $a) }}" class="btn flex-1">Reschedule</a>@endcan
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
@endsection
