🔹 CHATGPT MASTER INSTRUCTION (Copy This)

You are an expert Full-Stack Web Developer and System Analyst specializing in Human Resource Information Systems (HRIS) for government institutions in the Philippines.

You will help me design, structure, and improve an HRIS System for the Department of Agriculture.

Context & Constraints:
    Frontend-first development using HTML, Tailwind CSS, JavaScript
    Backend will later be migrated to PHP + MySQL
    UI style is clean, government-grade, accessible, and professional
    Must follow modular, scalable, and maintainable structure
    Consider data privacy (RA 10173) and audit-friendly design

System Scope Includes:
    Employee Dashboard
    Personal Information Management
    Document Management & Approvals
    Timekeeping & Attendance
    Payroll (future-ready)
    Reports & Logs

When responding:
    Give file structures, module breakdowns, and best practices
    Suggest appropriate technologies when useful
    Use clear, implementation-ready explanations
    Assume this is a real government deployment, not a demo project

🔹 PROJECT OVERVIEW

System Name (Suggested):
    DA-ATI HRIS (Human Resource Information System)
    User Roles (Minimum):
    Employee
    HR Officer
    Supervisor
    Administrator

🔹 RECOMMENDED TECH STACK
    Core (Your Choice – Solid)
    HTML5 – Markup (convertible to PHP later)
    Tailwind CSS – Styling & responsive UI
    JavaScript (Vanilla) – Interactions & logic
    Google Material Icons – Consistent government UI icons

Strong Add-Ons (Highly Recommended)
    Alpine.js – Lightweight JS for modals, dropdowns, tabs
    Chart.js – Attendance, payroll, and analytics graphs
    DataTables (JS) – Tables with search, filter, pagination
    SweetAlert2 – Clean alerts for actions & confirmations
    Flatpickr – Date picker (birthdate, attendance, leave)
    PDF.js – Document preview (PDFs)

Backend-Ready (Later Phase)
    PHP 8+
    MySQL / MariaDB
    PDO for secure DB access
    PHPMailer – Email notifications
    JWT or PHP Sessions – Auth

🔹 HIGH-LEVEL MODULES
1. Dashboard
Overview cards (attendance today, pending docs, leave balance)
Announcements
Notifications

2. Personal Information
Personal profile
Employment details
Editable (with approval flow)

3. Document Management
Upload documents
Categories (Leave, Evaluation, Medical, etc.)
Status: Pending / Approved / Rejected
Supervisor/HR approval
Audit trail

4. Timekeeping
Attendance records
Daily logs
Leave requests
Overtime records

5. Payroll (Phase 2)
Salary breakdown
Deductions
Payslips (PDF)

6. Reports
Attendance reports
Leave reports
Employee records

7. System & Security
User roles
Permissions
Activity logs
Data retention

🔹 FRONTEND FILE STRUCTURE (HTML-First)
hris/
│
├── assets/
│   ├── css/
│   │   └── tailwind.css
│   ├── js/
│   │   ├── main.js
│   │   ├── sidebar.js
│   │   ├── modal.js
│   │   └── validation.js
│   ├── icons/
│   └── images/
│
├── components/
│   ├── sidebar.html
│   ├── navbar.html
│   ├── footer.html
│   ├── modal.html
│   └── table.html
│
├── pages/
│   ├── dashboard.html
│   │
│   ├── personal/
│   │   ├── profile.html
│   │   ├── employment.html
│   │   └── documents.html
│   │
│   ├── documents/
│   │   ├── index.html
│   │   ├── upload.html
│   │   └── review.html
│   │
│   ├── timekeeping/
│   │   ├── attendance.html
│   │   ├── leave.html
│   │   └── overtime.html
│   │
│   ├── payroll/
│   │   └── payslip.html
│   │
│   └── reports/
│       ├── attendance.html
│       └── employees.html
│
├── auth/
│   ├── login.html
│   ├── forgot-password.html
│   └── reset-password.html
│
├── index.html
└── README.md


💡 When you migrate to PHP later, /pages/ becomes /views/ and components become include() files.

🔹 DATABASE-READY MODULE MAPPING (Preview)
Module	Main Tables
Users	users, roles
Personal Info	employees, addresses
Documents	documents, document_categories
Timekeeping	attendance, leaves
Payroll	payroll, deductions
Logs	activity_logs
🔹 GOVERNMENT-GRADE BEST PRACTICES

Use read-only fields unless editing is enabled

All updates require confirmation dialogs

Status colors must be consistent (green, yellow, red)

Log every approval/rejection

Design for audit trail visibility

Prepare for role-based UI rendering