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

            <div class="profile-field">
                <label for="request-images">Images (optional)</label>
                <input id="request-images" ref="imageInput" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple @change="selectImages">
                <small class="upload-help">Up to 3 JPEG, PNG, or WebP images. Maximum 5 MB each.</small>
                <div v-if="imagePreviews.length" class="image-previews">
                    <div v-for="(preview, index) in imagePreviews" :key="preview.url" class="image-preview">
                        <img :src="preview.url" :alt="preview.name">
                        <button type="button" @click="removeImage(index)">Remove</button>
                    </div>
                </div>
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
                description: '',
                images: []
            },
            imagePreviews: []
        }
    },

    async mounted() {
        await this.loadOptions()
    },

    beforeUnmount() {
        this.clearPreviews()
    },

    methods: {
        selectImages(event) {
            const files = Array.from(event.target.files)
            if (files.length > 3) {
                this.$refs.msgBox.show('You can upload a maximum of 3 images.', 'error')
                event.target.value = ''
                return
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
            const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp']
            const unsupportedFile = files.find(file => {
                const extension = file.name.split('.').pop()?.toLowerCase()
                return !allowedTypes.includes(file.type) || !allowedExtensions.includes(extension)
            })

            if (unsupportedFile) {
                this.$refs.msgBox.show(`Cannot accept "${unsupportedFile.name}". Only JPEG, JPG, PNG, and WebP images are allowed.`, 'error')
                event.target.value = ''
                return
            }

            const oversizedFile = files.find(file => file.size > 5 * 1024 * 1024)
            if (oversizedFile) {
                this.$refs.msgBox.show(`Cannot accept "${oversizedFile.name}" because it is larger than 5 MB.`, 'error')
                event.target.value = ''
                return
            }
            this.clearPreviews()
            this.form.images = files
            this.imagePreviews = files.map(file => ({ name: file.name, url: URL.createObjectURL(file) }))
        },

        removeImage(index) {
            URL.revokeObjectURL(this.imagePreviews[index].url)
            this.imagePreviews.splice(index, 1)
            this.form.images.splice(index, 1)
            if (!this.form.images.length) this.$refs.imageInput.value = ''
        },

        clearPreviews() {
            this.imagePreviews.forEach(preview => URL.revokeObjectURL(preview.url))
            this.imagePreviews = []
        },

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
                    description: '',
                    images: []
                }
                this.clearPreviews()
                this.$refs.imageInput.value = ''
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

.upload-help { display:block; margin-top:8px; color:#64748b; }
.image-previews { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 180px)); gap:14px; margin-top:14px; }
.image-preview { position:relative; }
.image-preview img { width:100%; height:130px; object-fit:cover; border-radius:10px; border:1px solid #d6e0f5; }
.image-preview button { width:100%; margin-top:6px; border:0; background:#fee2e2; color:#991b1b; border-radius:7px; padding:7px; cursor:pointer; }

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
