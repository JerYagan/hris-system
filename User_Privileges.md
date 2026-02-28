USER PRIVILEGES 

# DOCUMENT MANAGEMENT MODULE
Admin:
- [ ] View all employee documents
- [ ] Approve or Reject submissions
- [ ] Request revisions
- [ ] Upload & and store official documents e.g. 201 files. 
- [ ] Archive finalize documents 
- [ ] Restore archived documents (if necessary) 
- [ ] View document audit trail 

Staff: 
- [x] View submitted documents
- [x] Verify required attachments 
- [x] Review documents for completeness
- [x] Forward documents to Admin for final decision 

Employee: 
- [ ] Upload documents
- [ ] View document status
- [ ] Resubmit revised documents when requested

# DASHBOARD
Admin: 
- [ ] View system-wide summary  (total employees, applicants, pending documents, approvals, attendance overview, etc.)
- [ ] Create New Announcement 
- [ ] View analytics and performance indicators 
- [ ] Post system-wide notifications 

Staff: 
- [x] View operational summary (pending reviews, document queue, assigned tasks)
- [x] View announcements 

Employee:
- [ ] View personal attendance summary 
- [ ] View leave status 
- [ ] View Submitted documents status 
- [ ] View announcements 

Applicant: 
- [ ] View application status

# RECRUITMENT
Admin: 
- [ ] Oversee entire recruitment process  (read-only supervisory view)
- [ ] Approve hiring decisions (final authority)
- [ ] Create New Job Posting
- [ ] Archive filled Positions (system-level archive only)
- [ ] Monitor recruitment analytics & reports 
- [ ] Override applicant status (with mandatory audit log)

Staff: (HR/Recruiter/ Evaluator/Interview Panel)
- [x] Manage recruitment records
- [x] View Application Deadlines 
- [x] Progress applicant status within assigned stage
- [ ] Record interview results
- [ ] Recommend hiring decision
- [ ] Forward applicant to next evaluator
- [ ] Receive forwarded applicants

# APPLICANTS / APPLICANT REGISTRATION
Admin: 
- [ ] View applicant records
- [ ] Modify decisions (with mandatory audit trail & reason)
Staff:
- [ ] Verify applicant details
- [ ] Forward for evaluation/interview ( add auto record for this who verified,date verified, who forwarded, date forwarded)

# APPLICANT TRACKING
Admin: 
- [ ] Monitor recruitment pipeline  (dashboard only)
- [ ] Set Schedule for Interview
- [ ] View status history ( read-only except override) 

Staff:
- [x] Monitor application progress 
- [x] Record interview results 
- [x] Receive forwarded applicants for interview 

Example flow:
- [ ] Applied
- [ ] Verified
- [ ] Interview
- [ ] Evaluation
- [ ] For Approval
- [ ] Hired/Rejected

# EVALUATION
Admin: 
- [ ] Approve final evaluation results
- [ ] Configure Evaluation Criteria 
- [ ] Generate System Recommendation 

Staff: 
- [x] Record interview results
- [x] Add HR remarks
- [x] Submit final evaluation (For admin approval)

# JOBS
Applicant: 
- [ ] View available job postings
- [ ] Submit applications 
- [ ] Upload Required documents 

# APPLICATIONS
Applicant: 
- [ ] View own applications information
- [ ] View timeline 
- [ ] View process stages

# APPLICATION FEEDBACK
Applicant: 
- [ ] View application result
- [ ] Receive hiring notifications

# PERSONAL INFORMATION
Admin: 
- [ ] Add,view and edit employee records
- [ ] Assign Department and Position 
- [ ] Manage Employee Status 
- [ ] Archive Employee Profile 
- [ ] View Audit trail 

Staff:
- [x] Update employee information (with approval of admin) 
- [x] Search employee records 
- [x] Recommend employee status change (not final approval) 

Employee:
- [ ] View personal profile
- [ ] Update limited personal details

# TIMEKEEPING
Admin:
- [ ] View all RFID attendance logs 
- [ ] Approve time adjustments
- [ ] Final decision for Approve/ Reject Leave Requests
- [ ] Register new employee for rfid 
- [ ] View attendance audit trail 
- [ ] Override approval with mandatory reason field 

