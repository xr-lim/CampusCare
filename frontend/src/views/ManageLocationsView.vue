<template>
  <MainLayout>
    <div class="container">
      <h1>Manage Locations</h1>
      
      <!-- Add Location Form -->
      <div class="form-section">
        <h2>Add New Location</h2>
        <form @submit.prevent="addLocation">
          <div class="form-group">
            <label for="locationName">Location Name *</label>
            <input 
              v-model="form.name" 
              type="text" 
              id="locationName" 
              placeholder="Enter location name (e.g., Block A, Room 201)"
              required
            >
            <span v-if="errors.name" class="error">{{ errors.name }}</span>
          </div>

          <div class="form-group">
            <label for="locationType">Location Type *</label>
            <select v-model="form.type" id="locationType" required>
              <option value="">-- Select Type --</option>
              <option value="Faculty">Faculty</option>
              <option value="Building">Building</option>
              <option value="Classroom">Classroom</option>
              <option value="Lab">Lab</option>
              <option value="Facility">Facility</option>
              <option value="Other">Other</option>
            </select>
            <span v-if="errors.type" class="error">{{ errors.type }}</span>
          </div>

          <div class="form-group">
            <label for="locationDescription">Description</label>
            <textarea 
              v-model="form.description" 
              id="locationDescription" 
              placeholder="Enter location description"
              rows="3"
            ></textarea>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Adding...' : 'Add Location' }}
          </button>
          <span v-if="successMessage" class="success">{{ successMessage }}</span>
          <span v-if="errorMessage" class="error">{{ errorMessage }}</span>
        </form>
      </div>

      <!-- Locations List -->
      <div class="list-section">
        <h2>Locations List</h2>
        <div v-if="loadingLocations" class="loading">Loading locations...</div>
        
        <table v-else class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Description</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="location in locations" :key="location.id">
              <td>{{ location.id }}</td>
              <td>{{ location.name }}</td>
              <td><span class="badge">{{ location.type }}</span></td>
              <td>{{ location.description || 'N/A' }}</td>
              <td>{{ formatDate(location.created_at) }}</td>
              <td>
                <button @click="editLocation(location)" class="btn btn-edit">Edit</button>
                <button @click="deleteLocation(location.id)" class="btn btn-delete">Delete</button>
              </td>
            </tr>
            <tr v-if="locations.length === 0">
              <td colspan="6" class="text-center">No locations found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Edit Modal -->
      <div v-if="editingLocation" class="modal-overlay" @click="cancelEdit">
        <div class="modal" @click.stop>
          <h2>Edit Location</h2>
          <form @submit.prevent="updateLocation">
            <div class="form-group">
              <label for="editLocationName">Location Name *</label>
              <input 
                v-model="editForm.name" 
                type="text" 
                id="editLocationName"
                required
              >
              <span v-if="errors.editName" class="error">{{ errors.editName }}</span>
            </div>

            <div class="form-group">
              <label for="editLocationType">Location Type *</label>
              <select v-model="editForm.type" id="editLocationType" required>
                <option value="">-- Select Type --</option>
                <option value="Faculty">Faculty</option>
                <option value="Building">Building</option>
                <option value="Classroom">Classroom</option>
                <option value="Lab">Lab</option>
                <option value="Facility">Facility</option>
                <option value="Other">Other</option>
              </select>
              <span v-if="errors.editType" class="error">{{ errors.editType }}</span>
            </div>

            <div class="form-group">
              <label for="editLocationDescription">Description</label>
              <textarea 
                v-model="editForm.description" 
                id="editLocationDescription"
                rows="3"
              ></textarea>
            </div>

            <div class="modal-actions">
              <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? 'Saving...' : 'Save Changes' }}
              </button>
              <button type="button" @click="cancelEdit" class="btn btn-secondary">Cancel</button>
            </div>
            <span v-if="editErrorMessage" class="error">{{ editErrorMessage }}</span>
          </form>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../layouts/MainLayout.vue';
import { getLocations, createLocation, updateLocation, deleteLocation } from '../services/api.js';

