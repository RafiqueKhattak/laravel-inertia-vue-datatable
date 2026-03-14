<template>
  <div class="qdt-root">

    <!-- TOOLBAR -->
    <div class="qdt-toolbar">
      <div class="qdt-search-wrap">
        <svg class="qdt-search-icon" viewBox="0 0 20 20" fill="none">
          <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M14 14l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <input v-model="localFilters.search" class="qdt-search-input" type="text" placeholder="Search..." @input="debouncedSearch"/>
      </div>

      <div class="qdt-toolbar-right">
        <template v-if="selectedIds.length && bulkActions.length">
          <span class="qdt-selected-badge">{{ selectedIds.length }} selected</span>
          <button v-for="action in bulkActions" :key="action.key" class="qdt-btn qdt-btn-danger" @click="handleBulkAction(action)">{{ action.label }}</button>
        </template>

        <slot name="actions" />

        <div v-if="exportable" class="qdt-dropdown" v-click-outside="() => actionsOpen = false">
          <button class="qdt-btn qdt-btn-outline" @click="actionsOpen = !actionsOpen">
            <svg viewBox="0 0 16 16" fill="none" width="13" height="13" style="margin-right:4px"><path d="M8 3v7M5 7l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            Actions
          </button>
          <div v-if="actionsOpen" class="qdt-dropdown-menu qdt-dropdown-menu--right">
            <button class="qdt-dropdown-item" @click="exportAs('xlsx'); actionsOpen=false">Export XLSX</button>
            <button class="qdt-dropdown-item" @click="exportAs('csv'); actionsOpen=false">Export CSV</button>
          </div>
        </div>

        <div class="qdt-dropdown" v-click-outside="() => filtersOpen = false">
          <button class="qdt-btn qdt-btn-outline" @click="filtersOpen = !filtersOpen">
            <svg viewBox="0 0 16 16" fill="none" width="13" height="13" style="margin-right:4px"><path d="M2 4h12M4 8h8M6 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            Filters
          </button>
          <div v-if="filtersOpen" class="qdt-dropdown-menu qdt-dropdown-menu--right">
            <slot name="filters"><span class="qdt-dropdown-empty">No filters defined.</span></slot>
          </div>
        </div>

        <div v-if="showColumnToggle" class="qdt-dropdown" v-click-outside="() => colsOpen = false">
          <button class="qdt-btn qdt-btn-outline" @click="colsOpen = !colsOpen">
            <svg viewBox="0 0 16 16" fill="none" width="13" height="13" style="margin-right:4px"><rect x="2" y="2" width="5" height="12" rx="1" stroke="currentColor" stroke-width="1.3"/><rect x="9" y="2" width="5" height="12" rx="1" stroke="currentColor" stroke-width="1.3"/></svg>
            Columns
          </button>
          <div v-if="colsOpen" class="qdt-dropdown-menu qdt-dropdown-menu--right">
            <label v-for="col in toggleableColumns" :key="col.key" class="qdt-col-item">
              <span class="qdt-col-checkbox" :class="{ 'qdt-col-checkbox--on': col.visible }">
                <svg v-if="col.visible" viewBox="0 0 10 10" fill="none" width="10" height="10"><path d="M2 5l2.5 2.5L8 3" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
              <input type="checkbox" v-model="col.visible" class="sr-only" />
              {{ col.label }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLE — fixed container, overlay loading (no layout shift) -->
    <div class="qdt-table-container">
      <Transition name="qdt-fade">
        <div v-if="loading" class="qdt-overlay">
          <div class="qdt-spinner-wrap">
            <svg class="qdt-spinner" viewBox="0 0 24 24" fill="none" width="26" height="26">
              <circle cx="12" cy="12" r="10" stroke="#e5e7eb" stroke-width="3"/>
              <path d="M12 2a10 10 0 0 1 10 10" stroke="#6366f1" stroke-width="3" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
      </Transition>

      <div class="qdt-table-scroll">
        <table class="qdt-table">
          <thead>
            <tr>
              <th v-if="selectable" class="qdt-th qdt-th--check">
                <input type="checkbox" class="qdt-checkbox" :checked="allSelected" :indeterminate.prop="someSelected" @change="toggleAll"/>
              </th>
              <th v-for="col in visibleColumns" :key="col.key" class="qdt-th" :class="{ 'qdt-th--sortable': col.sortable, 'qdt-th--sorted': localFilters.sort === col.key }" @click="col.sortable && sort(col.key)">
                <div class="qdt-th-inner">
                  <span>{{ col.label }}</span>
                  <span v-if="col.sortable" class="qdt-sort-icon">
                    <template v-if="localFilters.sort === col.key">
                      <svg v-if="localFilters.direction === 'asc'" viewBox="0 0 10 12" fill="none" width="9" height="11"><path d="M5 10V2M1 5l4-4 4 4" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      <svg v-else viewBox="0 0 10 12" fill="none" width="9" height="11"><path d="M5 2v8M1 7l4 4 4-4" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </template>
                    <svg v-else viewBox="0 0 10 14" fill="none" width="9" height="12" opacity=".35"><path d="M5 12V2M1 5.5l4-4 4 4" stroke="#6b7280" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 2v10M1 8.5l4 4 4-4" stroke="#6b7280" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </span>
                </div>
              </th>
              <th v-if="$slots.rowActions" class="qdt-th qdt-th--actions"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!rows.length">
              <td :colspan="colSpan" class="qdt-td-empty">
                <div class="qdt-empty-inner">
                  <svg viewBox="0 0 48 48" fill="none" width="40" height="40"><rect x="6" y="10" width="36" height="28" rx="3" stroke="#d1d5db" stroke-width="2"/><path d="M6 18h36" stroke="#d1d5db" stroke-width="2"/><path d="M14 26h8M14 32h14" stroke="#d1d5db" stroke-width="2" stroke-linecap="round"/></svg>
                  <p>No records found.</p>
                </div>
              </td>
            </tr>
            <tr v-for="row in rows" :key="row[rowKey]" class="qdt-tr" :class="{ 'qdt-tr--selected': isSelected(row) }">
              <td v-if="selectable" class="qdt-td qdt-td--check">
                <input type="checkbox" class="qdt-checkbox" :checked="isSelected(row)" @change="toggleRow(row)"/>
              </td>
              <td v-for="col in visibleColumns" :key="col.key" class="qdt-td">
                <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row, col.key)">{{ getValue(row, col.key) }}</slot>
              </td>
              <td v-if="$slots.rowActions" class="qdt-td qdt-td--actions">
                <slot name="rowActions" :row="row" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="qdt-footer">
      <div class="qdt-footer-left">
        <span v-if="selectedIds.length">{{ selectedIds.length }} of {{ meta.total }} row(s) selected.</span>
        <span v-else>No rows selected.</span>
      </div>
      <div class="qdt-footer-right">
        <span class="qdt-perpage-label">Rows per page</span>
        <div class="qdt-select-wrap">
          <select class="qdt-perpage-select" v-model="localFilters.per_page" @change="changePerPage">
            <option v-for="n in meta.per_page_options" :key="n" :value="n">{{ n }}</option>
          </select>
          <svg class="qdt-select-caret" viewBox="0 0 10 6" fill="none" width="10" height="6"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        </div>
        <span class="qdt-page-info">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
        <div class="qdt-pagination">
          <button class="qdt-pag-btn" :disabled="meta.current_page <= 1" @click="goToPage(1)"><svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M10 2L5 7l5 5M3 2v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
          <button class="qdt-pag-btn" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)"><svg viewBox="0 0 8 14" fill="none" width="7" height="13"><path d="M7 1L1 7l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
          <button class="qdt-pag-btn" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)"><svg viewBox="0 0 8 14" fill="none" width="7" height="13"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
          <button class="qdt-pag-btn" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.last_page)"><svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M4 2l5 5-5 5M11 2v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const vClickOutside = {
  mounted(el, binding) {
    el.__vco__ = (e) => { if (!el.contains(e.target)) binding.value(e) }
    document.addEventListener('pointerdown', el.__vco__)
  },
  unmounted(el) { document.removeEventListener('pointerdown', el.__vco__) },
}

