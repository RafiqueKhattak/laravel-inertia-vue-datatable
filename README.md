# Laravel Inertia DataTable

A full-featured DataTable package for **Laravel + Inertia.js + Vue 3**.

## Features

| Feature | ✓ |
|---|---|
| Server-side pagination | ✅ |
| Column sorting (asc/desc) | ✅ |
| Search / filtering | ✅ |
| Row selection + select all | ✅ |
| Bulk actions | ✅ |
| Column visibility toggle | ✅ |
| Export CSV / XLSX | ✅ |
| Custom action buttons (slot) | ✅ |
| Custom cell renderers (slot) | ✅ |
| Debounced search | ✅ |

---

## Installation

```bash
composer require qbits/laravel-inertia-vue-datatable
```

Publish config + Vue component:

```bash
php artisan vendor:publish --tag=datatable-config
php artisan vendor:publish --tag=datatable-components
```

The Vue component will land at:
```
resources/js/vendor/datatable/Components/DataTable.vue
```

---

## Backend: Controller

Add the `HasDataTable` trait to any controller and call `$this->dataTable()`:

```php
use YourVendor\InertiaDataTable\Traits\HasDataTable;

class UserController extends Controller
{
    use HasDataTable;

    private function columns(): array
    {
        return [
            ['key' => 'id',    'label' => 'ID',    'sortable' => true,  'searchable' => false, 'exportable' => true],
            ['key' => 'name',  'label' => 'Name',  'sortable' => true,  'searchable' => true,  'exportable' => true],
            ['key' => 'email', 'label' => 'Email', 'sortable' => true,  'searchable' => true,  'exportable' => true],
        ];
    }

    public function index()
    {
        $query = User::query();
        $tableData = $this->dataTable($query, $this->columns());

        return Inertia::render('Users/Index', [
            'table' => $tableData,
        ]);
    }

    public function export(Request $request)
    {
        $ids    = array_filter(explode(',', $request->input('ids', '')));
        $format = $request->input('format', 'xlsx');
        $query  = User::query();

        return Excel::download(
            new DataTableExport($query, $this->columns(), $ids),
            'users.' . $format
        );
    }

    public function bulkDestroy(Request $request)
    {
        User::whereIn('id', $request->ids)->delete();
        return back();
    }
}
```

### Routes

```php
Route::get('/users',               [UserController::class, 'index']);
Route::get('/users/export',        [UserController::class, 'export']);
Route::delete('/users/bulk-destroy', [UserController::class, 'bulkDestroy']);
```

---

## Column Definition

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `key` | string | *required* | Model attribute / DB column |
| `label` | string | *required* | Column header text |
| `sortable` | bool | `false` | Allow sorting by this column |
| `searchable` | bool | `false` | Include in LIKE search |
| `exportable` | bool | `false` | Include in CSV/XLSX export |
| `visible` | bool | `true` | Initial visibility |

---

## Frontend: Vue Component

```vue
<DataTable
  :columns="table.columns"
  :rows="table.data"
  :meta="table.meta"
  :filters="table.filters"
  :bulk-actions="[{ key: 'delete', label: 'Delete Selected' }]"
  :exportable="true"
  export-url="/users/export"
  row-key="id"
  @bulk-action="handleBulkAction"
  @selection-change="onSelectionChange"
/>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `columns` | Array | required | Column definitions (from server) |
| `rows` | Array | `[]` | Current page rows |
| `meta` | Object | required | Pagination meta |
| `filters` | Object | `{}` | Active filter state |
| `rowKey` | String | `'id'` | Unique row identifier |
| `selectable` | Boolean | `true` | Show row checkboxes |
| `showColumnToggle` | Boolean | `true` | Show columns menu |
| `exportable` | Boolean | `true` | Show export buttons |
| `exportUrl` | String | `''` | URL to hit for export |
| `bulkActions` | Array | `[]` | `[{ key, label }]` |
| `searchDebounce` | Number | `350` | Debounce ms for search input |

### Events

| Event | Payload | Description |
|-------|---------|-------------|
| `bulkAction` | `{ action: string, ids: number[] }` | Fired on bulk action click |
| `selectionChange` | `number[]` | Selected row IDs |

### Slots

| Slot | Props | Description |
|------|-------|-------------|
| `cell-{key}` | `{ row, value }` | Custom cell renderer per column |
| `rowActions` | `{ row }` | Action buttons at end of each row |
| `actions` | — | Extra buttons in the toolbar |

---

## Configuration

`config/datatable.php`

```php
return [
    'per_page'         => 15,
    'per_page_options' => [10, 15, 25, 50, 100],
    'export_filename'  => 'export',
];
```

---

## Requirements

- PHP 8.1+
- Laravel 10 or 11
- Inertia.js + Vue 3
- `maatwebsite/excel` ^3.1

---

## License

MIT
