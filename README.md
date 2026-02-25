# MediTrack - Online Doctor Appointment System

MediTrack is a full-featured online doctor appointment booking platform built with PHP, MySQL, and Bootstrap 5.

## Features

- **Patient Portal** – Register, find doctors by specialization, book appointments, view invoices, chat with doctors, and leave reviews.
- **Doctor Portal** – Manage availability (date/time slots), view and approve/cancel appointments, chat with patients.
- **Admin Panel** – Manage doctors and patients, view all appointments, manage invoices, approve/cancel appointments.
- **Invoice Generation** – PDF invoices generated automatically via FPDF when an appointment is approved.
- **Real-Time Chat** – In-appointment chat between patients and doctors using AJAX polling.
- **Star Rating & Reviews** – Patients can leave reviews for completed appointments.
  
## Demo

<img width="1891" height="787" alt="Screenshot 2025-06-27 221553" src="https://github.com/user-attachments/assets/401a2f42-f024-4ca4-b5c1-7a552c90246b" />
<img width="1776" height="574" alt="image" src="https://github.com/user-attachments/assets/3a92d862-b770-42f9-bbaa-8cb6d4c60e1f" />
<img width="1828" height="542" alt="image" src="https://github.com/user-attachments/assets/93ede4b1-87a9-4d65-ba08-48933b8dec3e" />

<img width="1898" height="866" alt="Screenshot 2025-07-10 234204" src="https://github.com/user-attachments/assets/91aac799-9d54-46c8-82d5-3bbfdd0546c5" />
<img width="1900" height="871" alt="Screenshot 2025-07-10 234213" src="https://github.com/user-attachments/assets/9f8962e0-0342-4cf3-87db-8084d6475eff" />

<img width="1900" height="870" alt="Screenshot 2025-07-10 234224" src="https://github.com/user-attachments/assets/128aa7ab-5be6-4214-9960-408aa6371ab4" />
<img width="1895" height="864" alt="Screenshot 2025-07-10 234337" src="https://github.com/user-attachments/assets/2f7b23fc-9277-4bf0-8a4a-44e11b12ed22" />




## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.x (procedural) |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5.3, Bootstrap Icons |
| PDF | FPDF Library |
| Server | Apache (XAMPP) |

## Installation

### Prerequisites
- XAMPP (PHP 8.x + Apache + MySQL)
- Git

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/Yug-Kunjadiya/MediTrack-Online-Doctor-Appointment-.git
   cd MediTrack-Online-Doctor-Appointment-
   ```

2. **Place in XAMPP htdocs**
   ```
   C:\xampp\htdocs\meditrack\
   ```

3. **Import the database**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Click **Import** → choose `meditrack_db.sql`
   - Click **Go**

4. **Configure the database** *(if needed)*
   Edit `config/db.php`:
   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');       // Your MySQL password
   define('DB_NAME', 'meditrack_db');
   $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, 3306);
   ```

5. **Start XAMPP** – Start Apache and MySQL.

6. **Open in browser**
   ```
   http://localhost/meditrack/
   ```

## Default Admin Login

| Field | Value |
|-------|-------|
| Email | admin@meditrack.com |
| Password | admin123 |

> **Important:** Change the admin password immediately after first login.

## Folder Structure

```
meditrack/
├── admin/              # Admin panel pages
│   └── partials/       # Admin header, sidebar, footer
├── auth/               # Login, register, logout pages
├── assets/
│   ├── css/style.css   # Custom styles
│   └── js/script.js    # Custom scripts
├── config/db.php       # Database connection & helper functions
├── doctor/             # Doctor portal pages
│   └── partials/       # Doctor header, sidebar, footer
├── includes/           # Global header, footer, FPDF library
├── uploads/doctors/    # Doctor profile image uploads
├── user/               # Patient portal pages
│   └── partials/       # User header, sidebar, footer
├── chat.php            # Chat interface
├── chat_actions.php    # Chat AJAX handler
├── generate_invoice_pdf.php  # PDF invoice generation
├── index.php           # Home/landing page
└── meditrack_db.sql    # Database schema (import this!)
```

## License

This project is open-source and available under the [MIT License](LICENSE).
