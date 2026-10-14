<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    private Service $cleaning;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-12 08:00:00'); // a Monday
        $this->doctor = $this->doctor();
        $this->patient = Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
        $this->cleaning = Service::factory()->create(['name' => 'Cleaning', 'duration_minutes' => 45, 'price' => 120]);
    }

    private function book(array $overrides = [])
    {
        return $this->actingAs($this->receptionist())->post('/appointments', [
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'service_id' => $this->cleaning->id,
            'date' => '2026-10-13', 'time' => '10:00', ...$overrides,
        ]);
    }

    public function test_booking_uses_the_service_duration(): void
    {
        $this->book()->assertRedirect('/appointments?week=2026-10-13')->assertSessionHasNoErrors();
        $a = Appointment::firstOrFail();
        $this->assertSame('2026-10-13 10:45', $a->ends_at->format('Y-m-d H:i'));
    }

    public function test_double_booking_the_same_doctor_is_rejected(): void
    {
        $this->book();
        $this->book(['time' => '10:30'])->assertSessionHasErrors(['time' => 'The doctor is already booked 10:00–10:45 (Jane Doe).']);
        $this->book(['time' => '10:45'])->assertSessionHasNoErrors(); // back-to-back is fine
        $this->assertSame(2, Appointment::count());
    }

    public function test_cancelled_slots_can_be_rebooked_and_other_doctors_are_independent(): void
    {
        $this->book();
        Appointment::first()->update(['status' => 'cancelled']);
        $this->book()->assertSessionHasNoErrors();
        $this->book(['doctor_id' => $this->doctor()->id])->assertSessionHasNoErrors();
    }

    public function test_opening_hours_weekends_and_past_times(): void
    {
        $this->book(['time' => '07:30'])->assertSessionHasErrors('time');
        $this->book(['time' => '18:30'])->assertSessionHasErrors('time'); // 45 minutes would end after 19:00
        $this->book(['date' => '2026-10-17'])->assertSessionHasErrors(['date' => 'The clinic is closed at weekends.']);
        $this->book(['date' => '2026-10-09'])->assertSessionHasErrors(['time' => 'That time has already passed.']);
        $this->assertSame(0, Appointment::count());
    }

    public function test_only_active_doctors_can_be_booked(): void
    {
        $this->book(['doctor_id' => $this->receptionist()->id])->assertSessionHasErrors('doctor_id');
        $this->doctor->update(['active' => false]);
        $this->book()->assertSessionHasErrors('doctor_id');
    }

    public function test_rescheduling_does_not_clash_with_itself(): void
    {
        $this->book();
        $a = Appointment::first();
        $this->actingAs($this->receptionist())->put("/appointments/{$a->id}", [
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id, 'service_id' => $this->cleaning->id, 'date' => '2026-10-13', 'time' => '10:15',
        ])->assertSessionHasNoErrors();
        $this->assertSame('10:15', $a->fresh()->starts_at->format('H:i'));
    }

    public function test_calendar_shows_the_week(): void
    {
        $this->book();
        $this->actingAs($this->receptionist())->get('/appointments?week=2026-10-12')
            ->assertOk()->assertSee('Oct 12 – Oct 16, 2026')->assertSee('Jane Doe');
        $this->actingAs($this->receptionist())->get('/appointments?week=2026-10-19')->assertDontSee('Jane Doe');
    }
}
