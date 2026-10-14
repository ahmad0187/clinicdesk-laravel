@extends('layouts.app')
@section('title', 'New invoice')

@section('content')
<form method="POST" action="{{ route('invoices.store') }}" class="card max-w-4xl p-6"
      x-data="{
        items: @js(array_values($items)),
        taxRate: {{ (float) old('tax_rate', 0) }},
        services: @js($services),
        add(s = null) {
          const row = { description: s ? s.name : '', quantity: 1, unit_price: s ? Number(s.price) : 0 };
          const last = this.items[this.items.length - 1];
          if (s && last && !last.description && !Number(last.unit_price)) this.items.splice(this.items.length - 1, 1, row); else this.items.push(row);
        },
        get subtotal() { return this.items.reduce((t, i) => t + (Number(i.quantity) || 0) * (Number(i.unit_price) || 0), 0) },
        get tax() { return this.subtotal * (Number(this.taxRate) || 0) / 100 },
        money(v) { return '$' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
      }">
    @csrf
    <div class="mb-6 flex items-start justify-between">
        <div><p class="text-sm text-slate-500">Invoice number</p><p class="text-xl font-bold">{{ $number }}</p></div>
        @if ($appointment)<p class="rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800">For {{ $appointment->service?->name ?? 'appointment' }} on {{ $appointment->starts_at->format('M j, Y') }}</p>@endif
    </div>
    <input type="hidden" name="appointment_id" value="{{ old('appointment_id', $appointment?->id) }}">
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="label" for="patient_id">Patient *</label>
            <select id="patient_id" name="patient_id" class="input" required>
                <option value="">Choose a patient…</option>
                @foreach ($patients as $p)<option value="{{ $p->id }}" @selected((int) old('patient_id', $patientId) === $p->id)>{{ $p->last_name }}, {{ $p->first_name }}</option>@endforeach
            </select>
            @error('patient_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            @error('appointment_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <x-field name="issued_at" label="Issue date *" type="date" :value="now()->toDateString()" required />
        <x-field name="due_at" label="Due date *" type="date" :value="now()->addDays(14)->toDateString()" required />
    </div>

    <h2 class="mb-2 mt-6 text-sm font-semibold">Line items</h2>
    @error('items')<p class="mb-2 text-xs text-rose-600">{{ $message }}</p>@enderror
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full">
            <thead class="bg-slate-50"><tr><th class="th w-1/2">Description</th><th class="th w-24">Qty</th><th class="th w-36">Unit price</th><th class="th w-32 text-right">Amount</th><th class="th w-10"></th></tr></thead>
            <tbody>
                <template x-for="(item, i) in items" :key="i">
                    <tr class="border-t border-slate-100">
                        <td class="px-2 py-2"><input class="input" :name="`items[${i}][description]`" x-model="item.description" required placeholder="e.g. Professional cleaning" aria-label="Description"></td>
                        <td class="px-2 py-2"><input class="input" type="number" step="0.01" min="0.01" :name="`items[${i}][quantity]`" x-model="item.quantity" required aria-label="Quantity"></td>
                        <td class="px-2 py-2"><input class="input" type="number" step="0.01" min="0" :name="`items[${i}][unit_price]`" x-model="item.unit_price" required aria-label="Unit price"></td>
                        <td class="px-4 py-2 text-right text-sm font-semibold tabular-nums" x-text="money((Number(item.quantity) || 0) * (Number(item.unit_price) || 0))"></td>
                        <td class="px-2 py-2"><button type="button" class="text-lg text-slate-400 hover:text-rose-600" @click="items.splice(i, 1)" x-show="items.length > 1" aria-label="Remove line">×</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    @if ($errors->has('items.*'))<p class="mt-2 text-xs text-rose-600">{{ $errors->first('items.*') }}</p>@endif
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button type="button" class="btn" @click="add()">+ Add line</button>
        <select class="input w-auto" @change="if ($event.target.value !== '') { add(services[$event.target.value]); $event.target.value = '' }" aria-label="Add a service">
            <option value="">Add a service…</option>
            <template x-for="(s, i) in services" :key="i"><option :value="i" x-text="`${s.name} · $${Number(s.price).toFixed(2)}`"></option></template>
        </select>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <x-field name="notes" label="Notes for the patient" type="textarea" value="Thank you for choosing our clinic." />
        <div class="space-y-2 rounded-lg bg-slate-50 p-4 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-semibold tabular-nums" x-text="money(subtotal)"></span></div>
            <div class="flex items-center justify-between gap-3"><label for="tax_rate" class="text-slate-500">Tax rate (%)</label><input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="50" class="input w-24 text-right" x-model="taxRate"></div>
            <div class="flex justify-between"><span class="text-slate-500">Tax</span><span class="font-semibold tabular-nums" x-text="money(tax)"></span></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><span class="font-bold">Total</span><span class="font-bold tabular-nums" x-text="money(subtotal + tax)"></span></div>
        </div>
    </div>
    <div class="mt-6 flex justify-end gap-2">
        <a href="{{ route('invoices.index') }}" class="btn">Cancel</a>
        <button class="btn btn-primary">Create invoice</button>
    </div>
</form>
@endsection
