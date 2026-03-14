<template>
  <tr
    class="qdt-tr"
    :class="{ 'qdt-tr--selected': selected }"
  >

    <!-- Checkbox -->
    <td class="qdt-td qdt-td--check">
      <input
        type="checkbox"
        class="qdt-checkbox"
        :checked="selected"
        @change="$emit('toggle')"
      />
    </td>

    <!-- Optional: favorite star -->
    <td v-if="showFavorite" class="qdt-td qdt-td--fav">
      <button
        class="qdt-fav-btn"
        :class="{ active: row._favorite }"
        @click.stop="$emit('toggleFavorite')"
      >
        <svg viewBox="0 0 14 14" fill="none" width="13" height="13">
          <path
            d="M7 1l1.5 4H13l-3.5 2.5 1.5 4L7 9l-4 2.5 1.5-4L1 5h4.5z"
            :stroke="row._favorite ? '#f59e0b' : '#d1d5db'"
            :fill="row._favorite ? '#f59e0b' : 'none'"
            stroke-width="1.2"
            stroke-linejoin="round"
          />
        </svg>
      </button>
    </td>

    <!-- Data cells -->
    <td
      v-for="col in columns"
      :key="col.key"
      class="qdt-td"
      :class="{ 'qdt-td--numeric': col.numeric }"
    >
      <slot :name="`cell-${col.key}`" :row="row" :value="getValue(col.key)">
        {{ getValue(col.key) }}
      </slot>
    </td>

    <!-- Row actions — visible on hover -->
    <td class="qdt-td qdt-td--rowactions">
      <div class="qdt-row-actions-wrap">
        <slot name="rowActions" :row="row" />
      </div>
    </td>

  </tr>
</template>

<script setup>
const props = defineProps({
  row:         { type: Object,  required: true },
  columns:     { type: Array,   required: true },
  selected:    { type: Boolean, default: false },
  showFavorite:{ type: Boolean, default: false },
})

defineEmits(['toggle', 'toggleFavorite'])

function getValue(key) {
  return key.split('.').reduce((o, k) => o?.[k], props.row)
}
</script>
