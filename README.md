# 🏫 CampusCare – Campus Maintenance System

Welcome to the **CampusCare** repository! This is a full-stack web application designed to streamline campus maintenance reporting and tracking. 

This guide is designed for development team members to clone, set up, configure, and run both the **PHP Slim 4 Backend** and the **Vue.js 3 (Vite) Frontend** smoothly inside a local **XAMPP** environment.

---

## 📋 System Prerequisites

Ensure you have the following installed on your machine before setting up:

* **XAMPP** (with PHP 8.1+ and MySQL/MariaDB)
* **Composer** (PHP dependency package manager)
* **Node.js** (v18+ LTS recommended) & `npm`
* **Git** and **VS Code**

---

## 🛠️ Complete Local Machine Setup Installation

### 1. Repository Positioning
To ensure Apache reads the backend files correctly, your local Git repository folder must live entirely inside your XAMPP `htdocs` directory.

> 📍 **Correct Path Target:** `C:\xampp\htdocs\CampusCare`

---

### 2. Database Synchronization & Initialization
We use **Schema Tracking** via Git to ensure everyone is working with the same database structure.

1. Open your **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.
2. Open your web browser and navigate to: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
3. Click on **New** in the left sidebar, name the database `campuscare`, and click **Create**.
4. Select your newly created database, then click on the **Import** tab at the top menu bar.
5. Browse and select the repository schema file located at: `database/schema.sql`
6. Click **Go** at the bottom of the page to run the file and build the tables.

> 💡 **Initial Test Accounts Provided:** The initial schema file automatically seeds four pre-configured developer testing accounts using secure hashed credentials:
> * **Admin Account:** Email: `admin@campus.edu` | Password: `password123`
> * **Technician Account:** Email: `tech1@campus.edu` | Password: `password123`
> * **Staff Account:** Email: `JohnDoe@campus.edu` | Password: `password123`
> * **Student Account:** Email: `janedoe@campus.edu` | Password: `password123`

---

### 3. Backend Implementation Config & Environment
Open your terminal and change directory into the backend folder:

```bash
cd backend
```

Download and map the required framework packages (Slim, Firebase JWT, PSR-7) by running:

```bash
composer install
```

Generate the required directory lookup tables for namespaces:

```bash
composer dump-autoload
```

---

### 4. Frontend Installation & Configurations
Open a new terminal session or switch directory directly into the frontend folder:

```bash
cd ../frontend
```

Install all required Node packages, Vue components, Vite plugins, and Axios dependencies:

```bash
npm install
```

## 🚀 Running the Application in Development Mode
1. Ensure Apache and MySQL are running inside your XAMPP Control Panel.
2. Run your Vite frontend compiler toolset:

```bash
cd frontend
npm run dev
```
3. Open your browser and navigate to the local address provided by Vite (typically http://localhost:5173).
4. Test the registration or use the pre-seeded account credentials (admin@campus.edu / password123) to log in.

## 🧱 Adding a New View Using the Shared Layout

To keep the application layout consistent, all pages must be rendered inside the shared template structure (`MainLayout.vue`). This ensures your page automatically inherits the top Navigation Bar, the role-based Sidebar, and the theme without rewriting the structural code.

* ### Create Your View Component
Navigate to `src/views/` and create a new Vue component file for your feature module (e.g., `SubmitReportView.vue` or `AssignedTasksView.vue`). 

Inside your file, write **only** the core body content of your workspace module. **Do not** re-import or manually add the navbar or sidebar elements here:

```vue
<template>
  <div class="dashboard-card">
    <h1>Your Module Title</h1>
    <p>Start constructing your form fields, tables, or analytical data dashboards right here.</p>
    
    </div>
</template>

<script>
export default {
  name: 'YourModuleNameView',
  data() {
    return {
      // Your module state data
    }
  }
}
</script>
```

* ### Use MessageBox instead of alert()
Use the pre-built global toast component by injecting <MessageBox ref="msgBox" /> into your view markup and executing this.$refs.msgBox.show('Your custom text string here', 'success' | 'error').
