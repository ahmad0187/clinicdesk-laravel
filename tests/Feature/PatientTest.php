<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_a_patient_and_log_it(): void
    {
        $res = $this->actingAs($this->receptionist())->post('/patients', [
            'first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '(555) 123-4567',
            'email' => 'jane@example.com', 'date_of_birth' => '1990-05-01', 'gender' => 'female',
        ]);
        $patient = Patient::firstOrFail();
        $res->assertRedirect("/patients/{$patient->id}");
        $this->assertSame('Jane Doe', $patient->full_name);
        $this->assertDatabaseHas('activity_logs', ['action' => 'created', 'description' => 'Registered patient Jane Doe']);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->receptionist())->from('/patients/create')->post('/patients', [
            'first_name' => '', 'last_name' => 'Doe', 'phone' => 'call me', 'email' => 'nope', 'date_of_birth' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors(['first_name', 'phone', 'email', 'date_of_birth']);
        $this->assertSame(0, Patient::count());
    }

    public function test_search_by_name_full_name_phone_and_email(): void
    {
        Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '(555) 111-2222', 'email' => 'jane@example.com']);
        Patient::factory()->create(['first_name' => 'John', 'last_name' => 'Smith', 'phone' => '(555) 999-0000', 'email' => 'john@example.com']);
        $user = $this->receptionist();

        foreach (['doe', 'Jane Doe', '111-2222', 'jane@'] as $term) {
            $this->actingAs($user)->get('/patients?q='.urlencode($term))->assertSee('Jane Doe')->assertDontSee('John Smith');
        }
    }

    public function test_update_and_archive(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
        $user = $this->receptionist();

        $this->actingAs($user)->put("/patients/{$patient->id}", [...$patient->only(['first_name', 'last_name', 'phone']), 'notes' => 'Allergic to latex'])->assertRedirect();
        $this->assertSame('Allergic to latex', $patient->fresh()->notes);

        $this->actingAs($user)->delete("/patients/{$patient->id}")->assertRedirect('/patients');
        $this->assertSoftDeleted($patient);
        $this->assertSame(2, ActivityLog::count());
    }
}
