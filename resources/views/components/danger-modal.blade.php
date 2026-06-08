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
         class="w-full max-w-xl overflow-hidden rounded-2xl border border-slate-700 bg-[#0f172a] shadow-2xl relative z-10">
        
        <form method="POST" action="{{ $action }}" class="m-0">
            @csrf
            @method($method)

            <!-- Content -->
            <div class="p-6">
                <div class="flex items-start gap-4">

                    <!-- Warning Icon -->
                    <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-red-400">
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
                        <h2 class="text-xl font-bold text-white">
                            {{ $title }}
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            {{ $slot }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 border-t border-slate-700 bg-[#111827] px-6 py-4">

                <!-- Cancel Button -->
                <button
                    type="button"
                    @click="open = false"
                    class="rounded-xl border border-slate-600 bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-700 hover:text-white">
                    Cancel
                </button>

                <!-- Delete Button -->
                <button
                    type="submit"
                    class="rounded-xl !bg-red-600 px-5 py-2.5 text-sm font-semibold !text-white shadow-lg shadow-red-950/40 transition hover:!bg-red-500">
                    Delete
                </button>

            </div>
        </form>
    </div>
</div>
