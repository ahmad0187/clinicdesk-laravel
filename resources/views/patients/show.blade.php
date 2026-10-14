@extends('layouts.app')
@section('title', $patient->full_name)
@section('actions')
    @can('manage-patients')
        <a href="{{ route('patients.edit', $patient) }}" class="btn">Edit</a>
        <a href="{{ route('appointments.create', ['patient' => $patient->id]) }}" class="btn btn-primary">Book appointment</a>
    @endcan
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <section class="card h-fit p-5">
        <div class="flex items-center gap-4">
            <span class="grid h-14 w-14 place-items-center rounded-full bg-brand-100 text-lg font-bold text-brand-700">{{ mb_substr($patient->first_name, 0, 1) }}{{ mb_substr($patient->last_name, 0, 1) }}</span>
            <div><p class="text-lg font-bold">{{ $patient->full_name }}</p><p class="text-sm text-slate-500">{{ $patient->age ? $patient->age.' years' : 'Age unknown' }}{{ $patient->gender ? ' · '.ucfirst($patient->gender) : '' }}</p></div>
        </div>
        <dl class="mt-5 space-y-3 text-sm">
            @foreach (['Phone' => $patient->phone, 'Email' => $patient->email, 'Date of birth' => $patient->date_of_birth?->format('M j, Y'), 'Address' => $patient->address, 'Insurance' => $patient->insurance_provider ?? 'Self-pay', 'Patient since' => $patient->created_at->format('M Y')] as $k => $v)
                <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $k }}</dt><dd class="text-right font-medium">{{ $v ?? '—' }}</dd></div>
            @endforeach
        </dl>
        @if ($patient->notes)<p class="mt-5 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{{ $patient->notes }}</p>@endif
        @can('view-invoices')
            <div class="mt-5 flex items-center justify-between rounded-lg {{ $balance > 0 ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800' }} p-3 text-sm font-semibold">
                <span>Outstanding balance</span><span>${{ number_format($balance, 2) }}</span>
            </div>
        @endcan
        @can('manage-patients')
            <form method="POST" action="{{ route('patients.destroy', $patient) }}" class="mt-4" onsubmit="return confirm('Archive this patient? Their history is kept.')">@csrf @method('DELETE')
                <button class="btn btn-danger w-full">Archive patient</button></form>
        @endcan
    </section>

    <div class="space-y-6 xl:col-span-2">
        <section class="card">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold">Appointments</h2>
            <table class="w-full">
                <thead class="bg-slate-50/70"><tr><th class="th">Date</th><th class="th">Service</th><th class="th">Doctor</th><th class="th">Status</th><th class="th"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($patient->appointments as $a)
                        <tr>
                            <td class="td whitespace-nowrap font-medium">{{ $a->starts_at->format('M j, Y · H:i') }}</td>
                            <td class="td text-slate-600">{{ $a->service?->name ?? 'Consultation' }}</td>
                            <td class="td whitespace-nowrap text-slate-600">{{ $a->doctor->name }}</td>
                            <td class="td"><span class="badge {{ $a->status->badge() }}">{{ $a->status->label() }}</span></td>
                            <td class="td whitespace-nowrap text-right">
                                @can('manage-invoices')
                                    @if ($a->status->value === 'completed' && ! $a->invoice)
                                        <a href="{{ route('invoices.create', ['appointment' => $a->id]) }}" class="text-sm font-semibold text-brand-700 hover:underline">Create invoice</a>
                                    @elseif ($a->invoice)
                                        <a href="{{ route('invoices.show', $a->invoice) }}" class="text-sm text-slate-500 hover:underline">{{ $a->invoice->number }}</a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No appointments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        @can('view-invoices')
            <section class="card">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold">Invoices</h2>
                <table class="w-full">
                    <thead class="bg-slate-50/70"><tr><th class="th">Number</th><th class="th">Issued</th><th class="th">Due</th><th class="th text-right">Total</th><th class="th">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($patient->invoices as $inv)
                            <tr>
                                <td class="td"><a href="{{ route('invoices.show', $inv) }}" class="font-semibold hover:text-brand-700">{{ $inv->number }}</a></td>
                                <td class="td text-slate-600">{{ $inv->issued_at->format('M j, Y') }}</td>
                                <td class="td text-slate-600">{{ $inv->due_at->format('M j, Y') }}</td>
                                <td class="td text-right font-semibold tabular-nums">${{ number_format($inv->total, 2) }}</td>
                                <td class="td"><span class="badge {{ $inv->status->badge() }}">{{ $inv->isOverdue() ? 'Overdue' : $inv->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @endcan
    </div>
</div>
@endsection
