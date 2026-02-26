# DA HRIS Supabase Data Dictionary (Admin-First)

This document defines the recommended **database tables** for DA HRIS using **Supabase Postgres**.

## Design Principles

- Use `auth.users` as identity source (Supabase Auth).
- Use app tables in `public` schema with `uuid` primary keys.
- Keep **Admin and RBAC** tables first because all modules depend on them.
- Use `created_at`, `updated_at`, and optional `deleted_at` (soft delete) for auditability.
- Store critical approval decisions in history tables (never overwrite final decisions without history).

---

## 1) Admin & Access Control (Foundation)

### 1.1 `organizations`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | `gen_random_uuid()` |
| code | text unique | e.g. `DA-ATI` |
| name | text | full organization name |
| is_active | boolean | default true |
| created_at | timestamptz | default now() |
| updated_at | timestamptz | default now() |

### 1.2 `offices`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| organization_id | uuid FK -> organizations.id | |
| office_code | text unique | |
| office_name | text | |
| office_type | text | `central`,`regional`,`provincial`,`division`,`unit` |
| parent_office_id | uuid FK -> offices.id | nullable hierarchical office |
| is_active | boolean | default true |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 1.3 `roles`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| role_key | text unique | `admin`,`hr_officer`,`supervisor`,`staff`,`employee`,`applicant` |
| role_name | text | display name |
| description | text | |
| is_system | boolean | cannot be deleted if true |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 1.4 `permissions`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| permission_key | text unique | e.g. `users.manage`, `payroll.approve` |
| module_name | text | `users`,`payroll`,`timekeeping`, etc. |
| action_name | text | `read`,`create`,`update`,`approve`,`export` |
| description | text | |
| created_at | timestamptz | |

### 1.5 `role_permissions`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| role_id | uuid FK -> roles.id | |
| permission_id | uuid FK -> permissions.id | |
| created_at | timestamptz | |
| unique(role_id, permission_id) | constraint | |

### 1.6 `user_accounts`
Profile mirror of `auth.users` + HRIS account metadata.

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | same value as `auth.users.id` |
| email | text unique | official email |
| username | text unique nullable | optional login alias |
| mobile_no | text | |
| account_status | text | `pending`,`active`,`suspended`,`disabled`,`archived` |
| email_verified_at | timestamptz | |
| last_login_at | timestamptz | |
| lockout_until | timestamptz | |
| failed_login_count | int | default 0 |
| must_change_password | boolean | default false |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 1.7 `user_role_assignments`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| user_id | uuid FK -> user_accounts.id | |
| role_id | uuid FK -> roles.id | |
| office_id | uuid FK -> offices.id | nullable |
| assigned_by | uuid FK -> user_accounts.id | admin/staff actor |
| assigned_at | timestamptz | |
| is_primary | boolean | default false |
| expires_at | timestamptz nullable | |
| unique(user_id, role_id, office_id) | constraint | |

### 1.8 `access_requests`
For `request-access` page and approval workflow.

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| full_name | text | |
| official_email | text | |
| office_unit | text | |
| requested_role_id | uuid FK -> roles.id | |
| employee_reference_no | text nullable | |
| reason | text | |
| status | text | `pending`,`approved`,`rejected`,`cancelled` |
| reviewed_by | uuid FK -> user_accounts.id nullable | |
| reviewed_at | timestamptz nullable | |
| review_notes | text nullable | |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 1.9 `login_audit_logs`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| user_id | uuid FK -> user_accounts.id nullable | null when email not found |
| email_attempted | text | |
| auth_provider | text | `password`,`magic_link`,`oauth` |
| event_type | text | `login_success`,`login_failed`,`logout`,`password_reset` |
| ip_address | inet | |
| user_agent | text | |
| metadata | jsonb | |
| created_at | timestamptz | |

