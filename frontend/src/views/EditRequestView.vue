<template>
    <MessageBox ref="msgBox" />

    <div class="profile-card">
        <div class="profile-header">
            <h2>Edit Request</h2>
            <p>Update your maintenance request while it is still pending.</p>
        </div>

        <p v-if="loading">Loading request...</p>

        <form v-else @submit.prevent="submitUpdate">
            <p v-if="form.status && form.status !== 'Pending'" class="warning-text">
                This request can no longer be edited because it is not pending.
            </p>

            <div class="profile-field">
                <label>Title</label>
                <input v-model="form.title" type="text" maxlength="150" :disabled="form.status !== 'Pending'" required>
            </div>

            <div class="profile-field">
                <label>Category</label>
                <select v-model="form.category_id" :disabled="form.status !== 'Pending'" required>
                    <option value="">Select category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <div class="profile-field">
                <label>Location</label>
                <select v-model="form.location_id" :disabled="form.status !== 'Pending'" required>
                    <option value="">Select location</option>
                    <option v-for="location in locations" :key="location.id" :value="location.id">
                        {{ location.name }}
                    </option>
                </select>
            </div>

            <div class="profile-field">
                <label>Priority</label>
                <select v-model="form.priority" :disabled="form.status !== 'Pending'" required>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <div class="profile-field">
                <label>Description</label>
                <textarea v-model="form.description" maxlength="1000" :disabled="form.status !== 'Pending'" required></textarea>
            </div>

            <div class="profile-actions">
                <button type="submit" class="save-btn" :disabled="saving || form.status !== 'Pending'">
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>

                <router-link :to="`/requests/${form.id}`" class="small-btn">
                    Back
                </router-link>
            </div>
        </form>
    </div>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import {
    getCategories,
    getLocations,
    getRequestById,
    updateRequest
} from '../services/requestService'

export default {
    components: {
        MessageBox
    },

    data() {
        return {
            loading: false,
            saving: false,
            categories: [],
            locations: [],
            form: {
                id: '',
                title: '',
                category_id: '',
                location_id: '',
                priority: 'Medium',
                description: '',
                status: ''
            }
        }
    },

    async mounted() {
        await this.loadPageData()
    },

    methods: {
        async loadPageData() {
            this.loading = true

            try {
                const id = this.$route.params.id

                const [requestRes, categoryRes, locationRes] = await Promise.all([
                    getRequestById(id),
                    getCategories(),
                    getLocations()
                ])

                this.categories = categoryRes.data
                this.locations = locationRes.data

                this.form = {
                    id: requestRes.data.id,
                    title: requestRes.data.title,
                    category_id: requestRes.data.category_id,
                    location_id: requestRes.data.location_id,
                    priority: requestRes.data.priority,
                    description: requestRes.data.description,
                    status: requestRes.data.status
                }
            } catch (error) {
                console.error(error)

                const message =
                    error.response?.data?.message ||
                    'Unable to load request.'

                this.$refs.msgBox.show(message, 'error')
            } finally {
                this.loading = false
            }
        },

        validateForm() {
            if (!this.form.title.trim()) {
                return 'Title is required.'
            }

            if (!this.form.category_id) {
                return 'Category is required.'
            }

            if (!this.form.location_id) {
                return 'Location is required.'
            }

            if (!this.form.description.trim()) {
                return 'Description is required.'
            }

            return null
        },

        async submitUpdate() {
            const validationError = this.validateForm()

            if (validationError) {
                this.$refs.msgBox.show(validationError, 'error')
                return
            }

            this.saving = true

            try {
                const res = await updateRequest(this.form.id, {
                    title: this.form.title,
                    category_id: this.form.category_id,
                    location_id: this.form.location_id,
                    priority: this.form.priority,
                    description: this.form.description
                })

                this.$refs.msgBox.show(
                    res.data?.message || 'Request updated successfully.',
                    'success'
                )

                setTimeout(() => {
                    this.$router.push(`/requests/${this.form.id}`)
                }, 800)
            } catch (error) {
                console.error(error)

                const message =
                    error.response?.data?.message ||
                    'Unable to update request.'

                this.$refs.msgBox.show(message, 'error')
            } finally {
                this.saving = false
            }
        }
    }
}
</script>

<style scoped>
.profile-field select,
.profile-field textarea {
    width: 100%;
    padding: 14px 16px;
    border: 1.5px solid #d6e0f5;
    border-radius: 10px;
    background: #f8faff;
    font-family: 'Poppins', sans-serif;
    font-size: .95rem;
    box-sizing: border-box;
}

.profile-field textarea {
    min-height: 140px;
    resize: vertical;
}

.profile-field select:focus,
.profile-field textarea:focus {
    outline: none;
    border-color: #1E3A8A;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(30,58,138,.08);
}

.warning-text {
    padding: 12px 14px;
    border-radius: 8px;
    background: #fff5f5;
    color: #b71c1c;
    border: 1px solid #f5c2c7;
}

.small-btn {
    display: inline-block;
    padding: 12px 18px;
    border-radius: 8px;
    background: #e8f0fe;
    color: #1E3A8A;
    text-decoration: none;
    font-family: 'Poppins', sans-serif;
    font-size: .9rem;
    margin-left: 10px;
}
</style>