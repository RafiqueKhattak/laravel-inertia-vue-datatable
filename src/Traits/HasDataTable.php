<?php

namespace QBits\InertiaDataTable\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasDataTable
{
    public function dataTable(Builder $query, array $columns, array $options = []): array
    {
        $request  = request();

        $perPage  = (int) $request->input('per_page', $options['perPage'] ?? config('datatable.per_page', 15));
        $search   = $request->input('search', '');
        $sortKey  = $request->input('sort', $options['defaultSort'] ?? null);
        $sortDir  = $request->input('direction', $options['defaultDirection'] ?? 'asc');
        $selectedColumns = $request->input('columns', null);

        // Search
        if ($search) {
            $searchable = collect($columns)->where('searchable', true)->pluck('key');
            if ($searchable->isNotEmpty()) {
                $query->where(function (Builder $q) use ($searchable, $search) {
                    foreach ($searchable as $col) {
                        $q->orWhere($col, 'like', "%{$search}%");
                    }
                });
            }
        }

        // Sort
        if ($sortKey) {
            $sortable = collect($columns)->where('key', $sortKey)->where('sortable', true)->first();
            if ($sortable) {
                $query->orderBy($sortKey, in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'asc');
            }
        }

        // Paginate
        $allowedPerPage = config('datatable.per_page_options', [10, 15, 25, 50, 100]);
        $perPage        = in_array($perPage, $allowedPerPage) ? $perPage : ($allowedPerPage[0] ?? 15);
        $paginated      = $query->paginate($perPage)->withQueryString();

        // Visible columns
        $visibleKeys = $selectedColumns
            ? array_intersect(explode(',', $selectedColumns), array_column($columns, 'key'))
            : array_column($columns, 'key');

        $normalisedColumns = array_map(function ($col) use ($visibleKeys) {
            return array_merge(['sortable' => false, 'searchable' => false, 'visible' => in_array($col['key'], $visibleKeys)], $col);
        }, $columns);

        return [
            'data'    => $paginated->items(),
            'meta'    => [
                'current_page'     => $paginated->currentPage(),
                'last_page'        => $paginated->lastPage(),
                'per_page'         => $paginated->perPage(),
                'total'            => $paginated->total(),
                'from'             => $paginated->firstItem(),
                'to'               => $paginated->lastItem(),
                'per_page_options' => $allowedPerPage,
            ],
            'filters' => [
                'search'    => $search,
                'sort'      => $sortKey,
                'direction' => $sortDir,
                'per_page'  => $perPage,
            ],
            'columns' => $normalisedColumns,
        ];
    }
}
