# User Address Feature Documentation

## Overview
This feature allows reporters to save their address during registration and use it for quick emergency reporting.

## Database Changes

### New Table: `user_addresses`
Created a new table to store user addresses from registration:

```sql
CREATE TABLE user_addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    house_no VARCHAR(50) DEFAULT NULL,
    purok VARCHAR(100) DEFAULT NULL,
    barangay VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    landmark VARCHAR(255) DEFAULT NULL,
    is_primary TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

## Features

### 1. Registration Form Enhancement (`register.php`)
- Added comprehensive address fields to the registration form
- Required fields: Barangay, City, Province, Region
- Optional fields: House No., Purok, Postal Code, Landmark
- Address is automatically saved to `user_addresses` table upon registration
- Transaction-based registration ensures data consistency

### 2. Quick Address Selection (`report_emergency.php`)
When logged-in users visit the emergency reporting page, they see:

#### Address Choice Buttons
- **"Use My Saved Address"** button: Pre-fills location form with saved address
- **"Report Different Location"** button: Allows manual entry of new location

#### Features:
- Saved address preview displayed when selected
- Form fields are locked/disabled when using saved address
- Smooth transitions and visual feedback
- Responsive design for mobile and desktop
- Hover effects on buttons for better UX

### 3. How It Works

#### For New Users (Registration):
1. User registers with complete address information
2. Address is saved to `user_addresses` table with `is_primary = 1`
3. User can now use this address for quick reporting

#### For Reporting Emergency:
1. Logged-in user navigates to report emergency page
2. System fetches user's primary address from database
3. User sees two options:
   - **Use Saved Address**: Automatically fills all location fields
   - **Report Different Location**: Manually enter new location
4. User completes and submits the report

## User Experience

### Registration Flow
```
Register → Fill Personal Info → Fill Address → Submit → Address Saved
```

### Emergency Reporting Flow (Logged In)
```
Report Emergency → Choose Address Option →
    ├─ Use Saved: Auto-fill → Submit
    └─ New Location: Manual Entry → Submit
```

### Emergency Reporting Flow (Not Logged In)
```
Report Emergency → Manual Entry Required → Submit
```

## Technical Implementation

### Backend Changes

#### `register.php`
- Added address field processing
- Implemented transaction for user + address creation
- Added address validation
- Clear form data after successful registration

#### `report_emergency.php`
- Fetch user's primary address on page load
- Pass address data to JavaScript via `window.userSavedAddress`
- Display address choice UI for logged-in users with saved addresses

### Frontend Changes

#### HTML Structure
```html
<div class="address-choice-container">
    <button id="useSavedAddress">Use My Saved Address</button>
    <button id="useNewAddress">Report Different Location</button>
    <div id="savedAddressPreview">Preview of saved address</div>
</div>
```

#### JavaScript Functionality
- Button click handlers for address selection
- Auto-fill function for saved address
- Form field enable/disable toggle
- Visual feedback and styling updates
- Cascading select population (Region → Province → City → Barangay)

## Database Migration

Run the migration SQL file:
```bash
/opt/lampp/bin/mysql -u root disaster_monitoring < sql/create_user_addresses_table.sql
```

## Benefits

1. **Speed**: Logged-in users can report emergencies faster
2. **Accuracy**: Reduces typos and location errors
3. **Convenience**: Users don't need to remember/type full address every time
4. **Flexibility**: Users can still report from different locations when needed
5. **User Experience**: Smooth, intuitive interface with clear options

## Future Enhancements

Potential improvements for future versions:
- Multiple saved addresses per user
- Edit/update saved address from profile page
- Recent locations history
- Map integration for visual address selection
- Address validation using external APIs
- Geocoding for better location accuracy

## Testing Checklist

- [ ] New user registration with address
- [ ] Address saved correctly in database
- [ ] Logged-in user sees address choice buttons
- [ ] "Use Saved Address" fills form correctly
- [ ] "Report Different Location" allows manual entry
- [ ] Form validation works for both options
- [ ] Anonymous users can still report (no address buttons shown)
- [ ] Mobile responsive design works correctly
- [ ] Database transactions work properly

## Notes

- Anonymous users (not logged in) will not see the address choice buttons
- Users without a saved address will not see the buttons
- The `is_primary` flag allows for future multi-address support
- Address fields are validated on both frontend and backend
