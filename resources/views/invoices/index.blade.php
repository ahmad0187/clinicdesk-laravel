@extends('layouts.app')
@section('title', 'Invoices')
@section('actions')
    @can('manage-invoices')<a href="{{ route('invoices.create') }}" class="btn btn-primary">New invoice</a>@endcan
@endsection

@section('content')
<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <x-kpi label="Unpaid" :value="'$'.number_format($totals['unpaid'], 2)" />
    <x-kpi label="Overdue" :value="'$'.number_format($totals['overdue'], 2)" />
    <x-kpi label="Collected this month" :value="'$'.number_format($totals['paid_month'], 2)" />
</div>

<div class="mb-4 flex flex-wrap items-center gap-2">
    <div class="flex gap-1 rounded-lg bg-slate-100 p-1">
        @foreach (['' => 'All', 'unpaid' => 'Unpaid', 'overdue' => 'Overdue', 'paid' => 'Paid', 'void' => 'Void'] as $v => $l)
            <a href="{{ route('invoices.index', array_filter(['status' => $v, 'q' => request('q')])) }}" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ (string) $status === $v ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ $l }}</a>
        @endforeach
    </div>
    <form method="GET" class="ml-auto flex gap-2">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Invoice number or patient" class="input w-64" aria-label="Search invoices">
    </form>
</div>

<div class="card overflow-x-auto">
    <table class="w-full">
        <thead class="border-b border-slate-100 bg-slate-50/70"><tr><th class="th">Number</th><th class="th">Patient</th><th class="th">Issued</th><th class="th">Due</th><th class="th text-right">Total</th><th class="th">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($invoices as $inv)
                <tr class="hover:bg-slate-50">
                    <td class="td"><a href="{{ route('invoices.show', $inv) }}" class="font-semibold hover:text-brand-700">{{ $inv->number }}</a></td>
                    <td class="td"><a href="{{ route('patients.show', $inv->patient) }}" class="hover:text-brand-700">{{ $inv->patient->full_name }}</a></td>
                    <td class="td whitespace-nowrap text-slate-600">{{ $inv->issued_at->format('M j, Y') }}</td>
                    <td class="td whitespace-nowrap {{ $inv->isOverdue() ? 'font-semibold text-rose-600' : 'text-slate-600' }}">{{ $inv->due_at->format('M j, Y') }}</td>
                    <td class="td text-right font-semibold tabular-nums">${{ number_format($inv->total, 2) }}</td>
                    <td class="td"><span class="badge {{ $inv->isOverdue() ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : $inv->status->badge() }}">{{ $inv->isOverdue() ? 'Overdue' : $inv->status->label() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $invoices->links() }}</div>
@endsection
