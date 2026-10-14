<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('view-activity');

        return view('activity.index', ['logs' => ActivityLog::with('user')->latest('created_at')->latest('id')->paginate(25)]);
    }
}
