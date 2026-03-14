<template>
  <div class="qdt-root">

    <!-- Toolbar -->
    <DataTableToolbar
      v-model:search="localFilters.search"
      :selected-count="selectedIds.length"
      :bulk-actions="bulkActions"
      :columns="toggleableColumns"
      :show-column-toggle="showColumnToggle"
      :exportable="exportable"
      :filters-open="filtersOpen"
      :cols-open="colsOpen"
      :actions-open="actionsOpen"
      :has-active-filters="hasActiveFilters"
      @bulk-action="handleBulkAction"
      @toggle-filters="filtersOpen = !filtersOpen; colsOpen = false; actionsOpen = false"
      @toggle-cols="colsOpen = !colsOpen; filtersOpen = false; actionsOpen = false"
      @toggle-actions="actionsOpen = !actionsOpen; filtersOpen = false; colsOpen = false"
      @clear-filters="clearFilters"
      @toggle-column="toggleColumn"
      @export="exportAs"
      @update:search="onSearchInput"
    >
      <template v-for="(_, name) in toolbarSlots" #[name]="slotProps">
        <slot :name="name" v-bind="slotProps ?? {}" />
      </template>
    </DataTableToolbar>

    <!-- Table container -->
    <div class="qdt-table-container">

      <!-- Loading overlay -->
      <DataTableLoading :show="loading" />

      <div class="qdt-table-scroll">
        <table class="qdt-table">

          <!-- Header -->
          <DataTableHeader
            :columns="visibleColumns"
            :sort="localFilters.sort"
            :direction="localFilters.direction"
            :all-selected="allSelected"
            :some-selected="someSelected"
            :show-favorite="showFavorite"
            :from="meta.from ?? 0"
            :to="meta.to ?? 0"
            :total="meta.total ?? 0"
            @sort="onSort"
            @toggle-all="toggleAll"
          />

          <tbody>
            <!-- Empty state -->
            <DataTableEmpty
              v-if="!rows.length"
              :colspan="colSpan"
            />

            <!-- Rows -->
            <DataTableRow
              v-for="row in rows"
              :key="row[rowKey]"
              :row="row"
              :columns="visibleColumns"
              :selected="isSelected(row)"
              :show-favorite="showFavorite"
              @toggle="toggleRow(row)"
              @toggle-favorite="toggleFavorite(row)"
            >
              <!-- Forward all cell slots -->
              <template
                v-for="col in visibleColumns"
                #[`cell-${col.key}`]="slotProps"
              >
                <slot :name="`cell-${col.key}`" v-bind="slotProps" />
              </template>

              <!-- Forward rowActions slot -->
              <template #rowActions="slotProps">
                <slot name="rowActions" v-bind="slotProps" />
              </template>
            </DataTableRow>
          </tbody>

        </table>
      </div>
    </div>

    <!-- Footer -->
    <DataTableFooter
      :per-page="localFilters.per_page"
      :per-page-options="meta.per_page_options ?? [15, 25, 50, 100]"
      :current-page="meta.current_page ?? 1"
      :last-page="meta.last_page ?? 1"
      @change-per-page="onChangePerPage"
      @go-to-page="goToPage"
    />

  </div>
</template>

<script setup>
import { ref, computed, reactive, useSlots } from 'vue'
import { router } from '@inertiajs/vue3'

import DataTableToolbar from './DataTable/DataTableToolbar.vue'
import DataTableHeader  from './DataTable/DataTableHeader.vue'
import DataTableRow     from './DataTable/DataTableRow.vue'
import DataTableEmpty   from './DataTable/DataTableEmpty.vue'
import DataTableFooter  from './DataTable/DataTableFooter.vue'
import DataTableLoading from './DataTable/DataTableLoading.vue'

// ── Props ─────────────────────────────────────────────────────────────────
const props = defineProps({
  columns:           { type: Array,   required: true },
  rows:              { type: Array,   default: () => [] },
  meta:              { type: Object,  required: true },
  filters:           { type: Object,  default: () => ({}) },
  rowKey:            { type: String,  default: 'id' },
  selectable:        { type: Boolean, default: true },
  showColumnToggle:  { type: Boolean, default: true },
  showFavorite:      { type: Boolean, default: false },
  exportable:        { type: Boolean, default: true },
  exportUrl:         { type: String,  default: '' },
  bulkActions:       { type: Array,   default: () => [] },
  searchDebounce:    { type: Number,  default: 350 },
})

