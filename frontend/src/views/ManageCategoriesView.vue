<template>
    <div class="container">
      <h1>Manage Categories</h1>
      
      <!-- Add Category Form -->
      <div class="form-section">
        <h2>Add New Category</h2>
        <form @submit.prevent="addCategory">
          <div class="form-group">
            <label for="categoryName">Category Name *</label>
            <input 
              v-model="form.name" 
              type="text" 
              id="categoryName" 
              placeholder="Enter category name"
              required
            >
            <span v-if="errors.name" class="error">{{ errors.name }}</span>
          </div>

          <div class="form-group">
            <label for="categoryDescription">Description</label>
            <textarea 
              v-model="form.description" 
              id="categoryDescription" 
              placeholder="Enter category description"
              rows="3"
            ></textarea>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Adding...' : 'Add Category' }}
          </button>
          <span v-if="successMessage" class="success">{{ successMessage }}</span>
          <span v-if="errorMessage" class="error">{{ errorMessage }}</span>
        </form>
      </div>

      <!-- Categories List -->
      <div class="list-section">
        <h2>Categories List</h2>
        <div v-if="loadingCategories" class="loading">Loading categories...</div>
        
        <table v-else class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Description</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in categories" :key="category.id">
              <td>{{ category.id }}</td>
              <td>{{ category.name }}</td>
              <td>{{ category.description || 'N/A' }}</td>
              <td>{{ formatDate(category.created_at) }}</td>
              <td>
                <button @click="editCategory(category)" class="btn btn-edit">Edit</button>
                <button @click="deleteCategory(category.id)" class="btn btn-delete">Delete</button>
              </td>
            </tr>
            <tr v-if="categories.length === 0">
              <td colspan="5" class="text-center">No categories found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Edit Modal -->
      <div v-if="editingCategory" class="modal-overlay" @click="cancelEdit">
        <div class="modal" @click.stop>
          <h2>Edit Category</h2>
          <form @submit.prevent="updateCategory">
            <div class="form-group">
              <label for="editCategoryName">Category Name *</label>
              <input 
                v-model="editForm.name" 
                type="text" 
                id="editCategoryName"
                required
              >
              <span v-if="errors.editName" class="error">{{ errors.editName }}</span>
            </div>

            <div class="form-group">
              <label for="editCategoryDescription">Description</label>
              <textarea 
                v-model="editForm.description" 
                id="editCategoryDescription"
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
</template>

<script>
import { getCategories, createCategory, updateCategory, deleteCategory } from '../services/api.js';

export default {
  name: 'ManageCategories',
  data() {
    return {
      categories: [],
      form: {
        name: '',
        description: ''
      },
      editForm: {
        name: '',
        description: ''
      },
      editingCategory: null,
      loading: false,
      loadingCategories: true,
      successMessage: '',
      errorMessage: '',
      editErrorMessage: '',
      errors: {}
    };
  },
  mounted() {
    this.fetchCategories();
  },
  methods: {
    async fetchCategories() {
      try {
        this.loadingCategories = true;
        const response = await getCategories();
        this.categories = response.data;
      } catch (error) {
        this.errorMessage = 'Failed to load categories';
        console.error(error);
      } finally {
        this.loadingCategories = false;
      }
    },

    validateForm() {
      this.errors = {};
      
      if (!this.form.name.trim()) {
        this.errors.name = 'Category name is required';
      } else if (this.form.name.length > 100) {
        this.errors.name = 'Category name must not exceed 100 characters';
      }

      return Object.keys(this.errors).length === 0;
    },

    validateEditForm() {
      this.errors = {};
      
      if (!this.editForm.name.trim()) {
        this.errors.editName = 'Category name is required';
      } else if (this.editForm.name.length > 100) {
        this.errors.editName = 'Category name must not exceed 100 characters';
      }

      return Object.keys(this.errors).length === 0;
    },

    async addCategory() {
      if (!this.validateForm()) {
        return;
      }

      try {
        this.loading = true;
        this.errorMessage = '';
        this.successMessage = '';

        const token = localStorage.getItem('token');
        if (!token) {
          this.errorMessage = 'You must be logged in to add categories';
          return;
        }

        const response = await createCategory(this.form, token);
        
        this.successMessage = 'Category added successfully!';
        this.form = { name: '', description: '' };
        
        setTimeout(() => {
          this.successMessage = '';
          this.fetchCategories();
        }, 2000);
      } catch (error) {
        this.errorMessage = error.response?.data?.message || 'Failed to add category';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    editCategory(category) {
      this.editingCategory = category;
      this.editForm = {
        name: category.name,
        description: category.description || ''
      };
      this.editErrorMessage = '';
    },

    cancelEdit() {
      this.editingCategory = null;
      this.editForm = { name: '', description: '' };
      this.errors = {};
    },

    async updateCategory() {
      if (!this.validateEditForm()) {
        return;
      }

      try {
        this.loading = true;
        this.editErrorMessage = '';

        const token = localStorage.getItem('token');
        if (!token) {
          this.editErrorMessage = 'You must be logged in to update categories';
          return;
        }

        await updateCategory(this.editingCategory.id, this.editForm, token);
        
        this.cancelEdit();
        this.fetchCategories();
      } catch (error) {
        this.editErrorMessage = error.response?.data?.message || 'Failed to update category';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async deleteCategory(id) {
      if (!confirm('Are you sure you want to delete this category?')) {
        return;
      }

      try {
        this.loading = true;

        const token = localStorage.getItem('token');
        if (!token) {
          this.errorMessage = 'You must be logged in to delete categories';
          return;
        }

        await deleteCategory(id, token);
        this.fetchCategories();
      } catch (error) {
        this.errorMessage = error.response?.data?.message || 'Failed to delete category';
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
  max-width: 1000px;
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

input, textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

input:focus, textarea:focus {
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
