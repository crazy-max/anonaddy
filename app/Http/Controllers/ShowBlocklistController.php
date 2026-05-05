<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShowBlocklistController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100|min:1',
            'filter_type' => ['nullable', Rule::in(['all', 'email', 'domain'])],
            'sort' => ['nullable', Rule::in(['created_at', 'value', 'blocked', 'last_blocked', '-created_at', '-value', '-blocked', '-last_blocked'])],
            'page_size' => 'nullable|integer|in:50,100',
        ]);

        $query = user()
            ->blockedSenders()
            ->select(['id', 'user_id', 'type', 'value', 'blocked', 'last_blocked', 'created_at']);

        $filterType = $validated['filter_type'] ?? 'all';
        if ($filterType !== 'all') {
            $query->where('type', $filterType);
        }

        if (isset($validated['search'])) {
            $searchTerm = strtolower($validated['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(value) LIKE ?', ['%'.$searchTerm.'%']);
            });
        }

        $sort = $validated['sort'] ?? '-created_at';
        $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortColumn = ltrim($sort, '-');
        $query->orderBy($sortColumn, $sortDirection);

        $blockedSenders = $query
            ->paginate($validated['page_size'] ?? 50)
            ->withQueryString()
            ->onEachSide(1);

        return Inertia::render('Blocklist/Index', [
            'initialRows' => $blockedSenders,
            'search' => $validated['search'] ?? null,
            'initialFilterType' => $filterType,
            'initialSort' => $sortColumn,
            'initialSortDirection' => $sortDirection,
            'initialPageSize' => isset($validated['page_size']) ? (int) $validated['page_size'] : 50,
        ]);
    }
}
