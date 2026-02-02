## Scholarship Application System - Database Setup Verification

### What Has Been Implemented

#### 1. **Database Table** 
The `scholarship_applications` table is automatically created when the form is first submitted. The table includes:
- Student information (name, age, sex, date of birth)
- Family information (parents' names and occupations)
- Address details (street, house number, barangay, municipality)
- Academic information (level, semester)
- Contact information (cellphone number)
- Document file paths (4 required documents)
- Status tracking (pending, approved, rejected, incomplete)
- Timestamps (submission and update dates)

#### 2. **Application Processing Flow**

**Form Submission** (`application_form.php`):
1. User fills out personal information (Page 1)
2. User uploads 4 required documents (Page 2)
3. Form validates all required fields locally
4. User confirms submission with modal
5. Form submits to `/students/process-application` route

**Backend Processing** (`process_application.php`):
1. ✅ Verifies user is logged in and is a student
2. ✅ Validates CSRF token for security
3. ✅ Creates upload directory if needed
4. ✅ Uploads all 4 files to `storage/uploads/requirements/`
5. ✅ Auto-creates database table on first submission
6. ✅ Sanitizes all form inputs
7. ✅ Inserts data into database with status = 'pending'
8. ✅ Returns success message and redirects to my_application page

#### 3. **File Upload Details**

**Upload Location**: `storage/uploads/requirements/`

**File Naming Format**: `app_{user_id}_{field_name}_{timestamp}.{ext}`

**Supported File Types**:
- Images: JPEG, PNG, GIF
- Documents: PDF

**File Size Limit**: 5MB per file

**Required Documents**:
1. Certificate of Registration (COR) / Certificate of Enrollment (COE)
2. 2nd Semester Certificate of Grades
3. Original Barangay Indigency of Student
4. Voters Certification

#### 4. **Database Fields Stored**

**Personal Information**:
- first_name, middle_name, last_name
- sex (Male/Female/Other)
- date_of_birth
- age
- cellphone_number

**Family Information**:
- mothers_maiden_name
- mothers_occupation
- fathers_name
- fathers_occupation

**Address**:
- street_address
- house_number
- barangay
- municipality

**Academic**:
- academic_level (1st-4th Year)
- semester

**Documents** (file paths):
- cor_coe_file
- cert_grades_file
- barangay_indigency_file
- voters_cert_file

**Status & Metadata**:
- status (pending by default)
- submission_date
- updated_at
- notes (for admin comments)

### How to Verify It's Working

1. **Login as a student**
2. **Fill out the application form completely**
3. **Upload all 4 required documents**
4. **Click "Submit Application"**
5. **Check the database**:
   ```sql
   SELECT * FROM scholarship_applications WHERE user_id = {your_user_id};
   ```

### Error Messages You Might See

| Error | Cause | Solution |
|-------|-------|----------|
| "Database error: Table not found" | Very rare - table auto-creates | Clear browser cache and resubmit |
| "Failed to move uploaded file" | Permission issue on `storage/` folder | Ensure `storage/uploads/requirements/` has write permissions (755) |
| "Invalid file type" | Wrong file format uploaded | Use JPEG, PNG, GIF, or PDF only |
| "File too large" | File exceeds 5MB | Compress or reduce file size |
| "Required fields are missing" | Form validation failed | Fill all required fields |

### Database Query to View Applications

```sql
-- View all applications
SELECT * FROM scholarship_applications;

-- View specific student's applications
SELECT * FROM scholarship_applications WHERE user_id = {user_id};

-- View pending applications
SELECT * FROM scholarship_applications WHERE status = 'pending';

-- View application with all details
SELECT 
    a.id,
    u.username,
    u.email,
    a.first_name,
    a.last_name,
    a.academic_level,
    a.status,
    a.submission_date
FROM scholarship_applications a
JOIN users u ON a.user_id = u.id
ORDER BY a.submission_date DESC;
```

### Next Steps

1. ✅ Form is ready to use
2. ✅ Database table auto-creates on first submission
3. ✅ All data is saved automatically
4. ⏭️ You can create a view page to display submitted applications
5. ⏭️ You can create an admin panel to approve/reject applications
