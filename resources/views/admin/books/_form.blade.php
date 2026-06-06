@csrf
@isset($book)
    @method('PUT')
@endisset
<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2"><label class="label">Title</label><input class="input" name="title" value="{{ old('title', $book->title ?? '') }}" required><x-field-error name="title" /></div>
    <div><label class="label">Author</label><input class="input" name="author" value="{{ old('author', $book->author ?? '') }}" required><x-field-error name="author" /></div>
    <div><label class="label">Publisher</label><input class="input" name="publisher" value="{{ old('publisher', $book->publisher ?? '') }}"><x-field-error name="publisher" /></div>
    <div><label class="label">Publication year</label><input class="input" type="number" min="1000" max="2100" name="publication_year" value="{{ old('publication_year', $book->publication_year ?? '') }}"><x-field-error name="publication_year" /></div>
    <div><label class="label">ISBN</label><input class="input" name="isbn" value="{{ old('isbn', $book->isbn ?? '') }}"><x-field-error name="isbn" /></div>
    <div><label class="label">Category</label><select class="input" name="category_id" required><option value="">Select category</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $book->category_id ?? null) == $category->id)>{{ $category->name }}</option>@endforeach</select><x-field-error name="category_id" /></div>
    @unless (isset($book))
        <div><label class="label">Number of copies</label><input class="input" type="number" min="1" max="200" name="number_of_copies" value="{{ old('number_of_copies', 1) }}" required><x-field-error name="number_of_copies" /></div>
        <div><label class="label">Shelf location</label><input class="input" name="shelf_location" value="{{ old('shelf_location') }}"><x-field-error name="shelf_location" /></div>
    @endunless
    <div class="sm:col-span-2"><label class="label">Description</label><textarea class="input min-h-36" name="description">{{ old('description', $book->description ?? '') }}</textarea><x-field-error name="description" /></div>
</div>
<div class="mt-6 flex justify-end gap-3"><a href="{{ route('admin.books.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">{{ isset($book) ? 'Save changes' : 'Create book' }}</button></div>
