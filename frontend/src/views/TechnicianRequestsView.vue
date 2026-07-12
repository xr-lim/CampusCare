<template>
  <MessageBox ref="msgBox" />

  <section class="profile-card">
    <div class="profile-header page-heading">
      <div>
        <h2>Assigned Requests</h2>
        <p>Review your assigned maintenance work and record its progress.</p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadRequests">Refresh</button>
    </div>

    <p v-if="loading" class="empty-state">Loading assigned requests...</p>
    <p v-else-if="requests.length === 0" class="empty-state">No maintenance requests are currently assigned to you.</p>

    <div v-else class="task-list">
      <article v-for="task in requests" :key="task.id" class="task-card">
        <div class="task-heading">
          <div>
            <span class="request-number">Request #{{ task.id }}</span>
            <h3>{{ task.title }}</h3>
          </div>
          <span class="status-badge" :class="statusClass(task.status)">{{ task.status }}</span>
        </div>

        <div class="task-meta">
          <span><strong>Priority:</strong> {{ task.priority }}</span>
          <span><strong>Category:</strong> {{ task.category_name }}</span>
          <span><strong>Location:</strong> {{ task.location_name }}</span>
          <span><strong>Requested by:</strong> {{ task.requester_name }}</span>
        </div>

        <p class="description">{{ task.description }}</p>
        <div v-if="task.images?.length" class="attachment-gallery">
          <a v-for="image in task.images" :key="image.id" :href="image.url" target="_blank" rel="noopener">
            <img :src="image.url" :alt="image.original_filename">
          </a>
        </div>
        <p v-if="task.latest_notes" class="latest-note"><strong>Latest update:</strong> {{ task.latest_notes }}</p>

        <form v-if="nextStatus(task.status)" class="update-form" @submit.prevent="submitUpdate(task)">
          <label :for="`notes-${task.id}`">Work notes</label>
          <textarea
            :id="`notes-${task.id}`"
            v-model.trim="notes[task.id]"
            maxlength="1000"
            rows="3"
            :placeholder="task.status === 'Assigned' ? 'Describe how you are starting this work...' : 'Describe the completed work...'"
            required
          />
          <button class="update-btn" :disabled="updatingId === task.id">
            {{ updatingId === task.id ? 'Updating...' : `Mark as ${nextStatus(task.status)}` }}
          </button>
        </form>
        <p v-else class="completed-message">This request has been completed.</p>
      </article>
    </div>
  </section>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getAssignedRequests, getRequestImage, updateAssignedRequest } from '../services/requestService'

export default {
  components: { MessageBox },
  data() {
    return { requests: [], notes: {}, loading: false, updatingId: null }
  },
  mounted() { this.loadRequests() },
  beforeUnmount() { this.revokeImageUrls() },
  methods: {
    async loadRequests() {
      this.loading = true
      try {
        const response = await getAssignedRequests()
        this.revokeImageUrls()
        this.requests = response.data
        await Promise.all(this.requests.flatMap(task => (task.images || []).map(async image => {
          const imageResponse = await getRequestImage(image.id)
          image.url = URL.createObjectURL(imageResponse.data)
        })))
      } catch (error) {
        this.$refs.msgBox.show(error.response?.data?.message || 'Unable to load assigned requests.', 'error')
      } finally {
        this.loading = false
      }
    },
    nextStatus(status) {
      return status === 'Assigned' ? 'In Progress' : status === 'In Progress' ? 'Completed' : null
    },
    statusClass(status) { return status.toLowerCase().replaceAll(' ', '-') },
    revokeImageUrls() {
      this.requests.flatMap(task => task.images || []).forEach(image => {
        if (image.url) URL.revokeObjectURL(image.url)
      })
    },
    async submitUpdate(task) {
      const status = this.nextStatus(task.status)
      if (!status || !this.notes[task.id]) return
      this.updatingId = task.id
      try {
        const response = await updateAssignedRequest(task.id, { status, notes: this.notes[task.id] })
        this.notes[task.id] = ''
        this.$refs.msgBox.show(response.data?.message || 'Request updated successfully.', 'success')
        await this.loadRequests()
      } catch (error) {
        this.$refs.msgBox.show(error.response?.data?.message || 'Unable to update request.', 'error')
      } finally {
        this.updatingId = null
      }
    }
  }
}
</script>

<style scoped>
.page-heading,.task-heading,.task-meta,.update-form{display:flex}.page-heading,.task-heading{justify-content:space-between;gap:20px}.page-heading{align-items:center}.refresh-btn,.update-btn{border:0;border-radius:8px;background:#1e3a8a;color:#fff;padding:10px 16px;font:600 .9rem Poppins;cursor:pointer}.refresh-btn:disabled,.update-btn:disabled{opacity:.6;cursor:not-allowed}.task-list{display:grid;gap:18px}.task-card{border:1px solid #d6e0f5;border-radius:12px;padding:22px;background:#f8faff}.request-number{font-size:.78rem;color:#64748b}.task-card h3{margin:4px 0 0;color:#1e3a8a}.status-badge{height:max-content;border-radius:999px;padding:6px 12px;font-size:.8rem;font-weight:600;background:#dbeafe;color:#1e40af}.status-badge.in-progress{background:#fef3c7;color:#92400e}.status-badge.completed{background:#d1fae5;color:#065f46}.task-meta{flex-wrap:wrap;gap:8px 22px;margin:18px 0;color:#475569;font-size:.88rem}.description{color:#475569;line-height:1.65}.latest-note{padding:12px;background:#fff;border-left:3px solid #38bdf8;color:#475569}.update-form{flex-direction:column;gap:9px;margin-top:18px}.update-form label{font-weight:600;color:#1e3a8a}.update-form textarea{resize:vertical;border:1.5px solid #d6e0f5;border-radius:8px;padding:12px;font:inherit}.update-form textarea:focus{outline:none;border-color:#1e3a8a}.update-btn{align-self:flex-start}.completed-message{margin:18px 0 0;color:#047857;font-weight:600}.empty-state{color:#64748b}@media(max-width:700px){.page-heading,.task-heading{align-items:flex-start}.task-meta{flex-direction:column}.profile-card{padding:22px}}
.attachment-gallery{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,190px));gap:12px;margin:16px 0}.attachment-gallery img{display:block;width:100%;height:130px;object-fit:cover;border-radius:9px;border:1px solid #d6e0f5}
</style>
