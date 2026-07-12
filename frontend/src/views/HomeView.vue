<template>
  <MessageBox ref="msgBox" />
  <section class="user-dashboard">
    <header class="dashboard-heading">
      <div>
        <p class="eyebrow">{{ isTechnician ? 'Technician Dashboard' : 'Request Dashboard' }}</p>
        <h1>Welcome back, {{ userName }}</h1>
        <p>{{ dashboardDescription }}</p>
      </div>
      <router-link :to="primaryAction.path" class="primary-action">{{ primaryAction.label }}</router-link>
    </header>

    <p v-if="loading" class="empty-state">Loading your dashboard...</p>

    <template v-else>
      <div class="overview-grid">
        <article v-for="card in overviewCards" :key="card.label" class="overview-card" :class="card.tone">
          <div class="overview-icon"><i :class="card.icon" /></div>
          <div><span>{{ card.label }}</span><strong>{{ card.value }}</strong></div>
        </article>
      </div>

      <article class="recent-card">
        <div class="section-heading">
          <div>
            <h2>{{ isTechnician ? 'Recent Assigned Requests' : 'Recent Submitted Requests' }}</h2>
            <p>{{ isTechnician ? 'Your latest assigned maintenance work.' : 'The latest requests you submitted.' }}</p>
          </div>
          <router-link :to="listPath" class="text-link">View all</router-link>
        </div>

        <div v-if="recentRequests.length" class="recent-list">
          <router-link v-for="item in recentRequests" :key="item.id" :to="requestPath(item.id)" class="recent-item">
            <div class="request-copy">
              <span>Request #{{ item.id }}</span>
              <h3>{{ item.title }}</h3>
              <p>{{ item.category_name }} · {{ item.location_name }}</p>
            </div>
            <div class="request-summary">
              <span class="status-chip" :class="statusClass(item.status)">{{ item.status }}</span>
              <small>{{ formatDate(item.created_at) }}</small>
            </div>
          </router-link>
        </div>
        <div v-else class="empty-state">
          {{ isTechnician ? 'No requests are currently assigned to you.' : 'You have not submitted any requests yet.' }}
        </div>
      </article>
    </template>
  </section>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getAssignedRequests, getMyRequests } from '../services/requestService'

export default {
  components: { MessageBox },
  data() {
    const storedUser = JSON.parse(localStorage.getItem('user') || '{}')
    return { requests: [], loading: false, user: storedUser }
  },
  computed: {
    isTechnician() { return (this.user.role || '').toLowerCase() === 'technician' },
    userName() { return this.user.username || this.user.name || 'User' },
    dashboardDescription() {
      return this.isTechnician
        ? 'Monitor your assigned work and keep maintenance progress up to date.'
        : 'Track your submitted maintenance requests and their current progress.'
    },
    primaryAction() {
      return this.isTechnician
        ? { label: 'View Assigned Requests', path: '/assigned-tasks' }
        : { label: 'Submit New Request', path: '/submit-request' }
    },
    listPath() { return this.isTechnician ? '/assigned-tasks' : '/my-requests' },
    recentRequests() { return this.requests.slice(0, 5) },
    overviewCards() {
      const count = (...statuses) => this.requests.filter(item => statuses.includes(item.status)).length
      return this.isTechnician
        ? [
            { label: 'Total Assigned', value: this.requests.length, icon: 'fas fa-clipboard-list', tone: 'blue' },
            { label: 'Awaiting Start', value: count('Assigned'), icon: 'fas fa-hourglass-start', tone: 'amber' },
            { label: 'In Progress', value: count('In Progress'), icon: 'fas fa-screwdriver-wrench', tone: 'cyan' },
            { label: 'Completed', value: count('Completed'), icon: 'fas fa-circle-check', tone: 'green' }
          ]
        : [
            { label: 'Total Submitted', value: this.requests.length, icon: 'fas fa-file-lines', tone: 'blue' },
            { label: 'Pending', value: count('Pending'), icon: 'fas fa-clock', tone: 'amber' },
            { label: 'Active', value: count('Assigned', 'In Progress'), icon: 'fas fa-gears', tone: 'cyan' },
            { label: 'Completed', value: count('Completed'), icon: 'fas fa-circle-check', tone: 'green' }
          ]
    }
  },
  mounted() {
    if ((this.user.role || '').toLowerCase() === 'admin') {
      this.$router.replace('/admin/dashboard')
      return
    }
    this.loadDashboard()
  },
  methods: {
    async loadDashboard() {
      this.loading = true
      try {
        const response = this.isTechnician ? await getAssignedRequests() : await getMyRequests()
        this.requests = response.data
      } catch (error) {
        this.$refs.msgBox.show(error.response?.data?.message || 'Unable to load dashboard information.', 'error')
      } finally {
        this.loading = false
      }
    },
    requestPath(id) { return this.isTechnician ? `/assigned-tasks/${id}` : `/requests/${id}` },
    statusClass(status) { return status.toLowerCase().replaceAll(' ', '-') },
    formatDate(value) { return new Date(value).toLocaleDateString() }
  }
}
</script>

