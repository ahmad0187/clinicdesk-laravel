<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-services');

        return view('services.index', ['services' => Service::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-services');
        $service = Service::create($this->validated($request) + ['active' => true]);
        ActivityLog::record('created', $service, "Added service {$service->name}");

        return back()->with('status', "{$service->name} added.");
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        Gate::authorize('manage-services');
        $service->update($this->validated($request) + ['active' => $request->boolean('active')]);
        ActivityLog::record('updated', $service, "Updated service {$service->name}");

        return back()->with('status', "{$service->name} updated.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
        ]);
    }
}
