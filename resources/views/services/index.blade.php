@extends('layouts.app')
@section('title', 'Services & prices')

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="card overflow-x-auto xl:col-span-2">
        <table class="w-full">
            <thead class="border-b border-slate-100 bg-slate-50/70"><tr><th class="th">Service</th><th class="th">Duration</th><th class="th">Price</th><th class="th">Active</th><th class="th"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($services as $s)
                    <tr x-data="{ edit: false }">
                        <td colspan="5" class="p-0">
                            <form method="POST" action="{{ route('services.update', $s) }}" class="grid grid-cols-[1fr_7rem_7rem_4rem_5rem] items-center gap-2 px-4 py-2.5">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $s->name }}" class="input" :disabled="!edit" aria-label="Name">
                                <input name="duration_minutes" type="number" value="{{ $s->duration_minutes }}" class="input" :disabled="!edit" aria-label="Duration in minutes">
                                <input name="price" type="number" step="0.01" value="{{ $s->price }}" class="input" :disabled="!edit" aria-label="Price">
                                <input type="checkbox" name="active" value="1" class="accent-brand-600" @checked($s->active) :disabled="!edit" aria-label="Active">
                                <button type="button" x-show="!edit" @click="edit = true" class="text-sm font-semibold text-brand-700">Edit</button>
                                <button x-show="edit" x-cloak class="btn btn-primary px-2 py-1 text-xs">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('services.store') }}" class="card h-fit space-y-4 p-5">
        @csrf
        <h2 class="font-semibold">Add a service</h2>
        <x-field name="name" label="Name *" required />
        <div class="grid grid-cols-2 gap-3">
            <x-field name="duration_minutes" label="Minutes *" type="number" value="30" required />
            <x-field name="price" label="Price ($) *" type="number" step="0.01" required />
        </div>
        <button class="btn btn-primary w-full">Add service</button>
    </form>
</div>
<style>[x-cloak]{display:none!important}</style>
@endsection
