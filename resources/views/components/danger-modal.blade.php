@props(['name', 'title', 'action', 'method' => 'DELETE'])

<div x-data="{ open: false }"
     @open-modal.window="open = $event.detail === '{{ $name }}'"
     @keydown.escape.window="open = false"
     x-cloak
     x-show="open"
     class="fixed inset-0 z-[70] flex items-center justify-center p-4">
    
    <!-- Backdrop with blur -->
    <div x-show="open" 
         x-transition.opacity 
         @click="open = false" 
         class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"></div>

    <!-- Modal Card -->
    <div x-show="open" 
         x-transition 
         class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900 relative z-10">
        
        <form method="POST" action="{{ $action }}" class="m-0">
            @csrf
            @method($method)

            <!-- Content -->
            <div class="p-6">
                <div class="flex items-start gap-4">

                    <!-- Warning Icon -->
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-red-500 dark:text-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-5 w-5"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>

                    <!-- Text -->
                    <div class="text-left">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                            {{ $title }}
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            {{ $slot }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/80">

                <!-- Cancel Button -->
                <button
                    type="button"
                    @click="open = false"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white">
                    Cancel
                </button>

                <!-- Delete Button -->
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-950/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 focus:ring-offset-slate-900">
                    Delete
                </button>

            </div>
        </form>
    </div>
</div>