### 1.10 `activity_logs`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| actor_user_id | uuid FK -> user_accounts.id | |
| module_name | text | |
| entity_name | text | table/object |
| entity_id | uuid nullable | |
| action_name | text | `create`,`update`,`approve`,`reject`,`delete`,`export` |
| old_data | jsonb nullable | |
| new_data | jsonb nullable | |
| ip_address | inet nullable | |
| created_at | timestamptz | |

### 1.11 `notifications`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| recipient_user_id | uuid FK -> user_accounts.id | |
| category | text | `system`,`hr`,`application`,`payroll`,`document` |
| title | text | |
| body | text | |
| link_url | text nullable | |
| is_read | boolean | default false |
| read_at | timestamptz nullable | |
| created_at | timestamptz | |

### 1.12 `system_settings`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | single-row or scoped rows |
| setting_key | text unique | e.g. `security.lockout_threshold` |
| setting_value | jsonb | |
| updated_by | uuid FK -> user_accounts.id | |
| updated_at | timestamptz | |

---

## 2) Person, Profile, and PDS Data

### 2.1 `people`
Master personal profile for admin/staff/employee/applicant.

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| user_id | uuid FK -> user_accounts.id unique nullable | applicants may exist before auth linking |
| surname | text | |
| first_name | text | |
| middle_name | text nullable | |
| name_extension | text nullable | JR/SR/etc |
| date_of_birth | date nullable | |
| place_of_birth | text nullable | |
| sex_at_birth | text nullable | `male`,`female` |
| civil_status | text nullable | |
| height_m | numeric(4,2) nullable | |
| weight_kg | numeric(5,2) nullable | |
| blood_type | text nullable | |
| citizenship | text nullable | |
| dual_citizenship | boolean nullable | |
| dual_citizenship_country | text nullable | |
| telephone_no | text nullable | |
| mobile_no | text nullable | |
| personal_email | text nullable | |
| agency_employee_no | text nullable unique | |
| profile_photo_url | text nullable | |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 2.2 `person_addresses`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| address_type | text | `residential`,`permanent`,`business`,`mailing` |
| house_no | text nullable | |
| street | text nullable | |
| subdivision | text nullable | |
| barangay | text nullable | |
| city_municipality | text nullable | |
| province | text nullable | |
| zip_code | text nullable | |
| country | text default 'Philippines' | |
| is_primary | boolean | |
| created_at | timestamptz | |

### 2.3 `person_government_ids`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| id_type | text | `umid`,`pagibig`,`philhealth`,`psn`,`tin` |
| id_value_encrypted | text | store encrypted/tokenized value |
| last4 | text nullable | masked display |
| created_at | timestamptz | |
| unique(person_id, id_type) | constraint | |

### 2.4 `person_family_spouses`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| surname | text nullable | |
| first_name | text nullable | |
| middle_name | text nullable | |
| extension_name | text nullable | |
| occupation | text nullable | |
| employer_business_name | text nullable | |
| business_address | text nullable | |
| telephone_no | text nullable | |
| sequence_no | int | for multi-spouse entry order |
| created_at | timestamptz | |

### 2.5 `person_family_children`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| full_name | text | |
| birth_date | date nullable | |
| sequence_no | int | |
| created_at | timestamptz | |

### 2.6 `person_parents`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| parent_type | text | `father`,`mother` |
| surname | text nullable | |
| first_name | text nullable | |
| middle_name | text nullable | |
| extension_name | text nullable | |
| created_at | timestamptz | |
| unique(person_id, parent_type) | constraint | |

### 2.7 `person_educations`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| education_level | text | `elementary`,`secondary`,`vocational`,`college`,`graduate` |
| school_name | text nullable | |
| course_degree | text nullable | |
| period_from | text nullable | preserve form format |
| period_to | text nullable | |
| highest_level_units | text nullable | |
| year_graduated | text nullable | |
| honors_received | text nullable | |
| sequence_no | int | |
| created_at | timestamptz | |

