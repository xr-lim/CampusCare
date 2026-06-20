import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import HomeView from '../views/HomeView.vue'

const routes = [
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
  },

  {
    path:'/login',
    component:LoginView
  },

  {
    path:'/register',
    component:RegisterView
  }
]

const router = createRouter({history:createWebHistory(), routes})

router.beforeEach((to,from,next)=>{
  const token = localStorage.getItem('token')

  if(to.meta.requiresAuth && !token){
    next('/login')
  }
  else{
    next()
  }
})

export default router