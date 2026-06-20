<template>
  <div class="app-layout">
    <NavBar />

    <div class="main-container">
      <SideBar />

      <main class="dashboard-layout">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import NavBar from '../components/Navbar.vue'
import SideBar from '../components/Sidebar.vue'

export default {
  components: {
    NavBar,
    SideBar
  }
}
</script>