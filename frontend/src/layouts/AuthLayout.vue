<template>
  <div class="auth-layout-container">
    <slot />
  </div>
</template>

<style scoped>
.auth-layout-container {
  min-height: 100vh;
  width: 100%;
  background-color: #f3f4f6;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
</style>