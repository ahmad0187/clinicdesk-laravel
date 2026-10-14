<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-staff');

        return view('staff.index', ['staff' => User::orderBy('role')->orderBy('name')->withCount('appointments')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('manage-staff');

        return view('staff.form', ['staff' => new User(['active' => true])]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['active' => true]);
        ActivityLog::record('created', $user, "Added {$user->role->label()} {$user->name}");

        return redirect()->route('staff.index')->with('status', "{$user->name} can now sign in.");
    }

    public function edit(User $staff): View
    {
        Gate::authorize('manage-staff');

        return view('staff.form', ['staff' => $staff]);
    }

    public function update(StaffRequest $request, User $staff): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($staff->is($request->user()) && (! $data['active'] || $data['role'] !== 'admin')) {
            return back()->withErrors(['role' => 'You cannot remove your own admin access.'])->withInput();
        }
        $staff->update($data);
        ActivityLog::record('updated', $staff, "Updated staff member {$staff->name}");

        return redirect()->route('staff.index')->with('status', 'Staff member updated.');
    }
}
