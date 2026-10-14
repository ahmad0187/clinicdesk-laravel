@extends('layouts.app')
@section('title', $appointment->exists ? 'Reschedule appointment' : 'Book appointment')

@section('content')
@php
    $durations = $services->mapWithKeys(fn ($s) => [$s->id => $s->duration_minutes]);
@endphp
<form method="POST" action="{{ $appointment->exists ? route('appointments.update', $appointment) : route('appointments.store') }}" class="card max-w-3xl p-6"
      x-data="{ service: '{{ old('service_id', $appointment->service_id) }}', durations: @js($durations), get minutes() { return this.durations[this.service] ?? 30 } }">
    @csrf
    @if ($appointment->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="label" for="patient_id">Patient *</label>
            <select id="patient_id" name="patient_id" class="input" required>
                <option value="">Choose a patient…</option>
                @foreach ($patients as $p)<option value="{{ $p->id }}" @selected((int) old('patient_id', $appointment->patient_id) === $p->id)>{{ $p->last_name }}, {{ $p->first_name }} · {{ $p->phone }}</option>@endforeach
            </select>
            @error('patient_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-slate-500">New patient? <a class="font-semibold text-brand-700 hover:underline" href="{{ route('patients.create') }}">Register them first</a>.</p>
        </div>
        <div>
            <label class="label" for="doctor_id">Doctor *</label>
            <select id="doctor_id" name="doctor_id" class="input" required>
                <option value="">Choose a doctor…</option>
                @foreach ($doctors as $d)<option value="{{ $d->id }}" @selected((int) old('doctor_id', $appointment->doctor_id) === $d->id)>{{ $d->name }}{{ $d->specialty ? ' · '.$d->specialty : '' }}</option>@endforeach
            </select>
            @error('doctor_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="service_id">Service</label>
            <select id="service_id" name="service_id" class="input" x-model="service">
                <option value="">Consultation (30 min)</option>
                @foreach ($services as $s)<option value="{{ $s->id }}">{{ $s->name }} · {{ $s->duration_minutes }} min · ${{ number_format($s->price, 0) }}</option>@endforeach
            </select>
        </div>
        <x-field name="date" label="Date *" type="date" :value="$appointment->starts_at?->toDateString()" required />
        <div>
            <x-field name="time" label="Start time *" type="time" step="900" min="08:00" max="18:45" :value="$appointment->starts_at?->format('H:i')" required />
            <p class="mt-1 text-xs text-slate-500">Takes <span x-text="minutes"></span> minutes. Open weekdays 08:00–19:00.</p>
        </div>
        <x-field name="notes" label="Notes" type="textarea" :value="$appointment->notes" class="sm:col-span-2" placeholder="Reason for visit, special requirements…" />
    </div>
    <div class="mt-6 flex justify-end gap-2">
        <a href="{{ route('appointments.index') }}" class="btn">Cancel</a>
        <button class="btn btn-primary">{{ $appointment->exists ? 'Save changes' : 'Book appointment' }}</button>
    </div>
</form>
@endsection
