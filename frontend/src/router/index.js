import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import Dashboard from '../views/Dashboard.vue';
import Unauthorized from '../views/Unauthorized.vue';

function getUserFromToken() {
  const token = localStorage.getItem('token');
  if (!token) return null;
  try {
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    return JSON.parse(window.atob(base64)); // Contains: id, username, role
  } catch (e) {
    return null;
  }
}

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { layout: 'AuthLayout', guestOnly: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { layout: 'AuthLayout', guestOnly: true }
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: Unauthorized,
    meta: { layout: 'AuthLayout' }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { layout: 'MainLayout', requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  const user = getUserFromToken();

  if (to.meta.guestOnly && user) {
    return next('/dashboard');
  }

  if (to.meta.requiresAuth && !user) {
    return next('/login');
  }

  if (to.meta.roles && (!user || !to.meta.roles.includes(user.role))) {
    return next('/unauthorized');
  }

  next();
});

export default router;