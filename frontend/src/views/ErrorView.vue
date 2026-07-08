<template>
  <div class="error-container">

    <div class="error-card">

      <div class="error-icon">
        <i :class="errorIcon"></i>
      </div>

      <h1>{{ errorCode }}</h1>

      <h2>{{ errorTitle }}</h2>

      <p>
        {{ errorMessage }}
      </p>

      <div class="error-actions">
        <button v-if="errorCode === 401" @click="goLogin" class="primary-btn">
          <i class="fas fa-sign-in-alt"></i>
          Login Again
        </button>

        <button v-else @click="goHome" class="primary-btn">
          <i class="fas fa-home"></i>
          Return Home
        </button>
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        computed: {
            errorCode(){
                return Number(this.$route.query.code) || 500
            },

            errorTitle(){
                switch(this.errorCode){
                    case 401:
                        return "Session Expired"

                    case 403:
                        return "Access Denied"

                    default:
                        return "Something Went Wrong"
                }
            },

            errorMessage(){
                switch(this.errorCode){

                    case 401:
                        return "Your session has expired or you are not authenticated. Please login again."

                    case 403:
                        return "You do not have permission to access this page."

                    default:
                        return "An unexpected error occurred. Please try again later."
                }
            },

            errorIcon(){
                switch(this.errorCode){

                    case 401:
                        return "fas fa-lock"

                    case 403:
                        return "fas fa-ban"

                    default:
                        return "fas fa-triangle-exclamation"
                }
            }
        },

        methods:{
            goLogin(){
                localStorage.removeItem('token')
                localStorage.removeItem('user')

                this.$router.push('/login')
            },

            goHome(){
                this.$router.push('/home')
            }
        }
    }
</script>

<style scoped>
    .error-container {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #f8fafc;
        font-family: 'Poppins', sans-serif;
    }
    .error-card {
        width: 420px;
        background: white;
        padding: 45px 40px;
        border-radius: 16px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .error-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
        background: #eff6ff;
        color: #1E3A8A;
        font-size: 2rem;
    }
    .error-card h1 {
        font-size: 3.5rem;
        margin: 0;
        color: #1E3A8A;
        font-weight: 700;
    }
    .error-card h2 {
        margin: 10px 0;
        font-size: 1.4rem;
        color: #1f2937;
    }
    .error-card p {
        margin: 20px auto 30px;
        max-width: 320px;
        color: #6b7280;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    .error-actions {
        display: flex;
        justify-content: center;
    }
    .primary-btn {
        width: 100%;
        max-width: 220px;
        padding: 13px 20px;
        border: none;
        border-radius: 8px;
        background: #1E3A8A;
        color: white;
        font-family: 'Poppins', sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .primary-btn i {
        margin-right: 8px;
    }
    .primary-btn:hover {
        background: #172554;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(30,58,138,0.2);
    }
    @media(max-width:500px){
        .error-card {
            width: calc(100% - 40px);
            padding: 35px 25px;
        }
        .error-card h1 {
            font-size: 3rem;
        }
    }
</style>