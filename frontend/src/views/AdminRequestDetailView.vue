<template>
  <div class="admin-page">
    <MessageBox ref="msgBox" />

    <section class="page-header">
      <div>
        <h1>Request #{{ requestId }}</h1>
        <p>Review request details, assign a technician, and record status changes.</p>
      </div>
      <router-link to="/admin/requests" class="secondary-action">
        Back to Requests
      </router-link>
    </section>

    <div v-if="loading" class="dashboard-card">
      <p>Loading request details...</p>
    </div>

    <template v-else-if="requestItem">
      <section class="detail-layout">
        <div class="detail-main-column">
          <article class="dashboard-card">
            <div class="card-section-title">
              <h2>Request Information</h2>
              <span class="status-chip" :class="statusClass(requestItem.status)">
                {{ requestItem.status }}
              </span>
            </div>

            <div class="detail-grid">
              <div class="detail-field detail-field--full">
                <label>Description</label>
                <p>{{ requestItem.description }}</p>
              </div>

              <div v-if="requestItem.images?.length" class="detail-field detail-field--full">
                <label>Attached Images</label>
                <div class="attachment-gallery">
                  <a v-for="image in requestItem.images" :key="image.id" :href="image.url" target="_blank" rel="noopener">
                    <img :src="image.url" :alt="image.original_filename">
                  </a>
                </div>
              </div>

              <div class="detail-field">
                <label>Requester</label>
                <p>{{ requestItem.requester_name }}</p>
                <small>{{ requestItem.requester_email }}</small>
              </div>

              <div class="detail-field">
                <label>Assigned Technician</label>
                <p>{{ requestItem.technician_name || 'Unassigned' }}</p>
                <small v-if="requestItem.technician_email">{{ requestItem.technician_email }}</small>
              </div>

              <div class="detail-field">
                <label>Category</label>
                <p>{{ requestItem.category_name }}</p>
              </div>

              <div class="detail-field">
                <label>Location</label>
                <p>{{ requestItem.location_name }}</p>
              </div>

              <div class="detail-field">
                <label>Urgency</label>
                <p>
                  <span class="urgency-chip" :class="urgencyClass(requestItem.urgency)">
                    {{ requestItem.urgency }}
                  </span>
                </p>
              </div>

              <div class="detail-field">
                <label>Created</label>
                <p>{{ formatDate(requestItem.created_at) }}</p>
              </div>

              <div class="detail-field">
                <label>Last Updated</label>
                <p>{{ formatDate(requestItem.updated_at) }}</p>
              </div>
            </div>
          </article>

          <article class="dashboard-card">
            <div class="card-section-title">
              <h2>Status History</h2>
              <span>{{ history.length }} update{{ history.length === 1 ? '' : 's' }}</span>
            </div>

            <div v-if="history.length === 0" class="empty-state">
              No status history has been recorded yet.
            </div>

            <div v-else class="history-list">
              <article v-for="item in history" :key="item.id" class="history-card">
                <div class="history-topline">
                  <strong>{{ item.old_status }} → {{ item.new_status }}</strong>
                  <span>{{ formatDate(item.created_at) }}</span>
                </div>
                <p class="history-meta">
                  Updated by {{ item.updated_by_name }} ({{ item.updated_by_role }})
                </p>
                <p v-if="item.remarks" class="history-remarks">{{ item.remarks }}</p>
              </article>
            </div>
          </article>
        </div>

        <aside class="detail-side-column">
          <article v-if="canAssign" class="dashboard-card">
            <div class="card-section-title">
              <h2>Assign Technician</h2>
            </div>

            <form class="stack-form" @submit.prevent="submitAssignment">
              <div class="filter-field">
                <label for="technicianAssign">Technician</label>
                <select id="technicianAssign" v-model="assignment.technician_id" required>
                  <option value="" disabled>Select a technician</option>
                  <option v-for="technician in lookups.technicians" :key="technician.id" :value="String(technician.id)">
                    {{ technician.username }} ({{ technician.email }})
                  </option>
                </select>
              </div>

              <button class="save-btn" type="submit" :disabled="assigning">
                {{ assigning ? 'Assigning...' : 'Save Technician' }}
              </button>
            </form>
          </article>

          <article v-if="canReject" class="dashboard-card rejection-card">
            <div class="card-section-title">
              <h2>Reject Request</h2>
            </div>
            <p class="rejection-help">Reject this request only when there is a clear and valid reason. The reason will be visible in its status history.</p>
            <form class="stack-form" @submit.prevent="submitRejection">
              <div class="filter-field">
                <label for="rejectionReason">Reason for rejection</label>
                <textarea
                  id="rejectionReason"
                  v-model.trim="rejection.reason"
                  rows="5"
                  minlength="10"
                  maxlength="1000"
                  placeholder="Explain why this request cannot be accepted..."
                  required
                />
                <small>{{ rejection.reason.length }}/1000 characters</small>
              </div>
              <button class="reject-btn" type="submit" :disabled="rejecting">
                {{ rejecting ? 'Rejecting...' : 'Reject Request' }}
              </button>
            </form>
          </article>

        </aside>
      </section>
    </template>

    <section v-else class="dashboard-card">
      <div class="empty-state">
        The requested maintenance record could not be loaded.
      </div>
    </section>
  </div>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import {
  assignTechnician,
  getAdminLookups,
  getAdminRequestById,
  getAdminRequestHistory,
  rejectAdminRequest
} from '../services/adminService'
import { getRequestImage } from '../services/requestService'

