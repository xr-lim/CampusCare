import { createRouter, createWebHistory } from 'vue-router'

import MainLayout from '../layouts/MainLayout.vue'
import ErrorView from '../views/ErrorView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import HomeView from '../views/HomeView.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import AdminRequestsView from '../views/AdminRequestsView.vue'
import AdminRequestDetailView from '../views/AdminRequestDetailView.vue'

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

  if (to.meta.roles?.length) {
    const currentRole = (user?.role || '').toLowerCase()
    const allowedRoles = to.meta.roles.map((role) => role.toLowerCase())

    if (!allowedRoles.includes(currentRole)) {
      return '/error?code=403'
    }
  }
})

export default router
