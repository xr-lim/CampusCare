<template>
    <MessageBox ref="msgBox" />
    <div class="profile-card">
        <div class="profile-header">
        <h2>My Profile</h2>
        </div>

        <form @submit.prevent="updateProfile">

        <div class="profile-field">
            <label>Username</label>
            <input type="text" v-model="user.username" required>
        </div>

        <div class="profile-field">
            <label>Email</label>
            <input type="email" v-model="user.email" disabled>
        </div>

        <div class="profile-field">
            <label>Role</label>
            <input type="text" :value="user.role" disabled>
        </div>

        <div class="profile-actions">
            <button type="submit" class="save-btn">
            <i class="fas fa-floppy-disk"></i>
            Save Changes
            </button>
        </div>

        </form>
    </div>

    <div class="danger-section">
        <h3>
        <i class="fas fa-triangle-exclamation"></i>
        Danger Zone
        </h3>

        <p>
        Once you delete your account, all of your information will be
        permanently removed. This action cannot be undone.
        </p>

        <button class="delete-btn" @click="deleteAccount">
        <i class="fas fa-trash"></i>
            Delete Account
        </button>
    </div>
</template>

<script>
import {profile, updateProfile, deleteProfile} from "../services/authService";
import MessageBox from '../components/MessageBox.vue';

export default {
    components: {
        MessageBox
    },
    data() {
        return {
        user: {
            username: "",
            email: "",
            password: "",
            role: ""
        }
        };
    },

    async mounted() {
        this.loadProfile();
    },

    methods: {
        async loadProfile() {
            try {
                const res = await profile();

                this.user.username = res.data.username;
                this.user.email = res.data.email;
                this.user.role = res.data.role;

            } catch (error) {
                console.error(error);

                const message =
                error.response?.data?.message ||
                "Unable to load profile.";

                this.$refs.msgBox.show(message, "error");
            }
        },

        async updateProfile() {
            try {
                const res = await updateProfile(this.user);
                
                const savedUser = JSON.parse(localStorage.getItem("user"));
                savedUser.username = this.user.username;
                localStorage.setItem("user", JSON.stringify(savedUser));

                const successMessage = res.data?.message || "Profile updated successfully.";

                this.$refs.msgBox.show(successMessage, "success");

                this.user.password = "";

            } catch (error) {

                console.error(error);

                const message =
                error.response?.data?.message ||
                "Unable to update profile.";

                this.$refs.msgBox.show(message, "error");
            }
        },

        async deleteAccount() {
            if (!confirm("Are you sure you want to permanently delete your account?")) {
                return;
            }

            try {

                const res = await deleteProfile();

                this.$refs.msgBox.show(
                res.data?.message || "Account deleted successfully.",
                "success"
                );

                localStorage.removeItem("token");
                localStorage.removeItem("user");

                setTimeout(() => {
                this.$router.push("/login");
                }, 1500);

            } catch (error) {

                console.error(error);

                const message =
                error.response?.data?.message ||
                "Unable to delete account.";

                this.$refs.msgBox.show(message, "error");
            }
            }
    }
    };
</script>