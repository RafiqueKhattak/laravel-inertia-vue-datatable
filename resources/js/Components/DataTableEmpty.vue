<template>
  <tr>
    <td :colspan="colspan" class="qdt-td-empty">
      <div class="qdt-empty-inner">
        <svg viewBox="0 0 48 48" fill="none" width="40" height="40">
          <rect x="6" y="10" width="36" height="28" rx="3"
            stroke="#d1d5db" stroke-width="2"/>
          <path d="M6 18h36" stroke="#d1d5db" stroke-width="2"/>
          <path d="M14 26h8M14 32h14" stroke="#d1d5db"
            stroke-width="2" stroke-linecap="round"/>
        </svg>
        <p>{{ message }}</p>
      </div>
    </td>
  </tr>
</template>

<script setup>
defineProps({
  colspan: { type: Number, default: 1 },
  message: { type: String, default: 'No records found.' },
})
</script>
