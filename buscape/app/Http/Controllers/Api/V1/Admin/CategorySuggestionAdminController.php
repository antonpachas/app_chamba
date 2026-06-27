<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\PaginatesAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategorySuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CategorySuggestionAdminController extends Controller
{
    use PaginatesAdminResources;
    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'pending');
        $query = CategorySuggestion::query()
            ->with('user:id,full_name,email')
            ->orderByDesc('created_at');

        if (in_array($status, ['pending', 'reviewed', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($this->adminPerPage($request));
        $pendingCount = CategorySuggestion::query()->where('status', 'pending')->count();

        return $this->adminPaginatedResponse(
            $paginator,
            fn (CategorySuggestion $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'note' => $s->note,
                'status' => $s->status,
                'created_at' => $s->created_at,
                'user' => $s->user ? [
                    'id' => $s->user->id,
                    'full_name' => $s->user->full_name,
                    'email' => $s->user->email,
                ] : null,
            ],
            ['pending_count' => $pendingCount],
        );
    }

    public function updateStatus(Request $request, int $suggestion): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewed,rejected'],
        ]);

        $row = CategorySuggestion::query()->findOrFail($suggestion);
        $row->status = $data['status'];
        $row->save();

        return response()->json([
            'message' => 'Estado actualizado.',
            'data' => ['id' => $row->id, 'status' => $row->status],
        ]);
    }

    public function approve(int $suggestion): JsonResponse
    {
        $row = CategorySuggestion::query()->findOrFail($suggestion);
        $name = trim($row->name);

        if ($name === '') {
            return response()->json(['message' => 'El nombre de la sugerencia está vacío.'], 422);
        }

        $exists = Category::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            $row->status = 'reviewed';
            $row->save();

            return response()->json([
                'message' => 'Ya existía una categoría con ese nombre. La sugerencia se marcó como revisada.',
                'data' => ['suggestion_id' => $row->id, 'status' => $row->status],
            ]);
        }

        $category = Category::query()->create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'is_active' => true,
        ]);

        $row->status = 'reviewed';
        $row->save();

        return response()->json([
            'message' => "Categoría «{$name}» creada y sugerencia marcada como revisada.",
            'data' => [
                'suggestion_id' => $row->id,
                'status' => $row->status,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            ],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'categoria';
        }

        $slug = $base;
        $n = 0;
        while (Category::query()->where('slug', $slug)->exists()) {
            $n++;
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
