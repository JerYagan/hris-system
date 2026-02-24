ADMIN:
  OVERALL_SYSTEM:
    - [ ] Add Bagong Pilipinas logo in header (ATI clean logo available)
    - [ ] Rename Department to Division
    - [ ] Add warning/confirmation before changing status in all modules

DASHBOARD:
  SUMMARY_CARDS:
    - [ ] Replace Attendance Alerts → Pending Time Adjustments
    - [ ] Replace Draft Announcements → Pending Recruitment Decision
    - [ ] Replace Unread Notifications → Pending Documents
    - [ ] Replace Absent → Absence Rate This Week (%) (Update: Saturday 5:30AM)
    - [ ] Add Total Employees card
    - [ ] Add On Leave card
    - [ ] Align View Notifications statuses
  CHARTS:
    - [ ] Display chart update timestamp
    - [ ] Attendance chart format:
          "Attendance Summary - <DATE>
           (Auto-updated <DATE> at 5:30AM)"
    - [ ] Attendance chart updates daily at 5:30AM
    - [ ] Recruitment chart updates daily at 12:00NN
    - [ ] Add settings to configure chart update times

RECRUITMENT:
  - [ ] Fix error: "Selected office or position is invalid" on New Job
  - [ ] Add employment status in job listing (Plantilla/Permanent or Contractual)
  - [ ] Clarify Job Title vs Job Position (keep only one)
  - [ ] Dropdown should show only confirmed available positions
  - [ ] Clarify difference between Archived and Closed
  - [ ] Remove redundant bottom colored filters
  - [ ] Standardize statuses across Applicants, Evaluation, Applicant Tracking
  - [ ] Show applicant requirements during application:
        - PDS
        - WES
        - Eligibility (CSC/PRC)
        - Transcript of Records
  - [ ] In Job Listing view:
        - Show applicants
        - Show requirements
        - Show recommendation score (rule-based)
  - [ ] Add "Add as Employee" button when applicant is Hired
  - [ ] Auto-extract PDS data when converting to Employee
  - [ ] Connect to Personal Information and Document Management modules

DOCUMENT_MANAGEMENT:
  - [ ] Add search and filter for document uploads
  - [ ] Clarify Archived status behavior
  - [ ] Move "Archived documents cannot be reviewed" banner inside review view
  - [ ] Allow only Approve or Reject in document review
  - [ ] Return rejected documents with notes to employee
  - [ ] Fix bug: Needs Revision shows as Draft

PERSONAL_INFORMATION:
  - [ ] Fix tab highlight bug (Personal Info also highlights Document Management)
  - [ ] Convert fields to searchable dropdown:
        - Place of Birth
        - Civil Status
        - Blood Type
        - City/Municipality
        - Barangay
  - [ ] Auto-fill ZIP code based on City + Barangay
  - [ ] Add checkbox: Permanent Address same as Residential Address
  - [ ] Allow upload of requirements/documents
  - [ ] Connect 201 files from application and personal information
  - [ ] Prevent Add Employee without contact number and email

TIMEKEEPING:
  - [ ] Display suspensions/holidays per employee per day
  - [ ] Remove Late status (flexi schedule except Monday 8AM ceremony)
  - [ ] Lock Time Adjustment decisions after submission
  - [ ] Rejected time adjustments require new submission
  - [ ] Leave requests default status = Pending
  - [ ] Rejected leave requests cannot be modified
  - [ ] Add full employee timekeeping history
  - [ ] Auto-connect timekeeping to Payroll computation
  - [ ] Handle Official Business (OB):
        - Time In before leaving
        - Time In after event
        - Count as whole day if tagged OB

PAYROLL_MANAGEMENT:
  - [ ] Automatic salary computation based on Salary Grade
  - [ ] Include deductions
  - [ ] Display full breakdown in admin
  - [ ] Send payroll summary to employee email

REPORTS_AND_ANALYTICS:
  - [ ] Rename Report → REPORTS and Analytics

PRAISE:
  - [ ] Add admin evaluate/nominate button
  - [ ] Improve UI of New Cycle and Category buttons
  - [ ] Clarify publishing location (Notification or Dashboard)
  - [ ] Move Praise Reports button to Reports and Analytics
  - [ ] Evaluation fields: Who, When, Final Rating only

LEARNING_AND_DEVELOPMENT:
  - [ ] Add New Training with advance email notification
  - [ ] Use single attendance log per employee
  - [ ] Add View History button
  - [ ] Move Reports and Analytics section to top of module

USER_MANAGEMENT:
  - [ ] Employment classification options:
        - Plantilla/Permanent
        - Contractual/COS
  - [ ] Remove Office Type if Central Office only
  - [ ] Auto-populate Division when user is selected

MY_PROFILE:
  - [ ] Add admin password change feature
  - [ ] Clarify verification method (email or phone)

ANNOUNCEMENT:
  - [ ] Allow targeting specific employee visibility