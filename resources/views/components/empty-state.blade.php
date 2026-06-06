@props(['title', 'message'])
<div class="panel px-6 py-14 text-center">
    <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-indigo-100 text-xl font-black text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">LF</div>
    <h3 class="mt-4 text-lg font-bold">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ $message }}</p>
    @if (isset($action))
        <div class="mt-5">{{ $action }}</div>
    @endif
</div>
