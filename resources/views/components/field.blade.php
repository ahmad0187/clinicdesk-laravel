@props(['name', 'label', 'type' => 'text', 'value' => null, 'class' => ''])
<div class="{{ $class }}">
    <label class="label" for="{{ $name }}">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'input min-h-24']) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->merge(['class' => 'input']) }}>
    @endif
    @error($name) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
</div>
