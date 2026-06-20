<template>
  <div class="input-group">
    <label v-if="label" :for="id">{{ label }}</label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :class="{ 'input-error': error }"
    />
    <span v-if="error" class="error-message">{{ error }}</span>
  </div>
</template>

<script setup>
defineProps({
  modelValue: String,
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  error: String,
  id: String
});
defineEmits(['update:modelValue']);
</script>

<style scoped>
.input-group {
  margin-bottom: 1.25rem;
  display: flex;
  flex-direction: column;
}
label {
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  color: #374151;
}
input {
  padding: 0.65rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}
input:focus {
  outline: none;
  border-color: #2563eb;
}
.input-error {
  border-color: #dc2626;
}
.error-message {
  color: #dc2626;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}
</style>