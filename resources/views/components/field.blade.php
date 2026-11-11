{{--
    Pembungkus label + input + hint + pesan error validasi Laravel.
    <x-field label="Nama barang" name="name" hint="..." required>
        <input id="name" name="name" class="input" x-model="form.name">
    </x-field>
--}}
@props(['label', 'name' => null, 'for' => null, 'hint' => null, 'required' => false])
<div {{ $attributes->class('min-w-0') }}>
    <label for="{{ $for ?? $name }}" class="label">
        {{ $label }}
        @if ($required)<span class="text-bad-600" aria-hidden="true">*</span>@endif
    </label>
    {{ $slot }}
    @if ($name && $errors->has($name))
        <p class="error-text">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
