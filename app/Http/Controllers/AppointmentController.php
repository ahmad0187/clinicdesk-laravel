<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /** Week calendar (Mon–Fri) with an optional doctor filter. Doctors see their own calendar by default. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $week = CarbonImmutable::parse($request->input('week', 'now'))->startOfWeek();
        $doctorId = $request->input('doctor', $user->isDoctor() ? $user->id : null);

        $appointments = Appointment::with(['patient', 'doctor', 'service'])
            ->whereBetween('starts_at', [$week, $week->addDays(5)])
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Appointment $a) => $a->starts_at->toDateString());

        return view('appointments.index', [
            'week' => $week,
            'days' => collect(range(0, 4))->map(fn ($i) => $week->addDays($i)),
            'appointments' => $appointments,
            'doctors' => User::doctors()->get(),
            'doctorId' => $doctorId ? (int) $doctorId : null,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-appointments');

        return view('appointments.form', $this->formData(new Appointment([
            'patient_id' => $request->integer('patient') ?: null,
            'doctor_id' => $request->integer('doctor') ?: null,
            'starts_at' => $request->input('date') ? CarbonImmutable::parse($request->input('date').' 09:00') : now()->addDay()->setTime(9, 0),
        ])));
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        [$start, $end] = $request->window();
        $appointment = Appointment::create([
            ...$request->safe()->only(['patient_id', 'doctor_id', 'service_id', 'notes']),
            'starts_at' => $start, 'ends_at' => $end, 'status' => AppointmentStatus::Scheduled,
        ]);
        $appointment->load('patient', 'doctor');
        ActivityLog::record('booked', $appointment, "Booked {$appointment->patient->full_name} with {$appointment->doctor->name} on {$start->format('M j, H:i')}");

        return redirect()->route('appointments.index', ['week' => $start->toDateString()])->with('status', 'Appointment booked.');
    }

    public function edit(Appointment $appointment): View
    {
        Gate::authorize('manage-appointments');

        return view('appointments.form', $this->formData($appointment));
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        [$start, $end] = $request->window();
        $appointment->update([...$request->safe()->only(['patient_id', 'doctor_id', 'service_id', 'notes']), 'starts_at' => $start, 'ends_at' => $end]);
        ActivityLog::record('rescheduled', $appointment, "Updated appointment for {$appointment->patient->full_name} to {$start->format('M j, H:i')}");

        return redirect()->route('appointments.index', ['week' => $start->toDateString()])->with('status', 'Appointment updated.');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('update-appointment-status', $appointment);
        $data = $request->validate(['status' => ['required', new Enum(AppointmentStatus::class)], 'notes' => ['nullable', 'string', 'max:2000']]);
        $appointment->update(array_filter($data, fn ($v) => $v !== null));
        ActivityLog::record('status', $appointment, "Marked {$appointment->patient->full_name}'s appointment as {$appointment->status->label()}");

        return back()->with('status', "Appointment marked as {$appointment->status->label()}.");
    }

    private function formData(Appointment $appointment): array
    {
        return [
            'appointment' => $appointment,
            'patients' => Patient::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'phone']),
            'doctors' => User::doctors()->get(),
            'services' => Service::where('active', true)->orderBy('name')->get(),
        ];
    }
}