const emit = defineEmits(['bulkAction', 'selectionChange', 'favoriteToggle'])

const slots = useSlots()

// Toolbar slots to forward
const toolbarSlots = computed(() => {
  const names = ['topFilters', 'actions', 'filters']
  return Object.fromEntries(
    names.filter(n => !!slots[n]).map(n => [n, slots[n]])
  )
})

// ── State ─────────────────────────────────────────────────────────────────
const loading     = ref(false)
const filtersOpen = ref(false)
const colsOpen    = ref(false)
const actionsOpen = ref(false)
const selectedIds = ref([])

const localFilters = reactive({
  search:    props.filters.search    ?? '',
  sort:      props.filters.sort      ?? '',
  direction: props.filters.direction ?? 'asc',
  per_page:  props.filters.per_page  ?? props.meta?.per_page ?? 15,
})

const toggleableColumns = reactive(
  props.columns.map(c => ({ ...c, visible: c.visible !== false }))
)

// ── Computed ──────────────────────────────────────────────────────────────
const visibleColumns = computed(() => toggleableColumns.filter(c => c.visible))

const colSpan = computed(() =>
  visibleColumns.value.length
  + 2                               // checkbox + actions col
  + (props.showFavorite ? 1 : 0)
)

const allSelected = computed(() =>
  props.rows.length > 0 &&
  props.rows.every(r => selectedIds.value.includes(r[props.rowKey]))
)
const someSelected = computed(() =>
  !allSelected.value &&
  props.rows.some(r => selectedIds.value.includes(r[props.rowKey]))
)
const hasActiveFilters = computed(() =>
  !!(localFilters.search || localFilters.sort)
)

// ── Helpers ───────────────────────────────────────────────────────────────
function isSelected(row) {
  return selectedIds.value.includes(row[props.rowKey])
}

// ── Navigation ────────────────────────────────────────────────────────────
function navigate(extra = {}) {
  loading.value = true
  router.get(
    window.location.pathname,
    { ...localFilters, ...extra },
    {
      preserveState:  true,
      preserveScroll: true,
      replace:        true,
      onFinish: () => { loading.value = false },
    }
  )
}

let searchTimer = null
function onSearchInput(val) {
  localFilters.search = val
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => navigate({ page: 1 }), props.searchDebounce)
}

function onSort(key) {
  if (localFilters.sort === key) {
    localFilters.direction = localFilters.direction === 'asc' ? 'desc' : 'asc'
  } else {
    localFilters.sort      = key
    localFilters.direction = 'asc'
  }
  navigate({ page: 1 })
}

function goToPage(page) { navigate({ page }) }

function onChangePerPage(n) {
  localFilters.per_page = n
  navigate({ page: 1 })
}

function clearFilters() {
  localFilters.search    = ''
  localFilters.sort      = ''
  localFilters.direction = 'asc'
  navigate({ page: 1 })
}

// ── Column toggle ─────────────────────────────────────────────────────────
function toggleColumn(key) {
  const col = toggleableColumns.find(c => c.key === key)
  if (col) col.visible = !col.visible
}

// ── Selection ─────────────────────────────────────────────────────────────
function toggleRow(row) {
  const id = row[props.rowKey]
  selectedIds.value = selectedIds.value.includes(id)
    ? selectedIds.value.filter(i => i !== id)
    : [...selectedIds.value, id]
  emit('selectionChange', selectedIds.value)
}

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = selectedIds.value.filter(
      id => !props.rows.some(r => r[props.rowKey] === id)
    )
  } else {
    const s = new Set(selectedIds.value)
    props.rows.forEach(r => s.add(r[props.rowKey]))
    selectedIds.value = [...s]
  }
  emit('selectionChange', selectedIds.value)
}

// ── Favorite ──────────────────────────────────────────────────────────────
function toggleFavorite(row) {
  row._favorite = !row._favorite
  emit('favoriteToggle', { id: row[props.rowKey], value: row._favorite })
}

// ── Bulk + Export ─────────────────────────────────────────────────────────
function handleBulkAction(action) {
  emit('bulkAction', { action: action.key, ids: [...selectedIds.value] })
}

