# HRIS MODULE CHECKLIST  
**Department of Agriculture – Agricultural Training Institute (DA-ATI)**

This checklist serves as a **development tracker**, **system scope reference**, and **QA guide** for the DA-ATI Human Resource Information System (HRIS).

---

## 🟢 CORE SYSTEM (FOUNDATION)

### Authentication & Access Control
- [ ] Login (Employee / HR / Supervisor / Admin)
- [ ] Logout
- [ ] Forgot password
- [ ] Session handling
- [ ] Role-based access control (RBAC)
- [ ] Account status (Active / Disabled)

---

## 🟢 DASHBOARD MODULE

### Employee Dashboard
- [ ] Welcome header (name + role)
- [ ] Attendance summary (Today / This month)
- [ ] Pending documents count
- [ ] Leave balance summary
- [ ] Notifications panel
- [ ] Announcements section

### HR / Admin Dashboard
- [ ] Pending approvals count
- [ ] Employee count
- [ ] Attendance overview
- [ ] Recent activity logs

---

## 🟢 PERSONAL INFORMATION MODULE

### Personal Profile
- [ ] View personal details
- [ ] Read-only by default
- [ ] Edit profile button
- [ ] Field validation
- [ ] Save / Cancel changes
- [ ] Change request logging

### Employment Details
- [ ] Employee ID
- [ ] Job title
- [ ] Division / Office
- [ ] Supervisor
- [ ] Employment type
- [ ] Start date
- [ ] Employment status

---

## 🟢 DOCUMENT MANAGEMENT MODULE

### Employee Document Upload
- [ ] Upload document (PDF / Image)
- [ ] Document category selection
- [ ] File size validation
- [ ] Duplicate file handling
- [ ] Upload timestamp

### Document Listing
- [ ] Search documents
- [ ] Filter by category
- [ ] Sort by date
- [ ] Status indicator (Pending / Approved / Rejected)

### Document Actions
- [ ] View document
- [ ] Cancel submission (if pending)
- [ ] Re-upload rejected document

### Approval Workflow (HR / Supervisor)
- [ ] View submitted documents
- [ ] Approve document
- [ ] Reject document (with reason)
- [ ] Status update
- [ ] Approval timestamp

### Audit Trail
- [ ] Uploaded by
- [ ] Reviewed by
- [ ] Action history
- [ ] Date & time logs

---

## 🟢 TIMEKEEPING MODULE

### Attendance Records
- [ ] Daily attendance view
- [ ] Date range filter
- [ ] Time-in / Time-out
- [ ] Attendance status (Present / Absent / Late)

### Leave Management
- [ ] Leave request form
- [ ] Leave type selection
- [ ] Date range picker
- [ ] Reason field
- [ ] Leave balance validation

### Leave Approval
- [ ] Supervisor review
- [ ] Approve leave
- [ ] Reject leave
- [ ] Leave status tracking
- [ ] Leave history

### Overtime (Optional)
- [ ] Overtime request
- [ ] Approval flow
- [ ] Overtime log

---

## 🟡 REPORTS MODULE

### Employee Reports
- [ ] Employee list
- [ ] Filter by division
- [ ] Export (PDF / Excel)

### Attendance Reports
- [ ] Daily attendance report
- [ ] Monthly summary
- [ ] Late / absent logs
- [ ] Printable view

### Leave Reports
- [ ] Leave usage summary
- [ ] Leave balance report
- [ ] Approved vs rejected leaves

---

## 🟡 SYSTEM & ADMIN MODULE

### User Management
- [ ] Create user
- [ ] Assign role
- [ ] Reset password
- [ ] Activate / deactivate account

### Role & Permission Management
- [ ] Employee permissions
- [ ] HR permissions
- [ ] Supervisor permissions
- [ ] Admin permissions

### Activity Logs
- [ ] Login logs
- [ ] Data update logs
- [ ] Approval actions
- [ ] Timestamp & user tracking

---

## 🔵 SECURITY & COMPLIANCE (RA 10173)

### Data Privacy
- [ ] Limited access per role
- [ ] Mask sensitive data
- [ ] Secure file storage
- [ ] Audit trail enabled

### System Security
- [ ] Input sanitization
- [ ] File upload validation
- [ ] Session timeout
- [ ] CSRF protection (PHP phase)

---

## 🔵 FUTURE-READY MODULES (PHASE 2)

### Payroll Module
- [ ] Salary computation
- [ ] Deductions
- [ ] Payslip generation (PDF)
- [ ] Payslip history

### Notifications
- [ ] Email notifications
- [ ] In-system alerts
- [ ] Approval notifications

### Analytics
- [ ] Attendance trends
- [ ] Leave usage graphs
- [ ] Employee statistics

---

## 🔥 BONUS (THESIS / DEFENSE BOOSTERS)
- [ ] Role-based UI rendering
- [ ] Status color standardization
- [ ] Accessibility compliance (WCAG basics)
- [ ] Mobile responsiveness
- [ ] Audit-ready logs

---
