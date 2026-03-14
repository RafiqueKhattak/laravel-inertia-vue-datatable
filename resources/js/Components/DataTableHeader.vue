<template>
  <thead>
    <tr>

      <!-- Checkbox: select all -->
      <th class="qdt-th qdt-th--check">
        <input
          type="checkbox"
          class="qdt-checkbox"
          :checked="allSelected"
          :indeterminate.prop="someSelected"
          @change="$emit('toggleAll')"
        />
      </th>

      <!-- Optional: favorite column -->
      <th v-if="showFavorite" class="qdt-th qdt-th--fav"></th>

      <!-- Data columns -->
      <th
        v-for="col in columns"
        :key="col.key"
        class="qdt-th"
        :class="{
          'qdt-th--sortable': col.sortable,
          'qdt-th--sorted':   sort === col.key,
          'qdt-th--numeric':  col.numeric,
        }"
        @click="col.sortable && $emit('sort', col.key)"
      >
        <div class="qdt-th-inner">
          <span>{{ col.label }}</span>
          <span v-if="col.sortable" class="qdt-sort-icon">
            <!-- Active asc -->
            <svg
              v-if="sort === col.key && direction === 'asc'"
              viewBox="0 0 10 12" fill="none" width="9" height="10"
            >
              <path d="M5 10V2M1 5l4-4 4 4" stroke="#6366f1"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <!-- Active desc -->
            <svg
              v-else-if="sort === col.key && direction === 'desc'"
              viewBox="0 0 10 12" fill="none" width="9" height="10"
            >
              <path d="M5 2v8M1 7l4 4 4-4" stroke="#6366f1"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <!-- Inactive -->
            <svg v-else viewBox="0 0 10 14" fill="none" width="8" height="11" opacity=".35">
              <path d="M5 12V2M1 5.5l4-4 4 4" stroke="#6b7280"
                stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M5 2v10M1 8.5l4 4 4-4" stroke="#6b7280"
                stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
        </div>
      </th>

      <!-- Record count — top-right Odoo style "from–to / total" -->
      <th class="qdt-th qdt-th--count">
        <span v-if="total" class="qdt-record-count">
          {{ from }}–{{ to }} / {{ total }}
        </span>
      </th>

    </tr>
  </thead>
</template>

<script setup>
defineProps({
  columns:     { type: Array,   required: true },
  sort:        { type: String,  default: '' },
  direction:   { type: String,  default: 'asc' },
  allSelected: { type: Boolean, default: false },
  someSelected:{ type: Boolean, default: false },
  showFavorite:{ type: Boolean, default: false },
  from:        { type: Number,  default: 0 },
  to:          { type: Number,  default: 0 },
  total:       { type: Number,  default: 0 },
})

defineEmits(['sort', 'toggleAll'])
</script>
