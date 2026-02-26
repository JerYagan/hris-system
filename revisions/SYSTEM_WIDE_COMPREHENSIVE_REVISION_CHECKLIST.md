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
- [ ] Add queue search/filter controls and unified filter UX.
- [ ] Restrict final review actions to allowed final states.
- [ ] Return rejected docs with notes and resubmission path.
- [ ] Add separate tab for all employee submissions organized by approved 201 categories.
- [ ] Fix duplicate category entries and include `Other` category.
- [ ] Update archive confirmation UX using SweetAlert2 review-style flow.
- [ ] Improve full-width table fit; avoid unnecessary horizontal scroll.
- [ ] Ensure archive behavior mirrors correctly on admin and employee sides.

### Staff
- [ ] Lock recommendation edits after submit-to-admin (unless returned).
- [ ] Ensure PDS document visibility is correct in review queues.

### Employee
- [ ] Upload documents and track status.
- [ ] Resubmit revised documents when requested.
- [ ] Add personal view tabs for Submitted/Approved/Rejected + download where allowed.

### *Conflict / Decision*
- [ ] *Finalize archived strategy: keep archived status vs move to separate archive module and limit active statuses to Submitted/Approved/Rejected.*

---

## 7) Personal Information Module

### Admin
- [ ] Fix inability to add new employee record.
- [ ] Add duplicate employee merge/delete flow with audit logs.
- [ ] Keep assignment controls (Division/Position) with approval-safe workflow.

### Staff
- [ ] Restrict direct Division/Position changes to recommendation-only flow.
- [ ] Add strict required-field validation before profile update submission.
- [ ] Replace module-specific native alert styling/placement with standardized SweetAlert2 alert behavior.
- [ ] Adjust A-Z list/table text sizing for readability consistency with admin/staff layout baseline.

### Employee
- [ ] Lock non-editable fields in edit profile (middle name, birth date, place of birth) per policy.
- [ ] Add blood-type dropdown.
- [ ] Add controlled request flow for additional spouse entries with supporting docs and Admin approval.
- [ ] Show personal 201 file list with status and permitted downloads.

---

## 8) Timekeeping Module

### Admin
- [ ] Show daily attendance summary and downloadable/printable outputs.
- [ ] Review and finalize leave/time-adjustment decisions routed from staff.
- [ ] Support holiday/suspension configuration with payroll-aware paid handling.
- [ ] Manage official business (OB) approvals and pending requests.

### Staff
- [ ] Fix visibility gap where overtime-related requests do not appear in Admin queue when still enabled.
- [ ] Support RFID employee registration with auto-fill by employee ID.
- [ ] Keep RFID attendance assist marked temporary/supportive only.

### Employee
- [ ] Add leave request date validation (no past dates; policy-based limits by leave type).
- [ ] Add cancel option for pending leave only.
- [ ] Show leave credits and deduction behavior transparently.
- [ ] Remove redundant date input in time-adjustment request when date already selected from attendance record.
- [ ] Add official business request flow (time out/in behavior with approval path).
- [ ] Add overtime date validation if overtime remains enabled.

### *Conflict / Decision*
- [ ] *Finalize late policy mode (strict late threshold vs no-late/flexi policy).* 
- [ ] *Finalize overtime policy (retain overtime flow vs convert to CTO-only process).* 

---

## 9) Payroll Module

### Admin
- [ ] Ensure payroll is fully connected to timekeeping deductions and approved policies.
- [ ] Show complete payroll computation breakdown in UI and export/PDF.
- [ ] Enforce final payroll batch approval with audit logs.
- [ ] Keep secure payslip email sending with logging.

### Staff
- [ ] Change salary adjustment action to recommendation flow for Admin final approval.
- [ ] Fix generate payslip flow.
- [ ] Resolve payroll category open-button error.

### Employee
- [ ] Keep module naming clarity (`Payroll` vs `My Payslip`) based on approved UX.
- [ ] Ensure payslip view/history access is stable.

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
