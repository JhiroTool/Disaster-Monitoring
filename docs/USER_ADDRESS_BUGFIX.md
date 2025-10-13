# User Address Feature - Bug Fix

## Issue Identified

User ID 6 has a saved address in the database, but it wasn't being auto-filled correctly on the report_emergency.php page.

### Root Cause

The issue was **case-sensitivity mismatch** between:
- Database values (lowercase): `"halang"`, `"lipa city"`, `"batangas"`, `"calabarzon"`
- JavaScript dropdown options (proper case): `"Halang"`, `"Lipa City"`, `"Batangas"`, `"CALABARZON"`

The JavaScript was doing **exact string matching** (`selectElement.value = savedValue`), which failed when the case didn't match.

## Database State (User ID 6)

```sql
SELECT * FROM user_addresses WHERE user_id = 6;

| address_id | user_id | house_no | purok   | barangay | city      | province | region     | postal_code | landmark        | is_primary |
|------------|---------|----------|---------|----------|-----------|----------|------------|-------------|-----------------|------------|
| 1          | 6       | 231      | purok 3 | halang   | lipa city | batangas | calabarzon | 4217        | Near the school | 1          |
```

## Solution Implemented

### 1. **Case-Insensitive Option Matching**

Added a helper function that searches through select options and matches values case-insensitively:

```javascript
function findAndSelectOption(selectElement, targetValue) {
    if (!selectElement || !targetValue) return false;
    
    const targetLower = targetValue.toString().toLowerCase().trim();
    const options = selectElement.options;
    
    for (let i = 0; i < options.length; i++) {
        const optionValue = options[i].value.toLowerCase().trim();
        if (optionValue === targetLower) {
            selectElement.selectedIndex = i;
            return true;
        }
    }
    return false;
}
```

### 2. **Improved Event Handling**

Changed from direct `onchange()` calls to `dispatchEvent(new Event('change'))` for better event propagation.

### 3. **Better Visual Feedback**

- Added opacity change when fields are disabled
- Clear all fields when switching to "New Address" mode
- Reset dropdown menus properly
- Improved hover effects

### 4. **Extended Timing**

Increased setTimeout delays from 300ms to 500ms to ensure cascading dropdowns (Province → City → Barangay) have time to load.

## Files Modified

### `/opt/lampp/htdocs/Disaster-Monitoring/report_emergency.php`

**Changes:**
1. Added `findAndSelectOption()` helper function
2. Updated `fillSavedAddress()` to use case-insensitive matching
3. Enhanced `enableNewAddress()` to properly reset forms
4. Improved disabled field styling
5. Fixed hover effect detection

## Testing

### Debug Page Created: `test_user_address.php`

Visit this page while logged in to verify:
- ✅ User is logged in
- ✅ User details are loaded
- ✅ User address exists in database
- ✅ All address fields are populated correctly

### Manual Testing Steps

1. **Login as user with saved address** (e.g., User ID 6)
2. **Navigate to Report Emergency page**
3. **Verify buttons appear:**
   - "Use My Saved Address" button
   - "Report Different Location" button
4. **Click "Use My Saved Address":**
   - ✅ Region should be set to "CALABARZON"
   - ✅ Province should be set to "Batangas"
   - ✅ City should be set to "Lipa City"
   - ✅ Barangay should be set to "Halang"
   - ✅ Purok should show "purok 3"
   - ✅ House No should show "231"
   - ✅ Landmark should show "Near the school"
   - ✅ All fields should be locked/disabled with gray background
   - ✅ Green preview box should appear showing the address
5. **Click "Report Different Location":**
   - ✅ All fields should be cleared and enabled
   - ✅ Green preview box should disappear

## Expected Behavior Now

### For User ID 6:
```
Database Values:     JavaScript Will Match:
--------------       ---------------------
halang          →    Halang (option in dropdown)
lipa city       →    Lipa City (option in dropdown)
batangas        →    Batangas (option in dropdown)
calabarzon      →    CALABARZON (option in dropdown)
```

The system now uses **case-insensitive string comparison** to find the correct option regardless of how it was stored in the database.

## Additional Improvements

1. **Better Error Handling**: Function returns `false` if option not found
2. **Null Safety**: Checks if elements exist before accessing
3. **Trim Whitespace**: Removes leading/trailing spaces
4. **Visual Clarity**: Disabled fields have reduced opacity
5. **Form Reset**: New address mode clears all previous selections

## Future Considerations

### Data Normalization
Consider normalizing address data on input:
```php
// Example: Capitalize first letter of each word
function normalizeAddress($value) {
    return ucwords(strtolower(trim($value)));
}
```

Apply to registration form:
```php
$barangay = normalizeAddress($_POST['barangay']);
$city = normalizeAddress($_POST['city']);
$province = normalizeAddress($_POST['province']);
$region = strtoupper(normalizeAddress($_POST['region']));
```

This would ensure consistent formatting across the system.

## Debugging Tools

### Quick SQL Checks:
```sql
-- Check if user has address
SELECT * FROM user_addresses WHERE user_id = 6;

-- Check all user addresses
SELECT u.user_id, u.username_reporters, ua.* 
FROM users u 
LEFT JOIN user_addresses ua ON u.user_id = ua.user_id 
WHERE u.role = 'reporter';

-- Find users without addresses
SELECT u.user_id, u.username_reporters, u.first_name, u.last_name 
FROM users u 
LEFT JOIN user_addresses ua ON u.user_id = ua.user_id 
WHERE u.role = 'reporter' AND ua.address_id IS NULL;
```

### Browser Console Checks:
```javascript
// Check if saved address loaded
console.log(window.userSavedAddress);

// Check current form values
console.log({
    region: document.getElementById('region').value,
    province: document.getElementById('province').value,
    city: document.getElementById('city').value,
    barangay: document.getElementById('barangay').value
});
```

## Status

✅ **FIXED** - User address now loads correctly with case-insensitive matching.

Test with User ID 6 or any user with saved address data.
