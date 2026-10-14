@props(['label', 'value', 'sub' => null, 'tone' => 'brand'])
<div class="card p-5">
    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
    @if ($sub) <p class="mt-1 text-sm text-slate-400">{{ $sub }}</p> @endif
</div>
