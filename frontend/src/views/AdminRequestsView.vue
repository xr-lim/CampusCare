<template>
  <div class="admin-page admin-page--requests">
    <MessageBox ref="msgBox" />

    <section class="page-header">
      <div>
        <h1>Manage Requests</h1>
        <p>Filter and review all maintenance requests before assignment or status updates.</p>
      </div>
      <router-link to="/admin/dashboard" class="secondary-action">
        Back to Dashboard
      </router-link>
    </section>

    <section class="dashboard-card">
      <div class="card-section-title">
        <h2>Filters</h2>
      </div>

      <form class="filters-grid" @submit.prevent="applyFilters">
        <div class="filter-field filter-field--wide">
          <label for="search">Search</label>
          <input id="search" v-model.trim="filters.search" type="text" placeholder="Requester, email, or description" />
        </div>

        <div class="filter-field">
          <label for="status">Status</label>
          <select id="status" v-model="filters.status">
            <option value="">All statuses</option>
            <option v-for="status in lookups.statuses" :key="status" :value="status">
              {{ status }}
            </option>
          </select>
        </div>

        <div class="filter-field">
          <label for="urgency">Urgency</label>
          <select id="urgency" v-model="filters.urgency">
            <option value="">All urgencies</option>
            <option v-for="urgency in lookups.urgencies" :key="urgency" :value="urgency">
              {{ urgency }}
            </option>
          </select>
        </div>

        <div class="filter-field">
          <label for="category">Category</label>
          <select id="category" v-model="filters.category_id">
            <option value="">All categories</option>
            <option v-for="category in lookups.categories" :key="category.id" :value="String(category.id)">
              {{ category.name }}
            </option>
          </select>
        </div>

        <div class="filter-field">
          <label for="location">Location</label>
          <select id="location" v-model="filters.location_id">
            <option value="">All locations</option>
            <option v-for="location in lookups.locations" :key="location.id" :value="String(location.id)">
              {{ location.name }}
            </option>
          </select>
        </div>

        <div class="filter-field">
          <label for="technician">Technician</label>
          <select id="technician" v-model="filters.technician_id">
            <option value="">All technicians</option>
            <option v-for="technician in lookups.technicians" :key="technician.id" :value="String(technician.id)">
              {{ technician.username }}
            </option>
          </select>
        </div>

        <div class="filter-actions filter-actions--full">
          <button class="save-btn" type="submit" :disabled="loading">
            Apply Filters
          </button>
          <button class="ghost-btn" type="button" @click="resetFilters" :disabled="loading">
            Reset
          </button>
        </div>
      </form>
    </section>

    <section class="dashboard-card">
      <div class="card-section-title">
        <h2>Maintenance Requests</h2>
        <span>{{ requests.length }} result{{ requests.length === 1 ? '' : 's' }}</span>
      </div>

      <div v-if="loading" class="empty-state">
        Loading maintenance requests...
      </div>

      <div v-else-if="requests.length === 0" class="empty-state">
        No requests matched the current filters.
      </div>

      <div v-else class="table-wrap">
        <table class="admin-table admin-table--requests">
          <thead>
            <tr>
              <th>ID</th>
              <th>Requester</th>
              <th>Category</th>
              <th>Location</th>
              <th>Urgency</th>
              <th>Status</th>
              <th>Technician</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in requests" :key="item.id" class="clickable-row" @click="openRequest(item.id)">
              <td class="col-id">#{{ item.id }}</td>
              <td>
                <strong>{{ item.requester_name }}</strong>
                <span class="table-subcopy">{{ item.requester_email }}</span>
              </td>
              <td class="col-category">{{ item.category_name }}</td>
              <td class="col-location">{{ item.location_name }}</td>
              <td>
                <span class="urgency-chip" :class="urgencyClass(item.urgency)">{{ item.urgency }}</span>
              </td>
              <td>
                <span class="status-chip" :class="statusClass(item.status)">{{ item.status }}</span>
              </td>
              <td class="col-technician">{{ item.technician_name || 'Unassigned' }}</td>
              <td>
                <button class="inline-link inline-link--button" @click="openRequest(item.id)">
                  Open
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getAdminLookups, getAdminRequests } from '../services/adminService'

const initialFilters = () => ({
  search: '',
  status: '',
  urgency: '',
  category_id: '',
  location_id: '',
  technician_id: ''
})

export default {
  components: {
    MessageBox
  },
  data() {
    return {
      loading: true,
      requests: [],
      filters: initialFilters(),
      lookups: {
        categories: [],
        locations: [],
        technicians: [],
        statuses: [],
        urgencies: []
      }
    }
  },
  async mounted() {
    await Promise.all([this.loadLookups(), this.loadRequests()])
  },
  methods: {
    async loadLookups() {
      try {
        const res = await getAdminLookups()
        this.lookups = res.data
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to load filter options.'
        this.$refs.msgBox.show(message, 'error')
      }
    },
    async loadRequests() {
      this.loading = true

      try {
        const params = Object.fromEntries(
          Object.entries(this.filters).filter(([, value]) => value !== '')
        )

        const res = await getAdminRequests(params)
        this.requests = res.data.requests
      } catch (error) {
        const message = error.response?.data?.message || 'Unable to load maintenance requests.'
        this.$refs.msgBox.show(message, 'error')
      } finally {
        this.loading = false
      }
    },
    async applyFilters() {
      await this.loadRequests()
    },
    async resetFilters() {
      this.filters = initialFilters()
      await this.loadRequests()
    },
    openRequest(id) {
      this.$router.push(`/admin/requests/${id}`)
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

