# Race Selection Feature - Test Plan

## Overview
This document outlines the testing approach for the race selection feature that requires existing players without a race to pick one upon next login.

## Feature Description
Players who don't have a race selected in their profile will be automatically redirected to the race selection page when they attempt to access any protected route. Once a race is selected, they can continue to use the application normally.

## Test Scenarios

### 1. New User Registration
**Expected Behavior:** New users registering will have `race = NULL` in the database and will be prompted to select a race on first login.

**Test Steps:**
1. Navigate to `/register`
2. Fill in registration form with valid data
3. Submit registration
4. Verify redirect to `/race/select` instead of `/dashboard`
5. Verify flash message prompts user to select race

**Expected Result:** User is redirected to race selection page before accessing dashboard.

### 2. Existing User Without Race
**Expected Behavior:** Existing users (migrated from V1 or with NULL race) are redirected to race selection.

**Test Steps:**
1. Login with user account that has `race = NULL`
2. Attempt to access `/dashboard`
3. Verify redirect to `/race/select`
4. Verify flash message appears

**Expected Result:** User cannot access any protected routes until race is selected.

### 3. Race Selection Process
**Expected Behavior:** User selects a race successfully and is redirected to dashboard.

**Test Steps:**
1. Navigate to `/race/select` (or be redirected there)
2. View all 4 race options (Human, Cyborg, Android, Alien)
3. Select a race by clicking on the card
4. Click "Confirm Selection"
5. Verify database update: `users.race` = selected value
6. Verify redirect to `/dashboard`
7. Verify success flash message

**Expected Result:** Race is saved and user can access the application.

### 4. User With Race Selected
**Expected Behavior:** Users who already have a race selected can access all routes normally.

**Test Steps:**
1. Login with user account that has a valid race set
2. Access `/dashboard`
3. Verify no redirect occurs
4. Access other protected routes (/bank, /training, etc.)
5. Verify normal access

**Expected Result:** No race selection prompt, normal application flow.

### 5. Race Selection Page Security
**Expected Behavior:** Race selection page requires authentication but not race verification (to avoid infinite loop).

**Test Steps:**
1. Logout if logged in
2. Attempt to access `/race/select` directly
3. Verify redirect to `/login`
4. Login with valid credentials
5. Verify access to `/race/select` is granted

**Expected Result:** Authentication required, but race check is skipped for this route.

### 6. CSRF Protection
**Expected Behavior:** Race selection form is protected against CSRF attacks.

**Test Steps:**
1. Access `/race/select`
2. Inspect form HTML and copy CSRF token
3. Submit form without CSRF token or with invalid token
4. Verify error message appears
5. Verify race is NOT updated in database

**Expected Result:** CSRF validation prevents unauthorized submissions.

### 7. Invalid Race Selection
**Expected Behavior:** Attempting to select an invalid race fails gracefully.

**Test Steps:**
1. Access `/race/select`
2. Modify form data to submit an invalid race value (e.g., 'fake_race')
3. Submit form
4. Verify error message appears
5. Verify redirect back to `/race/select`
6. Verify race is NOT updated in database

**Expected Result:** Invalid race selections are rejected.

### 8. Race Immutability
**Expected Behavior:** Once a race is selected, users cannot change it through normal flow.

**Test Steps:**
1. Select a race as a new user
2. Attempt to access `/race/select` again
3. Verify normal access continues (no middleware block)
4. If form is submitted, it should update the race (by design)

**Note:** Current implementation allows race changes. If races should be immutable, add additional check in RaceController.

### 9. Database Migration
**Expected Behavior:** Migration adds race field without breaking existing data.

**Test Steps:**
1. Run migration: `php vendor/bin/phinx migrate`
2. Verify `users` table has `race` column
3. Verify existing users have `race = NULL`
4. Verify application continues to work

**Expected Result:** Migration succeeds, existing users have NULL race.

### 10. Middleware Execution Order
**Expected Behavior:** AuthMiddleware runs before RaceSelectionMiddleware.

**Test Steps:**
1. Access a protected route without authentication
2. Verify redirect to `/login` (not `/race/select`)
3. Login as user without race
4. Verify redirect to `/race/select`

**Expected Result:** Proper middleware chain execution.

## UI/UX Verification

### Race Selection Page Layout
- **Header:** "Select Your Race"
- **Subheader:** Explanation that choice is permanent
- **Race Cards:** 4 cards in a responsive grid (2x2 on desktop, 1 column on mobile)
- **Each Card Contains:**
  - Race name (large, accented text)
  - Description (multi-line)
  - Bonuses section (if applicable)
  - Radio button (hidden but functional)
- **Submit Button:** "Confirm Selection" at bottom center
- **Visual Feedback:**
  - Hover effect: Card glows with accent color
  - Selected state: Card highlighted with stronger glow
  - Smooth transitions on all interactions

### Accessibility
- Radio buttons are functional even though visually hidden
- Cards are labeled elements for screen readers
- Form validation messages appear clearly
- Flash messages visible at top of page

## Edge Cases

1. **User refreshes during race selection:** Form state should persist
2. **User uses browser back button:** Should return to race selection
3. **Concurrent sessions:** Race selection in one session should affect all sessions
4. **Database connection failure:** Error message shown, user can retry
5. **Multiple rapid submissions:** CSRF protection and database constraints prevent issues

## Performance Considerations

- Middleware adds one extra DB query per protected route for users without race
- Query is indexed on user_id (primary key) so performance impact is minimal
- Once race is selected, middleware passes without DB query (checks NULL in memory)

## Security Checklist

- ✅ CSRF protection on form submission
- ✅ Authentication required for race selection routes
- ✅ Input validation (race must be in allowed list)
- ✅ SQL injection prevention (using prepared statements)
- ✅ No sensitive data exposed in race configuration
- ✅ Flash messages don't leak system information

## Browser Compatibility

Test in:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Database Integrity

- `race` column is VARCHAR(50), nullable
- No foreign key constraints (race is a simple string)
- No unique constraints (multiple users can have same race)
- Column position: after `character_name`

## Rollback Plan

If issues arise:
1. Remove middleware from protected routes in `public/index.php`
2. Comment out race check in RaceSelectionMiddleware
3. Rollback migration if needed: `php vendor/bin/phinx rollback`

## Success Criteria

✅ New users can select a race before accessing the game
✅ Existing users without race are prompted to select one
✅ Race selection is secure and validated
✅ No infinite redirect loops
✅ UI is responsive and user-friendly
✅ All automated tests pass
✅ No performance degradation
