<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AppointmentRequest extends FormRequest
{
    public const OPEN_HOUR = 8;

    public const CLOSE_HOUR = 19;

    public function authorize(): bool
    {
        return $this->user()->can('manage-appointments');
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'doctor_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Doctor->value)->where('active', true)],
            'service_id' => ['nullable', 'exists:services,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:10', 'max:240'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Opening hours, past dates and double-booking are checked after the basic rules pass. */
    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            [$start, $end] = $this->window();
            $editing = $this->route('appointment');

            if ($start->isWeekend()) {
                $v->errors()->add('date', 'The clinic is closed at weekends.');
            }
            if ($start->hour < self::OPEN_HOUR || $end->gt($start->copy()->setTime(self::CLOSE_HOUR, 0))) {
                $v->errors()->add('time', sprintf('Appointments must be between %02d:00 and %02d:00.', self::OPEN_HOUR, self::CLOSE_HOUR));
            }
            if (! $editing && $start->isPast()) {
                $v->errors()->add('time', 'That time has already passed.');
            }
            $clash = Appointment::overlapping((int) $this->doctor_id, $start, $end, $editing?->id)->with('patient')->first();
            if ($clash) {
                $v->errors()->add('time', sprintf('The doctor is already booked %s–%s (%s).',
                    $clash->starts_at->format('H:i'), $clash->ends_at->format('H:i'), $clash->patient->full_name));
            }
        }];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function window(): array
    {
        $start = Carbon::parse($this->date.' '.$this->time);
        $minutes = (int) ($this->duration_minutes ?: (Service::find($this->service_id)?->duration_minutes ?? 30));

        return [$start, $start->copy()->addMinutes($minutes)];
    }
}
