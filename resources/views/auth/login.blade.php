<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-full font-sans text-slate-900 antialiased lg:grid-cols-2">
    <section class="hidden flex-col justify-between bg-brand-900 p-12 text-white lg:flex">
        <p class="flex items-center gap-2.5 text-lg font-bold"><span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-500">+</span>{{ config('app.name') }}</p>
        <div>
            <h2 class="text-4xl font-bold leading-tight tracking-tight">Run the front desk<br>without the paperwork.</h2>
            <p class="mt-4 max-w-md text-brand-100/80">Patients, appointments, invoices and staff in one place, with a dashboard that shows how the clinic is doing today.</p>
        </div>
        <p class="text-sm text-brand-100/60">Laravel · Blade · Tailwind CSS · Alpine.js · Chart.js</p>
    </section>
    <main class="grid place-items-center p-6">
        <form method="POST" action="{{ route('login') }}" class="w-full max-w-sm space-y-4">
            @csrf
            <h1 class="text-2xl font-bold tracking-tight">Sign in</h1>
            <x-field name="email" label="Email" type="email" autocomplete="email" required autofocus />
            <x-field name="password" label="Password" type="password" autocomplete="current-password" required />
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="accent-brand-600"> Remember me</label>
            <button class="btn btn-primary w-full py-2.5">Sign in</button>
            <div class="rounded-lg border border-dashed border-slate-300 p-3 text-xs text-slate-500">
                <p class="font-semibold text-slate-700">Demo accounts (password: <code>password</code>)</p>
                <p class="mt-1">admin@clinicdesk.test · reception@clinicdesk.test · dr.patel@clinicdesk.test</p>
            </div>
        </form>
    </main>
</body>
</html>
