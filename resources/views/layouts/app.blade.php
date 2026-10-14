<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased">
@php
    $nav = [
        ['dashboard', 'Dashboard', 'M3 12l9-9 9 9M5 10v10h14V10', true],
        ['appointments.index', 'Appointments', 'M8 3v3m8-3v3M4 9h16M5 6h14v14H5z', true],
        ['patients.index', 'Patients', 'M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zM4 21a8 8 0 0 1 16 0', true],
        ['invoices.index', 'Invoices', 'M7 3h10v18l-5-3-5 3zM10 8h4M10 12h4', auth()->user()->can('view-invoices')],
        ['services.index', 'Services', 'M4 6h16M4 12h16M4 18h10', auth()->user()->can('manage-services')],
        ['staff.index', 'Staff', 'M17 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 20v-2a4 4 0 0 0-3-3.9M16 2.1a4 4 0 0 1 0 7.8', auth()->user()->can('manage-staff')],
        ['activity', 'Activity log', 'M12 8v4l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', auth()->user()->can('view-activity')],
    ];
@endphp
<div class="flex min-h-full">
    <aside class="no-print sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-r border-slate-200 bg-white px-4 py-5 lg:flex">
        <a href="{{ route('dashboard') }}" class="mb-8 flex items-center gap-2.5 px-2 text-lg font-bold tracking-tight">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            </span>
            {{ config('app.name') }}
        </a>
        <nav class="flex flex-col gap-1">
            @foreach ($nav as [$route, $label, $icon, $visible])
                @continue(! $visible)
                @php($active = request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route))
                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="mt-auto rounded-xl bg-slate-50 p-3">
            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}{{ auth()->user()->specialty ? ' · '.auth()->user()->specialty : '' }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf<button class="text-xs font-semibold text-slate-500 hover:text-rose-600">Sign out</button></form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="no-print sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/85 px-6 py-3 backdrop-blur lg:px-8">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-bold tracking-tight">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex-1"></div>
            @yield('actions')
        </header>
        <main class="flex-1 px-6 py-6 lg:px-8">
            @if (session('status'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="no-print mb-5 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }} <button @click="show = false" class="text-emerald-600">×</button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
