<template>
  <Transition name="toast">
    <div v-if="visible" :class="['message-box', type]">
      <div class="message-content">
        <i :class="type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
        <span>{{ message }}</span>
      </div>
      <button @click="visible = false" class="close-toast-btn">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </Transition>
</template>

<script>
export default {
  data() {
    return {
      visible: false,
      message: '',
      type: 'success'
    }
  },
  methods: {
    show(msg, messageType = 'success', duration = 4000) {
      this.message = msg
      this.type = messageType
      this.visible = true

      clearTimeout(this.timeout)
      this.timeout = setTimeout(() => {
        this.visible = false
      }, duration)
    }
  }
}
</script>