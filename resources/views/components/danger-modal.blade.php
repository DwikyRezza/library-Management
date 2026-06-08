@props([
    'name',
    'title',
    'action',
    'method' => 'DELETE',
    'bookTitle' => null,
    'bookAuthor' => null,
    'confirmLabel' => 'Hapus',
])

<div x-data="{ open: false }"
     @open-modal.window="open = $event.detail === '{{ $name }}'"
     @keydown.escape.window="open = false"
     x-cloak
     x-show="open"
     class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto p-4 sm:items-center">
    <div x-show="open"
         x-transition.opacity
         @click="open = false"
         class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"></div>

    <div x-show="open"
         x-transition
         class="panel relative z-10 flex max-h-[calc(100vh-2rem)] w-full max-w-[500px] overflow-hidden">
        <form method="POST" action="{{ $action }}" class="m-0 flex max-h-[calc(100vh-2rem)] w-full flex-col">
            @csrf
            @method($method)

            <div class="overflow-y-auto p-6">
                <div class="flex items-start gap-4">
                    <div class="grid size-12 shrink-0 place-items-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-400/10 dark:text-rose-200">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="size-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1 text-left">
                        <h3 class="text-xl font-bold text-slate-950 dark:text-slate-100">{{ $title }}</h3>

                        @if($bookTitle)
                            <div class="mt-4 flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-white/10 dark:bg-white/5">
                                <div class="book-cover h-16 w-12 px-2 py-2">
                                    <span class="text-[8px] font-black uppercase text-blue-500 dark:text-blue-200">Book</span>
                                    <span class="line-clamp-2 text-[9px] font-black leading-tight">{{ $bookTitle }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ $bookTitle }}</h4>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $bookAuthor }}</p>
                                </div>
                            </div>
                        @endif

                        <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ $slot }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50/95 px-6 py-4 dark:border-white/10 dark:bg-slate-950/95 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" @click="open = false" class="btn-secondary w-full sm:w-auto">Batal</button>
                <button type="submit" class="btn-danger w-full sm:w-auto">{{ $confirmLabel }}</button>
            </div>
        </form>
    </div>
</div>
