<template>
  <Transition name="qdt-fade">
    <div v-if="show" class="qdt-overlay">
      <div class="qdt-spinner-wrap">
        <svg class="qdt-spinner" viewBox="0 0 24 24" fill="none" width="22" height="22">
          <circle cx="12" cy="12" r="10" stroke="#e5e7eb" stroke-width="3"/>
          <path d="M12 2a10 10 0 0 1 10 10" stroke="#6366f1"
            stroke-width="3" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
})
</script>