<style scoped>
.user-dashboard{display:flex;flex-direction:column;gap:24px}.dashboard-heading{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:30px;border-radius:18px;background:linear-gradient(135deg,#1e3a8a,#2457bd);color:#fff;box-shadow:0 12px 30px rgba(30,58,138,.18)}.eyebrow{margin:0 0 8px!important;color:#7dd3fc!important;font-size:.78rem!important;font-weight:700;text-transform:uppercase;letter-spacing:.1em}.dashboard-heading h1{margin:0 0 8px;font-size:2rem}.dashboard-heading p{margin:0;color:#dbeafe;line-height:1.6}.primary-action{flex-shrink:0;padding:12px 18px;border-radius:10px;background:#fff;color:#1e3a8a;text-decoration:none;font-weight:700}.overview-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.overview-card{display:flex;align-items:center;gap:16px;padding:22px;border-radius:16px;background:#fff;border:1px solid #e5ecf6;box-shadow:0 7px 20px rgba(15,23,42,.05)}.overview-icon{display:grid;place-items:center;width:48px;height:48px;border-radius:13px;font-size:1.15rem}.overview-card span{display:block;color:#64748b;font-size:.82rem;font-weight:600}.overview-card strong{display:block;margin-top:4px;color:#172554;font-size:1.8rem}.blue .overview-icon{background:#dbeafe;color:#1d4ed8}.amber .overview-icon{background:#fef3c7;color:#b45309}.cyan .overview-icon{background:#cffafe;color:#0e7490}.green .overview-icon{background:#dcfce7;color:#15803d}.recent-card{padding:26px;border-radius:16px;background:#fff;border:1px solid #e5ecf6;box-shadow:0 7px 20px rgba(15,23,42,.05)}.section-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}.section-heading h2{margin:0 0 5px;color:#172554;font-size:1.25rem}.section-heading p{margin:0;color:#64748b}.text-link{color:#1e3a8a;font-weight:700;text-decoration:none}.recent-list{display:grid}.recent-item{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:17px 4px;border-top:1px solid #e5ecf6;text-decoration:none}.request-copy span{color:#94a3b8;font-size:.75rem}.request-copy h3{margin:3px 0;color:#1e3a8a;font-size:1rem}.request-copy p{margin:0;color:#64748b;font-size:.85rem}.request-summary{display:flex;align-items:flex-end;flex-direction:column;gap:7px}.request-summary small{color:#94a3b8}.status-chip{white-space:nowrap}.empty-state{background:#fff}@media(max-width:1050px){.overview-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.dashboard-heading,.section-heading,.recent-item{align-items:flex-start;flex-direction:column}.overview-grid{grid-template-columns:1fr}.request-summary{align-items:flex-start}.primary-action{width:100%;box-sizing:border-box;text-align:center}}
</style>
