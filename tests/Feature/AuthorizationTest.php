<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_manage_staff_services_and_see_the_activity_log(): void
    {
        foreach (['/staff', '/staff/create', '/services', '/activity'] as $url) {
            $this->actingAs($this->receptionist())->get($url)->assertForbidden();
            $this->actingAs($this->admin())->get($url)->assertOk();
        }
    }

    public function test_doctors_cannot_see_billing_or_edit_patients(): void
    {
        $doctor = $this->doctor();
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)->get('/invoices')->assertForbidden();
        $this->actingAs($doctor)->get('/invoices/create')->assertForbidden();
        $this->actingAs($doctor)->get("/patients/{$patient->id}")->assertOk()->assertDontSee('Outstanding balance');
        $this->actingAs($doctor)->get("/patients/{$patient->id}/edit")->assertForbidden();
        $this->actingAs($doctor)->post('/patients', ['first_name' => 'A', 'last_name' => 'B', 'phone' => '5550001111'])->assertForbidden();
    }

    public function test_doctors_update_only_their_own_appointments(): void
    {
        $mine = Appointment::factory()->create();
        $other = Appointment::factory()->create();
        $doctor = $mine->doctor;

        $this->actingAs($doctor)->patch("/appointments/{$mine->id}/status", ['status' => 'completed'])->assertRedirect();
        $this->assertSame(AppointmentStatus::Completed, $mine->fresh()->status);

        $this->actingAs($doctor)->patch("/appointments/{$other->id}/status", ['status' => 'completed'])->assertForbidden();
        $this->actingAs($doctor)->get('/appointments/create')->assertForbidden();
    }

    public function test_receptionists_run_the_front_desk(): void
    {
        $r = $this->receptionist();
        foreach (['/', '/patients', '/patients/create', '/appointments', '/appointments/create', '/invoices', '/invoices/create'] as $url) {
            $this->actingAs($r)->get($url)->assertOk();
        }
        $this->assertSame(0, Invoice::count());
    }
}
