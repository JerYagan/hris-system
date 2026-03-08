**Login Page** 

- [x]  Change Department of Agriculture into Agricultural Training Institute

**Dashboard Module**

- [ ] for plantilla, can we make it a donut chart ? 
- [ ] can we add pending document for verification  for the available space? 
- [ ] for absent, just add today 

**Recruitment Module** 

- [ ]  Job Listing should include Requirements the 4 criteria: 
Eligibility
Education 
Experience
Training 
- [ ] Evaluation - Remove "(Rule-Based Algorithm) Text"
- [ ] Applicant - Add preview of submitted PDS, Career Exp, Work Experience of applicants when admin click view profile. 

- [ ] Add eligibility they must have a civil service license
- [ ] Add Training hours 
- [ ] Remove exam score since it is not part of the 4 criteria 
- [ ] For running evaluation and generating recommendation, it should be by position title since each position have different criteria. 
- [ ] For applicants that is recommended or approve for next stage, add a feature where they will receive an email for final review and need to come to office for signing job offer. 


**Personal Information Module** 

- [ ] add checkbox for the same residential and permanent address. 
- [ ] for adding employee since it followed the pds format can we add another spouse for muslim and add an option at the end to print the document? 
- [ ] How to delete if Iduplicate an employee record? 
- [ ] I cannot add new employee record


**Document Management Module** 

- [x]  Add option to view 201 files contents which are: 
- PDS 
- SSS
- Pagibig 
- Philhealth 
- NBI 
- Mayors Permits
- Medical 
- Drug Test 
- Health Card
- Cedula 
- Resume/ CV
// These are not official list for the 201 files. 

- [ ] can we add a separate tab where the admin can see the records or submitted document of all employee and it is arranged same with the list above? 


**Reports and Analytics Module** 

- [ ] remove late incidents 

**Timekeeping**

- [ ]  No lates since ATI work hours is flexible example  (Time In 8:30AM Time Out will be 5:30PM with 1hr break)
- [ ] Add option to add employees in official business management those who are in the office but still paid, the admin can add it or they can see the pending request of employee for it.
- [ ] Pls Add RFID Feature for recording of TIME IN, BREAK OUT, BREAK IN, TIME OUT where once the employee taps rfid card it will be read by the rfid reader then the system will check the db for matching employee then the system will sent otp to the registered number or email valid for 1-2 minutes. If correct attendance recorded, if incorrect access denied and they have 3 attempts. 
- [ ] Pending Leave Request - Add Leave Type and description UI on preview. 

**Payroll** 

- [ ] Is timekeeping and payroll is connected? what if the employee has 2 absents, how will the system deduct the 2 absents? 

USER MANAGEMENT 

- [ ]  Add validation or condition here if admin should be not disabled and there should only be 2 admins. 
- 



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


## Admin Revisions
- Create a comprehensive plan for admin revisions and improvements for all the Admin modules, based on Admin_Revisions.md,Admin_Revisions_2.md and User_Privileges.md.

- Include in this plan a localized js like the other user modules, this is to improve performance. This is to avoid loading all the js for all the modules when the admin only needs to access specific module.