### 2.8 `emergency_contacts`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| contact_name | text | |
| relationship | text | |
| mobile_no | text | |
| address | text nullable | |
| is_primary | boolean | |
| created_at | timestamptz | |

---

## 3) Employment, Organization, and User Assignment

### 3.1 `job_positions`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| position_code | text unique | |
| position_title | text | |
| salary_grade | text nullable | |
| employment_classification | text | `regular`,`coterminous`,`contractual`,`casual`,`job_order` |
| is_supervisory | boolean | |
| is_active | boolean | |
| created_at | timestamptz | |

### 3.2 `employment_records`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| office_id | uuid FK -> offices.id | |
| position_id | uuid FK -> job_positions.id | |
| hire_date | date | |
| employment_status | text | `active`,`on_leave`,`resigned`,`retired`,`terminated` |
| immediate_supervisor_person_id | uuid FK -> people.id nullable | |
| probation_end_date | date nullable | |
| separation_date | date nullable | |
| separation_reason | text nullable | |
| is_current | boolean | one active record per person |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 3.3 `user_office_scopes`
Optional strict access scope for admin/staff users.

| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| user_id | uuid FK -> user_accounts.id | |
| office_id | uuid FK -> offices.id | |
| scope_type | text | `self`,`office`,`subtree`,`organization` |
| created_at | timestamptz | |
| unique(user_id, office_id, scope_type) | constraint | |

---

## 4) Recruitment and Applicant Tracking

### 4.1 `job_requisitions`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| office_id | uuid FK -> offices.id | |
| position_id | uuid FK -> job_positions.id | |
| requested_by | uuid FK -> user_accounts.id | |
| required_headcount | int | |
| justification | text | |
| status | text | `draft`,`submitted`,`approved`,`rejected`,`closed` |
| approved_by | uuid FK -> user_accounts.id nullable | |
| approved_at | timestamptz nullable | |
| created_at | timestamptz | |

### 4.2 `job_postings`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| requisition_id | uuid FK -> job_requisitions.id nullable | |
| office_id | uuid FK -> offices.id | |
| position_id | uuid FK -> job_positions.id | |
| title | text | |
| description | text | |
| qualifications | text | |
| responsibilities | text | |
| posting_status | text | `draft`,`published`,`closed`,`archived` |
| open_date | date | |
| close_date | date | |
| published_by | uuid FK -> user_accounts.id | |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 4.3 `applicant_profiles`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| user_id | uuid FK -> user_accounts.id unique nullable | |
| full_name | text | |
| email | text | |
| mobile_no | text nullable | |
| current_address | text nullable | |
| resume_url | text nullable | |
| portfolio_url | text nullable | |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 4.4 `applications`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| applicant_profile_id | uuid FK -> applicant_profiles.id | |
| job_posting_id | uuid FK -> job_postings.id | |
| application_ref_no | text unique | |
| application_status | text | `submitted`,`screening`,`shortlisted`,`interview`,`offer`,`hired`,`rejected`,`withdrawn` |
| submitted_at | timestamptz | |
| updated_at | timestamptz | |

### 4.5 `application_status_history`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| application_id | uuid FK -> applications.id | |
| old_status | text nullable | |
| new_status | text | |
| changed_by | uuid FK -> user_accounts.id nullable | system/user |
| notes | text nullable | |
| created_at | timestamptz | |

### 4.6 `application_documents`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| application_id | uuid FK -> applications.id | |
| document_type | text | `resume`,`pds`,`transcript`,`certificate`,`id`,`other` |
| file_url | text | Supabase Storage URL/path |
| file_name | text | |
| mime_type | text | |
| file_size_bytes | bigint | |
| uploaded_at | timestamptz | |

### 4.7 `application_interviews`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| application_id | uuid FK -> applications.id | |
| interview_stage | text | `hr`,`technical`,`final` |
| scheduled_at | timestamptz | |
| interview_mode | text | `onsite`,`online`,`phone` |
| interviewer_user_id | uuid FK -> user_accounts.id | |
| score | numeric(5,2) nullable | |
| result | text nullable | `pass`,`fail`,`pending` |
| remarks | text nullable | |
| created_at | timestamptz | |

