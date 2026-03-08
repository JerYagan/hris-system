ADMIN:
OVERALL_SYSTEM:
- [ ] Add Bagong Pilipinas logo in header (ATI clean logo available)
- [ ] Rename Department to Division
- [ ] Add warning/confirmation before changing status in all modules

DASHBOARD:
SUMMARY_CARDS:
- [x] Replace Attendance Alerts → Pending Time Adjustments
- [x] Replace Draft Announcements → Pending Recruitment Decision
- [x] Replace Unread Notifications → Pending Documents
- [x] Replace Absent → Absence Rate This Week (%) (Update: Saturday 5:30AM)
- [x] Add Total Employees card
- [x] Add On Leave card
- [x] Align View Notifications statuses

CHARTS:
- [x] Display chart update timestamp
- [x] Attendance chart format:
"Attendance Summary - <DATE>
(Auto-updated <DATE> at 5:30AM)"
- [x] Attendance chart updates daily at 5:30AM
- [x] Recruitment chart updates daily at 12:00NN
- [x] Add settings to configure chart update times

RECRUITMENT:
- [x] Fix error: "Selected office or position is invalid" on New Job
- [x] Add employment status in job listing (Plantilla/Permanent or Contractual)
- [x] Clarify Job Title vs Job Position (keep only one)
- [x] Dropdown should show only confirmed available positions
- [x] Clarify difference between Archived and Closed
- [x] Remove redundant bottom colored filters
- [x] Standardize statuses across Applicants, Evaluation, Applicant Tracking

- [x] Show applicant requirements during application:
- [x] PDS
- [x] WES
- [x] Eligibility (CSC/PRC)
- [x] Transcript of Records

- In Job Listing view:
- [x] Show applicants
- [x] Show requirements
- [x] Show recommendation score (rule-based)
- [x] Add "Add as Employee" button when applicant is Hired
- [ ] Auto-extract PDS data when converting to Employee
- [x] Connect to Personal Information and Document Management modules

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
- [ ] Place of Birth
- [ ] Civil Status
- [ ] Blood Type
- [ ] City/Municipality
- [ ] Barangay
- [ ] Auto-fill ZIP code based on City + Barangay
- [x] Add checkbox: Permanent Address same as Residential Address
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
- [ ] Time In before leaving
- [ ] Time In after event
- [ ] Count as whole day if tagged OB

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
- [ ] Plantilla/Permanent
- [ ] Contractual/COS
- [ ] Remove Office Type if Central Office only
- [ ] Auto-populate Division when user is selected

MY_PROFILE:
- [ ] Add admin password change feature
- [ ] Clarify verification method (email or phone)

ANNOUNCEMENT:
- [ ] Allow targeting specific employee visibility

RULE-BASED ALGORITHM 
This algorithm will automatically screen applicants, check if minimum qualifications are met, reduce manual evaluation time, standardize screening process and it is used for initial qualification filtering, not final hiring decision, 

The admin should have option to set the criteria for each position title: 

CRITERIA 
1. Eligibility - Career Service Sub Professional
2. Education - 2 Years in College 
3. Training - 4 hours relevant training 
4. Work Experience - 1 Year relevant experience 

How Rule-Based Algorithm Works; 

It uses IF-THEN logic 

Example rule-based logic 
IF eligibility == required_eligibility
AND education_years >= required_education_years
AND training_hours >= required_training_hours
AND experience_years >= required_experience_years
THEN status = "Qualified for Evaluation"
ELSE status = "Not Qualified"



This is the sample process: 
Step 1- Applicant uploads documents based on the required requirements.
- Eligibilty certificate (PRC/CSC) 
- Transcript of Records 
-Training Certificate
- Certificate of Employment 

Step 2- Applicant Inputs Structured Data 
Along with upload, they must fill 
- Eligibility type (dropdown)
- Years in college (number field) 
-Training hours (number field) 
- Years of experience (number field) 

The uploaded file is for verification, not for automatic reading. 

The algorithm first evaluates based on encoded data. Then the Admin can see the auto result (Qualified/ Not Qualified) with scoring For example:
Eligibibilty = 25% 
Education = 25% 
Training = 25% 
Experience = 25% 

If total score ≥ 75 → Qualified

The uploaded documents is for validation. Then the Admin can approve or reject the applicant and put remarks the reason why it is rejected to notify the applicant. 


The Admin can update the ff: 
- Required Eligibility 
- Required Education 
- Required Training hours 
- Required Experience 

Rules are dynamic and the algorithm always checks againts the current job requirements.

Reference link that we can use: 
https://csc.gov.ph/career/job/5123356 for sample criteria and position title. 