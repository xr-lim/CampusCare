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
          <article class="dashboard-card">
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

          <article class="dashboard-card">
            <div class="card-section-title">
              <h2>Update Status</h2>
            </div>

            <form class="stack-form" @submit.prevent="submitStatusUpdate">
              <div class="filter-field">
                <label for="statusUpdate">New Status</label>
                <select id="statusUpdate" v-model="statusForm.status" required>
                  <option value="" disabled>Select a status</option>
                  <option v-for="status in lookups.statuses" :key="status" :value="status">
                    {{ status }}
                  </option>
                </select>
              </div>

              <div class="filter-field">
                <label for="remarks">Admin Remarks</label>
                <textarea
                  id="remarks"
                  v-model.trim="statusForm.remarks"
                  rows="4"
                  maxlength="1000"
                  placeholder="Optional remarks for the status update"
                />
              </div>

              <button class="save-btn" type="submit" :disabled="updatingStatus">
                {{ updatingStatus ? 'Updating...' : 'Update Status' }}
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
  updateAdminRequestStatus
} from '../services/adminService'

export default {
  components: {
    MessageBox
  },
  data() {
    return {
      loading: true,
      assigning: false,
      updatingStatus: false,
      requestItem: null,
      history: [],
      lookups: {
        technicians: [],
        statuses: []
      },
      assignment: {
        technician_id: ''
      },
      statusForm: {
        status: '',
        remarks: ''
      }
    }
  },
  computed: {
    requestId() {
      return this.$route.params.id
    }
  },
  async mounted() {
    await this.loadPage()
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
    async submitStatusUpdate() {
      if (!this.statusForm.status) {
        this.$refs.msgBox.show('Please choose a status before submitting.', 'error')
        return
      }

      this.updatingStatus = true

      try {
        const res = await updateAdminRequestStatus(this.requestId, this.statusForm)
        this.$refs.msgBox.show(res.data.message || 'Request status updated successfully.', 'success')
        this.statusForm = {
          status: '',
          remarks: ''
        }
        await Promise.all([this.loadRequest(), this.loadHistory()])
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to update request status.'
        this.$refs.msgBox.show(message, 'error')
      } finally {
        this.updatingStatus = false
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