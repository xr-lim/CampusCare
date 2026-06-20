<template>
  <div class="login-container">
    <MessageBox ref="msgBox" />

    <div class="login-card">
      <div class="card-header">
        <h2>Campus<span>Care</span></h2>
        <p>Create your account to get started.</p>
      </div>

      <form @submit.prevent="submit" class="login-form">
        <div class="input-group">
          <input v-model="username" type="text" required placeholder=" " id="username" />
          <label for="username">Username</label>
        </div>

        <div class="input-group">
          <input v-model="email" type="email" required placeholder=" " id="email" />
          <label for="email">Email Address</label>
        </div>

        <div class="input-group">
          <input v-model="password" type="password" required placeholder=" " id="password" />
          <label for="password">Password</label>
        </div>

        <div class="select-group">
          <select id="role" v-model="role" required>
            <option value="" disabled hidden></option>
            <option value="student">Student</option>
            <option value="staff">Staff</option>
            <option value="technician">Technician</option>
          </select>
          <label for="role">Register as</label>
        </div>

        <button type="submit" class="btn-login">
          <span>Register</span>
        </button>

        <div class="form-footer">
          <p>Already have an account? <router-link to="/login" class="footer-link">Login instead</router-link></p>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { register } from '../services/authService'
import MessageBox from '../components/MessageBox.vue'

export default {
  components: {
    MessageBox
  },
  data() {
    return {
      username: '',
      email: '',
      password: '',
      role: ''
    }
  },
  methods: {
    async submit() {
      try {
        const res = await register({
          username: this.username,
          email: this.email,
          password: this.password,
          role: this.role
        })

        const successMessage = res.data?.message || 'Registration successful!'
        this.$refs.msgBox.show(successMessage, 'success')

        setTimeout(() => {
          this.$router.push('/login')
        }, 1500)

      } catch (error) {
        console.error(error)
        const apiErrorMessage = error.response?.data?.message || 'Unable to connect to server'

        this.$refs.msgBox.show(apiErrorMessage, 'error')
      }
    }
  }
}
</script>