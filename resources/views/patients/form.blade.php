@extends('layouts.app')
@section('title', $patient->exists ? 'Edit '.$patient->full_name : 'New patient')

@section('content')
<form method="POST" action="{{ $patient->exists ? route('patients.update', $patient) : route('patients.store') }}" class="card max-w-3xl p-6">
    @csrf
    @if ($patient->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="first_name" label="First name *" :value="$patient->first_name" required />
        <x-field name="last_name" label="Last name *" :value="$patient->last_name" required />
        <x-field name="phone" label="Phone *" :value="$patient->phone" required />
        <x-field name="email" label="Email" type="email" :value="$patient->email" />
        <x-field name="date_of_birth" label="Date of birth" type="date" :value="$patient->date_of_birth?->toDateString()" />
        <div>
            <label class="label" for="gender">Gender</label>
            <select id="gender" name="gender" class="input">
                <option value="">Prefer not to say</option>
                @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('gender', $patient->gender) === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <x-field name="address" label="Address" :value="$patient->address" class="sm:col-span-2" />
        <x-field name="insurance_provider" label="Insurance provider" :value="$patient->insurance_provider" placeholder="Leave empty for self-pay" />
        <x-field name="notes" label="Notes (allergies, preferences…)" type="textarea" :value="$patient->notes" class="sm:col-span-2" />
    </div>
    <div class="mt-6 flex justify-end gap-2">
        <a href="{{ $patient->exists ? route('patients.show', $patient) : route('patients.index') }}" class="btn">Cancel</a>
        <button class="btn btn-primary">{{ $patient->exists ? 'Save changes' : 'Register patient' }}</button>
    </div>
</form>
@endsection
