<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Services\DashboardStats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_for_every_role(): void
    {
        foreach ([$this->admin(), $this->receptionist(), $this->doctor()] as $user) {
            $this->actingAs($user)->get('/')->assertOk()->assertSee('Appointments today');
        }
    }

    public function test_kpis(): void
    {
        $this->travelTo('2026-10-14 08:00');
        $patient = Patient::factory()->create();
        $doctor = $this->doctor();
        $today = fn ($h, $status) => Appointment::factory()->create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => $status,
            'starts_at' => "2026-10-14 {$h}:00", 'ends_at' => "2026-10-14 {$h}:30",
        ]);
        $today('09', AppointmentStatus::Scheduled);
        $today('10', AppointmentStatus::Cancelled);
        Appointment::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => AppointmentStatus::Completed, 'starts_at' => '2026-10-01 09:00', 'ends_at' => '2026-10-01 09:30']);
        Appointment::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'status' => AppointmentStatus::NoShow, 'starts_at' => '2026-10-02 09:00', 'ends_at' => '2026-10-02 09:30']);

        $invoice = fn ($status, $total, $due, $paidAt = null) => Invoice::create([
            'number' => Invoice::nextNumber(), 'patient_id' => $patient->id, 'issued_at' => '2026-10-01', 'due_at' => $due,
            'status' => $status, 'subtotal' => $total, 'tax' => 0, 'total' => $total, 'paid_at' => $paidAt,
        ]);
        $invoice('paid', 120, '2026-10-15', '2026-10-05 10:00');
        $invoice('paid', 80, '2026-09-15', '2026-09-20 10:00');   // last month
        $invoice('unpaid', 200, '2026-10-01');                    // overdue
        $invoice('unpaid', 50, '2026-10-30');

        $stats = (new DashboardStats(CarbonImmutable::parse('2026-10-14 08:00')))->forUser($doctor);
        $this->assertSame(1, $stats['kpis']['appointments_today']);
        $this->assertSame(120.0, $stats['kpis']['revenue_month']);
        $this->assertSame(250.0, $stats['kpis']['outstanding']);
        $this->assertSame(1, $stats['kpis']['overdue_count']);
        $this->assertSame(50.0, $stats['kpis']['no_show_rate']);
        $this->assertSame([80.0, 120.0], array_slice(array_column($stats['revenue_by_month'], 'value'), -2));
    }
}
