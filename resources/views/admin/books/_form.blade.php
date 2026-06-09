@csrf
@isset($book)
    @method('PUT')
@endisset

{{-- Core metadata --}}
<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="label">Title</label>
        <input class="input" name="title" value="{{ old('title', $book->title ?? '') }}" required>
        <x-field-error name="title" />
    </div>
    <div>
        <label class="label">Author</label>
        <input class="input" name="author" value="{{ old('author', $book->author ?? '') }}" required>
        <x-field-error name="author" />
    </div>
    <div>
        <label class="label">Publisher</label>
        <input class="input" name="publisher" value="{{ old('publisher', $book->publisher ?? '') }}">
        <x-field-error name="publisher" />
    </div>
    <div>
        <label class="label">Publication year</label>
        <input class="input" type="number" min="1000" max="2100" name="publication_year" value="{{ old('publication_year', $book->publication_year ?? '') }}">
        <x-field-error name="publication_year" />
    </div>
    <div>
        <label class="label">ISBN</label>
        <input class="input" name="isbn" value="{{ old('isbn', $book->isbn ?? '') }}">
        <x-field-error name="isbn" />
    </div>
    <div>
        <label class="label">Category</label>
        <select class="input" name="category_id" required>
            <option value="">Select category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $book->category_id ?? null) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-field-error name="category_id" />
    </div>
    @unless (isset($book))
        <div>
            <label class="label">Number of copies</label>
            <input class="input" type="number" min="1" max="200" name="number_of_copies" value="{{ old('number_of_copies', 1) }}" required>
            <x-field-error name="number_of_copies" />
        </div>
        <div>
            <label class="label">Shelf location</label>
            <input class="input" name="shelf_location" value="{{ old('shelf_location') }}">
            <x-field-error name="shelf_location" />
        </div>
    @endunless
    <div class="sm:col-span-2">
        <label class="label">Description</label>
        <textarea class="input min-h-36" name="description">{{ old('description', $book->description ?? '') }}</textarea>
        <x-field-error name="description" />
    </div>
</div>

{{-- File uploads --}}
<div class="mt-6 grid gap-5 sm:grid-cols-2">
    <div x-data="{
        preview: @js(isset($book) && $book->cover_image ? route('books.cover', $book) : ''),
        change(e) {
            const file = e.target.files[0];
            if (file) this.preview = URL.createObjectURL(file);
        }
    }">
        <label class="label">Cover image <span class="font-normal text-slate-400">(JPEG / PNG / WebP, max 5 MB)</span></label>
        <label
            class="relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-6 transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-white/10 dark:bg-white/5 dark:hover:border-blue-400"
            :class="preview ? 'pt-4' : 'py-10'"
        >
            <template x-if="preview">
                <div class="mb-2 max-h-[220px] overflow-hidden rounded-lg shadow-sm">
                    <img :src="preview" alt="Cover preview" class="h-full w-full object-cover object-top">
                </div>
            </template>

            <template x-if="!preview">
                <div class="flex flex-col items-center gap-2 text-slate-400 dark:text-slate-500">
                    <svg class="size-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-sm font-medium">Click to upload cover image</p>
                    <p class="text-xs">or drag and drop here</p>
                </div>
            </template>

            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="change($event)">

            <template x-if="preview">
                <span class="mt-1 text-xs text-blue-600 hover:underline dark:text-blue-300">Change image</span>
            </template>
        </label>
        <x-field-error name="cover_image" />
    </div>

    <div x-data="{
        fileName: '',
        fileSize: '',
        hasExisting: {{ isset($book) && $book->digitalAsset ? 'true' : 'false' }},
        existingName: @js(isset($book) && $book->digitalAsset ? $book->digitalAsset->original_name : ''),
        change(e) {
            const file = e.target.files[0];
            if (file) {
                this.fileName = file.name;
                this.fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            }
        }
    }">
        <label class="label">PDF file <span class="font-normal text-slate-400">(PDF only, max 100 MB)</span></label>
        <label class="relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-6 py-10 transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-white/10 dark:bg-white/5 dark:hover:border-blue-400">
            <template x-if="hasExisting && !fileName">
                <div class="flex flex-col items-center gap-2">
                    <div class="flex size-12 items-center justify-center rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-400/10 dark:text-rose-200">
                        <svg class="size-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                    </div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="existingName"></p>
                    <p class="text-xs text-slate-400">Click to replace this PDF</p>
                </div>
            </template>

            <template x-if="fileName">
                <div class="flex flex-col items-center gap-2">
                    <div class="flex size-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-200">
                        <svg class="size-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <p class="max-w-full truncate text-sm font-medium text-slate-700 dark:text-slate-300" x-text="fileName"></p>
                    <p class="text-xs text-slate-400" x-text="fileSize"></p>
                </div>
            </template>

            <template x-if="!hasExisting && !fileName">
                <div class="flex flex-col items-center gap-2 text-slate-400 dark:text-slate-500">
                    <svg class="size-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <p class="text-sm font-medium">Click to upload PDF</p>
                    <p class="text-xs">Book will be available in the in-app reader</p>
                </div>
            </template>

            <input type="file" name="pdf" accept="application/pdf" class="sr-only" @change="change($event)">
        </label>
        <x-field-error name="pdf" />
    </div>
</div>

{{-- Actions --}}
<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('admin.books.index') }}" class="btn-secondary">Cancel</a>
    <button class="btn-primary">{{ isset($book) ? 'Save changes' : 'Create book' }}</button>
</div>