### 4.8 `application_feedback`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| application_id | uuid FK -> applications.id unique | latest summary feedback |
| decision | text | `for_next_step`,`on_hold`,`rejected`,`hired` |
| feedback_text | text | |
| provided_by | uuid FK -> user_accounts.id | |
| provided_at | timestamptz | |

---

## 5) Document Management and Approvals

### 5.1 `document_categories`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| category_key | text unique | `leave`,`evaluation`,`medical`,`payroll`,`contract`,`id` |
| category_name | text | |
| requires_approval | boolean | |
| retention_years | int nullable | data retention policy |
| created_at | timestamptz | |

### 5.2 `documents`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| owner_person_id | uuid FK -> people.id | |
| category_id | uuid FK -> document_categories.id | |
| title | text | |
| description | text nullable | |
| storage_bucket | text | |
| storage_path | text | |
| current_version_no | int | default 1 |
| document_status | text | `draft`,`submitted`,`approved`,`rejected`,`archived` |
| uploaded_by | uuid FK -> user_accounts.id | |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 5.3 `document_versions`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| document_id | uuid FK -> documents.id | |
| version_no | int | |
| file_name | text | |
| mime_type | text | |
| size_bytes | bigint | |
| checksum_sha256 | text nullable | integrity |
| storage_path | text | |
| uploaded_by | uuid FK -> user_accounts.id | |
| uploaded_at | timestamptz | |
| unique(document_id, version_no) | constraint | |

### 5.4 `document_reviews`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| document_id | uuid FK -> documents.id | |
| reviewer_user_id | uuid FK -> user_accounts.id | |
| review_status | text | `pending`,`approved`,`rejected`,`needs_revision` |
| review_notes | text nullable | |
| reviewed_at | timestamptz nullable | |
| created_at | timestamptz | |

### 5.5 `document_access_logs`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| document_id | uuid FK -> documents.id | |
| viewer_user_id | uuid FK -> user_accounts.id | |
| access_type | text | `view`,`download`,`print` |
| accessed_at | timestamptz | |

---

## 6) Timekeeping and Leave Management

### 6.1 `work_schedules`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| effective_from | date | |
| effective_to | date nullable | |
| shift_name | text | |
| time_in_expected | time | |
| time_out_expected | time | |
| break_minutes | int | |
| is_flexible | boolean | |
| created_at | timestamptz | |

### 6.2 `attendance_logs`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| attendance_date | date | |
| time_in | timestamptz nullable | |
| time_out | timestamptz nullable | |
| hours_worked | numeric(5,2) nullable | |
| undertime_hours | numeric(5,2) nullable | |
| late_minutes | int nullable | |
| attendance_status | text | `present`,`late`,`absent`,`leave`,`holiday`,`rest_day` |
| source | text | `manual`,`biometric`,`import`,`api` |
| recorded_by | uuid FK -> user_accounts.id nullable | |
| created_at | timestamptz | |
| unique(person_id, attendance_date) | constraint | |

### 6.3 `leave_types`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| leave_code | text unique | |
| leave_name | text | |
| default_annual_credits | numeric(6,2) nullable | |
| requires_attachment | boolean | |
| is_active | boolean | |
| created_at | timestamptz | |

### 6.4 `leave_balances`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| leave_type_id | uuid FK -> leave_types.id | |
| year | int | |
| earned_credits | numeric(6,2) | |
| used_credits | numeric(6,2) | |
| remaining_credits | numeric(6,2) | |
| updated_at | timestamptz | |
| unique(person_id, leave_type_id, year) | constraint | |

