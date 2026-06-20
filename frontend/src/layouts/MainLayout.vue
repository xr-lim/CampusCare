<template>
  <div class="app-layout">
    <nav class="navbar">
      <div class="brand">🏫 CampusCare</div>
      <div class="nav-links">
        <span class="user-badge">{{ userRole }} View</span>
        <button @click="handleLogout" class="logout-btn">Logout</button>
      </div>
    </nav>
    <main class="content-container">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { ref, onMounted } from 'vue';

const router = useRouter();
const userRole = ref('User');

onMounted(() => {
  const token = localStorage.getItem('token');
  if (token) {
    const payload = JSON.parse(window.atob(token.split('.')[1]));
    userRole.value = payload.role;
  }
});

const handleLogout = () => {
  localStorage.removeItem('token');
  router.push('/login');
};
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  background-color: #f9fafb;
}
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: #ffffff;
  border-bottom: 1px solid #e5e7eb;
}
.brand {
  font-weight: 700;
  font-size: 1.25rem;
  color: #1e3a8a;
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}
.user-badge {
  background-color: #eff6ff;
  color: #2563eb;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 600;
}
.logout-btn {
  background: none;
  border: 1px solid #d1d5db;
  padding: 0.4rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}
.logout-btn:hover {
  background-color: #f3f4f6;
  color: #dc2626;
}
.content-container {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
</style>