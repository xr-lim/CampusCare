import { createRouter, createWebHistory } from 'vue-router'

//General
import MainLayout from '../layouts/MainLayout.vue'
import ErrorView from '../views/ErrorView.vue'

//Public
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'

//Common
import ProfileView from '../views/ProfileView.vue'
import HomeView from '../views/HomeView.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path:'/login',
    component:LoginView,
    meta:{
      public:true
    }
  },
  {
    path:'/register',
    component:RegisterView,
    meta:{
      public:true
    }
  },
  {
    path:'/error',
    name:'Error',
    component:ErrorView
  },

  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: 'home',
        name: 'Home',
        component: HomeView,
        meta:{
          requiresAuth:true
        }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: ProfileView,
        meta:{
          requiresAuth:true
        }
      }
    ]
  }
]

const router = createRouter({history:createWebHistory(), routes})

router.beforeEach((to)=>{
    const token = localStorage.getItem('token')
    const user = JSON.parse(localStorage.getItem('user'))

    if(to.meta.requiresAuth && !token){
      return '/login'
    }

    if(to.meta.role && user.role !== to.meta.role){
      return '/home'
    }
})

export default router