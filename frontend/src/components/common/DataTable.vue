<template>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
          <th v-if="$slots.actions">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, idx) in data" :key="row.id || idx">
          <td v-for="col in columns" :key="col.key">
            <slot :name="col.key" :row="row" :value="getValue(row, col.key)">
              {{ getValue(row, col.key) }}
            </slot>
          </td>
          <td v-if="$slots.actions">
            <slot name="actions" :row="row"></slot>
          </td>
        </tr>
        <tr v-if="!data.length">
          <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="text-center" style="padding: 2rem; color: var(--text-secondary)">
            {{ emptyText }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, default: () => [] },
  emptyText: { type: String, default: 'Tidak ada data' },
})

function getValue(row, key) {
  return key.split('.').reduce((obj, k) => obj?.[k], row) ?? '-'
}
</script>