### 6.5 `leave_requests`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| leave_type_id | uuid FK -> leave_types.id | |
| date_from | date | |
| date_to | date | |
| days_count | numeric(5,2) | |
| reason | text | |
| status | text | `pending`,`approved`,`rejected`,`cancelled` |
| reviewed_by | uuid FK -> user_accounts.id nullable | |
| reviewed_at | timestamptz nullable | |
| review_notes | text nullable | |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 6.6 `overtime_requests`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| overtime_date | date | |
| start_time | time | |
| end_time | time | |
| hours_requested | numeric(5,2) | |
| reason | text | |
| status | text | `pending`,`approved`,`rejected`,`cancelled` |
| approved_by | uuid FK -> user_accounts.id nullable | |
| approved_at | timestamptz nullable | |
| created_at | timestamptz | |

### 6.7 `time_adjustment_requests`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| attendance_log_id | uuid FK -> attendance_logs.id | |
| requested_time_in | timestamptz nullable | |
| requested_time_out | timestamptz nullable | |
| reason | text | |
| status | text | `pending`,`approved`,`rejected` |
| reviewed_by | uuid FK -> user_accounts.id nullable | |
| reviewed_at | timestamptz nullable | |
| created_at | timestamptz | |

### 6.8 `holidays`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| holiday_date | date unique | |
| holiday_name | text | |
| holiday_type | text | `regular`,`special`,`local` |
| office_id | uuid FK -> offices.id nullable | nullable for national |
| created_at | timestamptz | |

---

## 7) Payroll (Future-Ready)

### 7.1 `payroll_periods`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| period_code | text unique | e.g. `2026-02-A` |
| period_start | date | |
| period_end | date | |
| payout_date | date | |
| status | text | `open`,`processing`,`posted`,`closed` |
| created_at | timestamptz | |

### 7.2 `employee_compensations`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| person_id | uuid FK -> people.id | |
| effective_from | date | |
| effective_to | date nullable | |
| monthly_rate | numeric(14,2) | |
| daily_rate | numeric(14,2) nullable | |
| hourly_rate | numeric(14,2) nullable | |
| pay_frequency | text | `monthly`,`semi_monthly`,`weekly` |
| created_at | timestamptz | |

### 7.3 `payroll_runs`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| payroll_period_id | uuid FK -> payroll_periods.id | |
| office_id | uuid FK -> offices.id nullable | |
| run_status | text | `draft`,`computed`,`approved`,`released`,`cancelled` |
| generated_by | uuid FK -> user_accounts.id | |
| approved_by | uuid FK -> user_accounts.id nullable | |
| generated_at | timestamptz | |
| approved_at | timestamptz nullable | |

### 7.4 `payroll_items`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| payroll_run_id | uuid FK -> payroll_runs.id | |
| person_id | uuid FK -> people.id | |
| basic_pay | numeric(14,2) | |
| overtime_pay | numeric(14,2) | |
| allowances_total | numeric(14,2) | |
| deductions_total | numeric(14,2) | |
| gross_pay | numeric(14,2) | |
| net_pay | numeric(14,2) | |
| created_at | timestamptz | |
| unique(payroll_run_id, person_id) | constraint | |

### 7.5 `payroll_adjustments`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| payroll_item_id | uuid FK -> payroll_items.id | |
| adjustment_type | text | `earning`,`deduction` |
| adjustment_code | text | e.g. `TAX`,`PHILHEALTH`,`LATE`,`BONUS` |
| description | text | |
| amount | numeric(14,2) | |
| created_at | timestamptz | |

### 7.6 `payslips`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| payroll_item_id | uuid FK -> payroll_items.id unique | |
| payslip_no | text unique | |
| pdf_storage_path | text nullable | |
| released_at | timestamptz nullable | |
| viewed_at | timestamptz nullable | |
| created_at | timestamptz | |

---

## 8) Performance, PRAISE, Learning, Reports

### 8.1 `performance_cycles`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| cycle_name | text | |
| period_start | date | |
| period_end | date | |
| status | text | `draft`,`open`,`closed`,`archived` |
| created_at | timestamptz | |

