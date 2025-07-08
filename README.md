# Secure PHP Login & Admin Panel System

This is a **secure PHP-based user registration and login system** with an **admin dashboard**, **JWT authentication**, **file upload**, **PDF export**, and full **role-based access control**.

## 🔐 Features

- ✅ Secure login with hashed passwords (using `password_hash`)
- 🔑 Admin login using **JWT tokens** (10-minute expiry)
- 📁 Secure file uploads (PDF + Profile Picture)
- 👤 Separate dashboards for Admin and Users
- 🔄 Session & Cookie management
- 📄 Export user data to Excel (CSV)
- 🕵️ Admin can:
  - View all users
  - Edit/Delete users
  - Filter/Search by name, email, department
- 🧾 PDF Upload + Preview
- 🌈 User preferences: Color, Feedback, DOB, etc.
- **🛡️ CSRF Protection Implemented:** Enhanced security against Cross-Site Request Forgery attacks for critical state-changing actions.
- **🚪 Force Logout from Other Sessions:** Automatically invalidates old login sessions upon password change or a new successful login, enhancing account security.
- **🔄 Smart Password Reset/Change:** Handles password changes for both logged-in and logged-out users, maintaining the session for currently logged-in users.

## 🛠️ Technologies Used

- PHP (Core + MySQLi)
- MySQL (XAMPP)
- JWT (Firebase PHP JWT Library)
- SweetAlert2 for beautiful alerts
- Composer (for dependencies)
- HTML/CSS for frontend

## 📁 Folder Structure

/5thproject
│
├── auth_helper.php # JWT & auth functions (now includes session invalidation check)
├── db.php # DB connection
├── user_login.php # Login form
├── user_register.php # Registration form
├── process_login.php # Login backend (now generates session_id_version)
├── submit.php # Registration backend
├── dashboard_user.php # User dashboard
├── dashboard_admin.php # Admin dashboard
├── all_users.php # Admin: view users
├── user_view.php # User: view own profile
├── serve_file.php # Secure file serving
├── export_users_to_excel.php
├── logout.php
├── uploads/ # Profile pictures + PDFs (secured)
├── admin_delete_user.php # Admin: delete users (now with CSRF protection)
├── approve_delete.php # Email-based deletion approval (now with enhanced security)
├── forgot_password.php # Initiates password reset
├── reset_verify.php # Verifies OTP for password reset
├── reset_password.php # Sets new password (now with CSRF & session update)
├── change_password.php # Sets new password in his dashboard if login(now with CSRF & session update)
├── csrf_helper.php # Functions for CSRF token generation and validation
└── README.md


## 🚀 How to Use

1. Clone this repo or copy files to your XAMPP `htdocs` folder.
2. Run database SQL script to create table (remember to add `session_id_version` column to `info` table).
3. Open `user_register.php` in browser.
4. Register and then login.
5. For admin access:
   - Manually set a user's role to `'admin'` in DB.
   - Login as that user to access admin dashboard.

## 🧪 Security Notes

- JWT token stored in `HttpOnly` cookie
- Files are served using `serve_file.php` with proper validation
- Session is cleared on logout
- Passwords hashed with `password_hash()` and verified using `password_verify()`
- SQL queries use `prepare()` and `bind_param()` to prevent SQL Injection
- **Robust CSRF Protection:** All state-changing POST requests (like user deletion, password reset) are protected using hidden CSRF tokens and server-side validation.
- **Session Invalidation on Password Change:** Upon a successful password reset or new login, all other active sessions for that user are automatically logged out, preventing unauthorized access from compromised sessions.
- **Secure File Handling:** Uploaded files are stored in a secure directory outside the web root and served securely through `serve_file.php` to prevent direct access.

---

## 🧑‍💻 Author

**Muhammad Hassan** Intern Backend Developer | PHP | Node.js | AWS | Laravel (in progress)

---

## ❤️ Support

If you like this project, consider giving it a ⭐ on GitHub!
