<template>
  <div class="auth-card">
    <h2>Welcome Back</h2>
    <p class="subtitle">Log into CampusCare Facility Maintenance</p>

    <div v-if="apiError" class="alert alert-danger">{{ apiError }}</div>

    <form @submit.prevent="submitLogin">
      <BaseInput
        id="username"
        label="Username"
        v-model="form.username"
        placeholder="Enter your username"
        :error="errors.username"
      />

      <BaseInput
        id="password"
        label="Password"
        type="password"
        v-model="form.password"
        placeholder="••••••••"
        :error="errors.password"
      />

      <button type="submit" :disabled="loading" class="submit-btn">
        {{ loading ? 'Signing in...' : 'Sign In' }}
      </button>
    </form>

    <p class="footer-text">
      Don't have an account? <router-link to="/register">Register here</router-link>
    </p>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import BaseInput from '../components/BaseInput.vue';

const router = useRouter();
const loading = ref(false);
const apiError = ref('');

const form = reactive({ username: '', password: '' });
const errors = reactive({ username: '', password: '' });

const validateForm = () => {
  let isValid = true;
  errors.username = form.username ? '' : 'Username is required.';
  errors.password = form.password ? '' : 'Password is required.';
  if (!form.username || !form.password) isValid = false;
  return isValid;
};

const submitLogin = async () => {
  if (!validateForm()) return;
  
  loading.value = true;
  apiError.value = '';

  try {
    const response = await api.post('/login', form);
    localStorage.setItem('token', response.data.token);
    router.push('/dashboard');
  } catch (error) {
    apiError.value = error.response?.data?.message || 'Invalid username or password.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
  margin: 10vh auto;
}
h2 { color: #1f2937; margin-bottom: 0.25rem; font-weight: 700;}
.subtitle { color: #6b7280; font-size: 0.875rem; margin-bottom: 2rem; }
.submit-btn {
  width: 100%;
  background-color: #2563eb;
  color: white;
  padding: 0.75rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 0.5rem;
}
.submit-btn:disabled { background-color: #93c5fd; }
.alert-danger {
  background-color: #fef2f2;
  color: #dc2626;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}
.footer-text { text-align: center; font-size: 0.875rem; color: #4b5563; margin-top: 1.5rem; }
</style>