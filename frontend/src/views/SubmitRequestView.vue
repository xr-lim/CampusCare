<template>
    <MessageBox ref="msgBox" />

    <div class="profile-card">
        <div class="profile-header">
            <h2>Submit Maintenance Request</h2>
            <p>Report a facility issue for the maintenance team.</p>
        </div>

        <form @submit.prevent="submitRequest">
            <div class="profile-field">
                <label>Title</label>
                <input v-model="form.title" type="text" maxlength="150" required>
            </div>

            <div class="profile-field">
                <label>Category</label>
                <select v-model="form.category_id" required>
                    <option value="">Select category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <div class="profile-field">
                <label>Location</label>
                <select v-model="form.location_id" required>
                    <option value="">Select location</option>
                    <option v-for="location in locations" :key="location.id" :value="location.id">
                        {{ location.name }}
                    </option>
                </select>
            </div>

            <div class="profile-field">
                <label>Priority</label>
                <select v-model="form.priority" required>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <div class="profile-field">
                <label>Description</label>
                <textarea v-model="form.description" maxlength="1000" required></textarea>
            </div>

            <div class="profile-actions">
                <button type="submit" class="save-btn" :disabled="loading">
                    {{ loading ? 'Submitting...' : 'Submit Request' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import MessageBox from '../components/MessageBox.vue'
import { getCategories, getLocations, createRequest } from '../services/requestService'

export default {
    components: {
        MessageBox
    },

    data() {
        return {
            loading: false,
            categories: [],
            locations: [],
            form: {
                title: '',
                category_id: '',
                location_id: '',
                priority: 'Medium',
                description: ''
            }
        }
    },

    async mounted() {
        await this.loadOptions()
    },

    methods: {
        async loadOptions() {
            try {
                const [categoryRes, locationRes] = await Promise.all([
                    getCategories(),
                    getLocations()
                ])

                this.categories = categoryRes.data
                this.locations = locationRes.data
            } catch (error) {
                console.error(error)
                this.$refs.msgBox.show('Unable to load form options.', 'error')
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

        async submitRequest() {
            const validationError = this.validateForm()

            if (validationError) {
                this.$refs.msgBox.show(validationError, 'error')
                return
            }

            this.loading = true

            try {
                const res = await createRequest(this.form)

                this.$refs.msgBox.show(
                    res.data?.message || 'Maintenance request submitted successfully.',
                    'success'
                )

                this.form = {
                    title: '',
                    category_id: '',
                    location_id: '',
                    priority: 'Medium',
                    description: ''
                }
            } catch (error) {
                console.error(error)

                const message =
                    error.response?.data?.message ||
                    'Unable to submit maintenance request.'

                this.$refs.msgBox.show(message, 'error')
            } finally {
                this.loading = false
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

.save-btn:disabled {
    opacity: .7;
    cursor: not-allowed;
}
</style>