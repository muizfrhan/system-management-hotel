<template>
  <div class="w-full">
    <label v-if="label" :for="inputId" class="label">
      {{ label }}
      <span v-if="required" class="text-danger ml-0.5" aria-hidden="true">*</span>
    </label>
    <textarea
      :id="inputId"
      class="textarea-field"
      :value="modelValue"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="describedBy"
      :required="required"
      v-bind="attrsWithoutModel"
      @input="onInput"
    />
    <p v-if="error" :id="errorId" class="field-error" role="alert">{{ error }}</p>
    <p v-else-if="hint" :id="hintId" class="field-hint">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, useId, useAttrs } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const attrs = useAttrs()
const attrsWithoutModel = computed(() => {
  const rest = { ...attrs }
  delete rest.modelValue
  delete rest['onUpdate:modelValue']
  return rest
})

const uid = useId()
const inputId = computed(() => `textarea-${uid}`)
const errorId = computed(() => `textarea-${uid}-error`)
const hintId = computed(() => `textarea-${uid}-hint`)
const describedBy = computed(() => {
  if (props.error) return errorId.value
  if (props.hint) return hintId.value
  return undefined
})

function onInput(e) {
  emit('update:modelValue', e.target.value)
}
</script>
