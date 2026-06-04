<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\PaginatesAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CategoryAdminController extends Controller
{
    use PaginatesAdminResources;

    public function index(Request $request): JsonResponse
    {
        $q = Category::query()->orderBy('name');

        if ($search = trim((string) $request->query('q', ''))) {
            $q->where('name', 'like', '%'.$search.'%');
        }

        if ($request->query('is_active') !== null && $request->query('is_active') !== '') {
            $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $q->paginate($this->adminPerPage($request));

        return $this->adminPaginatedResponse($paginator, fn (Category $c) => $this->row($c));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCategory($request);

        $category = Category::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => "Categoría «{$category->name}» creada.",
            'data' => $this->row($category),
        ], 201);
    }

    public function update(Request $request, int $category): JsonResponse
    {
        $row = Category::query()->findOrFail($category);
        $data = $this->validateCategory($request, $row);

        $previousName = (string) $row->name;
        $row->name = $data['name'];
        if (mb_strtolower(trim($row->name)) !== mb_strtolower(trim($previousName))) {
            $row->slug = $this->uniqueSlug($data['name'], (int) $row->id);
        }
        $row->is_active = $data['is_active'] ?? $row->is_active;
        $row->save();

        return response()->json([
            'message' => 'Categoría actualizada.',
            'data' => $this->row($row),
        ]);
    }

    /** @return array<string, mixed> */
    private function row(Category $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'is_active' => (bool) $c->is_active,
            'listings_count' => $c->providerServices()->count(),
            'created_at' => $c->created_at,
        ];
    }

    /** @return array{name: string, is_active?: bool} */
    private function validateCategory(Request $request, ?Category $existing = null): array
    {
        $id = $existing?->id;

        return $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:120',
                Rule::unique('categories', 'name')->ignore($id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'categoria';
        }

        $slug = $base;
        $n = 0;
        while (
            Category::query()
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $n++;
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