const props = defineProps({
  columns:          { type: Array,   required: true },
  rows:             { type: Array,   default: () => [] },
  meta:             { type: Object,  required: true },
  filters:          { type: Object,  default: () => ({}) },
  rowKey:           { type: String,  default: 'id' },
  selectable:       { type: Boolean, default: true },
  showColumnToggle: { type: Boolean, default: true },
  exportable:       { type: Boolean, default: true },
  exportUrl:        { type: String,  default: '' },
  bulkActions:      { type: Array,   default: () => [] },
  searchDebounce:   { type: Number,  default: 350 },
})

const emit = defineEmits(['bulkAction', 'selectionChange'])

const loading     = ref(false)
const actionsOpen = ref(false)
const filtersOpen = ref(false)
const colsOpen    = ref(false)
const selectedIds = ref([])

const localFilters = reactive({
  search:    props.filters.search    ?? '',
  sort:      props.filters.sort      ?? '',
  direction: props.filters.direction ?? 'asc',
  per_page:  props.filters.per_page  ?? props.meta?.per_page ?? 15,
})

const toggleableColumns = reactive(props.columns.map(c => ({ ...c, visible: c.visible !== false })))

const visibleColumns = computed(() => toggleableColumns.filter(c => c.visible))
const colSpan = computed(() => visibleColumns.value.length + (props.selectable ? 1 : 0) + 1)
const allSelected = computed(() => props.rows.length > 0 && props.rows.every(r => selectedIds.value.includes(r[props.rowKey])))
const someSelected = computed(() => !allSelected.value && props.rows.some(r => selectedIds.value.includes(r[props.rowKey])))

