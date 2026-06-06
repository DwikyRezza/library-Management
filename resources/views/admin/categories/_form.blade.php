@csrf
@isset($category)
    @method('PUT')
@endisset
<div>
    <label class="label">Category name</label>
    <input class="input" name="name" value="{{ old('name', $category->name ?? '') }}" required>
    <x-field-error name="name" />
</div>
<div class="mt-5">
    <label class="label">Color</label>
    <select class="input" name="color">
        @foreach (['blue', 'emerald', 'amber', 'indigo', 'rose', 'slate'] as $color)
            <option value="{{ $color }}" @selected(old('color', $category->color ?? 'blue') === $color)>{{ ucfirst($color) }}</option>
        @endforeach
    </select>
</div>
<div class="mt-5">
    <label class="label">Description</label>
    <textarea class="input min-h-32" name="description">{{ old('description', $category->description ?? '') }}</textarea>
    <x-field-error name="description" />
</div>
<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('admin.categories.index') }}" class="btn-secondary">Cancel</a>
    <button class="btn-primary">{{ isset($category) ? 'Save changes' : 'Create category' }}</button>
</div>