function exportAs(format) {
  if (!props.exportUrl) return
  const p = new URLSearchParams({
    ...localFilters,
    ids: selectedIds.value.join(','),
    format,
  })
  window.location.href = `${props.exportUrl}?${p}`
}
</script>

<style>
/* ─────────────────────────────────────────────────────────────────────────
   SHARED STYLES — used by DataTable.vue + all sub-components
   ───────────────────────────────────────────────────────────────────────── */

/* Root */
.qdt-root {
  font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
  font-size: 13.5px;
  color: #1f2937;
  width: 100%;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

/* ── Topbar ────────────────────────────────────────────────────────────── */
.qdt-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  gap: 8px;
  flex-wrap: wrap;
  border-bottom: 1px solid #f3f4f6;
  background: #fff;
}
.qdt-topbar-filters {
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap; flex: 1;
}
.qdt-topbar-right {
  display: flex; align-items: center; gap: 4px; flex-wrap: wrap;
}

/* Filter input */
.qdt-filter-input-wrap { position: relative; }
.qdt-search-icon {
  position: absolute; left: 8px; top: 50%;
  transform: translateY(-50%);
  color: #9ca3af; pointer-events: none;
}
.qdt-filter-input {
  padding: 5px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  font-family: inherit;
  color: #374151;
  background: #fafafa;
  outline: none;
  min-width: 130px;
  transition: border-color .15s, box-shadow .15s;
}
.qdt-filter-input--search { padding-left: 28px; }
.qdt-filter-input:focus {
  border-color: #a5b4fc;
  background: #fff;
  box-shadow: 0 0 0 3px rgb(99 102 241 / .08);
}
.qdt-filter-input::placeholder { color: #9ca3af; }

/* ── Buttons ───────────────────────────────────────────────────────────── */
.qdt-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 13px; font-weight: 500; font-family: inherit;
  cursor: pointer; border: 1px solid transparent;
  transition: all .15s; white-space: nowrap; line-height: 1;
}
.qdt-btn-outline { border-color: #e5e7eb; background: #fff; color: #374151; }
.qdt-btn-outline:hover,
.qdt-btn-outline.qdt-btn--active,
.qdt-btn--active { background: #f3f4f6; border-color: #d1d5db; }
.qdt-btn-icon { padding: 5px 7px; }
.qdt-btn-ghost { background: none; border-color: transparent; color: #9ca3af; }
.qdt-btn-ghost:hover { color: #374151; background: #f3f4f6; }
.qdt-btn-danger { background: #ef4444; color: #fff; border-color: #ef4444; }
.qdt-btn-danger:hover { background: #dc2626; }
.qdt-btn-primary { background: #6366f1; color: #fff; border-color: #6366f1; }
.qdt-btn-primary:hover { background: #4f46e5; }
.qdt-selected-badge {
  font-size: 12px; font-weight: 500;
  color: #6366f1; background: #eef2ff;
  padding: 3px 9px; border-radius: 20px;
}

/* ── Dropdowns ─────────────────────────────────────────────────────────── */
.qdt-dropdown { position: relative; }
.qdt-dropdown-menu {
  position: absolute; top: calc(100% + 4px); left: 0; z-index: 100;
  background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
  box-shadow: 0 8px 24px rgb(0 0 0 / .10);
  min-width: 180px; padding: 4px;
  animation: qdtpop .12s ease;
}
.qdt-dropdown-menu--right { left: auto; right: 0; }
@keyframes qdtpop {
  from { opacity: 0; transform: translateY(-3px) scale(.98); }
  to   { opacity: 1; transform: none; }
}
.qdt-dropdown-section-title {
  padding: 6px 10px 4px;
  font-size: 11px; font-weight: 600; color: #9ca3af;
  text-transform: uppercase; letter-spacing: .06em;
}
.qdt-dropdown-item {
  display: flex; align-items: center;
  width: 100%; padding: 7px 10px;
  font-size: 13px; font-family: inherit; color: #374151;
  border: none; background: none; cursor: pointer;
  border-radius: 5px; transition: background .1s; text-align: left;
}
.qdt-dropdown-item:hover { background: #f3f4f6; }
.qdt-dropdown-empty { display: block; padding: 10px 12px; font-size: 12px; color: #9ca3af; }
.qdt-col-item {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 10px; font-size: 13px; color: #374151;
  cursor: pointer; border-radius: 5px; transition: background .1s;
}
.qdt-col-item:hover { background: #f3f4f6; }
.qdt-col-checkbox {
  width: 15px; height: 15px;
  border: 1.5px solid #d1d5db; border-radius: 3px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: all .12s;
}
.qdt-col-checkbox.on { background: #6366f1; border-color: #6366f1; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }

/* ── Loading overlay ───────────────────────────────────────────────────── */
.qdt-table-container { position: relative; }
.qdt-overlay {
  position: absolute; inset: 0;
  background: rgba(255,255,255,.72); z-index: 20;
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(1px);
}
.qdt-spinner-wrap {
  background: #fff; border-radius: 50%;
  box-shadow: 0 2px 12px rgb(0 0 0/.12); padding: 9px; display: flex;
}
.qdt-spinner { animation: qdtspin .7s linear infinite; }
@keyframes qdtspin { to { transform: rotate(360deg); } }
.qdt-fade-enter-active, .qdt-fade-leave-active { transition: opacity .18s; }
.qdt-fade-enter-from, .qdt-fade-leave-to { opacity: 0; }
.qdt-table-scroll { overflow-x: auto; }

/* ── Table ─────────────────────────────────────────────────────────────── */
.qdt-table { width: 100%; border-collapse: collapse; font-size: 13px; }

.qdt-th {
  padding: 8px 12px; text-align: left;
  font-size: 12.5px; font-weight: 600; color: #6b7280;
  background: #fff; border-bottom: 1px solid #e5e7eb;
  white-space: nowrap; user-select: none;
}
.qdt-th--check { width: 36px; padding: 8px 6px 8px 14px; }
.qdt-th--fav   { width: 28px; padding: 8px 4px; }
.qdt-th--count {
  text-align: right; padding-right: 14px; width: 90px;
  color: #9ca3af; font-weight: 500; font-size: 12px;
}
.qdt-th--numeric { text-align: right; }
.qdt-th--sortable { cursor: pointer; }
.qdt-th--sortable:hover { color: #374151; background: #fafafa; }
.qdt-th--sorted { color: #4f46e5; }
.qdt-th-inner { display: flex; align-items: center; gap: 4px; }
.qdt-sort-icon { display: flex; align-items: center; flex-shrink: 0; }
.qdt-record-count { font-variant-numeric: tabular-nums; }

.qdt-td {
  padding: 9px 12px;
  border-bottom: 1px solid #f3f4f6;
  color: #1f2937; vertical-align: middle;
}
.qdt-td--check { width: 36px; padding: 9px 6px 9px 14px; }
.qdt-td--fav   { width: 28px; padding: 9px 4px; }
.qdt-td--numeric { text-align: right; }
.qdt-td--rowactions {
  padding: 9px 12px 9px 6px; white-space: nowrap;
  text-align: right; width: 1%;
}
.qdt-tr:last-child .qdt-td { border-bottom: none; }
.qdt-tr:hover .qdt-td { background: #fafafa; }
.qdt-tr--selected .qdt-td { background: #eef2ff !important; }

/* Row actions — show on hover only */
.qdt-row-actions-wrap {
  display: flex; align-items: center; justify-content: flex-end; gap: 6px;
  opacity: 0; transition: opacity .12s;
}
.qdt-tr:hover .qdt-row-actions-wrap,
.qdt-tr--selected .qdt-row-actions-wrap { opacity: 1; }

/* ── Checkbox — rounded ────────────────────────────────────────────────── */
.qdt-checkbox {
  width: 15px; height: 15px;
  cursor: pointer;
  accent-color: #6366f1;
  border-radius: 4px;   /* rounded checkbox */
  appearance: none;
  -webkit-appearance: none;
  border: 1.5px solid #d1d5db;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all .12s;
  position: relative;
}
.qdt-checkbox:checked {
  background: #6366f1;
  border-color: #6366f1;
}
.qdt-checkbox:checked::after {
  content: '';
  display: block;
  width: 4px; height: 7px;
  border: 1.5px solid #fff;
  border-top: none; border-left: none;
  transform: rotate(45deg) translate(-1px, -1px);
}
.qdt-checkbox:indeterminate {
  background: #6366f1;
  border-color: #6366f1;
}
.qdt-checkbox:indeterminate::after {
  content: '';
  display: block;
  width: 7px; height: 1.5px;
  background: #fff;
  border-radius: 1px;
}
.qdt-checkbox:hover:not(:checked):not(:indeterminate) {
  border-color: #a5b4fc;
  background: #f5f3ff;
}

/* Favorite */
.qdt-fav-btn {
  background: none; border: none; cursor: pointer;
  padding: 2px; display: flex; align-items: center;
  opacity: .3; transition: opacity .12s;
}
.qdt-fav-btn:hover, .qdt-fav-btn.active { opacity: 1; }

/* Empty state */
.qdt-td-empty { padding: 0; border: none; }
.qdt-empty-inner {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 52px 20px; gap: 10px;
  color: #9ca3af; font-size: 13px;
}

/* ── Footer ────────────────────────────────────────────────────────────── */
.qdt-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 14px; border-top: 1px solid #f3f4f6; background: #fff; gap: 10px;
}
.qdt-perpage-pills { display: flex; align-items: center; gap: 2px; }
.qdt-pill {
  min-width: 36px; height: 28px; padding: 0 10px;
  border: 1px solid #e5e7eb; border-radius: 6px;
  background: #fff; font-size: 12.5px; font-family: inherit;
  font-weight: 500; color: #374151; cursor: pointer; transition: all .12s;
}
.qdt-pill:hover { background: #f3f4f6; border-color: #d1d5db; }
.qdt-pill--active { background: #1f2937; color: #fff; border-color: #1f2937; }
.qdt-footer-right { display: flex; align-items: center; gap: 8px; }
.qdt-page-info { font-size: 12.5px; color: #6b7280; white-space: nowrap; }
.qdt-pagination { display: flex; align-items: center; gap: 2px; }
.qdt-pag-btn {
  display: flex; align-items: center; justify-content: center;
  width: 28px; height: 28px;
  border: 1px solid #e5e7eb; border-radius: 6px;
  background: #fff; color: #6b7280; cursor: pointer; transition: all .12s;
}
.qdt-pag-btn:hover:not(:disabled) { background: #f3f4f6; color: #1f2937; border-color: #d1d5db; }
.qdt-pag-btn:disabled { opacity: .3; cursor: not-allowed; }

/* ── Dark mode ─────────────────────────────────────────────────────────── */
@media (prefers-color-scheme: dark) {
  .qdt-root { background: #111827; border-color: #374151; color: #f3f4f6; }
  .qdt-topbar { background: #111827; border-color: #374151; }
  .qdt-filter-input { background: #1f2937; border-color: #374151; color: #f3f4f6; }
  .qdt-filter-input:focus { background: #1f2937; border-color: #6366f1; }
  .qdt-btn-outline { background: #1f2937; border-color: #374151; color: #d1d5db; }
  .qdt-btn-outline:hover { background: #374151; }
  .qdt-dropdown-menu { background: #1f2937; border-color: #374151; }
  .qdt-dropdown-item { color: #d1d5db; }
  .qdt-dropdown-item:hover { background: #374151; }
  .qdt-col-item { color: #d1d5db; }
  .qdt-col-item:hover { background: #374151; }
  .qdt-th { background: #111827; color: #9ca3af; border-color: #374151; }
  .qdt-th--sortable:hover { background: #1f2937; color: #f3f4f6; }
  .qdt-td { color: #e5e7eb; border-color: #1f2937; }
  .qdt-tr:hover .qdt-td { background: #1f2937; }
  .qdt-tr--selected .qdt-td { background: #1e1b4b !important; }
  .qdt-checkbox { border-color: #4b5563; background: #1f2937; }
  .qdt-checkbox:hover:not(:checked):not(:indeterminate) { background: #312e81; border-color: #6366f1; }
  .qdt-footer { background: #111827; border-color: #374151; }
  .qdt-pill { background: #1f2937; border-color: #374151; color: #d1d5db; }
  .qdt-pill:hover { background: #374151; }
  .qdt-pill--active { background: #f9fafb; color: #111827; border-color: #f9fafb; }
  .qdt-pag-btn { background: #1f2937; border-color: #374151; color: #9ca3af; }
  .qdt-pag-btn:hover:not(:disabled) { background: #374151; color: #f3f4f6; }
  .qdt-overlay { background: rgba(17,24,39,.75); }
  .qdt-spinner-wrap { background: #1f2937; }
}
</style>
