<template>
    <div>
        <h2>User Profile</h2>
        <p>{{ user.email }}</p>
        <p>{{ user.role }}</p>
    </div>
</template>

<script>
    import { profile }
    from '../services/authService'

    export default{

        data(){
            return{
                user:{}
            }
        },

        async mounted(){

        const res =await profile()
        this.user = res.data
        }
    }
</script>