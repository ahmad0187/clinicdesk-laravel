<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        // Role-based permissions. Admins can do everything below.
        $frontDesk = fn (User $u) => $u->hasRole(Role::Admin, Role::Receptionist);

        Gate::define('manage-patients', $frontDesk);
        Gate::define('manage-appointments', $frontDesk);
        Gate::define('manage-invoices', $frontDesk);
        Gate::define('view-invoices', $frontDesk);
        Gate::define('manage-staff', fn (User $u) => $u->isAdmin());
        Gate::define('manage-services', fn (User $u) => $u->isAdmin());
        Gate::define('view-activity', fn (User $u) => $u->isAdmin());

        // Doctors may update the status and notes of their own appointments.
        Gate::define('update-appointment-status', fn (User $u, Appointment $a) => $frontDesk($u) || ($u->isDoctor() && $a->doctor_id === $u->id));
    }
}
