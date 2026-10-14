@extends('layouts.app')
@section('title', 'Patients')
@section('actions')
    @can('manage-patients')<a href="{{ route('patients.create') }}" class="btn btn-primary">New patient</a>@endcan
@endsection

@section('content')
<form method="GET" class="mb-4 flex gap-2">
    <input type="search" name="q" value="{{ $q }}" placeholder="Search by name, email or phone" class="input max-w-md" aria-label="Search patients">
    <button class="btn">Search</button>
    @if ($q)<a href="{{ route('patients.index') }}" class="btn">Clear</a>@endif
</form>

<div class="card overflow-x-auto">
    <table class="w-full">
        <thead class="border-b border-slate-100 bg-slate-50/70"><tr>
            <th class="th">Patient</th><th class="th">Phone</th><th class="th">Age</th><th class="th">Insurance</th><th class="th">Visits</th><th class="th">Last / next visit</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($patients as $p)
                <tr class="hover:bg-slate-50">
                    <td class="td"><a href="{{ route('patients.show', $p) }}" class="font-semibold hover:text-brand-700">{{ $p->full_name }}</a><p class="text-xs text-slate-500">{{ $p->email ?? 'No email' }}</p></td>
                    <td class="td whitespace-nowrap text-slate-600">{{ $p->phone }}</td>
                    <td class="td text-slate-600">{{ $p->age ?? '—' }}</td>
                    <td class="td text-slate-600">{{ $p->insurance_provider ?? 'Self-pay' }}</td>
                    <td class="td text-slate-600">{{ $p->appointments_count }}</td>
                    <td class="td whitespace-nowrap text-slate-600">{{ $p->appointments_max_starts_at ? \Illuminate\Support\Carbon::parse($p->appointments_max_starts_at)->format('M j, Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No patients found{{ $q ? " for “{$q}”" : '' }}.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $patients->links() }}</div>
@endsection
