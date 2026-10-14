<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Patient $patient, array $overrides = []): array
    {
        return [
            'patient_id' => $patient->id, 'issued_at' => '2026-10-12', 'due_at' => '2026-10-26', 'tax_rate' => 8,
            'items' => [
                ['description' => 'Cleaning', 'quantity' => 1, 'unit_price' => 120],
                ['description' => 'X-ray', 'quantity' => 2, 'unit_price' => 25.5],
                // a tampered client-side total must be ignored
                ['description' => 'Fluoride', 'quantity' => 1, 'unit_price' => 35, 'amount' => 9999],
            ],
            ...$overrides,
        ];
    }

    public function test_totals_are_calculated_on_the_server(): void
    {
        $this->travelTo('2026-10-12 09:00');
        $patient = Patient::factory()->create();
        $this->actingAs($this->receptionist())->post('/invoices', $this->payload($patient))->assertRedirect();

        $invoice = Invoice::with('items')->firstOrFail();
        $this->assertSame('INV-2026-0001', $invoice->number);
        $this->assertSame('206.00', $invoice->subtotal);
        $this->assertSame('16.48', $invoice->tax);
        $this->assertSame('222.48', $invoice->total);
        $this->assertSame(['120.00', '51.00', '35.00'], $invoice->items->pluck('amount')->all());
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
    }

    public function test_numbers_are_sequential_per_year(): void
    {
        $this->travelTo('2026-10-12 09:00');
        $patient = Patient::factory()->create();
        $user = $this->receptionist();
        $this->actingAs($user)->post('/invoices', $this->payload($patient));
        $this->actingAs($user)->post('/invoices', $this->payload($patient));
        $this->assertSame(['INV-2026-0001', 'INV-2026-0002'], Invoice::orderBy('id')->pluck('number')->all());
    }

    public function test_validation_and_one_invoice_per_appointment(): void
    {
        $appointment = Appointment::factory()->create(['status' => 'completed']);
        $user = $this->receptionist();
        $this->actingAs($user)->post('/invoices', $this->payload($appointment->patient, ['items' => [], 'due_at' => '2026-10-01']))
            ->assertSessionHasErrors(['items', 'due_at']);

        $this->actingAs($user)->post('/invoices', $this->payload($appointment->patient, ['appointment_id' => $appointment->id]))->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/invoices', $this->payload($appointment->patient, ['appointment_id' => $appointment->id]))
            ->assertSessionHasErrors(['appointment_id' => 'This appointment has already been invoiced.']);
    }

    public function test_payment_and_voiding_rules(): void
    {
        $patient = Patient::factory()->create();
        $user = $this->receptionist();
        $this->actingAs($user)->post('/invoices', $this->payload($patient));
        $invoice = Invoice::firstOrFail();

        $this->actingAs($user)->post("/invoices/{$invoice->id}/pay", ['payment_method' => 'card'])->assertRedirect();
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $this->actingAs($user)->post("/invoices/{$invoice->id}/pay", ['payment_method' => 'card'])->assertStatus(409);
        $this->actingAs($user)->post("/invoices/{$invoice->id}/void")->assertStatus(409);
    }

    public function test_overdue_filter_and_printable_view(): void
    {
        $this->travelTo('2026-10-12 09:00');
        $patient = Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
        $user = $this->receptionist();
        $this->actingAs($user)->post('/invoices', $this->payload($patient, ['issued_at' => '2026-09-01', 'due_at' => '2026-09-15']));
        $invoice = Invoice::firstOrFail();
        $this->assertTrue($invoice->isOverdue());

        $this->actingAs($user)->get('/invoices?status=overdue')->assertSee($invoice->number);
        $this->actingAs($user)->get("/invoices/{$invoice->id}")->assertOk()->assertSee('Jane Doe')->assertSee('$222.48')->assertSee('Overdue');
    }
}
