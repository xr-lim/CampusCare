# 🏫 CampusCare – Campus Maintenance System

Welcome to **CampusCare**, a full-stack web application for campus maintenance request management.

---

## 📋 Project Overview

**CampusCare** is a complete maintenance request tracking system designed for universities. It enables students and staff to report maintenance issues and allows administrators to manage and track those requests.

### Key Features
- ✅ User authentication with JWT
- ✅ Role-based access control (Student, Staff, Technician, Admin)
- ✅ Maintenance request CRUD operations
- ✅ Category and location management
- ✅ Status tracking and history
- ✅ Admin dashboard
- ✅ Responsive Vue.js 3 frontend
- ✅ PHP Slim 4 REST API backend

---

## 🛠️ System Requirements

- **XAMPP** (PHP 8.1+, MySQL/MariaDB)
- **Composer** (PHP package manager)
- **Node.js** 18+ LTS & npm
- **Git** and **VS Code**

---

## 📁 Project Structure

```
CampusCare/
├── backend/                 # PHP Slim 4 REST API
│   ├── config/             # Database & JWT config
│   ├── middleware/         # JWT & Role middleware
│   ├── routes/             # API endpoints
│   │   ├── auth.php       # Authentication routes
│   │   ├── user.php       # User routes
│   │   ├── categories.php # Category CRUD
│   │   └── locations.php  # Location CRUD
│   ├── public/            # Entry point (index.php)
│   └── vendor/            # Composer dependencies
├── frontend/               # Vue.js 3 + Vite
│   ├── src/
│   │   ├── components/    # Reusable Vue components
│   │   ├── layouts/       # Page layouts
│   │   ├── router/        # Route definitions
│   │   ├── services/      # API client
│   │   └── views/         # Page components
│   └── package.json       # npm dependencies
├── database/
│   └── schema.sql         # Database schema with seed data
└── README.md              # This file
```

---

## 🚀 Quick Start Guide

### Step 1: Clone Repository
```bash
cd C:\xampp\htdocs
git clone <repo-url> CampusCare
cd CampusCare
```

### Step 2: Database Setup
1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Import `database/schema.sql`
3. Verify tables: `users`, `categories`, `locations`, `maintenance_requests`, `status_updates`

### Step 3: Backend Setup
```bash
cd backend
composer install
```

### Step 4: Frontend Setup
```bash
cd frontend
npm install
npm run dev
```

The frontend will run on `http://localhost:5173`

---

## 📚 Person 4 – Category, Location, Testing & Deployment Tasks

**Person 4** is responsible for the Category and Location management modules, testing, and deployment preparation.

### Completed Tasks ✅

#### 1. **Database Schema Extension**
- Added `categories` table
- Added `locations` table  
- Added `maintenance_requests` table
- Added `status_updates` table
- Included relationships and seed data
- **File:** [database/schema.sql](database/schema.sql)

#### 2. **Backend API - Categories (CRUD)**
- ✅ `GET /categories` – Fetch all categories
- ✅ `GET /categories/{id}` – Fetch single category
- ✅ `POST /categories` – Create category (Admin only)
- ✅ `PUT /categories/{id}` – Update category (Admin only)
- ✅ `DELETE /categories/{id}` – Delete category (Admin only)
- **Features:** JWT protection, validation, error handling
- **File:** [backend/routes/categories.php](backend/routes/categories.php)

#### 3. **Backend API - Locations (CRUD)**
- ✅ `GET /locations` – Fetch all locations
- ✅ `GET /locations/{id}` – Fetch single location
- ✅ `POST /locations` – Create location (Admin only)
- ✅ `PUT /locations/{id}` – Update location (Admin only)
- ✅ `DELETE /locations/{id}` – Delete location (Admin only)
- **Features:** JWT protection, validation, type field, error handling
- **File:** [backend/routes/locations.php](backend/routes/locations.php)

#### 4. **Frontend Components**
- ✅ Manage Categories page with form & table
- ✅ Manage Locations page with form & table
- ✅ Edit modal dialogs
- ✅ Form validation (frontend & backend)
- ✅ Success/error messages
- **Files:**
  - [frontend/src/views/ManageCategoriesView.vue](frontend/src/views/ManageCategoriesView.vue)
  - [frontend/src/views/ManageLocationsView.vue](frontend/src/views/ManageLocationsView.vue)

#### 5. **API Service Functions**
- ✅ `getCategories()`, `getCategory(id)`, `createCategory()`, `updateCategory()`, `deleteCategory()`
- ✅ `getLocations()`, `getLocation(id)`, `createLocation()`, `updateLocation()`, `deleteLocation()`
- **File:** [frontend/src/services/api.js](frontend/src/services/api.js)

#### 6. **Router Configuration**
- ✅ Admin-protected routes for category management
- ✅ Admin-protected routes for location management
- ✅ Route guards for role-based access
- **File:** [frontend/src/router/index.js](frontend/src/router/index.js)

#### 7. **Database Seed Data**
- ✅ 6 sample categories (Electrical, Furniture, AC, Internet, Plumbing, Other)
- ✅ 10 sample locations (Faculties, Buildings, Labs, Classrooms, Facilities)
- **File:** [database/schema.sql](database/schema.sql)

