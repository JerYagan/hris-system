# Department of Agriculture  
## Human Resource Information System (DA HRIS)

---

## Overview

The **Department of Agriculture Human Resource Information System (DA HRIS)** is a web-based platform designed to centralize and streamline human resource operations for the Department of Agriculture. The system supports employee information management, timekeeping, payroll, document handling, and administrative HR processes in a secure and structured environment.

The project is currently in the **frontend-first development phase** with PHP-based page structuring. Core UI flows are in place for multiple roles, while database integration and full backend processing remain in the next phase.

---

## Project Status

- Frontend UI development in active progress  
- Multi-role page structure established (`admin`, `employee`, `applicant`, `staff`, `auth`)  
- Applicant-side experience redesigned to be consumer-friendly (top navigation, responsive dropdown categories, card-based views)  
- Shared layout architecture implemented through PHP includes per role  
- Employee-side UI implemented and structured  
- Admin and staff modules scaffolded with functional page shells  
- Backend integration pending  
- Authentication and role-based access control still in implementation phase  

---

## Technology Stack

### Frontend
- HTML5  
- Tailwind CSS  
- Vanilla JavaScript  
- Google Material Symbols / Material Icons  
- Chart.js  

### Current Application Layer
- PHP (page rendering and include-based layouts)  

### Planned Backend/Data Layer
- PHP  
- MySQL  
- Session-based authentication  

---

## User Roles

The workspace currently contains dedicated page groups for:

- Applicant  
- Employee  
- Staff  
- Admin  
- Authentication views (`login`, `forgot/reset password`, access request)

### Employee

Employees are standard users of the system and can:

- View the employee dashboard  
- Manage personal information  
- Upload and track document status  
- Submit and monitor leave requests  
- View payroll summaries and payslip history  
- Access PRAISE records  
- Generate personal reports  

### Admin

Administrators are authorized HR personnel responsible for system-wide operations. Admin functionality is planned and will include:

- Viewing the administrative dashboard  
- Managing recruitment processes  
- Approving or rejecting employee requests  
- Managing user accounts  
- Generating HR analytics and reports  
- Configuring system settings  

---

## Applicant Pages Overview (Implemented UI)

| Page | Description |
|-----|------------|
| Dashboard | Applicant journey overview, status cards, quick actions, and recent updates |
| Job Listings | Card-based vacancy browsing with filter controls |
| Job Details | Detailed vacancy information with application CTA |
| Submit Application | Full-page application form (position, qualifications, document uploads) |
| My Applications | Application timeline, milestones, and next-step guidance |
| Application Feedback | Decision view using status-based feedback state |
| Notifications | Inbox-style updates for application and system messages |
| Profile | View/edit account profile information |
| Support | Contact HR, FAQ, and Helpdesk sections |

---

## Employee Pages Overview

| Page | Description |
|-----|------------|
| Dashboard | Overview of employee requests, notifications, and quick actions |
| Personal Information | Employee profile, documents, and employment details |
| Document Management | Uploading and tracking employee documents |
| Timekeeping | Attendance records and leave request management |
| Payroll | Salary breakdown and payslip history |
| PRAISE | Performance-based incentives and recognition |
| Personal Reports | Generation and download of employee-related reports |

---

## UI Guidelines

- Professional and government-grade appearance  
- High readability and accessibility  
- Minimal and purposeful interactions  
- Consistent layout and spacing across pages  
- Role-differentiated UX while preserving overall DA visual identity  

### Status Indicators

- **Approved**: Green  
- **Pending**: Yellow  
- **Rejected**: Red  

---

## Security Considerations (Planned)

- Session-based authentication  
- Role-based access control  
- Input validation and sanitization  
- CSRF protection  
- Secure file handling  
- Audit logging for sensitive actions  

---

## Future Enhancements

- Complete backend integration with MySQL  
- Implement full authentication and role-based authorization flow  
- Persist recruitment/application data to database (currently simulated UI data in several pages)  
- Connect forms to server-side validation and processing  
- Email and in-app notification delivery integration  
- Report export to PDF and CSV  
- Audit trail implementation  
- Final UI QA and cross-role consistency pass  

---

## Notes

This project is intended for academic and internal system development. The design and workflow follow common HRIS patterns used in Philippine government agencies, with emphasis on clarity, maintainability, and scalability.

---

## License

**Internal Use**  
Department of Agriculture