### 8.2 `performance_evaluations`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| cycle_id | uuid FK -> performance_cycles.id | |
| employee_person_id | uuid FK -> people.id | |
| evaluator_user_id | uuid FK -> user_accounts.id | |
| final_rating | numeric(5,2) nullable | |
| remarks | text nullable | |
| status | text | `draft`,`submitted`,`reviewed`,`approved` |
| created_at | timestamptz | |
| updated_at | timestamptz | |

### 8.3 `praise_awards`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| award_code | text unique | |
| award_name | text | |
| description | text nullable | |
| criteria | text nullable | |
| is_active | boolean | |
| created_at | timestamptz | |

### 8.4 `praise_nominations`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| award_id | uuid FK -> praise_awards.id | |
| nominee_person_id | uuid FK -> people.id | |
| nominated_by_user_id | uuid FK -> user_accounts.id | |
| cycle_id | uuid FK -> performance_cycles.id nullable | |
| justification | text | |
| status | text | `pending`,`approved`,`rejected` |
| reviewed_by | uuid FK -> user_accounts.id nullable | |
| reviewed_at | timestamptz nullable | |
| created_at | timestamptz | |

### 8.5 `training_programs`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| program_code | text unique | |
| title | text | |
| provider | text | |
| start_date | date | |
| end_date | date | |
| mode | text | `online`,`onsite`,`hybrid` |
| status | text | `planned`,`open`,`ongoing`,`completed`,`cancelled` |
| created_at | timestamptz | |

### 8.6 `training_enrollments`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| program_id | uuid FK -> training_programs.id | |
| person_id | uuid FK -> people.id | |
| enrollment_status | text | `enrolled`,`completed`,`failed`,`dropped` |
| score | numeric(5,2) nullable | |
| certificate_url | text nullable | |
| created_at | timestamptz | |
| unique(program_id, person_id) | constraint | |

### 8.7 `generated_reports`
| Column | Type | Notes |
|---|---|---|
| id | uuid PK | |
| requested_by | uuid FK -> user_accounts.id | |
| report_type | text | `attendance`,`payroll`,`performance`,`documents`,`recruitment` |
| filters_json | jsonb | |
| file_format | text | `pdf`,`csv`,`xlsx` |
| storage_path | text nullable | |
| status | text | `queued`,`processing`,`ready`,`failed` |
| generated_at | timestamptz nullable | |
| created_at | timestamptz | |

---

## 9) Suggested Indexes

- `user_accounts(email)`, `user_accounts(account_status)`
- `user_role_assignments(user_id, is_primary)`
- `people(agency_employee_no)`
- `employment_records(person_id, is_current)`
- `applications(job_posting_id, application_status)`
- `attendance_logs(person_id, attendance_date)`
- `leave_requests(person_id, status, date_from)`
- `payroll_items(person_id)`
- `documents(owner_person_id, category_id, document_status)`
- `activity_logs(actor_user_id, created_at desc)`
- `notifications(recipient_user_id, is_read, created_at desc)`

---

## 10) Supabase Notes (Implementation)

- Enable extensions: `pgcrypto`, optionally `citext`.
- Add trigger function to auto-update `updated_at` on mutable tables.
- Add RLS for all user-facing tables.
- Keep secrets/PII encrypted at application layer before insert if required by policy.
- Use Supabase Storage buckets:
  - `hris-documents`
  - `hris-applications`
  - `hris-payslips`

---

## 11) Minimum Seed Data

1. `organizations`: DA-ATI main org row.
2. `offices`: central office + key units.
3. `roles`: admin, hr_officer, supervisor, staff, employee, applicant.
4. `permissions`: module/action permissions.
5. `role_permissions`: full admin access + scoped role grants.
6. Create first admin in `auth.users` and mirror to `user_accounts`.
7. Assign `admin` in `user_role_assignments` with `is_primary = true`.

---

This dictionary is ready to be converted into executable Supabase SQL migrations.