export default {
  components: {
    MessageBox
  },
  data() {
    return {
      loading: true,
      assigning: false,
      rejecting: false,
      requestItem: null,
      history: [],
      lookups: {
        technicians: [],
        statuses: []
      },
      assignment: {
        technician_id: ''
      },
      rejection: {
        reason: ''
      }
    }
  },
  computed: {
    requestId() {
      return this.$route.params.id
    },
    canReject() {
      return ['Pending', 'Assigned'].includes(this.requestItem?.status)
    },
    canAssign() {
      return ['Pending', 'Assigned'].includes(this.requestItem?.status)
    }
  },
  async mounted() {
    await this.loadPage()
  },
  beforeUnmount() {
    this.revokeImageUrls()
  },
  methods: {
    async loadPage() {
      this.loading = true

      try {
        await Promise.all([
          this.loadLookups(),
          this.loadRequest(),
          this.loadHistory()
        ])
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to load the admin request page.'
        this.$refs.msgBox.show(message, 'error')
      } finally {
        this.loading = false
      }
    },
    async loadLookups() {
      try {
        const res = await getAdminLookups()
        this.lookups = {
          technicians: res.data.technicians,
          statuses: res.data.statuses
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to load admin lookup data.'
        this.$refs.msgBox.show(message, 'error')
        throw error
      }
    },
    async loadRequest() {
      try {
        const res = await getAdminRequestById(this.requestId)
        this.requestItem = res.data.request
        await Promise.all((this.requestItem.images || []).map(async image => {
          const imageResponse = await getRequestImage(image.id)
          image.url = URL.createObjectURL(imageResponse.data)
        }))
        this.assignment.technician_id = this.requestItem.technician_id
          ? String(this.requestItem.technician_id)
          : ''
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to load the request details.'
        this.$refs.msgBox.show(message, 'error')
      }
    },
    async loadHistory() {
      try {
        const res = await getAdminRequestHistory(this.requestId)
        this.history = res.data.history
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to load request history.'
        this.$refs.msgBox.show(message, 'error')
      }
    },
    revokeImageUrls() {
      ;(this.requestItem?.images || []).forEach(image => {
        if (image.url) URL.revokeObjectURL(image.url)
      })
    },
    async submitAssignment() {
      if (!this.assignment.technician_id) {
        this.$refs.msgBox.show('Please select a technician first.', 'error')
        return
      }

      this.assigning = true

      try {
        const res = await assignTechnician(this.requestId, Number(this.assignment.technician_id))
        this.$refs.msgBox.show(res.data.message || 'Technician assigned successfully.', 'success')
        await this.loadRequest()
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to assign technician.'
        this.$refs.msgBox.show(message, 'error')
      } finally {
        this.assigning = false
      }
    },
    async submitRejection() {
      if (this.rejection.reason.length < 10) {
        this.$refs.msgBox.show('Please provide a clear rejection reason of at least 10 characters.', 'error')
        return
      }
      if (!window.confirm('Reject this maintenance request? This action will remove any technician assignment.')) return

      this.rejecting = true
      try {
        const res = await rejectAdminRequest(this.requestId, this.rejection.reason)
        this.rejection.reason = ''
        this.$refs.msgBox.show(res.data.message || 'Maintenance request rejected successfully.', 'success')
        await Promise.all([this.loadRequest(), this.loadHistory()])
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to reject maintenance request.'
        this.$refs.msgBox.show(message, 'error')
      } finally {
        this.rejecting = false
      }
    },
    statusClass(status) {
      return status.toLowerCase().replace(/\s+/g, '-')
    },
    urgencyClass(urgency) {
      return urgency.toLowerCase()
    },
    formatDate(value) {
      return new Date(value).toLocaleString()
    }
  }
}
</script>
