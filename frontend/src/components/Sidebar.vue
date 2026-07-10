<template>
  <aside class="sidebar">
    <div class="sidebar-header">
      <span class="role-badge">{{ userRole }} Portal</span>
    </div>

    <nav class="sidebar-menu">

      <router-link to="/home" class="menu-item" active-class="active">
        <i class="fas fa-house icon"></i>
        Home
      </router-link>

      <template v-if="userRole === 'student' || userRole === 'staff'">

        <router-link to="/submit-request" class="menu-item" active-class="active">
          <i class="fas fa-file-circle-plus icon"></i>
          Submit Request
        </router-link>

        <router-link to="/my-requests" class="menu-item" active-class="active">
          <i class="fas fa-magnifying-glass icon"></i>
          My Requests
        </router-link>

      </template>

      <template v-if="userRole === 'technician'">

        <router-link to="/assigned-tasks" class="menu-item" active-class="active">
          <i class="fas fa-screwdriver-wrench icon"></i>
          Assigned Tasks
        </router-link>

      </template>

      <template v-if="userRole === 'admin'">

        <router-link to="/maintenance-reports" class="menu-item" active-class="active">
          <i class="fas fa-chart-column icon"></i>
          Maintenance Reports
        </router-link>

      </template>

      <router-link to="/profile" class="menu-item" active-class="active">
        <i class="fas fa-user icon"></i>
        Profile
      </router-link>

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
      this.userRole = parsed.role
        ? parsed.role.toLowerCase()
        : 'student'
    }
  }
}
</script>