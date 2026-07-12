<template>
  <MessageBox ref="msgBox" />
  <section class="profile-card">
    <div class="profile-header page-heading">
      <div>
        <h2>Assigned Requests</h2>
        <p>Select a request to review its details and update its progress.</p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadRequests">Refresh</button>
    </div>

    <p v-if="loading" class="empty-state">Loading assigned requests...</p>
    <p v-else-if="requests.length === 0" class="empty-state">No maintenance requests are currently assigned to you.</p>

    <div v-else class="task-list">
      <article v-for="task in requests" :key="task.id" class="task-card">
        <div>
          <span class="request-number">Request #{{ task.id }}</span>
          <h3>{{ task.title }}</h3>
          <div class="task-meta">
            <span><strong>Priority:</strong> {{ task.priority }}</span>
            <span><strong>Category:</strong> {{ task.category_name }}</span>
            <span><strong>Location:</strong> {{ task.location_name }}</span>
            <span><strong>Requested by:</strong> {{ task.requester_name }}</span>
          </div>
        </div>
        <div class="task-actions">
          <span class="status-badge" :class="statusClass(task.status)">{{ task.status }}</span>
          <router-link :to="`/assigned-tasks/${task.id}`" class="view-btn">View</router-link>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getAssignedRequests } from '../services/requestService'

export default {
  components: { MessageBox },
  data: () => ({ requests: [], loading: false }),
  mounted() { this.loadRequests() },
  methods: {
    async loadRequests() {
      this.loading = true
      try {
        const response = await getAssignedRequests()
        this.requests = response.data
      } catch (error) {
        this.$refs.msgBox.show(error.response?.data?.message || 'Unable to load assigned requests.', 'error')
      } finally {
        this.loading = false
      }
    },
    statusClass(status) { return status.toLowerCase().replaceAll(' ', '-') }
  }
}
</script>

<style scoped>
.page-heading,.task-card,.task-actions,.task-meta{display:flex}.page-heading,.task-card{justify-content:space-between;gap:20px}.page-heading{align-items:center}.refresh-btn,.view-btn{border:0;border-radius:8px;background:#1e3a8a;color:#fff;padding:10px 16px;font:600 .9rem Poppins;cursor:pointer;text-decoration:none}.refresh-btn:disabled{opacity:.6;cursor:not-allowed}.task-list{display:grid;gap:18px}.task-card{align-items:center;border:1px solid #d6e0f5;border-radius:12px;padding:22px;background:#f8faff}.request-number{font-size:.78rem;color:#64748b}.task-card h3{margin:4px 0 0;color:#1e3a8a}.task-meta{flex-wrap:wrap;gap:8px 22px;margin-top:14px;color:#475569;font-size:.88rem}.task-actions{align-items:center;gap:12px}.status-badge{white-space:nowrap;border-radius:999px;padding:6px 12px;font-size:.8rem;font-weight:600;background:#dbeafe;color:#1e40af}.status-badge.in-progress{background:#fef3c7;color:#92400e}.status-badge.completed{background:#d1fae5;color:#065f46}.empty-state{color:#64748b}@media(max-width:700px){.page-heading,.task-card{align-items:flex-start;flex-direction:column}.task-actions{width:100%;justify-content:space-between}.task-meta{flex-direction:column}.profile-card{padding:22px}}
</style>
