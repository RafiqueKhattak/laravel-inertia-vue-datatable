<?php

namespace QBits\InertiaDataTable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeInertiaTable extends Command
{
    protected $signature = 'make:inertia-table
                            {model : Model name (e.g. User, Product, BlogPost)}
                            {--columns= : Comma-separated column keys (e.g. id,name,email,created_at)}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate Controller + Vue Page for QBits DataTable';

    public function handle(): int
    {
        $model = Str::studly($this->argument('model'));
        $modelPlural = Str::plural($model);
        $modelLower = Str::lower($model);
        $modelSlug = Str::kebab($modelPlural);        // e.g. blog-posts
        $modelCamel = Str::camel($modelPlural);        // e.g. blogPosts
        $routeName = Str::snake($modelPlural);        // e.g. blog_posts → blog_posts
        $routeSlug = $modelSlug;                      // e.g. blog-posts (URL)

        // ── Parse columns ────────────────────────────────────────────────────
        $rawCols = $this->option('columns')
            ? array_map('trim', explode(',', $this->option('columns')))
            : ['id', 'name', 'email', 'created_at'];

        // ── Paths ────────────────────────────────────────────────────────────
        $controllerPath = app_path("Http/Controllers/{$model}Controller.php");
        $vueDirPath = resource_path("js/Pages/{$modelPlural}");
        $vuePath = "{$vueDirPath}/Index.vue";

        // ── Guard: already exists ────────────────────────────────────────────
        foreach ([$controllerPath, $vuePath] as $path) {
            if (file_exists($path) && !$this->option('force')) {
                $this->error("File already exists: {$path}");
                $this->line('  Use --force to overwrite.');
                return self::FAILURE;
            }
        }

        // ── Generate Controller ───────────────────────────────────────────────
        $colDefs = $this->buildColumnDefs($rawCols);
        $colSelect = implode("', '", $rawCols);

        $controller = <<<PHP
<?php

namespace App\Http\Controllers;

use App\Models\\{$model};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use QBits\InertiaDataTable\Exports\DataTableExport;
use QBits\InertiaDataTable\Traits\HasDataTable;

class {$model}Controller extends Controller
{
    use HasDataTable;

    private function columns(): array
    {
        return [
{$colDefs}
        ];
    }

    public function index(Request \$request): Response
    {
        \$query = {$model}::query()->select(['{$colSelect}']);
        \$tableData = \$this->dataTable(\$query, \$this->columns());

        return Inertia::render('{$modelPlural}/Index', [
            'table' => \$tableData,
        ]);
    }

    public function export(Request \$request)
    {
        \$format = \$request->input('format', 'xlsx');
        \$ids    = array_filter(explode(',', \$request->input('ids', '')));
        \$query  = {$model}::query()->select(['{$colSelect}']);
        \$filename = '{$modelLower}_' . now()->format('Ymd_His') . '.' . \$format;

        return Excel::download(
            new DataTableExport(\$query, \$this->columns(), \$ids),
            \$filename,
            \$format === 'csv'
                ? \Maatwebsite\Excel\Excel::CSV
                : \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function bulkDestroy(Request \$request)
    {
        \$request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        {$model}::whereIn('id', \$request->ids)->delete();
        return back()->with('success', count(\$request->ids) . ' {$modelLower}(s) deleted.');
    }
}
PHP;

        // ── Generate Vue Page ─────────────────────────────────────────────────
        $cellSlots = $this->buildCellSlots($rawCols);

        $vue = <<<VUE
<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    {$modelPlural} Management
                </h2>
                <span class="text-sm text-gray-500">Total: {{ table.meta.total }}</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <!-- Flash Message -->
                <div v-if="\$page.props.flash?.success"
                     class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                    ✅ {{ \$page.props.flash.success }}
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <DataTable
                            :columns="table.columns"
                            :rows="table.data"
                            :meta="table.meta"
                            :filters="table.filters"
                            :bulk-actions="[{ key: 'delete', label: 'Delete Selected' }]"
                            :exportable="true"
                            :export-url="route('{$routeName}.export')"
                            row-key="id"
                            @bulk-action="handleBulkAction"
                            @selection-change="ids => selectedIds = ids"
                        >
{$cellSlots}
                            <template #rowActions="{ row }">
                                <div class="flex items-center gap-2">
                                    <button
                                        class="text-xs font-medium text-red-600 dark:text-red-400 hover:underline"
                                        @click="confirmDelete(row)"
                                    >Delete</button>
                                </div>
                            </template>

                            <template #actions>
                                <!-- Add your custom toolbar buttons here -->
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DataTable from '@/vendor/datatable/Components/DataTable.vue'

const props = defineProps({ table: { type: Object, required: true } })
const selectedIds = ref([])

function handleBulkAction({ action, ids }) {
    if (action === 'delete' && confirm('Delete ' + ids.length + ' record(s)?')) {
        router.delete(route('{$routeName}.bulk-destroy'), {
            data: { ids },
            preserveScroll: true,
            onSuccess: () => { selectedIds.value = [] },
        })
    }
}

function confirmDelete(row) {
    if (confirm('Delete this record?')) {
        router.delete(route('{$routeName}.bulk-destroy'), {
            data: { ids: [row.id] },
            preserveScroll: true,
        })
    }
}
</script>
VUE;

        // ── Route hint ───────────────────────────────────────────────────────
        $routeHint = <<<ROUTES
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/{$routeSlug}',                [{$model}Controller::class, 'index'])->name('{$routeName}.index');
    Route::get('/{$routeSlug}/export',         [{$model}Controller::class, 'export'])->name('{$routeName}.export');
    Route::delete('/{$routeSlug}/bulk-destroy',[{$model}Controller::class, 'bulkDestroy'])->name('{$routeName}.bulk-destroy');
});
ROUTES;

        // ── Write files ───────────────────────────────────────────────────────
        if (!is_dir($vueDirPath)) {
            mkdir($vueDirPath, 0755, true);
        }

        file_put_contents($controllerPath, $controller);
        file_put_contents($vuePath, $vue);

        // ── Output ────────────────────────────────────────────────────────────
        $this->newLine();
        $this->info("✅  {$model}Controller  →  app/Http/Controllers/{$model}Controller.php");
        $this->info("✅  Vue Page           →  resources/js/Pages/{$modelPlural}/Index.vue");
        $this->newLine();
        $this->comment('📌  Add these routes to routes/web.php:');
        $this->newLine();
        $this->line($routeHint);
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildColumnDefs(array $cols): string
    {
        $lines = [];
        foreach ($cols as $key) {
            $label = Str::title(str_replace('_', ' ', $key));
            $sortable = in_array($key, ['id', 'created_at', 'updated_at', 'name', 'email', 'title']) ? 'true' : 'false';
            $searchable = in_array($key, ['name', 'email', 'title', 'description', 'slug']) ? 'true' : 'false';

            $lines[] = "            ['key' => '{$key}', 'label' => '{$label}', 'sortable' => {$sortable}, 'searchable' => {$searchable}, 'exportable' => true],";
        }
        return implode("\n", $lines);
    }

    private function buildCellSlots(array $cols): string
    {
        $slots = [];
        foreach ($cols as $key) {
            if ($key === 'created_at' || $key === 'updated_at') {
                $slots[] = <<<VUE
                            <template #cell-{$key}="{ value }">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ value ? new Date(value).toLocaleDateString() : '—' }}
                                </span>
                            </template>
VUE;
            } elseif ($key === 'email') {
                $slots[] = <<<VUE
                            <template #cell-email="{ value }">
                                <a :href="'mailto:' + value" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">{{ value }}</a>
                            </template>
VUE;
            }
        }
        return implode("\n", $slots);
    }
}