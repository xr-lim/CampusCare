<template>
  <div class="error-container">
    <h1>403 - Forbidden Access</h1>
    <p>Your current account privileges do not allow viewing this administrative resource.</p>
    <router-link to="/dashboard" class="back-link">Return to Dashboard</router-link>
  </div>
</template>
<style scoped>
.error-container { text-align: center; padding: 4rem 2rem; }
h1 { color: #dc2626; }
.back-link { display: inline-block; margin-top: 1.5rem; color: #2563eb; text-decoration: underline; }
</style>