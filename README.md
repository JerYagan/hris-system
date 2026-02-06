# Department of Agriculture  
## Human Resource Information System (DA HRIS)

---

## Overview

The **Department of Agriculture Human Resource Information System (DA HRIS)** is a web-based platform designed to centralize and streamline human resource operations for the Department of Agriculture. The system supports employee information management, timekeeping, payroll, document handling, and administrative HR processes in a secure and structured environment.

This project is currently in the **frontend development phase** and focuses on establishing a consistent and scalable user interface. Backend integration and server-side processing will be implemented in a later phase.

---

## Project Status

- Frontend UI development in progress  
- Employee-side pages implemented  
- Admin dashboard UI scaffolded  
- Backend integration pending  
- Authentication and role-based access control pending  

---

## Technology Stack

### Frontend
- HTML5  
- Tailwind CSS  
- Vanilla JavaScript  
- Google Material Icons  
- Chart.js  

### Planned Backend
- PHP  
- MySQL  
- Session-based authentication  

---

## Project Structure

/
├── index.html
├── README.md
│
├── auth/
│ └── login.html
│
├── pages/
│ ├── admin/
│ │ └── dashboard.html
│ │
│ └── employee/
│ ├── dashboard.html
│ ├── personal-information.html
│ ├── document-management.html
│ ├── timekeeping.html
│ ├── payroll.html
│ ├── praise.html
│ └── personal-reports.html
│
├── assets/
│ ├── images/
│ │ └── hero-img.jpg
│ └── js/


---

## User Roles

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

- Conversion of HTML pages to PHP  
- Centralized layout using PHP includes  
- Database integration with MySQL  
- Email notifications  
- Report export to PDF and CSV  
- Audit trail implementation  

---

## Notes

This project is intended for academic and internal system development. The design and workflow follow common HRIS patterns used in Philippine government agencies, with emphasis on clarity, maintainability, and scalability.

---

## License

**Internal Use**  
Department of Agriculture