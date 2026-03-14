<template>
  <div class="qdt-footer">

    <!-- Left: per-page pill buttons (Odoo style) -->
    <div class="qdt-perpage-pills">
      <button
        v-for="n in perPageOptions"
        :key="n"
        class="qdt-pill"
        :class="{ 'qdt-pill--active': perPage == n }"
        @click="$emit('changePerPage', n)"
      >{{ n }}</button>
    </div>

    <!-- Right: page info + navigation arrows -->
    <div class="qdt-footer-right">
      <span class="qdt-page-info">
        Page {{ currentPage }} of {{ lastPage }}
      </span>
      <div class="qdt-pagination">

        <!-- First -->
        <button
          class="qdt-pag-btn"
          :disabled="currentPage <= 1"
          title="First page"
          @click="$emit('goToPage', 1)"
        >
          <svg viewBox="0 0 14 14" fill="none" width="11" height="11">
            <path d="M10 2L5 7l5 5M3 2v10" stroke="currentColor"
              stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <!-- Prev -->
        <button
          class="qdt-pag-btn"
          :disabled="currentPage <= 1"
          title="Previous page"
          @click="$emit('goToPage', currentPage - 1)"
        >
          <svg viewBox="0 0 8 14" fill="none" width="6" height="12">
            <path d="M7 1L1 7l6 6" stroke="currentColor"
              stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <!-- Next -->
        <button
          class="qdt-pag-btn"
          :disabled="currentPage >= lastPage"
          title="Next page"
          @click="$emit('goToPage', currentPage + 1)"
        >
          <svg viewBox="0 0 8 14" fill="none" width="6" height="12">
            <path d="M1 1l6 6-6 6" stroke="currentColor"
              stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <!-- Last -->
        <button
          class="qdt-pag-btn"
          :disabled="currentPage >= lastPage"
          title="Last page"
          @click="$emit('goToPage', lastPage)"
        >
          <svg viewBox="0 0 14 14" fill="none" width="11" height="11">
            <path d="M4 2l5 5-5 5M11 2v10" stroke="currentColor"
              stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

      </div>
    </div>

  </div>
</template>

<script setup>
defineProps({
  perPage:       { type: Number, required: true },
  perPageOptions:{ type: Array,  default: () => [20, 100, 500] },
  currentPage:   { type: Number, required: true },
  lastPage:      { type: Number, required: true },
})

defineEmits(['changePerPage', 'goToPage'])
</script>