Staff:
- [x] Monitor attendance records
- [x] Assist for Log Daily Attendance (rfid) 
- [x] Recommend approval for  Leave/Overtime 
- [x] Register new employee for rfid 

Employee:
- [ ] View personal attendance, Leave Balance, Leave requests, time adjustment requests, overtime requests
- [ ] Submit time adjustment requests
- [ ] Leave filing
- [ ] File Overtime 
- [ ] View RFID status (once tap)

# PAYROLL MANAGEMENT
Admin: 
- [ ] Manage Salary Setup for all Employees
- [ ] Process Payroll from staff 
- [ ] Generate and export  Payroll reports 
- [ ] Approve Payroll Batches 
- [ ] View Employee Payslips 
- [ ] Send Payslip to Employees via Email ( Encrypt emails with payslips to prevent data leakage.)
- [ ] Audit Trail Logging (Every modification, calculation, export, and email must be logged)

Staff:

- [x] Compute Monthly Payroll and Export it 
- [x] Process Payroll for approval of admin 
- [x] Generate Payslip and send it via email after admin approval 

Employee:
- [ ] View payslips
- [ ] View payroll history

# REPORT AND ANALYTICS
Admin: 
- [ ] Generate all system reports
- [ ] Generate Audit Logs 

Staff: 
- [x] Generate operational reports

Employee:
- [ ] View personal reports

# PRAISE
Admin: 
- [ ] Manage awards and recognition
- [ ] Approve recognitions
- [ ] Publish Awardee
- [ ] Create award categories 
- [ ] Edit award information 
- [ ] Change status of awards 
- [ ] Review submitted nominations and finalize candidate list 
- [ ] Generate recognition reports 
- [ ] Audit trail 

Staff:
- [x] Record nominations 
- [x] Nominate new employee for awards and recognitions
- [x] Manage nominations for awards 
- [x] Generate Evaluation Reports 

Employee:
- [ ] View awards received
- [ ] View Supervisor Evaluations 

# EMPLOYEE EVALUATION
Admin:
- [ ] Set Evaluation Periods 
- [ ] Approve Supervisor Ratings 
- [ ] View approved employees per quarter and overall performance ratings. 

Staff: 
- [ ] Submit supervisor ratings
- [x] Add remarks 
- [x] Forward for admin approval 

Workflow:
- [ ] Staff/Manager submits
- [ ] Admin approves
- [ ] System finalizes

# AWARDS AND RECOGNITION
Admin: 
- [ ] Create award categories
- [ ] Approve recipients
- [ ] Edit Award Information 
- [ ] Change award Status 
- [ ] Review Submitted nominations and finalize candidate list

# PRAISE REPORTS
Admin:
- [ ] Generate recognition reports

# LEARNING AND DEVELOPMENT
Admin: 
- [ ] Manage Employee Training Records
- [ ] Create New Training programs 
- [ ] Update Employee Training  Attendance 
- [ ] View L&D  Reports and Analytics 
- [ ] Audit Trail (Log all creation, updates, attendance changes, and report views)

Staff: 
- [x] View training records
- [x] Update Employee Attendance 
- [x] View Reports and Analytics 

Employee: 
- [ ] View assigned trainings
- [ ] View training history

# USER MANAGEMENT
Admin:
- [ ] Create and manage user accounts
- [ ] Assign roles
- [ ] Reset passwords
- [ ] Archive Accounts 
- [ ] Add Position 
- [ ] Assign Role 
- [ ] Add Department 
- [ ] View support tickets from employee (Admin can forward tickets to Staff or respond directly)

# MY PROFILE
Admin:
- [ ] View and update profile
- [ ] Update Account Preferences 
- [ ] Login Activity 
Staff: 
- [ ] View and update profile

Applicants: 
- [ ] View and update profile

# SETTINGS
Admin: 
- [ ] Configure system settings
- [ ] Backup & Restore Data of Full System 
- [ ] SMTP Email Configurations 
- [ ] Notification Management 
- [ ] Access Logs/ Audit Trail 

Employee: 
- [ ] Manage personal account settings

# SUPPORT
Employee: 
- [ ] Submit support requests

Applicants: 
- [ ] Submit inquiries