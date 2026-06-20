<template>
  <nav class="navbar">
    <div class="nav-brand">
      <h2>Campus<span>Care</span></h2>
    </div>
    <div class="nav-menu">
      <div class="user-profile" v-if="user">
        <div class="user-avatar">{{ user.username.charAt(0).toUpperCase() }}</div>
        <div class="user-info">
          <span class="user-name">{{ user.username }}</span>
          <span class="user-role">{{ user.role }}</span>
        </div>
      </div>
      <button @click="logout" class="btn-logout">
        <i class="fas fa-sign-out-alt logout-icon"></i>
        <span>Logout</span>
    </button>
    </div>
  </nav>
</template>

<script>
export default {
  data() {
    return {
      user: null
    }
  },
  mounted() {
    const savedUser = localStorage.getItem('user')
    if (savedUser) {
      this.user = JSON.parse(savedUser)
    }
  },
  methods: {
    logout() {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      this.$router.push('/login')
    }
  }
}
</script>