@props(['name'])
@error($name)
    <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
@enderror
