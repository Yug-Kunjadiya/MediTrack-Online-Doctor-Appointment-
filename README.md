# MediTrack — Online Doctor Appointment System

> A complete web-based healthcare appointment platform where patients find doctors, book time slots, chat in real-time, and download PDF invoices — while doctors manage their schedules and admins oversee the entire system.

---

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



## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Key Features](#2-key-features)
3. [Tech Stack](#3-tech-stack)
4. [System Architecture](#4-system-architecture)
5. [User Roles & Permissions](#5-user-roles--permissions)
6. [Complete Folder Structure](#6-complete-folder-structure)
7. [Database Schema](#7-database-schema)
8. [Page-by-Page Walkthrough](#8-page-by-page-walkthrough)
9. [Application Flow / User Journey](#9-application-flow--user-journey)
10. [Installation & Setup Guide](#10-installation--setup-guide)
11. [Default Login Credentials](#11-default-login-credentials)
12. [Configuration Reference](#12-configuration-reference)
13. [Security Measures](#13-security-measures)
14. [Known Limitations & Future Improvements](#14-known-limitations--future-improvements)

---

## 1. Project Overview

**MediTrack** is a full-stack PHP + MySQL web application that connects patients and doctors online. It solves the problem of phone-based appointment scheduling by allowing:

- Patients to **search for doctors** by name or specialization and **book a time slot** in seconds.
- Doctors to **publish their availability** (dates + times) and **manage appointments** from a clean dashboard.
- An admin to **oversee the entire platform** — add/edit/delete doctors and patients, manage all appointments and invoices.

All three user types have **separate, role-authenticated dashboards**. The frontend is Bootstrap 5 with vanilla JavaScript for dynamic features (slot selection, chat polling, star ratings via Fetch API).

---

## 2. Key Features

### For Patients
| Feature | Description |
|---------|-------------|
| Registration & Login | Secure patient account with bcrypt-hashed passwords |
| Find Doctors | Search by name or specialization with live filtering |
| View Availability | See available dates and time slots per doctor in real-time |
| Book Appointments | Select a date + time → submit → appointment created as `pending` |
| My Appointments | View upcoming & past appointments; cancel `pending`/`approved` ones |
| Real-Time Chat | Chat with the assigned doctor for any `approved` appointment |
| Reviews & Ratings | Leave a 1–5 star rating + comment after completing an appointment |
| Invoice Download | Download a professionally formatted PDF invoice |

### For Doctors
| Feature | Description |
|---------|-------------|
| Registration & Login | Separate doctor account with optional profile image upload |
| Dashboard Overview | See pending requests and today's schedule at a glance |
| Manage Availability | Add/remove time slots for any future date via an interactive UI |
| Appointment Actions | Approve, cancel, or mark appointments as completed |
| Patient Chat | Chat with patients for all `approved` appointments |
| Profile Management | Update profile photo and phone number |

### For Admins
| Feature | Description |
|---------|-------------|
| Secure Admin Login | Separate login page, queries its own `admin` table |
| Dashboard Stats | 6 live stat cards: appointments by status, doctor count, patient count |
| Manage Doctors | Add, edit, delete doctors; edit JSON availability slots |
| Manage Patients | Add, edit, delete patient accounts |
| All Appointments | Filter by status; approve, cancel, or complete any appointment |
| Manage Invoices | View all invoices, filter by status, update payment status + notes |
| PDF Invoice Access | Download any patient's invoice directly |

### System-Wide
- **Auto-complete** — Past `approved` appointments are auto-set to `completed` in the DB on page load.
- **Invoice auto-generation** — A PDF invoice (₹500.00 INR default) is auto-created when an appointment is approved.
- **Flash messages** — Success/warning/danger alerts stored in `$_SESSION['message']`, shown once, then cleared.
- **Session-based auth** — Role validated (`admin` / `doctor` / `user`) on every protected page.

---

## 3. Tech Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Web Server | **Apache** (XAMPP) | Serves PHP pages |
| Backend | **PHP 8.x** (procedural) | Logic, routing, DB queries |
| Database | **MySQL / MariaDB** | Relational data storage |
| UI Framework | **Bootstrap 5.3** | Responsive layout & components |
| Icons | **Bootstrap Icons 1.10** | All UI icons (vector) |
| Fonts | **Google Fonts** (Montserrat + Open Sans) | Typography |
| PDF Generation | **FPDF** (included in `/includes/fpdf/`) | Server-side PDF rendering |
| Dynamic UI | **Vanilla JavaScript + Fetch API** | Chat, slot select, star ratings |
| Session | **PHP native sessions** | Login state + flash messages |

---

## 4. System Architecture

```
Browser (Patient / Doctor / Admin)
         │
         ▼
  Apache Web Server  (http://localhost/meditrack/)
         │
         ▼
  ┌─────────────────────────────────────────────────────────┐
  │  PHP Application                                        │
  │                                                         │
  │  config/db.php  ──►  $conn (MySQLi)                     │
  │                  ──►  set_message()  display_message()  │
  │                  ──►  redirect()   check_login()        │
  │                                                         │
  │  Landing / Auth  ──  includes/header.php + footer.php   │
  │  Admin Panel     ──  admin/partials/admin_header.php    │
  │  Doctor Panel    ──  doctor/partials/doctor_header.php  │
  │  Patient Panel   ──  user/partials/user_header.php      │
  └─────────────────────────────────────────────────────────┘
         │
         ▼
  MySQL Database  (meditrack_db)
  ┌──────────┬────────────┬──────────────┬──────────┬─────────┬──────────┐
  │  admin   │   users    │   doctors    │appoint-  │invoices │ messages │
  │          │ (patients) │              │ments     │         │  (chat)  │
  └──────────┴────────────┴──────────────┴──────────┴─────────┴──────────┘
```

**Request lifecycle:**
1. Browser requests a PHP page.
2. Page includes its panel header (e.g. `admin/partials/admin_header.php`).
3. Panel header includes `config/db.php` → starts session, opens DB connection, defines helpers.
4. `check_login('admin')` validates session role — redirects unauthorized users.
5. Page logic runs (DB queries, form POST handling).
6. HTML output rendered to browser.
7. Panel footer closes layout, includes Bootstrap JS, closes `$conn`.

---

## 5. User Roles & Permissions

| Action | Admin | Doctor | Patient | Guest |
|--------|:-----:|:------:|:-------:|:-----:|
| View landing page | ✅ | ✅ | ✅ | ✅ |
| Patient registration | — | — | ✅ | ✅ |
| Doctor registration | — | — | — | ✅ |
| Admin login | ✅ | — | — | — |
| User / Doctor login | — | ✅ | ✅ | — |
| Browse doctors list | ✅ | ✅ | ✅ | ✅ |
| Book appointment | — | — | ✅ | — |
| Approve / cancel appointment | ✅ | ✅ (own) | ✅ (cancel own only) | — |
| Manage availability | — | ✅ | — | — |
| Chat | — | ✅ | ✅ | — |
| Leave review | — | — | ✅ (completed only) | — |
| Download invoice PDF | ✅ (all) | ✅ (own) | ✅ (own) | — |
| Manage all users/doctors | ✅ | — | — | — |
| Update invoice status | ✅ | — | — | — |

---

## 6. Complete Folder Structure

```
meditrack/
│
├── index.php                      # Public landing page
├── chat.php                       # Chat UI (loads correct header dynamically)
├── chat_actions.php               # AJAX: send_message & fetch_messages (JSON)
├── generate_invoice_pdf.php       # Streams FPDF invoice to browser
├── meditrack_db.sql               # ◄ IMPORT THIS — full DB schema + default admin
├── README.md                      # This documentation
├── .gitignore
│
├── config/
│   └── db.php                     # DB connect + set_message(), display_message(),
│                                  #   redirect(), check_login() helpers
│
├── auth/
│   ├── login.php                  # Login form (user / doctor role selector)
│   ├── register.php               # Patient self-registration
│   ├── doctor_register.php        # Doctor self-registration + image upload
│   ├── admin_login.php            # Admin-only login
│   └── logout.php                 # Session destroy + redirect home
│
├── includes/
│   ├── header.php                 # Global navbar for landing & auth pages
│   ├── footer.php                 # Global footer for landing & auth pages
│   └── fpdf/
│       ├── fpdf.php               # FPDF library core
│       └── font/                  # FPDF built-in font metric files
│
├── assets/
│   ├── css/style.css              # All custom styles (CSS variables, panel layouts)
│   ├── js/script.js               # Site-wide JS (sidebar active link)
│   └── img/
│       └── default_avatar.svg     # Fallback when no doctor photo uploaded
│
├── admin/
│   ├── index.php                  # Dashboard: stat cards + quick links
│   ├── manage_doctors.php         # List & delete doctors
│   ├── edit_doctor.php            # Add / edit doctor + availability JSON
│   ├── manage_users.php           # List & delete patients
│   ├── edit_user.php              # Add / edit patient
│   ├── view_appointments.php      # All appointments: filter + approve/cancel/complete
│   └── manage_invoices.php        # All invoices: filter + update status modal
│   └── partials/
│       ├── admin_header.php       # Admin navbar, check_login('admin'), opens layout
│       ├── admin_sidebar.php      # Sidebar nav + mobile offcanvas
│       └── admin_footer.php       # Closes layout, JS includes, closes $conn
│
├── doctor/
│   ├── index.php                  # Dashboard: profile card + pending + today's schedule
│   ├── manage_availability.php    # Interactive date/slot manager (JS heavy)
│   └── view_appointments.php      # Doctor's appointments: filter + actions
│   └── partials/
│       ├── doctor_header.php      # Doctor navbar, check_login('doctor'), opens layout
│       ├── doctor_sidebar.php     # Sidebar nav links
│       └── doctor_footer.php      # Closes layout, JS includes
│
├── user/
│   ├── index.php                  # Dashboard: upcoming appointments + quick actions
│   ├── view_doctors.php           # Doctor cards with search/filter + async ratings
│   ├── book_appointment.php       # Slot picker + booking form + reviews section
│   ├── my_appointments.php        # Accordion: upcoming & past + review modal
│   ├── get_doctor_reviews.php     # AJAX: returns JSON { average_rating, reviews[] }
│   └── submit_review.php          # POST handler for review submissions
│   └── partials/
│       ├── user_header.php        # Patient navbar, check_login('user'), opens layout
│       ├── user_sidebar.php       # Sidebar nav links
│       └── user_footer.php        # Closes layout, JS includes
│
└── uploads/
    └── doctors/                   # Doctor profile images (auto-created on first upload)
        └── .gitkeep
```

---

## 7. Database Schema

Database name: **`meditrack_db`** — 7 tables with foreign key constraints and cascading deletes.

### `admin` — Administrator accounts
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AI | |
| `name` | VARCHAR(150) | Display name |
| `email` | VARCHAR(150) UNIQUE | Login email |
| `password` | VARCHAR(255) | bcrypt hash |
| `created_at` | TIMESTAMP | Auto-set on INSERT |

### `users` — Patient accounts
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AI | |
| `name` | VARCHAR(150) | |
| `email` | VARCHAR(150) UNIQUE | |
| `password` | VARCHAR(255) | bcrypt hash |
| `role` | ENUM('user') | Always `'user'` |
| `created_at` | TIMESTAMP | |

### `doctors` — Doctor accounts + availability
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AI | |
| `name` | VARCHAR(150) | |
| `email` | VARCHAR(150) UNIQUE | |
| `specialization` | VARCHAR(150) | e.g. "Cardiologist" |
| `password` | VARCHAR(255) | bcrypt hash |
| `profile_image` | VARCHAR(255) | Filename stored in `uploads/doctors/` |
| `role` | ENUM('doctor') | Always `'doctor'` |
| `phone_number` | VARCHAR(30) | Optional, updated from doctor dashboard |
| `available_slots_json` | TEXT | JSON object (see format below) |
| `created_at` | TIMESTAMP | |

**`available_slots_json` Format:**
```json
{
  "2026-03-10": ["09:00", "10:00", "11:30"],
  "2026-03-11": ["14:00", "15:00", "16:30"]
}
```
Keys = dates (`YYYY-MM-DD`), values = arrays of 24h time strings (`HH:MM`).

### `appointments` — Booking records
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AI | |
| `user_id` | INT FK | → `users.id` ON DELETE CASCADE |
| `doctor_id` | INT FK | → `doctors.id` ON DELETE CASCADE |
| `appointment_date` | DATE | |
| `appointment_time` | TIME | Stored as `HH:MM:SS` |
| `status` | ENUM | `pending` → `approved` → `completed` (or `cancelled`) |
| `created_at` | TIMESTAMP | |

**Status Transition Diagram:**
```
   [pending]
      │  └──► [cancelled]  (by patient, doctor, or admin)
      ▼
  [approved]  ──────────────────────────────────────► [completed]
      │                                           (auto when datetime passes,
      └──► [cancelled]  (by doctor or admin)       or manual by doctor/admin)
```

### `invoices` — Auto-generated billing records
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AI | |
| `appointment_id` | INT FK UNIQUE | One invoice per appointment max |
| `user_id` | INT FK | |
| `doctor_id` | INT FK | |
| `invoice_uid` | VARCHAR(100) UNIQUE | e.g. `INV-5F3A2B-42` |
| `amount` | DECIMAL(10,2) | Default: 500.00 |
| `currency` | VARCHAR(10) | Default: `INR` |
| `status` | ENUM | `unpaid` / `paid` / `cancelled` |
| `payment_details` | TEXT | Admin notes (e.g. transaction ID) |
| `due_date` | DATE | Created_at + 7 days |
| `created_at` | TIMESTAMP | |

### `reviews` — Patient feedback per appointment
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AI | |
| `appointment_id` | INT FK UNIQUE | One review per appointment enforced by DB |
| `user_id` | INT FK | The reviewing patient |
| `doctor_id` | INT FK | The reviewed doctor |
| `rating` | TINYINT | 1–5 stars (CHECK constraint) |
| `comment` | TEXT | Optional written feedback |
| `created_at` | TIMESTAMP | |

### `messages` — Per-appointment chat messages
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AI | |
| `appointment_id` | INT FK | Chat scoped to one appointment |
| `sender_id` | INT | ID in `users` or `doctors` table |
| `sender_role` | ENUM('user','doctor') | Identifies which table to look up sender |
| `receiver_id` | INT | |
| `receiver_role` | ENUM('user','doctor') | |
| `message_text` | TEXT | Raw message content |
| `timestamp` | DATETIME | Auto-set on INSERT |

---

## 8. Page-by-Page Walkthrough

### Public Pages

**`/meditrack/index.php`** — Landing Page
- Hero banner with context-sensitive CTA: Login/Register buttons for guests; "Go to Dashboard" for logged-in users.
- "For Patients" and "For Doctors" feature panels.
- "How It Works" 3-step section (Find Doctor → Book → Get Care).

---

### Auth Pages

**`/auth/login.php`**
- Email + password form with a role dropdown (`user` or `doctor`).
- Queries `users` or `doctors` table based on role selection.
- On success: `session_regenerate_id(true)`, session vars set, redirect to `/{role}/index.php`.

**`/auth/register.php`** — Patient Registration
- Fields: Full Name, Email, Password, Confirm Password.
- Validates: all required, valid email format, password ≥ 6 chars, passwords match, email uniqueness.
- Stores bcrypt hash. Redirects to login on success.

**`/auth/doctor_register.php`** — Doctor Registration
- Additional fields: Specialization, optional Profile Image upload.
- Image validated: MIME type (JPEG/PNG/GIF only), max 2 MB, renamed with `uniqid()`, saved in `uploads/doctors/`.
- DB insert includes `available_slots_json = '{}'` (empty, set later from dashboard).

**`/auth/admin_login.php`**
- Queries only the `admin` table. Sets `$_SESSION['role'] = 'admin'`.

**`/auth/logout.php`**
- `$_SESSION = []`, `session_destroy()`, redirect to `/meditrack/index.php`.

---

### Patient (User) Portal *(requires `check_login('user')`)*

**`/user/index.php`** — Patient Dashboard
- Up to 3 upcoming `pending`/`approved` appointments shown as a list card.
- Quick action buttons: "Find & Book Doctor", "My Appointments".

**`/user/view_doctors.php`** — Find a Doctor
- Doctor cards: photo, name, specialization, async star rating.
- Filter by doctor name (SQL `LIKE` search) and/or specialization (dropdown populated from DB).
- Star ratings loaded via `fetch()` calls to `get_doctor_reviews.php` for each card.

**`/user/book_appointment.php?doctor_id=X`**
- Doctor profile on the left (photo, name, spec, avg rating loaded async).
- Date dropdown: only future dates with ≥1 slot from `available_slots_json`.
- Time dropdown: populated via JavaScript when a date is selected. Already-booked slots (status `pending`/`approved` in DB) are excluded.
- POST handler: validates slot still available → inserts `appointments` row as `pending`.
- Reviews section below lists existing patient reviews for that doctor.

**`/user/my_appointments.php`**
- **Accordion layout** with two sections: "Upcoming & Pending" and "Past & Cancelled".
- Upcoming: Chat button (if `approved`), Cancel button.
- Past/completed: "Leave a Review" button (if not yet reviewed) or "Reviewed ✓" indicator.
- Invoice details shown inline for `approved` appointments with a linked invoice.
- **Review modal**: CSS-only 5-star input (RTL flip trick) + optional comment textarea → POST to `submit_review.php`.

**`/user/get_doctor_reviews.php`** — JSON API
- GET `?doctor_id=X` → returns `{ success, average_rating, total_reviews, reviews[] }`.
- `reviews[]` items include: user name, rating, comment (nl2br), formatted timestamp.

**`/user/submit_review.php`**
- POST only. Verifies:
  1. Appointment belongs to the session user and has `status = 'completed'`.
  2. No review already exists for the appointment (UNIQUE KEY guard).
- Inserts into `reviews`.

---

### Doctor Portal *(requires `check_login('doctor')`)*

**`/doctor/index.php`** — Doctor Dashboard
- **Profile card**: circular photo, name, spec, email, phone. Buttons open modals to update photo / phone.
- **Pending Requests**: up to 5 latest with inline Approve/Cancel links. "View All" link if more than 5.
- **Today's Schedule**: all `approved` appointments with `appointment_date = CURDATE()`.
- Profile image and phone number updated via POST on the same page.

**`/doctor/manage_availability.php`**
- DB loads `available_slots_json`, normalized and sorted.
- **Date picker** (min = today): selecting a date reveals slot management panel.
- Slot list: each slot has a ✕ remove button. "Add New Slot" adds a time from a time input.
- All changes are held in a JavaScript object in memory. A hidden `<input>` is updated before submit with the full JSON.
- On POST: PHP validates JSON structure (regex checks for date + HH:MM formats), sorts, deduplicates, writes back to DB.
- A `<pre>` overview shows the full current schedule in real-time as the doctor edits.

**`/doctor/view_appointments.php`**
- Doctor's own appointments only; filter dropdown by status.
- Actions: Approve (+ invoice auto-generated), Cancel, Mark Completed.
- Auto-complete of past `approved` appointments runs once per page load.

---

### Admin Panel *(requires `check_login('admin')`)*

**`/admin/index.php`** — Dashboard
- 6 stat cards (blue/green/yellow/red/cyan/grey): Total, Approved, Pending, Cancelled appointments; Total Doctors, Total Patients.
- System Management list with live badge counts.

**`/admin/manage_doctors.php`**
- Sortable table: ID, Name, Specialization, Email, Phone.
- Edit → `edit_doctor.php?id=X`. Delete → deletes DB row + unlinks profile image file.

**`/admin/edit_doctor.php`**
- Add mode: all fields + mandatory password.
- Edit mode: password optional (leave blank = no change).
- Raw JSON textarea for `available_slots_json` with format example hint.

**`/admin/manage_users.php`** / **`edit_user.php`**
- Same pattern as doctors but for patient accounts. Delete cascades all related data via FK.

**`/admin/view_appointments.php`**
- Full appointment list, status filterable. Each row has an Actions dropdown.
- Approve action also auto-generates an invoice if one doesn't already exist.
- Auto-complete of ALL past approved appointments runs on each page load.

**`/admin/manage_invoices.php`**
- Invoice table with UID, patient, doctor, amount, status badge, dates, PDF link.
- "Edit Status" button opens a Bootstrap modal (populated via JS `data-*` attributes):
  - Select new status: `unpaid` / `paid` / `cancelled`.
  - Optional payment notes textarea.
  - Submits POST to update `invoices.status`.

---

### Shared / Utility Files

**`/chat.php`**
- Detects session role and includes either `user_header.php` or `doctor_header.php`.
- Verifies appointment exists, is `approved`, and the two participants match.
- Chat box polls `chat_actions.php` every 3.5 seconds for new messages (`last_message_id` sent to avoid duplicates).
- Sent messages are blue (right-aligned); received messages are grey (left-aligned).
- Send via `fetch()` POST; the returned message is appended immediately without waiting for the next poll.

**`/chat_actions.php`** — AJAX Endpoint (JSON)
- `action=send_message` (POST): verifies participants → inserts message → returns the new message object.
- `action=fetch_messages` (GET): returns all or only new messages (since `last_message_id`) for an appointment.

**`/generate_invoice_pdf.php`**
- Access control: admin = any invoice; user/doctor = only own invoices.
- FPDF renders an A4 PDF with:
  - Custom `Header()` and `Footer()` (with page number + thank-you note).
  - Patient & doctor info block.
  - `FancyTable()` with zebra-striped rows: Description, Qty, Unit Price, Total.
  - Invoice metadata: UID, status, issued date, due date.
- Outputs with `Content-Disposition: inline` for browser preview + download.

---

## 9. Application Flow / User Journey

### Patient Journey
```
[Guest] ──► Register ──► Login (role: user)
                              │
                              ▼
                    Patient Dashboard
                              │
                              ▼
                    Find a Doctor (filter by name/spec)
                              │
                              ▼
                    Book Appointment
                    (pick date → pick time → submit)
                              │
                    ┌─────────┴──────────┐
              Slot available?          Slot taken?
                    │                     └──► Error, try again
                    ▼
          Appointment: [pending]
                    │
          Doctor/Admin approves
                    │
                    ▼
          Appointment: [approved]
          Invoice auto-created (₹500 INR)
                    │
          ┌─────────┼──────────────────┐
          ▼         ▼                  ▼
       Chat     Download PDF      Cancel (if needed)
                    │
          Datetime passes (auto)
                    │
                    ▼
          Appointment: [completed]
                    │
                    ▼
            Leave a Review (1–5 ★)
```

### Doctor Journey
```
[Guest] ──► Doctor Register ──► Login (role: doctor)
                                        │
                                        ▼
                            Manage Availability
                            (add dates + time slots)
                                        │
                                        ▼
                          Patient books → [pending] appears on dashboard
                                        │
                                        ▼
                                  Approve / Cancel
                                        │
                                 (if approved)
                                        ▼
                           Chat with patient  /  Mark Completed
```

### Admin Journey
```
Admin Login ──► Dashboard (live stats)
                   │
      ┌────────────┼───────────────────────────────┐
      ▼            ▼                               ▼
Manage Doctors  View All Appointments       Manage Invoices
(add/edit/del)  (approve/cancel/complete)   (update status/PDF)
      │
Manage Patients
(add/edit/del)
```

---

## 10. Installation & Setup Guide

### Prerequisites
- **XAMPP 8.x+** (Apache + MySQL + PHP 8.x) — [Download](https://www.apachefriends.org/)
- **Git** — [Download](https://git-scm.com/)
- A modern browser (Chrome, Firefox, Edge)
- Internet connection (Bootstrap & Google Fonts load from CDN)

### Step 1 — Get the code
```bash
git clone https://github.com/Yug-Kunjadiya/MediTrack-Online-Doctor-Appointment-.git C:\xampp\htdocs\meditrack
```
Or download the ZIP from GitHub and extract to `C:\xampp\htdocs\meditrack`.

### Step 2 — Start XAMPP
Open the XAMPP Control Panel and click **Start** for both **Apache** and **MySQL**.

### Step 3 — Import the database
1. Open `http://localhost/phpmyadmin` in your browser.
2. Click **Import** in the top navigation.
3. Click **Choose File** → select `C:\xampp\htdocs\meditrack\meditrack_db.sql`.
4. Click **Go** at the bottom.
5. You should see `meditrack_db` appear in the left panel with 7 tables.

### Step 4 — Verify configuration
Open `config/db.php` and confirm:
```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');        // Leave empty for default XAMPP
define('DB_NAME', 'meditrack_db');
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, 3306);
```
> Change `DB_PASSWORD` only if you set a MySQL root password.

### Step 5 — Open in browser
```
http://localhost/meditrack/
```

The site is now fully functional.

---

## 11. Default Login Credentials

> **Change admin password immediately after first login.**

### Admin
| URL | Email | Password |
|-----|-------|----------|
| `http://localhost/meditrack/auth/admin_login.php` | `admin@meditrack.com` | `admin123` |

### Patients & Doctors
Use the self-registration pages:
- Patient: `http://localhost/meditrack/auth/register.php`
- Doctor: `http://localhost/meditrack/auth/doctor_register.php`

---

## 12. Configuration Reference

| Setting | Location | Default | When to Change |
|---------|----------|---------|----------------|
| DB host | `config/db.php` | `localhost` | Remote/cloud DB |
| DB port | `config/db.php` | `3306` | Non-standard MySQL port |
| DB name | `config/db.php` | `meditrack_db` | Rename the database |
| Base URL | Every file (`$base_url`) | `/meditrack` | If folder renamed |
| Invoice amount | `doctor/view_appointments.php`, `admin/view_appointments.php` | `500.00` | Change consultation fee |
| Invoice currency | Same files | `'INR'` | Change to `'USD'` etc. |
| Max upload size | `auth/doctor_register.php`, `doctor/index.php` | `2 MB` | Larger profile images |
| Session start | `config/db.php` | Auto | — |

---

## 13. Security Measures

| Threat | Protection |
|--------|-----------|
| **SQL Injection** | All user input uses MySQLi **prepared statements** (`?` placeholders) — no raw string interpolation in queries |
| **XSS** | Every value echoed in HTML goes through `htmlspecialchars()` |
| **Password Storage** | `password_hash(PASSWORD_DEFAULT)` (bcrypt) on register; `password_verify()` on login |
| **Session Fixation** | `session_regenerate_id(true)` called immediately after every successful login |
| **Unauthorized Access** | `check_login($role)` on every protected page — wrong role gets redirected |
| **File Upload Attacks** | MIME type verified with `mime_content_type()` (not just extension), size capped at 2 MB, filename replaced with random `uniqid()` |
| **Invoice Snooping** | `generate_invoice_pdf.php` adds `WHERE i.user_id = ?` or `WHERE i.doctor_id = ?` restricting access to own invoices only |
| **Duplicate Bookings** | Slot is re-verified in DB at time of booking POST before inserting |
| **Duplicate Reviews** | UNIQUE KEY on `reviews.appointment_id` + PHP pre-check prevents double reviews |

---

## 14. Known Limitations & Future Improvements

| Area | Current State | Suggested Improvement |
|------|--------------|----------------------|
| **CSRF Protection** | No token on forms | Add CSRF tokens to all POST forms |
| **Email Notifications** | None | PHPMailer: notify patient on approval/cancellation |
| **Online Payment** | Invoice created, payment tracked manually | Integrate Razorpay / Stripe |
| **Chat Technology** | AJAX polling every 3.5 s | Upgrade to WebSockets for true push |
| **Pagination** | No pagination on lists | Add paginator on appointments, invoices, doctors |
| **Password Reset** | Not implemented | Forgot-password with email token |
| **Appointment Reminders** | None | Cron job → email/SMS 24 h before |
| **Multi-language** | English only | i18n / gettext support |
| **Unit Tests** | None | PHPUnit for DB logic |
| **Environment Config** | Hard-coded in PHP | `.env` file for production settings |
| **Admin-Created Doctors** | No email sent to doctor | Auto-email with temp password |
| **Invoice Amount** | Fixed ₹500 for all | Per-doctor or per-specialization fee config |

---

## License

This project is open-source and available under the [MIT License](LICENSE).

---

*Built with ❤️ by Yug Kunjadiya — MediTrack, 2026*
