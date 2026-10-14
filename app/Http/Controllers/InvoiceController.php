<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\InvoiceRequest;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-invoices');
        $status = $request->input('status');
        $invoices = Invoice::with('patient')
            ->when($status === 'overdue', fn ($q) => $q->where('status', InvoiceStatus::Unpaid)->whereDate('due_at', '<', today()))
            ->when(in_array($status, ['unpaid', 'paid', 'void'], true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('patient', fn ($p) => $p->search($request->input('q')))))
            ->orderByDesc('issued_at')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'status' => $status,
            'totals' => [
                'unpaid' => (float) Invoice::where('status', InvoiceStatus::Unpaid)->sum('total'),
                'overdue' => (float) Invoice::where('status', InvoiceStatus::Unpaid)->whereDate('due_at', '<', today())->sum('total'),
                'paid_month' => (float) Invoice::where('status', InvoiceStatus::Paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-invoices');
        $appointment = $request->filled('appointment') ? Appointment::with(['patient', 'service'])->findOrFail($request->integer('appointment')) : null;
        $items = $appointment?->service
            ? [['description' => $appointment->service->name.' ('.$appointment->starts_at->format('M j, Y').')', 'quantity' => 1, 'unit_price' => (float) $appointment->service->price]]
            : [['description' => '', 'quantity' => 1, 'unit_price' => 0]];

        return view('invoices.form', [
            'appointment' => $appointment,
            'patientId' => $appointment?->patient_id ?? $request->integer('patient'),
            'patients' => Patient::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'services' => Service::where('active', true)->orderBy('name')->get(['name', 'price']),
            'items' => old('items', $items),
            'number' => Invoice::nextNumber(),
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Amounts are always calculated on the server; client-side totals are display only.
        $items = collect($data['items'])->map(fn ($i) => [
            'description' => $i['description'], 'quantity' => round((float) $i['quantity'], 2),
            'unit_price' => round((float) $i['unit_price'], 2), 'amount' => round($i['quantity'] * $i['unit_price'], 2),
        ]);
        $subtotal = round($items->sum('amount'), 2);
        $tax = round($subtotal * $data['tax_rate'] / 100, 2);

        $invoice = DB::transaction(function () use ($data, $items, $subtotal, $tax) {
            $invoice = Invoice::create([
                ...collect($data)->except('items')->all(),
                'number' => Invoice::nextNumber(),
                'status' => InvoiceStatus::Unpaid,
                'subtotal' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax,
            ]);
            $invoice->items()->createMany($items->all());

            return $invoice;
        });
        ActivityLog::record('invoiced', $invoice, "Issued {$invoice->number} to {$invoice->patient->full_name} for $".number_format($invoice->total, 2));

        return redirect()->route('invoices.show', $invoice)->with('status', "Invoice {$invoice->number} created.");
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('view-invoices');

        return view('invoices.show', ['invoice' => $invoice->load(['patient', 'items', 'appointment.doctor'])]);
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('manage-invoices');
        abort_unless($invoice->status === InvoiceStatus::Unpaid, 409, 'Only unpaid invoices can be marked as paid.');
        $data = $request->validate(['payment_method' => ['required', 'in:cash,card,bank_transfer,insurance']]);
        $invoice->update(['status' => InvoiceStatus::Paid, 'paid_at' => now(), 'payment_method' => $data['payment_method']]);
        ActivityLog::record('paid', $invoice, "Recorded payment for {$invoice->number} ($".number_format($invoice->total, 2).')');

        return back()->with('status', "{$invoice->number} marked as paid.");
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        Gate::authorize('manage-invoices');
        abort_if($invoice->status === InvoiceStatus::Paid, 409, 'Paid invoices cannot be voided.');
        $invoice->update(['status' => InvoiceStatus::Void]);
        ActivityLog::record('voided', $invoice, "Voided {$invoice->number}");

        return back()->with('status', "{$invoice->number} was voided.");
    }
}
