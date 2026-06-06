<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\BookCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => BookCategory::query()
                ->withCount('books')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);
        BookCategory::query()->create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(BookCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, BookCategory $category): RedirectResponse
    {
        $data = $request->validated();

        if ($category->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category);
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(BookCategory $category): RedirectResponse
    {
        if ($category->books()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Category cannot be deleted while it is assigned to books.',
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    private function uniqueSlug(string $name, ?BookCategory $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (BookCategory::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
