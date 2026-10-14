<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\PatientRequest;
use App\Models\ActivityLog;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $patients = Patient::query()
            ->search($request->string('q')->toString())
            ->withCount('appointments')
            ->withMax('appointments', 'starts_at')
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(15)->withQueryString();

        return view('patients.index', ['patients' => $patients, 'q' => $request->input('q')]);
    }

    public function create(): View
    {
        Gate::authorize('manage-patients');

        return view('patients.form', ['patient' => new Patient]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->validated());
        ActivityLog::record('created', $patient, "Registered patient {$patient->full_name}");

        return redirect()->route('patients.show', $patient)->with('status', 'Patient registered.');
    }

    public function show(Patient $patient): View
    {
        $patient->load([
            'appointments' => fn ($q) => $q->with(['doctor', 'service', 'invoice'])->orderByDesc('starts_at'),
            'invoices' => fn ($q) => $q->orderByDesc('issued_at'),
        ]);

        return view('patients.show', [
            'patient' => $patient,
            'balance' => $patient->invoices->where('status', InvoiceStatus::Unpaid)->sum('total'),
        ]);
    }

    public function edit(Patient $patient): View
    {
        Gate::authorize('manage-patients');

        return view('patients.form', ['patient' => $patient]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());
        ActivityLog::record('updated', $patient, "Updated patient {$patient->full_name}");

        return redirect()->route('patients.show', $patient)->with('status', 'Patient updated.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        Gate::authorize('manage-patients');
        $patient->delete();
        ActivityLog::record('archived', $patient, "Archived patient {$patient->full_name}");

        return redirect()->route('patients.index')->with('status', "{$patient->full_name} was archived.");
    }
}
