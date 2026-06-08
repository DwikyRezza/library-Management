@props([
    'name',
    'title',
    'action',
    'method' => 'DELETE',
    'bookTitle' => null,
    'bookAuthor' => null,
])

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
         class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

    <!-- Modal Container -->
    <div x-show="open" 
         x-transition 
         class="w-full max-w-[500px] overflow-hidden rounded-2xl border border-slate-700/30 bg-[#122131] shadow-2xl relative z-10">
        
        <form method="POST" action="{{ $action }}" class="m-0">
            @csrf
            @method($method)

            <!-- Content -->
            <div class="p-6">
                <div class="flex items-start gap-4">

                    <!-- Warning Icon -->
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#92002a]/20 text-[#ef3b5b]">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-6 w-6"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>

                    <!-- Text Zone -->
                    <div class="flex-1 text-left">
                        <h3 class="text-xl font-bold text-[#d4e4fa]">
                            {{ $title }}
                        </h3>

                        <!-- Optional Book Preview Card -->
                        @if($bookTitle)
                            <div class="mt-4 flex items-center gap-4 p-3 bg-[#0d1c2d] rounded-xl border border-slate-750/30">
                                <div class="w-12 h-16 bg-[#273647] rounded shadow-sm flex-shrink-0 flex items-center justify-center text-indigo-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <h4 class="font-bold text-sm text-[#d4e4fa] truncate">{{ $bookTitle }}</h4>
                                    <p class="text-xs text-[#c6c6cc] truncate">{{ $bookAuthor }}</p>
                                </div>
                            </div>
                        @endif

                        <p class="mt-4 text-sm leading-6 text-[#c6c6cc]">
                            {{ $slot }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer (Action Zone) -->
            <div class="bg-white px-6 py-4 flex justify-end items-center gap-3">
                
                <!-- Cancel Button -->
                <button
                    type="button"
                    @click="open = false"
                    class="px-6 py-2.5 rounded-lg text-sm font-semibold text-[#263143] hover:bg-slate-100 transition-colors active:scale-95">
                    Batal
                </button>

                <!-- Delete Button -->
                <button
                    type="submit"
                    class="px-8 py-2.5 rounded-lg bg-[#92002a] text-white text-sm font-semibold shadow-sm hover:bg-[#92002a]/95 transition-all active:scale-95">
                    Hapus
                </button>

            </div>
        </form>
    </div>
</div>
