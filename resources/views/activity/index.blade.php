@extends('layouts.app')
@section('title', 'Activity log')

@section('content')
<div class="card">
    <ul class="divide-y divide-slate-100">
        @forelse ($logs as $log)
            <li class="flex items-center gap-4 px-5 py-3 text-sm">
                <span class="w-28 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $log->action }}</span>
                <span class="flex-1">{{ $log->description }}</span>
                <span class="shrink-0 text-slate-500">{{ $log->user?->name ?? 'System' }}</span>
                <time class="w-32 shrink-0 text-right text-slate-400" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->diffForHumans() }}</time>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-slate-500">No activity yet.</li>
        @endforelse
    </ul>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
