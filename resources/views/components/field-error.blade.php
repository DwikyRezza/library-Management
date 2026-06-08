@props(['name'])
@error($name)
    <p class="mt-1 text-xs font-medium text-rose-700 dark:text-rose-200">{{ $message }}</p>
@enderror
