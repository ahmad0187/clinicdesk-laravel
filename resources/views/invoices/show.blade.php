@extends('layouts.app')
@section('title', 'Invoice '.$invoice->number)
@section('actions')
    <button onclick="window.print()" class="btn">Print / PDF</button>
    @can('manage-invoices')
        @if ($invoice->status->value === 'unpaid')
            <form method="POST" action="{{ route('invoices.void', $invoice) }}" onsubmit="return confirm('Void this invoice?')">@csrf<button class="btn btn-danger">Void</button></form>
            <form method="POST" action="{{ route('invoices.pay', $invoice) }}" class="flex gap-2" x-data>
                @csrf
                <select name="payment_method" class="input w-auto" aria-label="Payment method">
                    <option value="card">Card</option><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="insurance">Insurance</option>
                </select>
                <button class="btn btn-primary">Mark as paid</button>
            </form>
        @endif
    @endcan
@endsection

@section('content')
<article class="card mx-auto max-w-3xl p-10 print:border-0 print:shadow-none">
    <header class="flex items-start justify-between">
        <div>
            <p class="flex items-center gap-2 text-xl font-bold"><span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-600 text-white">+</span>{{ config('app.name') }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ config('clinic.address') }}<br>{{ config('clinic.phone') }} · {{ config('clinic.email') }}</p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-bold tracking-tight">Invoice</p>
            <p class="mt-1 font-semibold">{{ $invoice->number }}</p>
            <span class="badge mt-2 {{ $invoice->isOverdue() ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : $invoice->status->badge() }}">{{ $invoice->isOverdue() ? 'Overdue' : $invoice->status->label() }}</span>
        </div>
    </header>

    <div class="mt-10 grid grid-cols-2 gap-6 text-sm">
        <div>
            <p class="font-semibold text-slate-500">Billed to</p>
            <p class="mt-1 font-semibold">{{ $invoice->patient->full_name }}</p>
            <p class="text-slate-600">{{ $invoice->patient->address }}</p>
            <p class="text-slate-600">{{ $invoice->patient->email }}</p>
        </div>
        <dl class="space-y-1 text-right">
            <div><dt class="inline text-slate-500">Issued:</dt> <dd class="inline font-medium">{{ $invoice->issued_at->format('M j, Y') }}</dd></div>
            <div><dt class="inline text-slate-500">Due:</dt> <dd class="inline font-medium">{{ $invoice->due_at->format('M j, Y') }}</dd></div>
            @if ($invoice->appointment)<div><dt class="inline text-slate-500">Treated by:</dt> <dd class="inline font-medium">{{ $invoice->appointment->doctor->name }}</dd></div>@endif
            @if ($invoice->paid_at)<div><dt class="inline text-slate-500">Paid:</dt> <dd class="inline font-medium">{{ $invoice->paid_at->format('M j, Y') }} ({{ str_replace('_', ' ', $invoice->payment_method) }})</dd></div>@endif
        </dl>
    </div>

    <table class="mt-10 w-full text-sm">
        <thead class="border-b-2 border-slate-900"><tr><th class="py-2 text-left">Description</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Unit price</th><th class="py-2 text-right">Amount</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($invoice->items as $item)
                <tr><td class="py-3">{{ $item->description }}</td><td class="py-3 text-right tabular-nums">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td><td class="py-3 text-right tabular-nums">${{ number_format($item->unit_price, 2) }}</td><td class="py-3 text-right font-medium tabular-nums">${{ number_format($item->amount, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="ml-auto mt-6 w-64 space-y-1.5 text-sm">
        <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="tabular-nums">${{ number_format($invoice->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Tax ({{ rtrim(rtrim(number_format($invoice->tax_rate, 2), '0'), '.') }}%)</span><span class="tabular-nums">${{ number_format($invoice->tax, 2) }}</span></div>
        <div class="flex justify-between border-t-2 border-slate-900 pt-2 text-base font-bold"><span>Total</span><span class="tabular-nums">${{ number_format($invoice->total, 2) }}</span></div>
    </div>

    @if ($invoice->notes)<p class="mt-10 border-t border-slate-100 pt-4 text-sm text-slate-500">{{ $invoice->notes }}</p>@endif
</article>
@endsection