function getValue(row, key) { return key.split('.').reduce((o, k) => o?.[k], row) }
function isSelected(row) { return selectedIds.value.includes(row[props.rowKey]) }

function navigate(extra = {}) {
  loading.value = true
  router.get(window.location.pathname, { ...localFilters, ...extra }, {
    preserveState: true, preserveScroll: true, replace: true,
    onFinish: () => { loading.value = false },
  })
}

let searchTimer = null
function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => navigate({ page: 1 }), props.searchDebounce)
}

function sort(key) {
  if (localFilters.sort === key) {
    localFilters.direction = localFilters.direction === 'asc' ? 'desc' : 'asc'
  } else { localFilters.sort = key; localFilters.direction = 'asc' }
  navigate({ page: 1 })
}

function goToPage(page) { navigate({ page }) }
function changePerPage() { navigate({ page: 1 }) }

function toggleRow(row) {
  const id = row[props.rowKey]
  selectedIds.value = selectedIds.value.includes(id) ? selectedIds.value.filter(i => i !== id) : [...selectedIds.value, id]
  emit('selectionChange', selectedIds.value)
}
function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = selectedIds.value.filter(id => !props.rows.some(r => r[props.rowKey] === id))
  } else {
    const s = new Set(selectedIds.value)
    props.rows.forEach(r => s.add(r[props.rowKey]))
    selectedIds.value = [...s]
  }
  emit('selectionChange', selectedIds.value)
}

function handleBulkAction(action) { emit('bulkAction', { action: action.key, ids: [...selectedIds.value] }) }
function exportAs(format) {
  if (!props.exportUrl) return
  const p = new URLSearchParams({ ...localFilters, ids: selectedIds.value.join(','), format })
  window.location.href = `${props.exportUrl}?${p}`
}
</script>

