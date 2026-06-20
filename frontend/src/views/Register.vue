<template>
  <div class="auth-card">
    <h2>Create Account</h2>
    <p class="subtitle">Join the maintenance network</p>

    <div v-if="apiError" class="alert alert-danger">{{ apiError }}</div>

    <form @submit.prevent="submitRegister">
      <BaseInput id="username" label="Username" v-model="form.username" :error="errors.username" />
      <BaseInput id="email" label="Email Address" type="email" v-model="form.email" :error="errors.email" />
      <BaseInput id="password" label="Password" type="password" v-model="form.password" :error="errors.password" />

      <div class="input-group">
        <label for="role">Account Type / Role</label>
        <select id="role" v-model="form.role">
          <option value="Student/Staff">Student / Staff</option>
          <option value="Technician">Technician</option>
        </select>
      </div>

      <button type="submit" :disabled="loading" class="submit-btn">
        {{ loading ? 'Registering...' : 'Register Account' }}
      </button>
    </form>
    <p class="footer-text">Already registered? <router-link to="/login">Sign In</router-link></p>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import BaseInput from '../components/BaseInput.vue';

const router = useRouter();
const loading = ref(false);
const apiError = ref('');

const form = reactive({ username: '', email: '', password: '', role: 'Student/Staff' });
const errors = reactive({ username: '', email: '', password: '' });

const validate = () => {
  let valid = true;
  errors.username = form.username ? '' : 'Username is required.';
  errors.email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) ? '' : 'Provide a valid email address.';
  errors.password = form.password.length >= 6 ? '' : 'Password must contain at least 6 characters.';
  if (errors.username || errors.email || errors.password) valid = false;
  return valid;
};

const submitRegister = async () => {
  if (!validate()) return;
  loading.value = true;
  apiError.value = '';

  try {
    await api.post('/register', form);
    router.push('/login');
  } catch (error) {
    apiError.value = error.response?.data?.message || 'Registration failed.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-card { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width:100%; max-width:400px; margin: 5vh auto;}
h2 { color: #1f2937; margin-bottom: 0.25rem;}
.subtitle { color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem; }
.input-group { margin-bottom: 1.25rem; display: flex; flex-direction: column; }
label { font-size: 0.875rem; font-weight: 600; margin-bottom: 0.35rem; color: #374151; }
select { padding: 0.65rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; background: white;}
.submit-btn { width: 100%; background-color: #2563eb; color: white; padding: 0.75rem; border: none; border-radius: 6px; font-weight:600; cursor:pointer; margin-top: 1rem;}
.alert-danger { background-color: #fef2f2; color: #dc2626; padding: 0.75rem; border-radius: 6px; font-size: 0.875rem; margin-bottom: 1rem; }
.footer-text { text-align: center; font-size: 0.875rem; color: #4b5563; margin-top: 1.5rem; }
</style>