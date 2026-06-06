@props(['name', 'title'])
<div x-data="{ open: false }"
     @open-modal.window="open = $event.detail === '{{ $name }}'"
     @keydown.escape.window="open = false"
     x-cloak
     x-show="open"
     class="fixed inset-0 z-[70] grid place-items-center p-4">
    <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-slate-950/70"></div>
    <div x-show="open" x-transition class="panel relative z-10 w-full max-w-md p-6">
        <h3 class="text-lg font-bold">{{ $title }}</h3>
        <div class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $slot }}</div>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="open = false" class="btn-secondary">Cancel</button>
            {{ $actions ?? '' }}
        </div>
    </div>
</div>
