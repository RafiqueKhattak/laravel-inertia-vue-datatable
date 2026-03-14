<template>
  <div class="qdt-topbar">

    <!-- Left: slot for inline column filter inputs -->
    <div class="qdt-topbar-filters">
      <slot name="topFilters">
        <div class="qdt-filter-input-wrap">
          <svg class="qdt-search-icon" viewBox="0 0 20 20" fill="none" width="14" height="14">
            <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.6"/>
            <path d="M14 14l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
          <input
            :value="search"
            class="qdt-filter-input qdt-filter-input--search"
            type="text"
            placeholder="Search..."
            @input="$emit('update:search', $event.target.value)"
          />
        </div>
      </slot>
    </div>

    <!-- Right: action buttons -->
    <div class="qdt-topbar-right">

      <!-- Bulk selected badge + actions -->
      <template v-if="selectedCount && bulkActions.length">
        <span class="qdt-selected-badge">{{ selectedCount }} selected</span>
        <button
          v-for="action in bulkActions"
          :key="action.key"
          class="qdt-btn qdt-btn-danger"
          @click="$emit('bulkAction', action)"
        >{{ action.label }}</button>
      </template>

      <!-- Custom slot (e.g. "+ Add" button) -->
      <slot name="actions" />

      <!-- Filter button -->
      <div class="qdt-dropdown">
        <button
          class="qdt-btn qdt-btn-outline"
          :class="{ 'qdt-btn--active': filtersOpen }"
          @click="$emit('toggleFilters')"
        >
          <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
            <path d="M2 4h12M4 8h8M6 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          Filter
        </button>
        <div v-if="filtersOpen" class="qdt-dropdown-menu">
          <slot name="filters">
            <span class="qdt-dropdown-empty">No filters defined.</span>
          </slot>
        </div>
      </div>

      <!-- Clear filters -->
      <button
        v-if="hasActiveFilters"
        class="qdt-btn qdt-btn-ghost qdt-btn-icon"
        title="Clear filters"
        @click="$emit('clearFilters')"
      >
        <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
          <path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>

      <!-- Sort options icon -->
      <button class="qdt-btn qdt-btn-outline qdt-btn-icon" title="Sort options">
        <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
          <path d="M2 5h8M2 8h5M2 11h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M12 3v10M9 10l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

      <!-- Columns toggle -->
      <div v-if="showColumnToggle" class="qdt-dropdown">
        <button
          class="qdt-btn qdt-btn-outline qdt-btn-icon"
          :class="{ 'qdt-btn--active': colsOpen }"
          title="Toggle columns"
          @click="$emit('toggleCols')"
        >
          <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
            <path d="M8 2v12M4 4H2v8h2V4zm8 0h-2v8h2V4z"
              stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <div v-if="colsOpen" class="qdt-dropdown-menu qdt-dropdown-menu--right">
          <div class="qdt-dropdown-section-title">Columns</div>
          <label
            v-for="col in columns"
            :key="col.key"
            class="qdt-col-item"
            @click.prevent="$emit('toggleColumn', col.key)"
          >
            <span class="qdt-col-checkbox" :class="{ on: col.visible }">
              <svg v-if="col.visible" viewBox="0 0 10 10" fill="none" width="9" height="9">
                <path d="M2 5l2.5 2.5L8 3" stroke="white" stroke-width="1.6"
                  stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            {{ col.label }}
          </label>
        </div>
      </div>

      <!-- Export / more actions (kebab) -->
      <div v-if="exportable" class="qdt-dropdown">
        <button
          class="qdt-btn qdt-btn-outline qdt-btn-icon"
          :class="{ 'qdt-btn--active': actionsOpen }"
          title="More actions"
          @click="$emit('toggleActions')"
        >
          <svg viewBox="0 0 16 16" fill="none" width="14" height="14">
            <circle cx="3"  cy="8" r="1.2" fill="currentColor"/>
            <circle cx="8"  cy="8" r="1.2" fill="currentColor"/>
            <circle cx="13" cy="8" r="1.2" fill="currentColor"/>
          </svg>
        </button>
        <div v-if="actionsOpen" class="qdt-dropdown-menu qdt-dropdown-menu--right">
          <button class="qdt-dropdown-item" @click="$emit('export', 'xlsx')">
            <svg viewBox="0 0 14 14" fill="none" width="13" height="13" style="margin-right:6px">
              <rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/>
              <path d="M5 5l2 2-2 2M9 5l-2 2 2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
            Export XLSX
          </button>
          <button class="qdt-dropdown-item" @click="$emit('export', 'csv')">
            <svg viewBox="0 0 14 14" fill="none" width="13" height="13" style="margin-right:6px">
              <rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/>
              <path d="M4 4h6M4 7h6M4 10h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
            Export CSV
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  search:           { type: String,  default: '' },
  selectedCount:    { type: Number,  default: 0 },
  bulkActions:      { type: Array,   default: () => [] },
  columns:          { type: Array,   default: () => [] },
  showColumnToggle: { type: Boolean, default: true },
  exportable:       { type: Boolean, default: true },
  filtersOpen:      { type: Boolean, default: false },
  colsOpen:         { type: Boolean, default: false },
  actionsOpen:      { type: Boolean, default: false },
  hasActiveFilters: { type: Boolean, default: false },
})

defineEmits([
  'update:search',
  'bulkAction',
  'toggleFilters',
  'toggleCols',
  'toggleActions',
  'clearFilters',
  'toggleColumn',
  'export',
])
</script>
