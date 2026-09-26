<template>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.key" scope="col">{{ col.label }}</th>
          <th v-if="$slots.actions" scope="col">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <template v-if="loading">
          <tr v-for="n in loadingRows" :key="`sk-${n}`">
            <td v-for="col in columns" :key="`sk-${n}-${col.key}`">
              <div class="skeleton h-4 w-3/4"></div>
            </td>
            <td v-if="$slots.actions">
              <div class="skeleton h-4 w-16"></div>
            </td>
          </tr>
        </template>
        <template v-else>
          <tr v-for="(row, idx) in data" :key="row.id || idx">
            <td v-for="col in columns" :key="col.key">
              <slot :name="col.key" :row="row" :value="getValue(row, col.key)">
                {{ getValue(row, col.key) }}
              </slot>
            </td>
            <td v-if="$slots.actions">
              <slot name="actions" :row="row" />
            </td>
          </tr>
          <tr v-if="!data.length">
            <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="!p-0">
              <slot name="empty">
                <EmptyState
                  :title="emptyTitle"
                  :description="emptyDescription"
                />
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import EmptyState from './EmptyState.vue'

withDefaults(
  defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    loadingRows: { type: Number, default: 5 },
    emptyTitle: { type: String, default: 'Tidak ada data' },
    emptyDescription: { type: String, default: '' },
  }),
  {}
)

function getValue(row, key) {
  return (
    key
      .split('.')
      .reduce((obj, k) => obj?.[k], row) ?? '-'
  )
}
</script>
