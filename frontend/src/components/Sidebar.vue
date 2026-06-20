<template>
  <aside class="sidebar">
    <div class="sidebar-header">
      <span class="role-badge">{{ userRole }} Portal</span>
    </div>
    
    <nav class="sidebar-menu">
      <a href="/home" @click.prevent class="menu-item active">
        <span class="icon">🏠</span> Home
      </a>

      <template v-if="userRole === 'student' || userRole === 'staff'">
        <a href="#" @click.prevent class="menu-item">
          <span class="icon">📝</span> Submit Report
        </a>
        <a href="#" @click.prevent class="menu-item">
          <span class="icon">🔍</span> Track Status
        </a>
      </template>

      <template v-if="userRole === 'technician'">
        <a href="#" @click.prevent class="menu-item">
          <span class="icon">🔧</span> Assigned Tasks
        </a>
      </template>

      <template v-if="userRole === 'admin'">
        <a href="#" @click.prevent class="menu-item">
          <span class="icon">📊</span> Maintenance Reports
        </a>
      </template>
    </nav>
  </aside>
</template>

<script>
export default {
  data() {
    return {
      userRole: 'student'
    }
  },
  mounted() {
    const savedUser = localStorage.getItem('user')
    if (savedUser) {
      const parsed = JSON.parse(savedUser)
      this.userRole = parsed.role ? parsed.role.toLowerCase() : 'student'
    }
  }
}
</script>