<style scoped>
.qdt-root { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; font-size: 14px; color: #111827; width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: #fff; }

/* Toolbar */
.qdt-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #f3f4f6; gap: 10px; flex-wrap: wrap; background: #fff; }
.qdt-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.qdt-search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 340px; }
.qdt-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #9ca3af; pointer-events: none; }
.qdt-search-input { width: 100%; padding: 7px 12px 7px 32px; border: 1px solid #e5e7eb; border-radius: 7px; font-size: 13px; color: #374151; background: #fafafa; outline: none; transition: border-color .15s, box-shadow .15s; box-sizing: border-box; }
.qdt-search-input:focus { border-color: #a5b4fc; background: #fff; box-shadow: 0 0 0 3px rgb(99 102 241 / .1); }

/* Buttons */
.qdt-btn { display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 7px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid transparent; transition: all .15s; white-space: nowrap; line-height: 1; }
.qdt-btn-outline { border-color: #e5e7eb; background: #fff; color: #374151; }
.qdt-btn-outline:hover { background: #f9fafb; border-color: #d1d5db; }
.qdt-btn-danger { background: #ef4444; color: #fff; }
.qdt-btn-danger:hover { background: #dc2626; }
.qdt-selected-badge { font-size: 12px; font-weight: 500; color: #6366f1; background: #eef2ff; padding: 3px 10px; border-radius: 20px; }

/* Dropdowns */
.qdt-dropdown { position: relative; }
.qdt-dropdown-menu { position: absolute; top: calc(100% + 6px); z-index: 50; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 8px 24px rgb(0 0 0 / .10); min-width: 160px; padding: 4px; animation: qdt-pop .12s ease; }
.qdt-dropdown-menu--right { right: 0; }
@keyframes qdt-pop { from { opacity: 0; transform: translateY(-4px) scale(.97); } to { opacity: 1; transform: none; } }
.qdt-dropdown-item { display: flex; align-items: center; width: 100%; padding: 7px 10px; font-size: 13px; color: #374151; border: none; background: none; cursor: pointer; border-radius: 5px; transition: background .12s; text-align: left; }
.qdt-dropdown-item:hover { background: #f3f4f6; }
.qdt-dropdown-empty { display: block; padding: 8px 12px; font-size: 12px; color: #9ca3af; }
.qdt-col-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; font-size: 13px; color: #374151; cursor: pointer; border-radius: 5px; transition: background .12s; }
.qdt-col-item:hover { background: #f3f4f6; }
.qdt-col-checkbox { width: 16px; height: 16px; border: 1.5px solid #d1d5db; border-radius: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .12s; }
.qdt-col-checkbox--on { background: #6366f1; border-color: #6366f1; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }

/* Table container + OVERLAY */
.qdt-table-container { position: relative; }
.qdt-overlay { position: absolute; inset: 0; background: rgb(255 255 255 / .72); z-index: 10; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(1px); }
.qdt-spinner-wrap { background: #fff; border-radius: 50%; box-shadow: 0 2px 12px rgb(0 0 0 / .12); padding: 10px; display: flex; }
.qdt-spinner { animation: qdt-spin .75s linear infinite; }
@keyframes qdt-spin { to { transform: rotate(360deg); } }
.qdt-fade-enter-active, .qdt-fade-leave-active { transition: opacity .18s; }
.qdt-fade-enter-from, .qdt-fade-leave-to { opacity: 0; }
.qdt-table-scroll { overflow-x: auto; }

/* Table */
.qdt-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.qdt-th { padding: 11px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; user-select: none; }
.qdt-th--check { width: 40px; padding: 11px 8px 11px 16px; }
.qdt-th--actions { width: 80px; }
.qdt-th--sortable { cursor: pointer; }
.qdt-th--sortable:hover { background: #f3f4f6; color: #374151; }
.qdt-th--sorted { color: #4f46e5; }
.qdt-th-inner { display: flex; align-items: center; gap: 5px; }
.qdt-sort-icon { display: flex; align-items: center; flex-shrink: 0; }
.qdt-td { padding: 11px 16px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
.qdt-td--check { width: 40px; padding: 11px 8px 11px 16px; }
.qdt-td--actions { padding: 8px 12px; white-space: nowrap; }
.qdt-tr:last-child .qdt-td { border-bottom: none; }
.qdt-tr:hover .qdt-td { background: #fafafa; }
.qdt-tr--selected .qdt-td { background: #eef2ff !important; }
.qdt-td-empty { padding: 0; border: none; }
.qdt-empty-inner { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px 20px; gap: 10px; color: #9ca3af; font-size: 13px; }
.qdt-checkbox { width: 15px; height: 15px; cursor: pointer; accent-color: #6366f1; }

/* Footer */
.qdt-footer { display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-top: 1px solid #f3f4f6; background: #fff; gap: 12px; flex-wrap: wrap; }
.qdt-footer-left { font-size: 13px; color: #6b7280; }
.qdt-footer-right { display: flex; align-items: center; gap: 14px; }
.qdt-perpage-label { font-size: 13px; color: #6b7280; white-space: nowrap; }
.qdt-select-wrap { position: relative; display: inline-flex; align-items: center; }
.qdt-perpage-select { appearance: none; padding: 5px 28px 5px 10px; border: 1px solid #e5e7eb; border-radius: 7px; font-size: 13px; color: #374151; background: #fff; cursor: pointer; outline: none; }
.qdt-perpage-select:focus { border-color: #a5b4fc; }
.qdt-select-caret { position: absolute; right: 8px; pointer-events: none; color: #9ca3af; }
.qdt-page-info { font-size: 13px; color: #6b7280; white-space: nowrap; }
.qdt-pagination { display: flex; align-items: center; gap: 2px; }
.qdt-pag-btn { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border: 1px solid #e5e7eb; border-radius: 7px; background: #fff; color: #374151; cursor: pointer; transition: all .15s; }
.qdt-pag-btn:hover:not(:disabled) { background: #f3f4f6; border-color: #d1d5db; }
.qdt-pag-btn:disabled { opacity: .35; cursor: not-allowed; }

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .qdt-root { background: #1f2937; border-color: #374151; color: #f3f4f6; }
  .qdt-toolbar { background: #1f2937; border-color: #374151; }
  .qdt-search-input { background: #111827; border-color: #374151; color: #f3f4f6; }
  .qdt-search-input:focus { background: #111827; border-color: #6366f1; }
  .qdt-btn-outline { background: #111827; border-color: #374151; color: #d1d5db; }
  .qdt-btn-outline:hover { background: #1f2937; border-color: #4b5563; }
  .qdt-dropdown-menu { background: #1f2937; border-color: #374151; }
  .qdt-dropdown-item { color: #d1d5db; }
  .qdt-dropdown-item:hover { background: #111827; }
  .qdt-col-item { color: #d1d5db; }
  .qdt-col-item:hover { background: #111827; }
  .qdt-col-checkbox { border-color: #4b5563; }
  .qdt-th { background: #111827; color: #9ca3af; border-color: #374151; }
  .qdt-th--sortable:hover { background: #1a2332; color: #d1d5db; }
  .qdt-td { color: #d1d5db; border-color: #374151; }
  .qdt-tr:hover .qdt-td { background: #111827; }
  .qdt-tr--selected .qdt-td { background: #1e1b4b !important; }
  .qdt-footer { background: #1f2937; border-color: #374151; }
  .qdt-footer-left { color: #9ca3af; }
  .qdt-perpage-label { color: #9ca3af; }
  .qdt-perpage-select { background: #111827; border-color: #374151; color: #d1d5db; }
  .qdt-page-info { color: #9ca3af; }
  .qdt-pag-btn { background: #111827; border-color: #374151; color: #d1d5db; }
  .qdt-pag-btn:hover:not(:disabled) { background: #1f2937; }
  .qdt-overlay { background: rgb(31 41 55 / .75); }
  .qdt-spinner-wrap { background: #1f2937; }
}
</style>
