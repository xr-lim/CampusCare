<template>
    <MessageBox ref="msgBox" />

    <div class="profile-card">
        <div class="profile-header">
            <h2>My Requests</h2>
            <p>View and track your submitted maintenance requests.</p>
        </div>

        <p v-if="loading">Loading requests...</p>

        <p v-else-if="requests.length === 0">
            You have not submitted any maintenance requests yet.
        </p>

        <div v-else class="request-list">
            <div v-for="request in requests" :key="request.id" class="request-item">
                <div>
                    <h3>{{ request.title }}</h3>
                    <p>{{ request.category_name }} - {{ request.location_name }}</p>>
                    <p>
                        Priority: {{ request.priority }} |
                        Status: {{ request.status }} |
                        Created: {{ formatDate(request.created_at) }}
                    </p>
                </div>

                <div class="request-actions">
                    <router-link :to="`/requests/${request.id}`" class="small-btn">
                        View
                    </router-link>

                    <router-link
                        v-if="request.status === 'Pending'"
                        :to="`/requests/${request.id}/edit`"
                        class="small-btn"
                    >
                        Edit
                    </router-link>

                    <button
                        v-if="request.status === 'Pending'"
                        class="cancel-btn"
                        @click="cancelSelectedRequest(request.id)"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getMyRequests, cancelRequest } from '../services/requestService'

export default {
    components: {
        MessageBox
    },

    data() {
        return {
            loading: false,
            requests: []
        }
    },

    async mounted() {
        await this.loadRequests()
    },

    methods: {
        async loadRequests() {
            this.loading = true

            try {
                const res = await getMyRequests()
                this.requests = res.data
            } catch (error) {
                console.error(error)
                this.$refs.msgBox.show('Unable to load requests.', 'error')
            } finally {
                this.loading = false
            }
        },

        async cancelSelectedRequest(id) {
            if (!confirm('Are you sure you want to cancel this request?')) {
                return
            }

            try {
                const res = await cancelRequest(id)

                this.$refs.msgBox.show(
                    res.data?.message || 'Request cancelled successfully.',
                    'success'
                )

                await this.loadRequests()
            } catch (error) {
                console.error(error)

                const message =
                    error.response?.data?.message ||
                    'Unable to cancel request.'

                this.$refs.msgBox.show(message, 'error')
            }
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString()
        }
    }
}
</script>

<style scoped>
.request-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.request-item {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 18px;
    border: 1px solid #d6e0f5;
    border-radius: 10px;
    background: #f8faff;
}

.request-item h3 {
    margin: 0 0 8px;
    color: #1E3A8A;
}

.request-item p {
    margin: 4px 0;
    color: #555;
}

.request-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.small-btn,
.cancel-btn {
    padding: 9px 14px;
    border-radius: 8px;
    border: none;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
    font-size: .9rem;
    cursor: pointer;
}

.small-btn {
    background: #1E3A8A;
    color: white;
}

.cancel-btn {
    background: #d32f2f;
    color: white;
}
</style>