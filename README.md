# Talent Gate - Backend

This is the Laravel backend API for the **Talent Gate** job board application.

## How to Run the Application (Backend & Frontend)

This application consists of a Laravel backend and a Vue 3 frontend. To run the full application locally, you need to start both servers concurrently.

### 1. Backend Setup (Laravel)

1. Open a terminal and navigate to the `backend` directory:
   ```bash
   cd backend
   ```
2. Install PHP dependencies via Composer:
   ```bash
   composer install
   ```
3. Create a copy of the environment file:
   ```bash
   cp .env.example .env
   ```
4. Generate the Laravel application key:
   ```bash
   php artisan key:generate
   ```
5. Run database migrations and seeders to initialize the database with sample data:
   ```bash
   php artisan migrate --seed
   ```
6. Start the local development server:
   ```bash
   php artisan serve
   ```
   The backend API will be available at `http://localhost:8000`.

### 2. Frontend Setup (Vue 3)

1. Open a **new terminal** and navigate to the `frontend` directory:
   ```bash
   cd frontend
   ```
2. Install Node.js dependencies:
   ```bash
   npm install
   ```
3. Create a copy of the environment file:
   ```bash
   cp .env.example .env
   ```
4. Start the Vite development server:
   ```bash
   npm run dev
   ```
   The frontend application will be available at `http://localhost:5173`. It is configured to automatically proxy `/api` requests to the local backend server (`http://localhost:8000`).

---

**Note:** You can sign in to the application using the seeded test accounts (e.g., `admin@talentgate.test` / `password`).
