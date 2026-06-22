import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import HomeView from '../views/HomeView.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path:'/login',
    component:LoginView
  },
  {
    path:'/register',
    component:RegisterView
  },
  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: 'home',
        name: 'Home',
        component: HomeView,
        meta:{requiresAuth:true}
      },
      {
        path: 'profile',
        name: 'Profile',
        component: ProfileView,
        meta:{requiresAuth:true}
      }
      /*{
        path: 'submit-report',
        name: 'SubmitReport',
        component: SubmitReportView // Injected automatically into <router-view />
      },*/
    ]
  }
]

const router = createRouter({history:createWebHistory(), routes})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    return '/login'
  }
})

export default router