@extends('layouts.app')
@section('title', 'Staff')
@section('actions')<a href="{{ route('staff.create') }}" class="btn btn-primary">Add staff member</a>@endsection

@section('content')
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead class="border-b border-slate-100 bg-slate-50/70"><tr><th class="th">Name</th><th class="th">Role</th><th class="th">Email</th><th class="th">Appointments</th><th class="th">Status</th><th class="th"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($staff as $u)
                <tr>
                    <td class="td"><p class="font-semibold">{{ $u->name }}</p>@if ($u->specialty)<p class="text-xs text-slate-500">{{ $u->specialty }}</p>@endif</td>
                    <td class="td"><span class="badge bg-slate-50 text-slate-700 ring-slate-500/20">{{ $u->role->label() }}</span></td>
                    <td class="td text-slate-600">{{ $u->email }}</td>
                    <td class="td text-slate-600">{{ $u->isDoctor() ? $u->appointments_count : '—' }}</td>
                    <td class="td"><span class="badge {{ $u->active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-100 text-slate-500 ring-slate-500/20' }}">{{ $u->active ? 'Active' : 'Disabled' }}</span></td>
                    <td class="td text-right"><a href="{{ route('staff.edit', $u) }}" class="text-sm font-semibold text-brand-700 hover:underline">Edit</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
