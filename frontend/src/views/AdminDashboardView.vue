<template>
  <div class="admin-page">
    <MessageBox ref="msgBox" />

    <section class="page-header">
      <div>
        <h1>Admin Dashboard</h1>
        <p>Track maintenance activity, workload, and the latest requests in one place.</p>
      </div>
      <router-link to="/admin/requests" class="secondary-action">
        Manage Requests
      </router-link>
    </section>

    <div v-if="loading" class="dashboard-card">
      <p>Loading dashboard summary...</p>
    </div>

    <template v-else>
      <section class="stats-grid">
        <article class="stat-card">
          <span class="stat-label">Total Requests</span>
          <strong class="stat-value">{{ summary.total }}</strong>
        </article>
        <article class="stat-card">
          <span class="stat-label">Pending</span>
          <strong class="stat-value">{{ summary.pending }}</strong>
        </article>
        <article class="stat-card">
          <span class="stat-label">Assigned</span>
          <strong class="stat-value">{{ summary.assigned }}</strong>
        </article>
        <article class="stat-card">
          <span class="stat-label">In Progress</span>
          <strong class="stat-value">{{ summary.inProgress }}</strong>
        </article>
        <article class="stat-card">
          <span class="stat-label">Completed</span>
          <strong class="stat-value">{{ summary.completed }}</strong>
        </article>
        <article class="stat-card">
          <span class="stat-label">Closed</span>
          <strong class="stat-value">{{ summary.cancelled + summary.rejected }}</strong>
        </article>
      </section>

      <section class="dashboard-card">
        <div class="card-section-title">
          <h2>Recent Requests</h2>
          <span>{{ recentRequests.length }} shown</span>
        </div>

        <div v-if="recentRequests.length === 0" class="empty-state">
          No maintenance requests are available yet.
        </div>

        <div v-else class="request-list-stack">
          <article v-for="item in recentRequests" :key="item.id" class="request-preview-card">
            <div class="request-preview-copy">
              <div class="request-preview-meta">
                <span class="status-chip" :class="statusClass(item.status)">{{ item.status }}</span>
                <span class="urgency-chip" :class="urgencyClass(item.urgency)">{{ item.urgency }}</span>
              </div>
              <h3>Request #{{ item.id }}</h3>
              <p>{{ item.description }}</p>
              <small>
                {{ item.requester_name }} • {{ item.category_name }} • {{ item.location_name }}
              </small>
            </div>

            <router-link :to="`/admin/requests/${item.id}`" class="inline-link">
              View Details
            </router-link>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getAdminDashboard } from '../services/adminService'

export default {
  components: {
    MessageBox
  },
  data() {
    return {
      loading: true,
      summary: {
        total: 0,
        pending: 0,
        assigned: 0,
        inProgress: 0,
        completed: 0,
        cancelled: 0,
        rejected: 0
      },
      recentRequests: []
    }
  },
  async mounted() {
    await this.loadDashboard()
  },
  methods: {
    async loadDashboard() {
      this.loading = true

      try {
        const res = await getAdminDashboard()
        this.summary = res.data.summary
        this.recentRequests = res.data.recentRequests
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to load the admin dashboard.'
        this.$refs.msgBox.show(message, 'error')
      } finally {
        this.loading = false
      }
    },
    statusClass(status) {
      return status.toLowerCase().replace(/\s+/g, '-')
    },
    urgencyClass(urgency) {
      return urgency.toLowerCase()
    }
  }
}
</script>