export default {
  name: 'ManageLocations',
  components: {
    MainLayout
  },
  data() {
    return {
      locations: [],
      form: {
        name: '',
        type: '',
        description: ''
      },
      editForm: {
        name: '',
        type: '',
        description: ''
      },
      editingLocation: null,
      loading: false,
      loadingLocations: true,
      successMessage: '',
      errorMessage: '',
      editErrorMessage: '',
      errors: {}
    };
  },
  mounted() {
    this.fetchLocations();
  },
  methods: {
    async fetchLocations() {
      try {
        this.loadingLocations = true;
        const response = await getLocations();
        this.locations = response.data;
      } catch (error) {
        this.errorMessage = 'Failed to load locations';
        console.error(error);
      } finally {
        this.loadingLocations = false;
      }
    },

    validateForm() {
      this.errors = {};
      
      if (!this.form.name.trim()) {
        this.errors.name = 'Location name is required';
      } else if (this.form.name.length > 100) {
        this.errors.name = 'Location name must not exceed 100 characters';
      }

      if (!this.form.type.trim()) {
        this.errors.type = 'Location type is required';
      } else if (this.form.type.length > 50) {
        this.errors.type = 'Location type must not exceed 50 characters';
      }

      return Object.keys(this.errors).length === 0;
    },

    validateEditForm() {
      this.errors = {};
      
      if (!this.editForm.name.trim()) {
        this.errors.editName = 'Location name is required';
      } else if (this.editForm.name.length > 100) {
        this.errors.editName = 'Location name must not exceed 100 characters';
      }

      if (!this.editForm.type.trim()) {
        this.errors.editType = 'Location type is required';
      } else if (this.editForm.type.length > 50) {
        this.errors.editType = 'Location type must not exceed 50 characters';
      }

      return Object.keys(this.errors).length === 0;
    },

    async addLocation() {
      if (!this.validateForm()) {
        return;
      }

      try {
        this.loading = true;
        this.errorMessage = '';
        this.successMessage = '';

        const token = localStorage.getItem('token');
        if (!token) {
          this.errorMessage = 'You must be logged in to add locations';
          return;
        }

        const response = await createLocation(this.form, token);
        
        this.successMessage = 'Location added successfully!';
        this.form = { name: '', type: '', description: '' };
        
        setTimeout(() => {
          this.successMessage = '';
          this.fetchLocations();
        }, 2000);
      } catch (error) {
        this.errorMessage = error.response?.data?.message || 'Failed to add location';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    editLocation(location) {
      this.editingLocation = location;
      this.editForm = {
        name: location.name,
        type: location.type,
        description: location.description || ''
      };
      this.editErrorMessage = '';
    },

    cancelEdit() {
      this.editingLocation = null;
      this.editForm = { name: '', type: '', description: '' };
      this.errors = {};
    },

    async updateLocation() {
      if (!this.validateEditForm()) {
        return;
      }

      try {
        this.loading = true;
        this.editErrorMessage = '';

        const token = localStorage.getItem('token');
        if (!token) {
          this.editErrorMessage = 'You must be logged in to update locations';
          return;
        }

        await updateLocation(this.editingLocation.id, this.editForm, token);
        
        this.cancelEdit();
        this.fetchLocations();
      } catch (error) {
        this.editErrorMessage = error.response?.data?.message || 'Failed to update location';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async deleteLocation(id) {
      if (!confirm('Are you sure you want to delete this location?')) {
        return;
      }

      try {
        this.loading = true;

        const token = localStorage.getItem('token');
        if (!token) {
          this.errorMessage = 'You must be logged in to delete locations';
          return;
        }

        await deleteLocation(id, token);
        this.fetchLocations();
      } catch (error) {
        this.errorMessage = error.response?.data?.message || 'Failed to delete location';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  margin-bottom: 30px;
  color: #333;
}

h2 {
  margin-bottom: 20px;
  color: #555;
  border-bottom: 2px solid #007bff;
  padding-bottom: 10px;
}

.form-section, .list-section {
  background: #f9f9f9;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 30px;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

input, select, textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  margin-right: 10px;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #545b62;
}

.btn-edit {
  background-color: #28a745;
  color: white;
  padding: 5px 15px;
  font-size: 12px;
  margin-right: 5px;
}

.btn-edit:hover {
  background-color: #218838;
}

.btn-delete {
  background-color: #dc3545;
  color: white;
  padding: 5px 15px;
  font-size: 12px;
}

.btn-delete:hover {
  background-color: #c82333;
}

.table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}

.table thead {
  background-color: #007bff;
  color: white;
}

.table th, .table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

.table tbody tr:hover {
  background-color: #f0f0f0;
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  background-color: #007bff;
  color: white;
  border-radius: 3px;
  font-size: 12px;
}

.loading {
  text-align: center;
  padding: 20px;
  color: #666;
}

.text-center {
  text-align: center;
  font-style: italic;
  color: #999;
}

.success {
  display: block;
  margin-top: 10px;
  padding: 10px;
  background-color: #d4edda;
  color: #155724;
  border-radius: 4px;
  font-size: 14px;
}

.error {
  display: block;
  margin-top: 5px;
  color: #dc3545;
  font-size: 12px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  padding: 30px;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
}

.modal h2 {
  margin-top: 0;
}

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.modal-actions button {
  flex: 1;
}
</style>
