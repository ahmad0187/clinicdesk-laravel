@extends('layouts.app')
@section('title', $staff->exists ? 'Edit '.$staff->name : 'Add staff member')

@section('content')
<form method="POST" action="{{ $staff->exists ? route('staff.update', $staff) : route('staff.store') }}" class="card max-w-2xl p-6" x-data="{ role: '{{ old('role', $staff->role?->value ?? 'receptionist') }}' }">
    @csrf
    @if ($staff->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="name" label="Full name *" :value="$staff->name" required />
        <x-field name="email" label="Email *" type="email" :value="$staff->email" required />
        <div>
            <label class="label" for="role">Role *</label>
            <select id="role" name="role" class="input" x-model="role">
                @foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}">{{ $r->label() }}</option>@endforeach
            </select>
            @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div x-show="role === 'doctor'"><x-field name="specialty" label="Specialty" :value="$staff->specialty" placeholder="e.g. Orthodontics" /></div>
        <x-field name="phone" label="Phone" :value="$staff->phone" />
        <x-field name="password" label="{{ $staff->exists ? 'New password (leave blank to keep)' : 'Password *' }}" type="password" autocomplete="new-password" />
        @if ($staff->exists)
            <label class="flex items-center gap-2 text-sm font-medium sm:col-span-2"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" class="accent-brand-600" @checked(old('active', $staff->active))> Can sign in</label>
        @endif
    </div>
    <p class="mt-4 text-xs text-slate-500"><b>Admins</b> manage everything. <b>Receptionists</b> manage patients, appointments and invoices. <b>Doctors</b> see their schedule and patient files, and update their own appointments.</p>
    <div class="mt-6 flex justify-end gap-2">
        <a href="{{ route('staff.index') }}" class="btn">Cancel</a>
        <button class="btn btn-primary">{{ $staff->exists ? 'Save changes' : 'Add staff member' }}</button>
    </div>
</form>
@endsection
