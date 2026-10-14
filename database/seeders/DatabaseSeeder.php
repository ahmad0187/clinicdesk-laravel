<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Realistic demo clinic: staff, a price list, 140 patients, ~6 months of appointments
 * (past and upcoming) and invoices for completed visits. All people are fictional.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(2026);
        mt_srand(2026);

        User::factory()->create(['name' => 'Sara Khan', 'email' => 'admin@clinicdesk.test', 'role' => Role::Admin]);
        User::factory()->create(['name' => 'Leo Martins', 'email' => 'reception@clinicdesk.test', 'role' => Role::Receptionist]);
        $doctors = collect([
            ['Dr. Aisha Patel', 'dr.patel@clinicdesk.test', 'General dentistry'],
            ['Dr. Marcus Chen', 'dr.chen@clinicdesk.test', 'Orthodontics'],
            ['Dr. Elena Rossi', 'dr.rossi@clinicdesk.test', 'Pediatric dentistry'],
        ])->map(fn ($d) => User::factory()->create(['name' => $d[0], 'email' => $d[1], 'role' => Role::Doctor, 'specialty' => $d[2]]));

        $services = collect([
            ['Check-up & exam', 30, 75], ['Professional cleaning', 45, 120], ['Tooth-coloured filling', 45, 180],
            ['Teeth whitening', 60, 450], ['Root canal (molar)', 90, 1250], ['Simple extraction', 30, 180],
            ['Orthodontic consultation', 30, 95], ['Emergency visit', 30, 140],
        ])->map(fn ($s) => Service::create(['name' => $s[0], 'duration_minutes' => $s[1], 'price' => $s[2]]));

        // Most patients registered long ago; a few joined this month.
        $patients = Patient::factory(140)->create()->each(function (Patient $p, int $i) {
            $p->created_at = $i < 8 ? Carbon::now()->subDays(mt_rand(0, 12)) : Carbon::now()->subDays(mt_rand(200, 900));
            $p->save();
        });

        $today = Carbon::today();
        $invoiceSeq = 0;
        for ($offset = -175; $offset <= 14; $offset++) {
            $day = $today->copy()->addDays($offset);
            if ($day->isWeekend()) {
                continue;
            }
            foreach ($doctors as $doctor) {
                $time = $day->copy()->setTime(8, 30);
                $close = $day->copy()->setTime(18, 30);
                while (true) {
                    $time->addMinutes([0, 0, 15, 30, 60][mt_rand(0, 4)]);
                    $service = $services->random();
                    $end = $time->copy()->addMinutes($service->duration_minutes);
                    if ($end->gt($close)) {
                        break;
                    }
                    if (mt_rand(1, 100) <= 22) { // a free slot
                        $time = $end;

                        continue;
                    }
                    $past = $end->lt(now());
                    $roll = mt_rand(1, 100);
                    $status = ! $past ? AppointmentStatus::Scheduled
                        : ($roll <= 84 ? AppointmentStatus::Completed : ($roll <= 93 ? AppointmentStatus::NoShow : AppointmentStatus::Cancelled));

                    $appointment = Appointment::create([
                        'patient_id' => $patients->filter(fn (Patient $p) => $p->created_at->lte($time))->random()->id,
                        'doctor_id' => $doctor->id,
                        'service_id' => $service->id,
                        'starts_at' => $time->copy(),
                        'ends_at' => $end,
                        'status' => $status,
                    ]);

                    if ($status === AppointmentStatus::Completed) {
                        $issued = $time->copy()->startOfDay();
                        $age = $issued->diffInDays($today);
                        // Older invoices are almost always settled; recent ones are often still open.
                        $paidChance = $age > 30 ? 995 : ($age > 21 ? 950 : ($age > 14 ? 850 : 500));
                        $paid = mt_rand(1, 1000) <= $paidChance;
                        $invoice = Invoice::create([
                            'number' => sprintf('INV-%d-%04d', $issued->year, ++$invoiceSeq),
                            'patient_id' => $appointment->patient_id,
                            'appointment_id' => $appointment->id,
                            'issued_at' => $issued,
                            'due_at' => $issued->copy()->addDays(14),
                            'status' => $paid ? InvoiceStatus::Paid : InvoiceStatus::Unpaid,
                            'subtotal' => $service->price, 'tax_rate' => 0, 'tax' => 0, 'total' => $service->price,
                            'paid_at' => $paid ? $issued->copy()->addDays(mt_rand(0, 12))->setTime(mt_rand(9, 17), 0)->min(now()) : null,
                            'payment_method' => $paid ? ['card', 'card', 'cash', 'insurance', 'bank_transfer'][mt_rand(0, 4)] : null,
                            'notes' => 'Thank you for choosing our clinic.',
                        ]);
                        $invoice->items()->create(['description' => $service->name, 'quantity' => 1, 'unit_price' => $service->price, 'amount' => $service->price]);
                    }
                    $time = $end;
                }
            }
        }
    }
}
