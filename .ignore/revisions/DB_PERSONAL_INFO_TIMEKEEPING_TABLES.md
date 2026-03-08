# DB Tables and Columns: Personal Information and Timekeeping

| Module | Table | Columns |
|---|---|---|
| Personal Information | `public.user_accounts` | `id`, `email`, `username`, `mobile_no`, `account_status`, `email_verified_at`, `last_login_at`, `lockout_until`, `failed_login_count`, `must_change_password`, `created_at`, `updated_at` |
| Personal Information | `public.people` | `id`, `user_id`, `surname`, `first_name`, `middle_name`, `name_extension`, `date_of_birth`, `place_of_birth`, `sex_at_birth`, `civil_status`, `height_m`, `weight_kg`, `blood_type`, `citizenship`, `dual_citizenship`, `dual_citizenship_country`, `telephone_no`, `mobile_no`, `personal_email`, `agency_employee_no`, `profile_photo_url`, `created_at`, `updated_at` |
| Personal Information | `public.person_addresses` | `id`, `person_id`, `address_type`, `house_no`, `street`, `subdivision`, `barangay`, `city_municipality`, `province`, `zip_code`, `country`, `is_primary`, `created_at` |
| Personal Information | `public.person_government_ids` | `id`, `person_id`, `id_type`, `id_value_encrypted`, `last4`, `created_at` |
| Personal Information | `public.person_family_spouses` | `id`, `person_id`, `surname`, `first_name`, `middle_name`, `extension_name`, `occupation`, `employer_business_name`, `business_address`, `telephone_no`, `sequence_no`, `created_at` |
| Personal Information | `public.person_family_children` | `id`, `person_id`, `full_name`, `birth_date`, `sequence_no`, `created_at` |
| Personal Information | `public.person_parents` | `id`, `person_id`, `parent_type`, `surname`, `first_name`, `middle_name`, `extension_name`, `created_at` |
| Personal Information | `public.person_educations` | `id`, `person_id`, `education_level`, `school_name`, `course_degree`, `period_from`, `period_to`, `highest_level_units`, `year_graduated`, `honors_received`, `sequence_no`, `created_at` |
| Personal Information | `public.emergency_contacts` | `id`, `person_id`, `contact_name`, `relationship`, `mobile_no`, `address`, `is_primary`, `created_at` |
| Personal Information (Applicant) | `public.applicant_profiles` | `id`, `user_id`, `full_name`, `email`, `mobile_no`, `current_address`, `training_hours_completed`, `resume_url`, `portfolio_url`, `created_at`, `updated_at` |
| Timekeeping | `public.work_schedules` | `id`, `person_id`, `effective_from`, `effective_to`, `shift_name`, `time_in_expected`, `time_out_expected`, `break_minutes`, `is_flexible`, `created_at` |
| Timekeeping | `public.attendance_logs` | `id`, `person_id`, `attendance_date`, `time_in`, `time_out`, `hours_worked`, `undertime_hours`, `late_minutes`, `attendance_status`, `source`, `recorded_by`, `created_at` |
| Timekeeping | `public.leave_types` | `id`, `leave_code`, `leave_name`, `default_annual_credits`, `requires_attachment`, `is_active`, `created_at` |
| Timekeeping | `public.leave_balances` | `id`, `person_id`, `leave_type_id`, `year`, `earned_credits`, `used_credits`, `remaining_credits`, `updated_at` |
| Timekeeping | `public.leave_requests` | `id`, `person_id`, `leave_type_id`, `date_from`, `date_to`, `days_count`, `reason`, `status`, `reviewed_by`, `reviewed_at`, `review_notes`, `created_at`, `updated_at` |
| Timekeeping | `public.overtime_requests` | `id`, `person_id`, `overtime_date`, `start_time`, `end_time`, `hours_requested`, `reason`, `status`, `approved_by`, `approved_at`, `created_at` |
| Timekeeping | `public.time_adjustment_requests` | `id`, `person_id`, `attendance_log_id`, `requested_time_in`, `requested_time_out`, `reason`, `status`, `reviewed_by`, `reviewed_at`, `created_at` |
| Timekeeping | `public.holidays` | `id`, `holiday_date`, `holiday_name`, `holiday_type`, `office_id`, `created_at` |
