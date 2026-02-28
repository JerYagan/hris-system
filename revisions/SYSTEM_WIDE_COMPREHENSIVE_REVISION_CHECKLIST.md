# System-Wide Comprehensive Revision Plan and Checklist (Per Module, Per User)

References used:
- revisions/system-revisions.md
- revisions/system-revisions-2.md
- revisions/Admin_Revisions.md
- revisions/Admin_Revisions_2.md
- revisions/staff-revisions.md
- User_Privileges.md
- FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md

Rules applied while consolidating:
- Only unresolved revisions are listed (completed `[x]` items are excluded).
- Per-module sections include only users that actually use/own that module.
- *Conflicting revisions* and *modules for removal/deprecation* are marked in asterisks.

---

## A) System-Wide Non-Negotiables (All Modules)

- [ ] Standardize all date/time operations and displays to Philippine Standard Time (PST, UTC+08:00).
- [ ] Use SweetAlert2 for confirmation, warning, status-change, and destructive actions.
- [ ] Use Flatpickr for all date/date-time pickers.
- [ ] Replace all native `localhost says` and inconsistent browser alerts.
- [ ] Add confirmation + reason capture for all status-changing actions.
- [ ] Add immutable audit logs for final decisions and overrides.
- [ ] Apply Department -> Division terminology in UI.
- [ ] Apply ATI branding updates and Bagong Pilipinas logo.
- [ ] Implement localized JS by role/page via `assets/js/bootstrap.js` dynamic imports.
- [ ] Enforce loading/skeleton/empty/filter-empty/error/success states for data-driven pages.
- [ ] Enforce server-side pagination/filter/sort for large lists.
- [ ] Apply admin list/modal baseline design on all review tables:
  - [ ] Header with title + helper text
  - [ ] Control row: left search (`Search Requests` style), right status filter (`All Statuses` default), optional date/office/type filters
  - [ ] Stable columns + pill status + compact action buttons
  - [ ] No-records and filter-empty states
  - [ ] Error state with retry
  - [ ] Modal: readonly context -> decision selector -> notes -> `Cancel` / `Save Decision` actions
  - [ ] Modal closes via cancel/icon/backdrop and resets state on close

## A.1) Mandatory Staff -> Admin Permission Flow (All Approval-Driven Modules)

- [ ] Enforce permission chain where Staff cannot finalize Admin-owned decisions.
- [ ] Staff view action must be `Submit to Admin for Approval/Reject` (recommendation only, not final disposition).
- [ ] Admin view must show all Staff-submitted pending approvals per module in a table.
- [ ] Required pending-approval table behavior:
  - [ ] Module-based queue with `All Statuses` default filter
  - [ ] Search + optional date/office/type filters
  - [ ] Stable columns: Request/Record ID, Subject, Submitted By (Staff), Submitted At (PST), Current Status, Actions
  - [ ] Review action opens modal with readonly context, decision selector, and notes
- [ ] Admin final action updates authoritative status (`Approved`/`Rejected`/`Needs Revision` per module policy).
- [ ] Staff view must show Admin final decision in queue and record timeline/history.
- [ ] Staff must receive in-app notification containing module, record reference, Admin decision, actor, and PST timestamp.
- [ ] Locking rules:
  - [ ] Staff cannot modify recommendation after submit unless returned by Admin
  - [ ] Staff cannot overwrite Admin final decision
  - [ ] Admin override/reject actions require reason and immutable audit log entry

---

## B) *Conflicting Revisions / Decision Locks*

- *Timekeeping Late Policy Conflict*: one request enforces `9:01 = late`, while multiple revisions require `no-late/flexi` handling.
- *Recruitment Criteria Scope Conflict*: per-position criteria requested vs global criteria MVP direction.
- *Archived Document Policy Conflict*: remove archived status vs keep archived state for records retention/audit.
- *Overtime Conflict*: remove overtime and convert to CTO vs existing overtime filing privileges/flows.
- *Evaluation Ownership Conflict*: keep staff evaluation stage vs remove/reduce staff evaluation to avoid redundancy.

## C) *Modules Needing Removal/Deprecation Decision*

- *PRAISE module* (multiple revisions request removal/de-scope).
- *Employee Evaluation module* (requested removal/de-scope due to process mismatch).

---

## 1) Login / Access / Public Homepage

### Applicant
- [ ] Add password creation validation.
- [ ] Fix forgot-password flow.

### Admin
- [ ] Align homepage copy with ATI mission/vision/strategic updates.
- [ ] Replace legacy homepage sections with updated institutional activity sections.
- [ ] Keep Careers list aligned with active recruitment postings.

---

## 2) Dashboard Module

### Admin
- [ ] Add `Pending Document for Verification` card.
- [ ] Convert plantilla chart to donut chart.
- [ ] Add “today” context in absence-related display.
- [ ] Remove redundant helper text (`You have X pending...`) when already covered by cards/notifications.
- [ ] Fix profile photo persistence issue (uploads disappear).
- [ ] Limit table entries to 10 and add pagination.

### Staff
- [ ] Keep dashboard scoped to operational summary + announcements only.

### Employee
- [ ] Replace leave-status card behavior with leave-credit/leave-card aligned display (SL/VL/CTO view).
- [ ] Add leave-card access integration (secured external/internal source as approved).

### Applicant
- [ ] Improve card visual hierarchy/color coding for readability (without hardcoding non-design-system tokens).
- [ ] In Dashboard, the Application Progress section is defaulted into "Application Submitted" even though the applicant has not actually applied to any job yet. This can be confusing for applicants who have just created their account and are seeing this section without having taken any action. It would be better to either hide this section until the applicant has submitted an application or change the default status to something like "No Applications Yet" to provide clearer context.

---

## 3) Recruitment Module (Job Listing / Applicants / Tracking / Offer)

