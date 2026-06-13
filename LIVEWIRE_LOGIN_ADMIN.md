# Laravel Livewire Login & Admin Pages

This documentation explains how to use the Login and Admin pages created with Laravel Livewire.

## Setup

### 1. Installation
Livewire is already installed and configured in this project.

### 2. Database Setup
The database has been updated to include a `role` column in the users table:

- `admin` role: Access to admin-only pages
- `user` role: Regular user access

### 3. Users Created

**Admin User:**
- Email: `admin@example.com`
- Password: `admin`

**Regular User:**
- Email: `user@example.com`
- Password: `user`

## Features

### Login Page (`/login`)
- Username/email authentication
- Password authentication
- Remember me functionality
- Role-based redirection
- Error handling for invalid credentials

### Admin Page (`/admin`)
- Protected by admin middleware
- Displays user information
- Logout functionality
- Admin-specific features displayed for admin users

### Dashboard Page (`/dashboard`)
- Protected by admin middleware
- Main admin dashboard

## Route Structure

- `/login` - Login page
- `/admin` - Admin panel (requires admin role)
- `/dashboard` - Main dashboard (requires admin role)

## Middleware

The `IsAdmin` middleware ensures that only users with the `admin` role can access admin-protected routes.

## Testing the Login System

1. Access the login page at http://127.0.0.1:8000/login
2. Use admin credentials (admin@example.com / admin) to log in
3. You will be redirected to the admin panel at http://127.0.0.1:8000/admin
4. Try logging out and logging in with a regular user (user@example.com / user)
5. Regular users will be redirected to their intended pages after login

## Notes

- Make sure the Laravel development server is running: `php artisan serve`
- The project uses Tailwind CSS for styling
- Livewire handles all form submissions and authentication logic
- All protected routes check for the admin role before allowing access