#### 8. **API Testing - Postman Collection**
- ✅ Complete Postman collection with all endpoints
- ✅ Authentication examples
- ✅ CRUD examples for categories and locations
- **File:** [CampusCare_API.postman_collection.json](CampusCare_API.postman_collection.json)
- **How to use:** Import into Postman → Replace `<your_admin_token>` with actual admin token from login

---

## 🧪 Testing Guide

### Option 1: Using Postman
1. Import `CampusCare_API.postman_collection.json` into Postman
2. Run **Login** endpoint to get admin token
3. Copy token and replace `<your_admin_token>` in category/location requests
4. Test Create, Read, Update, Delete operations

### Option 2: Using Frontend
1. Start frontend: `npm run dev`
2. Login as Admin (credentials: admin@campus.edu / password)
3. Navigate to "Manage Categories" and "Manage Locations"
4. Test create, edit, delete operations

### Test Data
```
Admin Login
─────────────────────────────
Email: admin@campus.edu
Password: password

Sample Category
─────────────────────────────
Name: Electrical
Description: Electrical issues including broken lights

Sample Location
─────────────────────────────
Name: Block A
Type: Building
Description: Administrative Building
```

---

## ✔️ Validation Rules

### Categories
- **Name:** Required, max 100 characters, unique
- **Description:** Optional
- **Admin Only:** Create, Update, Delete

### Locations
- **Name:** Required, max 100 characters, unique
- **Type:** Required, max 50 characters (Faculty, Building, Classroom, Lab, Facility, Other)
- **Description:** Optional
- **Admin Only:** Create, Update, Delete

### Error Handling
- Invalid ID returns 400 Bad Request
- Non-existent resources return 404 Not Found
- Duplicate names return 409 Conflict
- Non-admin access returns 403 Forbidden
- Missing token returns 401 Unauthorized
- Server errors return 500 Internal Server Error

---

## 📝 Remaining Person 4 Tasks (To Do)

After Person 2 and Person 3 complete their modules, Person 4 should:

### Maintenance Request Testing
- Test maintenance_requests endpoints with GET, POST, PUT, DELETE
- Verify status updates functionality
- Test request filtering and pagination

### Frontend Integration Testing
- Test category/location dropdowns in request form
- Verify CRUD operations across all modules
- Test role-based access control

### Documentation
- Create API documentation
- Document database relationships
- Create deployment guide

### Deployment Preparation
- Configure production environment
- Set up database backup
- Create deployment checklist
- Test on staging server

---

## 🔐 Authentication & Authorization

### JWT Token Flow
1. User logs in with email/password
2. Backend generates JWT token (24-hour expiry)
3. Frontend stores token in `localStorage`
4. API interceptor adds `Authorization: Bearer {token}` to all requests
5. Backend verifies token on protected routes

### Role-Based Access
- **Student/Staff:** Can view own requests, submit new requests
- **Technician:** Can view all requests, update status
- **Admin:** Full access to all features including category/location management

---

## 📋 API Endpoints Reference

### Categories
| Method | Endpoint | Auth | Role | Status |
|--------|----------|------|------|--------|
| GET | `/categories` | ❌ | Public | ✅ |
| GET | `/categories/{id}` | ❌ | Public | ✅ |
| POST | `/categories` | ✅ | Admin | ✅ |
| PUT | `/categories/{id}` | ✅ | Admin | ✅ |
| DELETE | `/categories/{id}` | ✅ | Admin | ✅ |

### Locations
| Method | Endpoint | Auth | Role | Status |
|--------|----------|------|------|--------|
| GET | `/locations` | ❌ | Public | ✅ |
| GET | `/locations/{id}` | ❌ | Public | ✅ |
| POST | `/locations` | ✅ | Admin | ✅ |
| PUT | `/locations/{id}` | ✅ | Admin | ✅ |
| DELETE | `/locations/{id}` | ✅ | Admin | ✅ |

---

## 🐛 Troubleshooting

### Backend Issues
**Q: 404 on API calls**
- A: Verify `.htaccess` is in `backend/public/`
- Check XAMPP Apache is running
- Verify base path in routes

**Q: CORS errors**
- A: Check CORS headers in `backend/public/index.php`
- Verify frontend URL matches allowed origins

### Frontend Issues
**Q: Cannot fetch categories/locations**
- A: Check backend is running on `http://localhost/CampusCare/backend/public`
- Verify API base URL in `frontend/src/services/api.js`
- Check browser console for errors

---

## 📞 Team Communication

- **Person 1:** Authentication & UI/UX
- **Person 2:** Maintenance requests (CREATE, READ, UPDATE, DELETE)
- **Person 3:** Admin dashboard & status updates
- **Person 4:** Category/Location management, testing, deployment

---

## 📄 License & Attribution

This project is for educational purposes as part of a full-stack development course.

---

## ✨ Summary

Person 4 has successfully implemented:
- ✅ Complete database schema with relationships
- ✅ Full CRUD APIs for categories and locations with JWT protection
- ✅ Admin-only frontend pages for managing categories and locations
- ✅ Form validation (frontend & backend)
- ✅ Error handling and user feedback
- ✅ Postman collection for API testing
- ✅ Seed data for testing

**Status:** Ready for integration with Person 2 & 3 modules ✅
