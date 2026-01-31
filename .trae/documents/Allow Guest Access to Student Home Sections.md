## Current State
- The student navbar already hides “My Application” when there is no logged-in user (auth_user_id is empty).
- However, the Student Home page (students/home → Homepage.php) currently forces login via enforce_student_profile_completed(), so Home/About Us/Requirements cannot be viewed as a guest.
- The student entry page (public/students/index.php) also redirects guests straight to students/login.

## Changes To Make
1. Make Student Home page publicly accessible
   - Update public/students/Homepage.php to remove the enforce_student_profile_completed($conn) call.
   - Keep session start and navbar include so the page still adapts when a user is logged in.

2. Make student landing page go to Home instead of Login
   - Update public/students/index.php to redirect to route_url('students/home') instead of route_url('students/login').

3. Keep “My Application” hidden when not logged in
   - No change needed: public/students/includes/navbar.php already wraps “My Application” in `if ($__auth_id)`.

## Verification
- As guest (no session): open / (root) and /students/home, confirm Home loads and About Us + Requirements anchors work, and “My Application” is not visible in navbar.
- As logged-in student: confirm “My Application” appears, profile dropdown works, and /students/application still requires login/profile completion.

## Files Involved
- public/students/Homepage.php
- public/students/index.php
- public/students/includes/navbar.php (verify only; likely no edit)