### Admin
- [x] In Applicant Registration module: when approving an applicant for next stage, the system marks it as fail including the email. The system should only mark the applicant as failed if they do not meet the qualification criteria for the job posting. If the applicant meets the criteria and is approved for the next stage, their status should be updated accordingly without marking them as failed. The email notification should also reflect the correct status and provide appropriate information based on whether they were approved or failed.
- [x] In Job Creation and Edit, change the education criteria from years to education level (e.g., Bachelor's Degree, High School Graduate, etc.) to better reflect the actual qualifications of the applicants. The system should evaluate the applicant's education level against the required education level for the job posting and provide a recommendation based on whether they meet that criterion or not. Do note that the applicant may type multiple education entries but the system should still select the highest educational attainment entered by the user.
- [x] Applicant Profile: Remove Career Summary field
- [x] Applicant Profile: traning hours still shows 0 even though I entered 8 hours in my profile. The system should display the actual training hours that the applicant entered in their profile, regardless of whether it meets the requirement or not. This will provide a clearer picture of the applicant's qualifications and allow for a more accurate evaluation against the job requirements.
- [x] Applicant Profile: the qualification snapshot section shows the wrong info. I made a new applicant account and made sure that is elligible for the application. Here are the errors:
  1. The eligibility requirement is not met even though I uploaded a CSC/PRC document in the required documents section.
  2. Education shows 0 years (I think we should change this to display the education level instead, e.g. Bachelor's Degree, High School Graduate, etc.). Do note that the applicant may type multiple education entries but the system should still select the highest educational attainment entered by the user.
  3. Training hours shows 4 hours even though the I typed 8 hours. (Yes, 4 hours is the requirement but it should still display the amount of hours the applicant entered)
  4. Experience shows 0 years even though I entered 3 years of work experience in my profile.
- [x] Applicant Profile: Remove Resume/Portfolio in 
- [x] Applicant Profile: Submitted documents shows "Other" instead of the required document type (e.g., Resume, Application Letter, TOR, etc.) that the applicant uploaded.
- [x] Experience column shows 0 years even though I entered 3 years of work experience in my profile. The system should display the actual years of experience that the applicant entered in their profile, regardless of whether it meets the requirement or not. This will provide a clearer picture of the applicant's qualifications and allow for a more accurate evaluation against the job requirements.
- [x] Why does the Education column shows 2 educational levels instead of 1? It should select only the highest attainment entered by the applicant and display that in the Education column. (See screenshot)
- [x] Also change the Eligibility Column to match the CSC/PRC flow instead of the old one (See screenshot)
- [x] Change the global and per position criteria to select education levels instead of years. For example, instead of having a criteria that requires "4 years of education", it should be "Bachelor's Degree" or "High School Graduate" etc. Update the schema if needed to accommodate this change and make sure that the evaluation logic correctly evaluates the applicant's education level against the required education level for the job posting. Do note that the applicant may type multiple education entries but the system should still select the highest educational attainment entered by the user.
- [x] Change the Education part in the tables too.
- [x] Audit the entire recruitment modules (Recruitment, Applicant Registration, Applicant Tracking, Evaluation) and make sure the new education changes aligns with the new education criteria and displays the education level instead of years in all relevant sections (job listing, applicant profile, evaluation tables, etc.) for consistency and accuracy in representing the applicant's qualifications.
- [x] Include 4 requirement criteria in job listing details: eligibility, education, training, experience.
- [x] Add applicant profile preview (PDS, career, work experience) in review. Make it an actual profile preview UI, kind of like a resume/CV view with sections, instead of just showing the PDS form fields. Include the employee profile picture if available.
- [x] Remove `(Rule-Based Algorithm)` label text from evaluation UI while retaining logic.
- [x] Remove exam score dependency from qualification criteria.
- [x] Add training-hours requirement in criteria configuration.
- [x] Add configurable eligibility requirement (e.g., CSC/PRC baseline by position/policy).
- [x] Add automated applicant emails for submitted/passed/failed/next-stage with customizable remarks.
- [x] *Add office-signing/final-review notice email for next-stage approved applicants.*
- [x] Enforce Admin-only final hiring decision and job status controls.
- [x] Missing qualification criteria should be automatically rejected and marked as failed with reason `Missing criteria: [list missing criteria]` to avoid manual review bottlenecks. The system should also be able to send an email notification to the applicant about the failed criteria and remarks with customized message.
- [x] In setting eligibility requirement, eligibility should be separated by a comma (,) instead of a space. For example, if the eligibility requirement is "CSC/PRC", it should be entered as "CSC, PRC" to ensure that the system correctly recognizes both requirements.
- [x] Evaluation module: Keep the recommendation score calculation logic based on the defined criteria and weights. If the applicant meets the criteria, the recommendation score should reflect that accordingly even if one of the criteria is not met (e.g., 0 years of experience but meets education, training, and eligibility requirements should not result in a not qualified status).
- [x] Admin: Just create a different page for viewing applicant profile, make it more comprehensive and well-designed, and link it to the applicant's name in the applicant list and review queues. This way, staff can easily access the full profile of the applicant without having to rely on the document view, which is currently showing bucket not found errors. The profile page should include all relevant information about the applicant, such as their personal details, education, work experience, training, and uploaded documents, presented in a clear and organized manner for easy review. And make sure to fix the document view issue as well, so that admin can also access the individual documents of the applicants when needed. Wire it to each "View Profile" action in the Recruitment modules and submodules for easy access.
- [x] Remove `Review Decisions` in View Position modal
- [x] It should open to a new tab when viewing applicant profile.
- [x] In creating new jobs, there should be a field for job Plantilla Number which is a unique identifier for each job posting. This field should be required and validated to prevent duplicates. The job listing and applicant tracking should also display the Plantilla Number for reference.
- [x] In creating job postings, the admin should be able to set specific qualification criteria for each job position, including eligibility requirements (e.g., CSC/PRC and it must be a dropdown with options), education level, training hours, and years of experience. The system should automatically evaluate applicants against these criteria and provide a recommendation score based on how well they meet the requirements. This will help streamline the screening process and ensure that only qualified candidates are considered for each position. Also add a none option for eligibility requirement in case there are positions that do not require any eligibility documents. If none is selected, the system should not consider eligibility as a factor in the evaluation and recommendation score for that position.
- [x] Make the modal for creating jobs much wider and properly organize each fields into sections (e.g., Job Details, Qualifications, Description) to improve readability and usability. Also, add helper text or tooltips for each field to guide the admin in filling out the form correctly.
- [x] Evaluation module: The admin can configure the evaluation criteria per job posting, and the system should automatically calculate the recommendation score for each applicant based on how well they meet the defined criteria.
- [x] Evaluation module: The "Run Rule-Based Evaluation" table should have a limit of 10 entries with pagination to improve performance and usability when there are many applicants. Also a filters and search functionality should be added to easily find specific applicants in the evaluation table.
- [x] Evaluation module: Move the generate system recommendation section at the top, and add another section below that for 2 actions (Configure global criteria vs Configure per-position criteria) to clarify the distinction and allow admin to easily navigate to the appropriate configuration based on their needs. Remove the "Configure Global Criteria" section as those 2 are modals now and can be accessed via the buttons in the new section.
- [x] Remove header for each recruitment module page and just keep the title of the module (e.g., Job Listings, Applicant Tracking) to save vertical space and reduce redundancy.
- [x] Evaluation module: Don't display hired applicants in the evaluation table to avoid confusion, and add a filter option to show/hide hired applicants if needed for reference.
- [x] In creating job modal, change the checklist of required documents to (Application Letter, Updated Resume/CV, Personal Data Sheet, Valid Government ID, Transcript of Records). 
- [x] Change the Eligibility requirement field from a dropdown to a checkbox that has a label of "CSC/PRC Eligibility Required". If checked, it means that the applicant must have either a CSC or PRC eligibility to be considered qualified for the position. If unchecked, it means that there are no eligibility requirements for the position.
- [x] When creating job posting, the admin can have the option to select from a predefined job position or type a new one. If the admin selects a predefined job position, the system should automatically populate the qualification criteria based on the selected position. If the admin types a new job position, the position will automatically be added to the system. This will help standardize job postings and ensure that similar positions have consistent qualification criteria, while still allowing flexibility for unique job titles.
- [x] In create job posting modal, improve Qualification Criteria section by adding a clear label and helper text to explain what each criterion means and how it affects the applicant's evaluation. Also improve Required Documents section.

### Staff
- [ ] Ensure staff status control is limited to allowed stages (up to offer/recommendation only).
- [ ] Add offer email action and acceptance-driven status forwarding for Admin final approval.
- [ ] Prevent re-setting interview schedule when already finalized/scheduled per rule.
- [ ] Fix failed email update actions from tracking/offers.
- [ ] Ensure `View Documents` in job listing works.
- [ ] Remove staff access to status updates and archive posting action where Admin-only.
- [ ] Replace applicant-registration native alert (`localhost says`) with standardized SweetAlert2 placement/UX.
- [ ] Evaluation module: Remove action on staff side and rearrange the columns by this (Applicant/Pipeline Status/Eligibility/Education/Training/Experience/Score/Rule Result)
- [ ] Evaluation module: Don't display hired applicants in the evaluation table to avoid confusion, and add a filter option to show/hide hired applicants if needed for reference.

## 3) Recruitment Module (Job Listing / Applicants / Tracking / Offer)
### Applicant
- [x] After applying (even with completed requirements and documents), it still results in an error. See image
- [x] In apply.php, even if the applicant uploaded and inserted a CSC/PRC file in the required documents section, the system still shows that the eligibility requirement is not met.
- [x] Use flatpickr for the date input fields in the application form to improve the user experience and ensure consistent date formatting.
- [x] Add a persist state for all the uploaded documents and inputs in the application form, so that if the applicant accidentally refreshes the page or navigates away and comes back, they won't lose all the uploaded documents and entered information.
- [x] In apply.php, make the default file selector for training certificate/proof upload more user-friendly and visually clear and not just the default file input.
- [x] In apply.php, The training requirement is met by default even though the applicant has not actually completed any training yet. Also, add a field for uploading and entering training certificates or proof of completed training hours, and the system should automatically check if the uploaded document meets the training requirement for the job posting and update the qualification criteria accordingly. This will help ensure that applicants are providing the necessary documentation to support their qualifications and allow for a more accurate evaluation against the job requirements.
- [x] In apply.php and Required Documents section, add another field for uploading CSC/PRC eligibility documents if the job posting has the eligibility requirement enabled. The system should automatically check if the uploaded document is a valid CSC or PRC eligibility document and mark it as fulfilled in the required documents checklist.
- [x] In apply.php Qualification Criteria section, clean the UI and make it more user-friendly by using a checklist style with clear indicators for which criteria are met and which are missing.
- [x] Revert back the job listing filter where the applicant can only see the job postings that are currently open and available for application. The filter should be based on the open date and close date of the job postings, ensuring that applicants only see relevant opportunities that they can apply for. This will improve the user experience by preventing confusion and ensuring that applicants are not trying to apply for positions that are no longer accepting applications.
- [x] Profile completion reminder should be a modal pop-up that appears when the applicant logs in and has not completed their profile information (educational background and work experience).
- [x] remove the duplicate system recommendation regarding the potential gaps.
- [x] In apply.php, the applicant should be able to add and remove educational background and work experience entries in their profile, and the system should automatically update their evaluation against the job's qualification criteria based on the changes they make. For example, if an applicant adds a new educational background that meets the education requirement for a job, their recommendation status should be updated to reflect that they now meet that criterion. Conversely, if they remove an educational background or work experience entry that was previously contributing to their qualification, the system should update their evaluation accordingly and provide feedback on how it affects their application status. This dynamic evaluation will help applicants understand how their profile information impacts their eligibility for the job and encourage them to maintain an up-to-date profile. The options for educational level should be (Elementary, Secondary, Vocational/Trade Course, College, Graduate Studies), it should be a dropdown selection to ensure consistency in the data and accurate evaluation against the job requirements. The work experience entries should include fields for position title, company name, start date, end date, and a brief description of responsibilities to provide a comprehensive view of the applicant's professional background for evaluation purposes.
- [x] In job-list.php, the Plantilla Item no. doesn't show in the job listing, it should be displayed alongside the job title for each job posting.
- [x] In apply.php, the system should be able to see the applicant's the educational background and work experience that they have entered in their profile and use that information to automatically evaluate the applicant against the qualification criteria set by the admin for the job posting. The system should then provide a recommendation or feedback to the applicant based on how well they meet the criteria, such as "You meet the education requirement but are missing the required years of experience" or "You are fully qualified for this position". This will help applicants understand their strengths and weaknesses in relation to the job requirements and encourage them to complete their profile information for better evaluation. Also if they haven't filled out their profile information yet, the system should prompt them to complete their profile before applying to the job, since that information is crucial for the evaluation process.
- [x] In job-view.php/apply.php, it should display the required documents based on the checklist the admin made when making the job posting. The system should automatically check off the documents that the applicant has already uploaded and indicate which required documents are still missing. This will help applicants keep track of their application requirements and ensure they submit all necessary documents for consideration. Also improve the UI for the required documents section to make it more user-friendly and visually clear which documents are required and which ones have been submitted, don't use the default file input UI for this, make it more of a checklist style with upload buttons for each required document and clear indicators for missing/fulfilled requirements.
- [x] Remove misplaced recruitment notification duplication in applicant module where not applicable.
- [x] In job-view.php/apply.php, the applicant should be able to see the 4 Qualification Criteria clearly. The criteria should be displayed in a way that is easy to understand. If the applicant does not meet any of the criteria, they should receive a clear message indicating which criteria they are missing and how it affects their application status.
- [x] In job-view.php/apply.php, add a field for displaying employment type (e.g., Full-time, Part-time, Contract) and work location (e.g., On-site, Remote, Hybrid) for each job posting. This information will help applicants understand the nature of the job and whether it fits their preferences and circumstances.
- [x] Verify if the applicant job listing actually displays all the open positions, because I just added a new job posting but I can't see it in the applicant job listing page. Make sure that the job listing page is properly fetching and displaying all active job postings from the database, and that there are no filters or issues preventing new postings from appearing.
- [x] Show interview schedule details (datetime, interviewer, location, status).
- [x] Fix PDS reference link target in application flow. (link: https://docs.google.com/spreadsheets/d/1XYXyBVqEKuUqPsCHxkf5Xr6I8iL7jGOKxCO6ZHKL9Rg/edit?gid=1928756542#gid=1928756542)
- [x] Add clear list of accepted valid government IDs.
- [x] Add stricter upload validation (actual document/file type policy).
- [x] Mark required-document indicator color correctly.
 
---

## 4) Applicant Tracking Module

### Admin
- [ ] Monitor full pipeline with read-only history plus controlled override.
- [ ] Set interview schedules with PST timestamps.

### Staff
- [ ] Verify applicant details and forward with audit metadata:
  - [ ] who verified / when verified
  - [ ] who forwarded / when forwarded
- [ ] Record interview results and recommendations for Admin final action.

### Applicant
- [ ] Show end-to-end application timeline and current stage.

---

## 5) Evaluation Module

### Admin
- [ ] Approve final evaluation results.
- [ ] Configure criteria and recommendation logic based on approved scope.
- [ ] Keep decision remarks visible to applicants where policy allows.

### Staff
- [ ] Use evaluation only for allowed recommendation stage and forward to Admin.

### *Module Decision*
- [ ] *Finalize whether staff-side evaluation remains or is collapsed into offer/recommendation flow to remove redundancy.*

---

## 6) Document Management Module

### Admin
- [x] The Upload Document modal is too big vertically and not scrollable
- [x] Move the file type column at the beginning, before the file name.
- [x] Make the "Upload Document to Local Storage" a modal instead, where the admin can click a button to upload a file and fill out the necessary information (file type, owner, date) in the modal form. This will provide a cleaner and more organized interface for uploading documents, and it will also allow for better validation and error handling during the upload process. Place it above along with the Document Management Header for better visibility and accessibility. Also, make sure that the file input in the modal is user-friendly and visually clear, such as a drag-and-drop area or a styled button that opens the file selector, to improve the overall user experience when uploading documents in the admin section.

- [x] In the section where the admin can upload their own file, instead of using the default UI for the search results of the owner, it should display a more modern search result UI that includes the owner's profile picture, name, and email address. This will make it easier for the admin to identify the owner of the document and provide a more visually appealing interface for managing uploaded files. Additionally, the search functionality should be improved to allow for more accurate and efficient searching of owners, such as by name or email address, to further enhance the user experience when uploading and managing documents in the system. Also change the file input default UI to a more user-friendly and visually clear design, such as a drag-and-drop area or a styled button that opens the file selector, to improve the overall user experience when uploading documents in the admin section.
- [x] In the section where the admin can upload their own file, change the categories to match the 201 file type from the other tables, make the Owner searchable instead of a dropdown, and add a date filter. This will help the admin to better organize and manage their uploaded documents by categorizing them according to the standard 201 file types, allowing for easier searching by owner, and filtering by date to quickly find relevant documents when needed.

- [x] Document Registry section: Make the action a dropdown, viewing documents should open up a new tab, fix the "Need Revision" tag, remove the document path below the file name
- [x] Tables only show few categories, it should show all the categories of documents uploaded by the users (Employee, Applicant) throughout the system:
    - Violation 
    - Memorandum Receipt
    - GSIS instead SSS
    - Copy of SALN
    - Service record 
    - COE
    - PDS
    - SSS
    - Pagibig
    - Philhealth
    - NBI
    - Medical
    - Drug Test
    - Others (For files that doesn't belong in 201 files)
- [x] In Document Uploaders section, the table header should not be sticky

- [x] There should be 4 sections in Admin Document Management:
  - Document Registry: It lists all the document the users (Employee, Applicant) uploaded throughout the system. The admin should be able to Review (modal) it and change the status.
  - Document Uploaders: It lists all the users (Employee, Applicant) and their compiled documents (modal), the admin should be able to view or download the document.
  - Pending staff approval: It lists all the documents the staff forwarded to admin for approval. The admin should be able to approve/reject said document with remarks.
  - Archived Documents: It lists all the documents archived by the admin and staff with an option to restore it.

  Notes: Make sure that all the tables has a limit of 10 entries, pagination, search and filters. Use sweetalert and flatpickr (in case date input is needed). I also think it is easier to make a tab between the employee and applicant in the sections just to make the navigations easier between the tables of the two. Make sure that the category options are the 201 files:
    - Violation 
    - Memorandum Receipt
    - GSIS instead SSS
    - Copy of SALN
    - Service record 
    - COE
    - PDS
    - SSS
    - Pagibig
    - Philhealth
    - NBI
    - Medical
    - Drug Test
    - Others (For files that doesn't belong in 201 files)

- [x] Add queue search/filter controls and unified filter UX.
- [x] Restrict final review actions to allowed final states.
- [x] Return rejected docs with notes and resubmission path.
- [x] Add separate tab for all employee submissions organized by approved 201 categories.
- [x] Fix duplicate category entries and include `Other` category.
- [x] Update archive confirmation UX using SweetAlert2 review-style flow.
- [x] Improve full-width table fit; avoid unnecessary horizontal scroll.
- [ ] Ensure archive behavior mirrors correctly on admin and employee sides.
- [x] Add a Pending Staff Review tab for documents that are still in the staff recommendation stage before reaching admin review, to help both staff and admin easily track the status of documents and ensure that they are processed in a timely manner.

### Staff
- [x] In Pending staff review, the recommendation field in the modal should retain the previous recommendation if the staff wants to edit and resubmit the document for admin review. This way, staff can easily make adjustments to their recommendation without having to re-enter it from scratch, and it also helps maintain a clear history of the recommendations made for each document.
- [x] In the Document Uploaders modal, the table header should not be sticky and the modal vertical sizing should be adjust to fit the screen properly without causing overflow or requiring excessive scrolling. This will improve the user experience by allowing staff to view the full list of uploaded documents and their details without losing sight of the column headers, and it will also ensure that the modal is usable on different screen sizes without layout issues.
- [x] Lock recommendation edits after submit-to-admin (unless returned).
- [x] Ensure PDS document visibility is correct in review queues.
- [x] Fix the 404 bucket not found error in "Employee Document Registry" section when staff tries to view applicant-submitted documents in the recruitment module.
- [x] All actions must have a confirmation step with SweetAlert2 and require reason capture for status changes.
- Notes: Make sure that all the tables has a limit of 10 entries, pagination, search and filters. Use sweetalert and flatpickr (in case date input is needed). I also think it is easier to make a tab between the employee and applicant in the sections just to make the navigations easier between the tables of the two. Make sure that the category options are the 201 files:
    - Violation 
    - Memorandum Receipt
    - GSIS instead SSS
    - Copy of SALN
    - Service record 
    - COE
    - PDS
    - SSS
    - Pagibig
    - Philhealth
    - NBI
    - Medical
    - Drug Test
    - Others (For files that doesn't belong in 201 files)
- [x] Add a Pending Staff Review table for documents that are still in the staff recommendation stage before reaching admin review, to help both staff and admin easily track the status of documents and ensure that they are processed in a timely manner.

### Employee
- [x] In Uploading Documents modal, the categories for the document types are only 2. It should match the 201 file types:
    - Violation 
    - Memorandum Receipt
    - GSIS instead SSS
    - Copy of SALN
    - Service record 
    - COE
    - PDS
    - SSS
    - Pagibig
    - Philhealth
    - NBI
    - Medical
    - Drug Test
    - Others (For files that doesn't belong in 201 files)
- [x] Align the action column to the very right of the table for better visibility and consistency with common UI patterns. This will help employees easily locate the actions they can take on their documents, such as viewing, downloading, or resubmitting, without having to scan through the entire row. Additionally, ensure that the action buttons are clearly labeled and visually distinct to further enhance usability and encourage employees to actively manage their documents and track their status effectively.
- [x] Revert back the desision to make the filters less saturated and smaller. Instead, do it in the status tags.
- [x] Push the action column to the end of the table.
- [x] Move the search and filters to the top right of the table for better visibility and accessibility.

- [x] Use sweetalert for all actions with confirmation and reason capture for status changes.
- [x] Update the UI elements to match the staff for consistency, while keeping the employee's view focused on their own document management and status tracking. Make the filters less saturated and smaller. (Refer to the image for reference)
- [x] Make the actions a dropdown (see reference image). Remove the "Apply action" button. The employee should be able to select an action from the dropdown (e.g., View Document, Download Document, Resubmit Document) and the system should immediately perform the selected action without requiring an additional click on an "Apply" button. This will streamline the user experience and make it more intuitive for employees to manage their documents and track their status effectively.
- [x] In Uploading documents (modal), the categories are still not the 201 file types, it should be changed to match the 201 file type categories:
    - Violation 
    - Memorandum Receipt
    - GSIS instead SSS
    - Copy of SALN
    - Service record 
    - COE
    - PDS
    - SSS
    - Pagibig
    - Philhealth
    - NBI
    - Medical
    - Drug Test
    - Others (For files that doesn't belong in 201 files)
- [x] Remove the locahost archive reason. Instead, when an employee clicks the archive action, a SweetAlert2 modal should appear asking for confirmation.

- [x] Limit table entries to 10 with pagination, search, and filters.
- [x] Upload documents and track status.
- [x] Resubmit revised documents when requested.
- [x] Add personal view tabs for Submitted/Approved/Rejected + download where allowed.
- [x] Fix Document management table display issues, it must not be horizontally scrollable and should fit the width of the page properly. Also, make sure that the category options are the 201 files:
    - Violation 
    - Memorandum Receipt
    - GSIS instead SSS
    - Copy of SALN
    - Service record 
    - COE
    - PDS
    - SSS
    - Pagibig
    - Philhealth
    - NBI
    - Medical
    - Drug Test
    - Others (For files that doesn't belong in 201 files)
- [x] Add an archive option for documents that are no longer active but need to be retained for record-keeping purposes, with a confirmation step using SweetAlert2 and reason capture. Archived documents should be moved to a separate "Archived Documents" tab in the employee's document management view, where they can see the archived documents along with their status and have the option to restore them if needed. This will help employees manage their documents more effectively while ensuring that important records are preserved in accordance with organizational policies.

### *Conflict / Decision*
- [ ] *Finalize archived strategy: keep archived status vs move to separate archive module and limit active statuses to Submitted/Approved/Rejected.*

---

## 7) Personal Information Module

### Admin
- [x] The employee profile page should be an actual profile page where the admin can view the employee's profile information in a more comprehensive and well-designed layout, similar to the applicant profile page in the recruitment module. This will allow the admin to easily review the employee's information and make informed decisions when approving staff recommendations for employee record changes. The profile page should include all relevant information about the employee, such as their personal details, employment history, educational background, and any other relevant information that can help the admin in their review process. (Please refer to the PDS or the existing add and edit employee profile form for the information that should be included in the profile page, but present it in a more organized and visually appealing way for easier review by the admin.)

- [x] Opening the employee profile page should not directly go into edit mode. there should be a button so the admin can modify the fields.
- [x] Move the staff and employee profiles section at the bottom
- [x] In the employee profile page, the admin should be able to edit the employee's profile information and save the changes. Just copy the edit profile form and functionality from the staff side and apply it to the admin side.
- [x] Add more fields based on the pds (See pictures)
- [x] Saving decision for the pending staff reviews is not working
- [x] Add section to view staff and employee profile in a card layout with basic information and profile picture for easy identification. This will allow the admin to quickly view and access the profiles of staff and employees without having to navigate through multiple pages or tables. The card layout should include key information such as name, position, division, and a profile picture if available, presented in a visually appealing way for easy recognition and access to their full profile details when needed. Clicking on the card should take the admin to the employee profile page where they can view more comprehensive information about the employee and manage their account and profile details as needed. This will improve the user experience for the admin and make it easier for them to manage employee records effectively.

- [x] create an employee profile page for admin to view the employee's profile information in a more comprehensive and well-designed layout, similar to the applicant profile page in the recruitment module. This will allow the admin to easily review the employee's information and make informed decisions when approving staff recommendations for employee record changes. The profile page should include all relevant information about the employee, such as their personal details, employment history, educational background, and any other relevant information that can help the admin in their review process. (Please refer to the PDS or the existing add and edit employee profile form for the information that should be included in the profile page, but present it in a more organized and visually appealing way for easier review by the admin.)

- [x] fix the page layout
- [x] also when creating staff/employee account, remove the HR Officer and Supervisor role
- [x] remove the create account functionality in this module
- [x] Also the numbers of entries in the tables is still more than 10. Make sure to limit it to 10 entries and add pagination, search, and filters to make it easier for admin and staff to navigate through the employee records, especially when there are many records in the system. Also, use flatpickr and sweetalert for the search and filters to make it more user-friendly and visually appealing.
- [x] Change the quick actions into two separate buttons (Create Account, Edit Profile) and place them beside the Personal Information header for better visibility and accessibility. Each button should open a modal with a clean and organized layout for creating accounts and editing profiles, allowing the admin to manage employee information more efficiently without navigating away from the main Personal Information page.
- [x] The autofill ZIP functionality in edit and add profile is still not working
- [x] Pending Staff Review section: Remove the Details column in the table
- [x] Audit the entire module and make sure to use flatpickr for all date input fields.

- [x] Implement the functionality where if the Admin typed the barangay and municipality, it should autofill the ZIP Code field. Refer to staff side for the logic of this functionality.
- [x] Improve the table layout of Pending Staff Review.
- [x] The admin should be able to make accounts of both the staff and employee, not just staff.
- [x] Turn the account creation and profile actions into a modal. These should be accessible by a button (quick actions) besides the Personal Information header for better visibility and accessibility. The modal should have a clean and organized layout with clear labels and input fields for the necessary information to create an account or edit a profile. This will improve the user experience by providing a more streamlined and efficient way for the admin to manage accounts and profiles without having to navigate away from the main Personal Information page.
- [x] When clicking the search result inside the Add or Edit profile modal, it closes the modal.
- [x] Add a Pending staff review table for employee records that are still in the staff recommendation stage before reaching admin review, to help both staff and admin easily track the status of employee record changes and ensure that they are processed in a timely manner. The admin should be able to review the staff recommendation and the proposed changes to the employee record, and then approve or reject the changes with remarks. This will help ensure that employee record changes are properly reviewed and approved by the admin, while also providing transparency and accountability in the change management process.
- [x] When approving staff recommendation for employee record changes, the admin should receive a notification with the details of the recommended changes and the changes in employee's profile information. This will help the admin make informed decisions and ensure that they are aware of the context of the changes they are approving. Additionally, the notification should include a link to the employee's profile for easy access and review before making a final decision on the recommended changes.
- [x] Add duplicate employee merge/delete flow with audit logs.
- [x] Keep assignment controls (Division/Position) with approval-safe workflow.
- [x] In the add and edit employee profile, the zip-code field is automatically filled based on the barangay and city/municipality selected by the user. Staff side already has this feature, so implement it in admin side.
- [x] In the add and edit employee profile the search function for Place of Birth, Civil Status, Blood Type, Barangay, City/Municipality, Province, ZIP Code should not use the default UI for the search results. Instead, it should display a more modern search result UI.

### Staff
- [x] fix the staff overview layout now that we've removed 1 stat card
- [x] move the search and filter from the profile actions to the employee management table section above the table
- [x] In reviewing changes, you should not use sweetalert for displaying the changes the staff made to the employee record. Instead, it should be displayed in a modal with a clean and organized layout that clearly shows the proposed changes side by side with the current information in the employee's profile. This will allow the staff to easily review the changes they made and ensure that they are accurate before submitting them for admin approval. The modal should also include options for the staff to edit their recommendation or add remarks before submitting it to the admin for final review. Also it always says 19 profile field(s) when in fact there are only fewer fields that are being changed.
- [x] Move the action column to the very right in the pending section
- [x] User should be able to click the down button when searching for Place of Birth, Civil Status, Blood Type, Barangay, City/Municipality, Province, ZIP Code in the add and edit employee profile without the modal closing. This will allow the staff to easily select the correct option from the search results without having to reopen the modal or losing their progress in filling out the employee profile information.


- [x] Use sweetalert for all actions with confirmation and reason capture for status changes.
- [x] Modals in this module should retain the information defined in the database. For example, when changing the division and position it should display the current division and position of the employee as the default value in the dropdown.
- [x] Remove the details column in the Pending section, also remove the NEEDS UPDATE stat card at the top
- [x] Pending section should have an action for reviewing the changes they (the staff) made to the employee record before submitting it to admin for approval.
- [x] Restrict direct Division/Position changes to recommendation-only flow.
- [x] Add strict required-field validation before profile update submission.
- [x] Replace module-specific native alert styling/placement with standardized SweetAlert2 alert behavior.
- [x] Adjust A-Z list/table text sizing for readability consistency with admin/staff layout baseline.
- [x] Add a pending admin approval table for employee records that are still in the staff recommendation stage before reaching admin review, to help both staff and admin easily track the status of employee record changes and ensure that they are processed in a timely manner.
- [x] In the add and edit employee profile the search function for Place of Birth, Civil Status, Blood Type, Barangay, City/Municipality, Province, ZIP Code should not use the default UI for the search results. Instead, it should display a more modern search result UI.
- [x] Change the actions icon in the table
- [x] Limit table entries to 10 with pagination, search, and filters.

### Employee
- [x] Remove "Personal 201 File List" section
- [x] Fix the employee profile photo not persisting after some time. also the employee profile photo doesn't show in the profile page in the admin side
- [x] The entirety of Personal Information module has broken icons. I think it's because this one uses google material symbols outlined.
- [x] Just exactly copy the edit profile form and functionality from the staff side and apply it to the employee side, but keep some of the fields view-only as per policy (middle name, birth date, place of birth). Include the search functionality for Place of Birth, Civil Status, Blood Type, Barangay, City/Municipality, Province, ZIP Code with modern UI instead of default HTML select. (See staff for reference on the logic). And make sure that the ZIP Code autofill works based on the barangay and municipality selected by the user. Copy it one to one.
- [x] Improve the buttons on uploading profile picture to be more user friendly, just use 1 button that opens the file selector instead of having two separate buttons for uploading and saving the profile picture. After the user selects a file, it should show a preview of the uploaded profile picture before saving it. This will improve the user experience by providing a more visually appealing interface for uploading profile pictures and allowing users to see how their profile picture will look before they save it.
- [x] Improve the Personal Profile section layout.
- [x] In Editing Profile modal, just copy the one from the staff side and apply it to the employee side, but keep some of the fields view-only as per policy (middle name, birth date, place of birth). Include the search functionality for Place of Birth, Civil Status, Blood Type, Barangay, City/Municipality, Province, ZIP Code with modern UI instead of default HTML select. (See staff for reference on the logic). And make sure that the ZIP Code autofill works based on the barangay and municipality selected by the user.
- [x] Lock non-editable fields in edit profile (middle name, birth date, place of birth) per policy.
- [x] Add search functionality for Place of Birth, Civil Status, Blood Type, Barangay, City/Municipality, Province, ZIP Code with modern UI instead of default HTML select. (See staff for reference on the logic)
- [x] Add controlled request flow for additional spouse entries with supporting docs and Admin approval.
- [x] Show personal 201 file list with status and permitted downloads.
- [x] Remove Personal Documents section and Approved Performance Evaluations section
- [x] Change the default file input when uploading a profile picture, I want a styled button that opens the file selector instead of the default file input UI, and after the user selects a file, it should show a preview of the uploaded profile picture before saving it. This will improve the user experience by providing a more visually appealing interface for uploading profile pictures and allowing users to see how their profile picture will look before they save it.

---

## 8) Timekeeping Module

### Admin
- [x] Remove cross table search and filters.
- [x] Add a table for reviewing staff recommendations for leave/CTO/time-adjustment requests with approval controls and audit logs.
- [x] Show daily attendance summary (present/absent/late) and downloadable/printable outputs.
- [x] Enforce approved flexi schedule windows (7AM-4PM, 8AM-5PM, 9AM-6PM) with late tagging starting at 9:01 AM.
- [x] Review and finalize leave/CTO/time-adjustment decisions routed from staff.
- [x] Lock time-adjustment decisions after final submission; rejected time-adjustment requests require new submission.
- [x] Keep leave requests defaulted to `Pending` and prevent modifying rejected leave requests.
- [x] Add complete employee timekeeping history view.
- [x] Support holiday/suspension configuration with payroll-aware paid handling.
- [x] Manage official business (OB) approvals and pending requests.
- [x] Add leave request preview details (leave type + description) in admin review.
- [x] Attendance snapshot section should show the attendance for the current day only.

### Staff
- [x] Fix visibility gap where staff-routed leave/CTO/time-adjustment requests do not appear in Admin queue.
- [x] Support RFID employee registration with auto-fill by employee ID.
- [x] Keep RFID attendance assist marked temporary/supportive only.

### Employee
- [x] Use sweetalert for all actions with submission and confirmation and reason capture for status changes. Avoid using localhost alerts for better UX consistency across the module.
- [x] Add labels above the calendar input on all the modals in Timekeeping module to indicate what the date input is for (e.g., "Select Leave Date", "Select Time Adjustment Date", etc.) to improve clarity and user experience when employees are filling out the forms for leave requests, time adjustments, and official business requests. This will help employees understand the purpose of the date input and ensure that they are selecting the correct dates for their requests, reducing confusion and potential errors in the submission process.
- [ ] Seems like flatpickr is bugged. Also prevent scrolling on the page when the modal is open
- [x] There are some modals that are not using flatpickr for the date input, make sure to use flatpickr for all date inputs in the employee side of the timekeeping module for consistency and better user experience.
- [x] Make the status less statured and smaller (See staff side)
- [x] Add icons to the actions for better visibility.
- [x] Use sweetalert and flatpickr for the search and filters to make it more user-friendly and visually appealing.
- [x] Above all the tables in this module, add a search bar and filters to make it easier for employees to navigate through their attendance records, leave requests, and time adjustments.
- [x] Add leave request date validation (no past dates; policy-based limits by leave type).
- [x] Add cancel option for pending leave only.
- [x] Show leave credits and deduction behavior transparently.
- [x] Display attendance late status using approved policy (flexi schedules enabled, late starts at 9:01 AM).
- [x] Remove redundant date input in time-adjustment request when date already selected from attendance record.
- [x] Add official business request flow (time out/in behavior with approval path).
- [x] Add CTO request validation and filing rules (date range and payroll cut-off constraints) under CTO-only policy.

## Across all users in Timekeeping Module
- [x] Some parts of the employee are still not using sweetalert and flatpickr, make sure to use it across the entire module for consistency and better user experience.
- [x] Normalize the naming scheme of the sections. e.g. Attendance Records, Leave Requests, Time Adjustments, Official Business Requests, etc. to ensure consistency and clarity across the module.
- [x] Ensure that the search and filter functionality is implemented across all tables in the module, and that it uses SweetAlert2 for a consistent and user-friendly experience when filtering records or searching for specific entries and Flatpickr for date inputs to enhance usability and visual appeal.
- [x] Audit if the flow matches the approved decision for leave/CTO/time-adjustment requests, and ensure that the status changes and visibility align with the approved workflow and policies.
- [x] Standardize the status tag design across all sections in the module, making them less saturated and smaller for a more modern and visually appealing look, while still maintaining clear visibility of the status information for employees when viewing their attendance records, leave requests, time adjustments, and official business requests.
- [x] Standardize the action buttons across all sections in the module by adding icons for better visibility and consistency, making it easier for employees to identify and interact with the available actions for their attendance records, leave requests, time adjustments, and official business requests.
- [x] Ensure that the attendance snapshot section is designed to show only the attendance for the current day, providing employees with a clear and focused view of their daily attendance status without overwhelming them with historical data in that section.
- [x] Ensure that the flow of the Employee submitting a leave/CTO/time-adjustment request, the Staff reviewing and making a recommendation, and the Admin receiving the recommendation and making a final decision is consistent with the approved workflow and policies, including the status changes, visibility of requests in the respective queues, and the ability to edit or resubmit requests as needed based on the decision outcomes.
- [x] Revise the Payroll Module to reflect the approved decision to remove overtime filing and convert it to a CTO-only process with a leave-style approval flow, ensuring that the timekeeping module's handling of leave requests and time adjustments is aligned with this change and that any references to overtime filing are updated accordingly in the UI and workflow. Also, make sure it accomodates to the holiday/suspension configuration with payroll-aware paid handling as per the approved decision.

### Cross users Timekeeping module revision
- [x] Audit if all the user has the same flow for CTO filing and approval, and make sure that the status changes and visibility align with the approved workflow and policies for CTO-only process.
- [x] Remove the legacy CTO Requests section in the Staff side

### *Conflict / Decision*
- [x] *Late policy finalized: Flexi schedules are enabled, and 9:01 AM onward is considered late.*
- [x] *Overtime policy finalized: Overtime filing is removed and converted to CTO-only process with leave-style approval flow.*

---

## 9) Payroll Module
### Admin
- [x] Audit the entire module and make sure that the salary adjustment is reflected. There are some sections and parts of admin that doesn't take the adjustment into account such as "View Employee Payslips" section, Approve Payroll Batches section, the modal there shows Adj +/- but it doesn't reflect the adjustment, and also in the payroll batch details when you click the batch, it also doesn't reflect the adjustment. Make sure that the salary adjustment is properly reflected in all relevant sections of the admin side of the payroll module, including the "View Employee Payslips" section, Approve Payroll Batches section, and the payroll batch details. This will ensure that the admin has accurate and up-to-date information on employee compensation and payroll computations, allowing them to make informed decisions when reviewing and approving payroll batches.
- [x] Audit the staff as well for this issue.
- [x] Approving salary adjustment doesn't work, when I approve a salary adjustment it shows a success message but when I go back to the payroll batch review, the salary adjustment is not reflected in the payroll batch details.
- [x] Verify if the salary adjustments recommended by staff are properly reflected in the payroll batch review for the admin (and also staff), and that the admin can see the details of the salary adjustments along with the staff's recommendation when reviewing the payroll batch for final approval. This will ensure that the admin has all the necessary information to make informed decisions on payroll batches, including any adjustments that may impact the payroll computation and employee compensation.
- [x] Add a logic where the system blocks the admin from approving a payroll batch if there are pending staff recommendations for salary adjustments that have not yet been reviewed and approved by the admin ONLY IN THAT PAYROLL BATCH. This will help ensure that all relevant information, including staff recommendations for salary adjustments, is considered and reviewed by the admin before approving a payroll batch, and it will also help prevent any potential issues or discrepancies in the payroll computation that may arise from unreviewed salary adjustments.
- [x] In approve payroll batches, the initial state for the batches is computed even though the staff has not yet made any recommendation or submitted it for approval. The initial state for the batches should be "Pending Review" or something similar to indicate that the batch is still awaiting staff recommendation and admin review, rather than showing it as already computed. This will help avoid confusion and ensure that the workflow accurately reflects the status of the payroll batch as it goes through the approval process. Same in the staff side in Generate Payslip section, the batch should not be shown as computed until the staff has made a recommendation and submitted it for admin approval.
- [x] Also in the reviewing payroll batches modal, it should show the breakdown of the payroll computation for the batch being reviewed, including the details of the salary setup, timekeeping records, deductions, and any other relevant information that contributes to the payroll computation. This will provide the admin with a clear and comprehensive view of how the payroll batch was computed, allowing them to make informed decisions when approving or rejecting the batch. The breakdown should be organized in a clear and easy-to-understand format, such as tables or sections, to help the admin quickly grasp the details of the payroll computation during their review process.
- [x] In setting up salary for employees, remove reason in the sweetalert confirmation for saving the salary setup. Since salary setup is a routine task that the admin will be doing, it may not be necessary to capture a reason for audit logs every time they save a salary setup, as long as there are proper audit logs in place to track the changes made to the salary setup.
- [x] Also don't include people with the role of staff in the employee list for managing salary setup, it should only include users with the employee role.
- [x] Remove the restriction for deleting payroll batches (including the released ones) to allow for better error handling and correction in case of any issues with the payroll batches.
- [x] Saving salary setup requires sweetalert confirmation and reason capture for audit logs.
- [x] Use flatpickr when selecting the effective date for salary setup to improve the user experience and ensure consistency in date input across the module.
- [x] The modals you implemented are broken (See images)
- [x] Add an instruction flow for the payroll batch approval process, which includes the staff preparing the payroll batch and submitting it to the admin for final approval, and the admin reviewing the submitted payroll batch along with the staff's recommendation and making a final decision to approve or reject the payroll batch. This instruction flow can be section below the quick action buttons, make it in a step-by-step format with clear and concise instructions for admin to follow, ensuring that the payroll batch approval process is well understood and followed correctly by all users involved.
- [x] Instead of having a standalone section for managing salary setup, the payroll module can have a section that lists all employees and the admin can select from that list to manage their salary setup. This way, the salary setup can be more integrated into the overall payroll management process and it will also allow the admin to easily access and manage the salary setup for each employee without having to navigate to a separate section. The salary setup can be displayed in a modal or a separate page that is accessed when the admin selects an employee from the list, and it should include all the necessary fields and options for managing the employee's salary setup in a clear and organized layout. This will improve the user experience for the admin and make it easier for them to manage employee salaries effectively within the payroll module.

- [x] Turn some of the sections here into a quick action button that opens a modal instead of having all the sections in one page. Generate Payroll Batch, Send Payslip to Employees via Email, Review Salary adjustments sections. Make sure that the quick links are at the top of the page along with the Payroll header for better visibility and accessibility.


- [x] Review payroll batch modal is too long vertically and does not fit the screen properly, especially for users with smaller screens.
- [x] Submitted Review Salary Adjustments (by the staff) are not showing in the admin. 
- [x] Approve Payroll Batches autoamtically showing as computed even though the staff has not yet made any recommendation or submitted it for approval.
- [x] Ensure that the payslip viewing and document generation are functioning correctly, with the payslip view showing accurate and complete information based on the payroll data, and the document generation producing correct and well-formatted payslips for employees. This includes verifying that all relevant payroll components (earnings, deductions, net pay) are accurately reflected in both the UI and the generated documents, and that any changes made to payroll data are properly updated in the payslip view and documents.
- [x] Keep secure payslip email sending with logging.
- [x] Remove/replace overtime filing references in payroll UI/export and align all policy wording to CTO-only process.
- [x] Ensure payroll is fully connected to timekeeping deductions and approved policies.
- [x] Ensure payroll runs include traceable source inputs (attendance, salary setup, deductions) for validation and auditability.
- [x] Show complete payroll computation breakdown in UI and export/PDF.
- [x] Enforce final payroll batch approval with audit logs.
- [x] Enforce payroll handoff flow: staff submit batch for admin approval; admin final decision reflects back to staff queue/history with timestamp and notification.
- [x] Add a recommendation column in the staff payroll queue/history to show the staff's recommendation for each payroll batch, and ensure that this recommendation is visible to the admin when reviewing the payroll batch for final approval. This will provide additional context for the admin's decision-making process and help ensure that staff input is considered in the payroll approval workflow.

## 9) Payroll Module
### Staff
- [x] Staff should be able to have a final review before generating payslips to employee, just copy the Review Payroll Batch modal from the admin side and apply it to the staff side as a final review before generating payslips for employees.
- [x] After creating salary adjustment, it doesn't show in the table in the Recommend Salary Adjustment section in the staff payroll module, and it also doesn't show in the admin side for review and approval.
- [x] In the create salary adjustment modal, the Adjustment Code field should be a dropdown selection instead of a free text input to ensure consistency and accuracy in the adjustment codes used for salary adjustments.
- [x] In the create salary adjustment modal, there should be a field for the staff to select their recommendation approval (e.g., Draft, Approve, Reject) for the salary adjustment they are creating.
- [x] Staff payroll module doesn't have the same scope as the admin so it only shows 1 employee in every section of the payroll module
- [x] Fix the modal for salary adjustment being too large vertically and not fitting the screen properly, especially for users with smaller screens.
- [x] Change salary adjustment action to recommendation flow for Admin final approval.
- [x] Fix generate payslip flow.
- [x] Resolve payroll category open-button error.
- [x] Show Admin final disposition of submitted payroll batches in staff queue/history with decision timestamp.

## 9) Payroll Module
### Employee
- [x] Ensure the payroll data is correctly reflected in the employee's payslip view and history, and that any changes made by the admin or staff are accurately updated in the employee's view.
- [x] Keep module naming clarity (`Payroll` vs `My Payslip`) based on approved UX.
- [x] Ensure payslip view/history access is stable.
- [x] Ensure deduction breakdown visibility (e.g., SSS/Pag-IBIG and other deductions) is clear in payslip UI and PDF.


### Cross users Payroll module revision:
- [ ] Validate payroll computation accuracy against approved policies (salary grade rules, absences/leave/timekeeping deductions, and period-based calculations).
- [ ] Validate payroll inputs traceability: each payroll run can be traced to attendance, compensation setup, adjustments, and deduction sources.
- [ ] Enforce payroll batch final approval controls with mandatory reason capture and immutable audit logs for compute/export/send/approve/reject actions.
- [ ] Ensure secure payslip delivery workflow (approved templates, access-safe payload handling) and log every send attempt/result.
- [ ] Remove/replace overtime filing references across payroll screens, exports, and labels; standardize wording to CTO-only policy with leave-style approval flow.
- [ ] Standardize payroll section naming across roles (module title, table headers, buttons, status labels) for consistent UX.
- [ ] Ensure admin payroll breakdown UI is clear and complete (earnings, deductions, net pay) and consistent with export/PDF output.
- [ ] Ensure employee payslip UI is clear and user-friendly, with visible deduction breakdown and stable view/history access.
- [ ] Execute pre-deployment payroll QA checklist (cross-role flow tests, policy conformance checks, and audit-log verification) based on revisions folder requirements.
- [x] Ensure that when generating payslip, it also generates the payslip document (PDF) and attaches it to the employee's profile for record-keeping and easy access. This will allow employees to easily view and download their payslips from their profile, and it will also help ensure that all payroll records are properly stored and organized within the system. Also if possible, attach the PDF in the emails sent to employees when sending payslips via email, so that employees can have a copy of their payslip in their email for easy reference and record-keeping.
- [x] Ensure to follow the Localized JS Performance optimization (FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md)
- [x] Enforce staff -> admin payroll handoff flow end-to-end (prepare/submit, admin final decision, reflected status/history for staff).

---

## 10) Reports and Analytics Module

### Admin
- [ ] Rename/report labels to `REPORTS and Analytics`.
- [ ] Remove late-incidents output where no-late policy is approved.
- [ ] Include audit logs and cross-module KPI reports.

### Staff
- [ ] Keep operational report generation aligned to allowed scope.

### Employee
- [ ] Keep personal-report visibility only.

---

## 11) Notifications Module

### Admin
- [ ] Keep announcements and notifications clearly distinct (announcements are org-wide broadcast type).
- [ ] Rename `Recent Announcements` to `Recent Notifications` where requested and contextually correct.
- [ ] Add audit trail/logging especially those triggered by Admin actions, to ensure accountability and traceability of communication within the system.

### Staff
- [ ] Ensure staff receives final Admin decision notifications on forwarded records with PST timestamp.
- [ ] Fix notification entry/action that routes to payroll category open flow (open action error).

### Employee
- [ ] Add richer training notification details (provider, venue, mode).
- [ ] Add hover/quick-view interaction and auto-read behavior when opened (if approved UX standard).

---

## 12) Announcement Module

### Admin
- [ ] Add targeted visibility by employee/group/role.
- [ ] Ensure timezone display consistency for non-admin viewers.

### Employee
- [ ] Show only targeted announcements.

---

## 13) Learning and Development Module

### Admin
- [ ] Add new training creation with advance notifications.
- [ ] Keep one attendance log per employee per training.
- [ ] Add history view.

### Staff
- [ ] Finalize naming/content decision: `Courses` vs `Training`.
- [ ] Clarify if draft courses are needed and who owns draft creation.
- [ ] Hide draft courses in staff views unless explicitly required by approved workflow.

### Employee
- [ ] Merge/streamline `Available Trainings` and `My Enrollments` behavior per approved flow.
- [ ] Add certificate view/download for completed trainings.

---

## 14) User Management Module

### Admin
- [ ] Enforce max 2 active admins.
- [ ] Prevent disabling protected admin account.
- [ ] Keep employment classification options aligned to policy.
- [ ] Auto-populate Division on user selection.
- [ ] Remove Office Type when deployment is central-office-only.
- [ ] Keep role assignment/support-ticket routing aligned with privileges.

---

## 15) My Profile Module

### Admin
- [ ] Add password change feature.
- [ ] Clarify active verification/recovery method (email or phone).

### Staff
- [ ] Ensure profile update flow and upload controls are consistent.
- [ ] Place `Choose File` control inside upload action pattern consistently in profile forms.

### Employee
- [ ] Fix the employee profile picture upload issue where the uploaded picture does not persist and disappears after some time.

### Applicant
- [ ] Keep profile update capability aligned with account scope.
- [ ] Add an upload profile picture feature with validation and preview in the applicant profile section. The profile picture should be displayed in the applicant's profile and included in the applicant profile preview for staff review.

---

## 16) Settings Module

### Admin
- [ ] Configure SMTP, notification controls, backup/restore, and access/audit log settings.

### Employee
- [ ] Keep personal settings options scoped and working.

---

## 17) Support Module

### Employee
- [ ] Add support request flow for profile change requests (name/marital status/etc.) with attachments.

### Applicant
- [ ] Add inquiry/support submission flow.

### Admin
- [ ] Route/resolve support tickets and forward to staff when needed.

---

## 18) *PRAISE Module (Removal/Retention Decision)*

### *Module Decision*
- [ ] *Confirm if PRAISE is removed/de-scoped system-wide or retained with reduced scope.*

### Admin (if retained)
- [ ] Manage awards/recognition with final approval authority.
- [ ] Clarify publication destination and reporting ownership.

### Staff (if retained)
- [ ] Keep nomination/evaluation support only within approved boundaries.

### Employee (if retained)
- [ ] Allow view/download of approved recognition certificates.

---

## 19) *Employee Evaluation Module (Removal/Retention Decision)*

### *Module Decision*
- [ ] *Confirm if Employee Evaluation module is removed from staff/employee navigation and process.*

### Admin (if retained)
- [ ] Set periods and approve supervisor ratings.

### Staff (if retained)
- [ ] Submit supervisor ratings and forward with remarks.

---

## 20) Frontend Performance Rollout Tasks (Role-Based JS Localization)

### Admin
- [ ] Complete localized module entries for each admin page in `assets/js/admin/*`.
- [ ] Remove reliance on global admin mega-script.
- [ ] Lazy-load heavy vendors only on pages that need them.

### Staff
- [ ] Localize staff page scripts and remove cross-module script loading.

### Employee
- [ ] Localize employee page scripts and prioritize dashboard/personal info/notifications.

### Applicant
- [ ] Localize applicant job/apply/status flows and keep helper logic page-bound.

---

## 21) Delivery Sequence (Suggested)

- [ ] Phase 0: System standards (PST, SweetAlert2, Flatpickr, audit, privilege enforcement).
- [ ] Phase 1: Critical blockers (recruitment job validation, add-employee bug, document status bug).
- [ ] Phase 2: Recruitment-tracking-evaluation decisions and automation.
- [ ] Phase 3: Personal info + document 201 alignment.
- [ ] Phase 4: Timekeeping + payroll policy lock and integration.
- [ ] Phase 5: Reports/notifications/announcements cleanup.
- [ ] Phase 6: Module removal/deprecation decisions and navigation cleanup.
- [ ] Phase 7: Full localized JS performance pass and QA sign-off.


To dos
Applicant side:
Change application timeline design
Audit UI

Add a modal for notifications in all users, when opening a notification it should open a modal that shows the details of the notification, it'll also mark the notifications as read. This will improve the user experience by providing a more visually appealing interface for viewing notifications and allowing users to easily manage their notifications without having to navigate away from the current page. The modal should also include any relevant information related to the notification, such as links or attachments, to provide users with all the necessary context when viewing their notifications.

Change the sidebar behavior to overlay instead of pushing the content when opened in the staff and employee side.

Add utility/tool pages in admin, such as OCR for document processing, setting up RFID for timekeeping, setting up email templates for notifications, etc. This will provide the admin with additional tools and functionalities to manage and maintain the system more effectively, and it will also help streamline various processes such as document processing, timekeeping setup, and notification management within the system.

change timekeeping CTO filing to match the excel sent by the client

