# Agricultural Training Institute HRIS

This project is a Human Resource Information System for the Agricultural Training Institute. It brings together day-to-day HR work in one web-based system so different users can manage records, follow processes, and track updates in a more organized way.

The system includes separate areas for applicants, employees, staff, and administrators. Across those areas, the workspace already contains modules for recruitment, personal information, document handling, timekeeping, payroll, reports, notifications, support, evaluation, PRAISE, and learning and development.

## What the system is for

The HRIS is designed to support common HR and workforce activities such as:

- posting and viewing career opportunities
- tracking job applications and applicant requirements
- managing employee records and profile information
- handling HR documents and document previews
- monitoring attendance, timekeeping, and related requests
- viewing payroll information and downloadable payslips
- generating reports and analytics views
- sending updates and notifications to users
- supporting recognition, evaluation, and learning activities

## Who uses it

- Applicants can browse openings, apply for positions, submit documents, and monitor their application progress.
- Employees can review personal records, manage documents, check timekeeping and payroll information, and access reports, support, and notifications.
- Staff users have operational pages for recruitment, document management, evaluation, payroll-related work, reports, and employee support.
- Admin users have broader oversight pages for dashboards, user management, recruitment, analytics, settings, and system-wide HR workflows.

## Main sections in the workspace

- `pages/applicant` for the applicant-facing experience
- `pages/employee` for employee self-service pages
- `pages/staff` for staff operational pages
- `pages/admin` for administrative dashboards and management pages
- `pages/auth` for login and account access flows
- `api` for server-side endpoints such as public careers data
- `assets/js` for shared and role-based frontend behavior
- `storage` for generated files such as documents, payslips, reports, and support attachments
- `tools` for internal helper scripts and QA utilities
- `revisions` for implementation notes, checklists, and setup references

## Technologies used

This system is built with a practical web stack already visible in the repository:

- PHP for page rendering, server-side processing, and API endpoints
- HTML and CSS for page structure and styling
- Tailwind CSS for much of the interface styling
- Vanilla JavaScript and ES modules for frontend interactions
- Supabase for backend-connected data access and realtime notifications in some flows
- JSON data files for location and reference data
- Chart.js for analytics and chart displays
- Swiper.js for the public homepage slider
- Google Material Icons for interface icons
- PHPMailer for email sending
- Dompdf for PDF generation
- PhpSpreadsheet for spreadsheet and export features

## Current state of the project

The repository already contains a broad set of working pages and role-based layouts. It is more than a simple prototype: the system has clear module coverage, shared scripts, storage areas, public landing pages, and supporting tools.

Some parts of the project are still evolving, especially where live data, integrations, or environment-based services are involved. For example, the codebase shows integration points for Supabase-backed data and notifications, and the revisions folder includes setup and QA materials that support ongoing development.

## Notes

This README is intended to describe the system in plain language. It focuses on what the system covers and the technologies present in the repository, without going deep into internal implementation details.
