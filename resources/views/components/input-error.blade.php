@props(['field'])
@error($field)
    <p class="mt-1 text-xs font-medium text-rose-600 flex items-center gap-1" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <span>{{ $message }}</span>
    </p>
@enderror
