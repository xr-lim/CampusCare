<template>
  <div class="login-container">
    <MessageBox ref="msgBox" />

    <div class="login-card">
      <div class="card-header">
        <h2>Campus<span>Care</span></h2>
        <p>Welcome back! Please login to your account.</p>
      </div>

      <form @submit.prevent="submit" class="login-form">
        <div class="input-group">
          <input v-model="email" type="email" required placeholder=" " id="email"/>
          <label for="email">Email Address</label>
        </div>

        <div class="input-group password-group">
          <input 
            v-model="password" 
            :type="showPassword ? 'text' : 'password'" 
            required 
            placeholder=" " 
            id="password"
          />
          <label for="password">Password</label>
          
          <button 
            type="button" 
            class="password-toggle" 
            @click="togglePassword"
            aria-label="Toggle password visibility"
          >
            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
          </button>
        </div>

        <button class="btn-login">
          <span>Login</span>
        </button>

        <div class="form-footer">
          <p>Don't have an account? <router-link to="/register" class="footer-link">Register now</router-link></p>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
  import { login } from '../services/authService'
  import MessageBox from '../components/MessageBox.vue'

  export default {
    components: {
      MessageBox
    },
    data() {
      return {
        email: '',
        password: '',
        showPassword: false
      }
    },
    methods: {
      togglePassword() {
        this.showPassword = !this.showPassword
      },
      async submit() {
        try {
          const res = await login({
            email: this.email,
            password: this.password
          })

          localStorage.setItem('token', res.data.token)
          localStorage.setItem('user', JSON.stringify(res.data.user))

          const role = (res.data.user?.role || '').toLowerCase()
          this.$router.push(role === 'admin' ? '/admin/dashboard' : '/home')

        } catch (error) {
          console.error(error)
          const apiErrorMessage = error.response?.data?.message || 'Unable to connect to server'

          this.$refs.msgBox.show(apiErrorMessage, 'error')
        }
      }
    }
  }
</script>
