<template>
    <MessageBox ref="msgBox" />

    <div class="profile-card">
        <div class="profile-header">
            <h2>Request Details</h2>
            <p>View the full information for this maintenance request.</p>
        </div>

        <p v-if="loading">Loading request details...</p>

        <div v-else-if="requestData">
            <div class="detail-row">
                <strong>Title:</strong>
                <span>{{ requestData.title }}</span>
            </div>

            <div class="detail-row">
                <strong>Category:</strong>
                <span>{{ requestData.category_name }}</span>
            </div>

            <div class="detail-row">
                <strong>Location:</strong>
                <span>{{ requestData.location_name }}</span>
            </div>

            <div class="detail-row">
                <strong>Priority:</strong>
                <span>{{ requestData.priority }}</span>
            </div>

            <div class="detail-row">
                <strong>Status:</strong>
                <span class="status-badge">{{ requestData.status }}</span>
            </div>

            <div class="detail-row">
                <strong>Assigned Technician:</strong>
                <span>{{ requestData.technician_name || 'Not assigned yet' }}</span>
            </div>

            <div class="detail-row">
                <strong>Created:</strong>
                <span>{{ formatDate(requestData.created_at) }}</span>
            </div>

            <div class="detail-row">
                <strong>Description:</strong>
                <p>{{ requestData.description }}</p>
            </div>

            <div class="profile-actions">
                <router-link to="/my-requests" class="small-btn">
                    Back
                </router-link>

                <router-link
                    v-if="requestData.status === 'Pending'"
                    :to="`/requests/${requestData.id}/edit`"
                    class="small-btn"
                >
                    Edit
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getRequestById } from '../services/requestService'

export default {
    components: {
        MessageBox
    },

    data() {
        return {
            loading: false,
            requestData: null
        }
    },

    async mounted() {
        await this.loadRequest()
    },

    methods: {
        async loadRequest() {
            this.loading = true

            try {
                const id = this.$route.params.id
                const res = await getRequestById(id)

                this.requestData = res.data
            } catch (error) {
                console.error(error)

                const message =
                    error.response?.data?.message ||
                    'Unable to load request details.'

                this.$refs.msgBox.show(message, 'error')
            } finally {
                this.loading = false
            }
        },

        formatDate(date) {
            return new Date(date).toLocaleString()
        }
    }
}
</script>

<style scoped>
.detail-row {
    margin-bottom: 18px;
}

.detail-row strong {
    display: block;
    color: #1E3A8A;
    margin-bottom: 6px;
}

.detail-row span,
.detail-row p {
    color: #555;
    margin: 0;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 8px;
    background: #e8f0fe;
    color: #1E3A8A;
    font-weight: 600;
}

.small-btn {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 8px;
    background: #1E3A8A;
    color: white;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
    font-size: .9rem;
    margin-right: 10px;
}
</style>