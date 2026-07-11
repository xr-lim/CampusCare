import { createRouter, createWebHistory } from 'vue-router'

import MainLayout from '../layouts/MainLayout.vue'
import ErrorView from '../views/ErrorView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import HomeView from '../views/HomeView.vue'
import SubmitRequestView from '../views/SubmitRequestView.vue'
import MyRequestsView from '../views/MyRequestsView.vue'
import RequestDetailsView from '../views/RequestDetailsView.vue'
import EditRequestView from '../views/EditRequestView.vue'
import TechnicianRequestsView from '../views/TechnicianRequestsView.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import AdminRequestsView from '../views/AdminRequestsView.vue'
import AdminRequestDetailView from '../views/AdminRequestDetailView.vue'
import ManageCategoriesView from '../views/ManageCategoriesView.vue'
import ManageLocationsView from '../views/ManageLocationsView.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    component: LoginView,
    meta: {
      public: true
    }
  },
  {
    path: '/register',
    component: RegisterView,
    meta: {
      public: true
    }
  },
  {
    path: '/error',
    name: 'Error',
    component: ErrorView
  },
  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: 'home',
        name: 'Home',
        component: HomeView,
        meta: {
          requiresAuth: true
        }
      },
      {
        path: 'submit-request',
        name: 'SubmitRequest',
        component: SubmitRequestView,
        meta: {
          requiresAuth: true
        }
      },
      {
        path: 'my-requests',
        name: 'MyRequests',
        component: MyRequestsView,
        meta: {
          requiresAuth: true
        }
      },
      {
        path: 'requests/:id',
        name: 'RequestDetails',
        component: RequestDetailsView,
        meta: {
          requiresAuth: true
        }
      },
      {
        path: 'requests/:id/edit',
        name: 'EditRequest',
        component: EditRequestView,
        meta: {
          requiresAuth: true
        }
      },
      {
        path: 'assigned-tasks',
        name: 'AssignedTasks',
        component: TechnicianRequestsView,
        meta: {
          requiresAuth: true,
          role: 'technician'
        }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: ProfileView,
        meta: {
          requiresAuth: true
        }
      },
      {
        path: 'admin/dashboard',
        name: 'AdminDashboard',
        component: AdminDashboardView,
        meta: {
          requiresAuth: true,
          roles: ['Admin']
        }
      },
      {
        path: 'admin/requests',
        name: 'AdminRequests',
        component: AdminRequestsView,
        meta: {
          requiresAuth: true,
          roles: ['Admin']
        }
      },
      {
        path: 'admin/requests/:id',
        name: 'AdminRequestDetail',
        component: AdminRequestDetailView,
        meta: {
          requiresAuth: true,
          roles: ['Admin']
        }
      },
      {
        path: 'manage-categories',
        name: 'ManageCategories',
        component: ManageCategoriesView,
        meta:{
          requiresAuth:true,
          role: 'Admin'
        }
      },
      {
        path: 'manage-locations',
        name: 'ManageLocations',
        component: ManageLocationsView,
        meta:{
          requiresAuth:true,
          role: 'Admin'
        }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const storedUser = localStorage.getItem('user')
  const user = storedUser ? JSON.parse(storedUser) : null

  if (to.meta.requiresAuth && !token) {
    return '/login'
  }

  const requiredRoles = to.meta.roles || (to.meta.role ? [to.meta.role] : [])
  if (requiredRoles.length) {
    const currentRole = (user?.role || '').toLowerCase()
    const allowedRoles = requiredRoles.map((role) => role.toLowerCase())

    if (!allowedRoles.includes(currentRole)) {
      return '/error?code=403'
    }
  }
})

export default router
