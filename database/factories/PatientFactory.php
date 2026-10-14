<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patient> */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        $gender = fake()->randomElement(['female', 'male']);

        return [
            'first_name' => fake()->firstName($gender),
            'last_name' => fake()->lastName(),
            'email' => fake()->optional(0.85)->safeEmail(),
            'phone' => fake()->numerify('(555) ###-####'),
            'date_of_birth' => fake()->dateTimeBetween('-85 years', '-3 years')->format('Y-m-d'),
            'gender' => $gender,
            'address' => fake()->streetAddress().', '.fake()->city(),
            'insurance_provider' => fake()->optional(0.6)->randomElement(['Delta Dental', 'Cigna', 'MetLife', 'Aetna', 'Guardian']),
            'notes' => fake()->optional(0.2)->randomElement(['Allergic to penicillin.', 'Prefers morning appointments.', 'Anxious patient: explain each step.', 'Wheelchair user.']),
        ];
    }
}
