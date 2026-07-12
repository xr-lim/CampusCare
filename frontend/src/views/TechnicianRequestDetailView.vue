<template>
  <MessageBox ref="msgBox" />
  <section class="profile-card">
    <div class="profile-header page-heading">
      <div>
        <span class="request-number">Request #{{ requestItem?.id }}</span>
        <h2>{{ requestItem?.title || 'Assigned Request' }}</h2>
      </div>
      <router-link to="/assigned-tasks" class="back-btn">Back to List</router-link>
    </div>

    <p v-if="loading" class="empty-state">Loading request details...</p>
    <div v-else-if="requestItem" class="detail-layout">
      <div class="detail-main">
        <div class="detail-grid">
          <div><strong>Status</strong><span class="status-badge" :class="statusClass(requestItem.status)">{{ requestItem.status }}</span></div>
          <div><strong>Priority</strong><span>{{ requestItem.priority }}</span></div>
          <div><strong>Category</strong><span>{{ requestItem.category_name }}</span></div>
          <div><strong>Location</strong><span>{{ requestItem.location_name }}</span></div>
          <div><strong>Requested by</strong><span>{{ requestItem.requester_name }} · {{ requestItem.requester_email }}</span></div>
          <div><strong>Submitted</strong><span>{{ formatDate(requestItem.created_at) }}</span></div>
        </div>

        <div class="description-block"><strong>Description</strong><p>{{ requestItem.description }}</p></div>

        <div v-if="requestItem.images?.length" class="description-block">
          <strong>Attached Images</strong>
          <div class="attachment-gallery">
            <a v-for="image in requestItem.images" :key="image.id" :href="image.url" target="_blank" rel="noopener">
              <img :src="image.url" :alt="image.original_filename">
            </a>
          </div>
        </div>

        <div class="description-block">
          <strong>Status History</strong>
          <div class="history-list">
            <div v-for="item in requestItem.history" :key="`${item.status}-${item.updated_at}`" class="history-item">
              <b>{{ item.status }}</b><span>{{ formatDate(item.updated_at) }} · {{ item.updated_by_name }}</span>
              <p v-if="item.update_notes">{{ item.update_notes }}</p>
            </div>
          </div>
        </div>
      </div>

      <aside class="update-panel">
        <h3>Update Progress</h3>
        <form v-if="nextStatus" @submit.prevent="submitUpdate">
          <label for="workNotes">Work notes</label>
          <textarea id="workNotes" v-model.trim="notes" maxlength="1000" rows="5" required />
          <button class="update-btn" :disabled="updating">
            {{ updating ? 'Updating...' : `Mark as ${nextStatus}` }}
          </button>
        </form>
        <p v-else class="completed-message">This request has been completed.</p>
      </aside>
    </div>
  </section>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getAssignedRequestById, getRequestImage, updateAssignedRequest } from '../services/requestService'

export default {
  components: { MessageBox },
  data: () => ({ requestItem: null, notes: '', loading: false, updating: false }),
  computed: {
    nextStatus() {
      return this.requestItem?.status === 'Assigned' ? 'In Progress' : this.requestItem?.status === 'In Progress' ? 'Completed' : null
    }
  },
  mounted() { this.loadRequest() },
  beforeUnmount() { this.revokeImageUrls() },
  methods: {
    async loadRequest() {
      this.loading = true
      try {
        this.revokeImageUrls()
        const response = await getAssignedRequestById(this.$route.params.id)
        this.requestItem = response.data
        await Promise.all((this.requestItem.images || []).map(async image => {
          const imageResponse = await getRequestImage(image.id)
          image.url = URL.createObjectURL(imageResponse.data)
        }))
      } catch (error) {
        this.$refs.msgBox.show(error.response?.data?.message || 'Unable to load assigned request.', 'error')
      } finally {
        this.loading = false
      }
    },
    async submitUpdate() {
      if (!this.nextStatus || !this.notes) return
      this.updating = true
      try {
        const response = await updateAssignedRequest(this.requestItem.id, { status: this.nextStatus, notes: this.notes })
        this.notes = ''
        this.$refs.msgBox.show(response.data?.message || 'Request updated successfully.', 'success')
        await this.loadRequest()
      } catch (error) {
        this.$refs.msgBox.show(error.response?.data?.message || 'Unable to update request.', 'error')
      } finally {
        this.updating = false
      }
    },
    revokeImageUrls() { (this.requestItem?.images || []).forEach(image => image.url && URL.revokeObjectURL(image.url)) },
    statusClass(status) { return status.toLowerCase().replaceAll(' ', '-') },
    formatDate(value) { return new Date(value).toLocaleString() }
  }
}
</script>

<style scoped>
.page-heading{display:flex;justify-content:space-between;align-items:center;gap:20px}.request-number{font-size:.8rem;color:#64748b}.back-btn,.update-btn{display:inline-block;border:0;border-radius:8px;background:#1e3a8a;color:#fff;padding:10px 16px;font:600 .9rem Poppins;text-decoration:none;cursor:pointer}.detail-layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:24px}.detail-main{min-width:0}.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.detail-grid>div,.description-block{display:flex;flex-direction:column;gap:7px}.detail-grid strong,.description-block>strong{color:#1e3a8a}.detail-grid span,.description-block p{color:#475569;margin:0;line-height:1.65}.description-block{margin-top:25px}.status-badge{width:max-content;border-radius:999px;padding:6px 12px;font-size:.8rem;font-weight:600;background:#dbeafe;color:#1e40af}.status-badge.in-progress{background:#fef3c7;color:#92400e}.status-badge.completed{background:#d1fae5;color:#065f46}.attachment-gallery{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,210px));gap:12px}.attachment-gallery img{display:block;width:100%;height:150px;object-fit:cover;border-radius:9px;border:1px solid #d6e0f5}.history-list{display:grid;gap:10px}.history-item{padding:13px;border:1px solid #d6e0f5;border-radius:9px;background:#f8faff}.history-item span{display:block;color:#64748b;font-size:.82rem;margin-top:3px}.history-item p{margin-top:7px}.update-panel{height:max-content;padding:22px;border:1px solid #d6e0f5;border-radius:12px;background:#f8faff}.update-panel h3{margin-top:0;color:#1e3a8a}.update-panel form{display:flex;flex-direction:column;gap:10px}.update-panel label{font-weight:600;color:#1e3a8a}.update-panel textarea{resize:vertical;border:1.5px solid #d6e0f5;border-radius:8px;padding:12px;font:inherit}.update-btn:disabled{opacity:.6}.completed-message{color:#047857;font-weight:600}.empty-state{color:#64748b}@media(max-width:850px){.detail-layout{grid-template-columns:1fr}}@media(max-width:600px){.page-heading{align-items:flex-start;flex-direction:column}.detail-grid{grid-template-columns:1fr}}
</style>
