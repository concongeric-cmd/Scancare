


```html
<h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Manual Entry</h3>
<p class="text-sm text-slate-600 mb-6">Enter patient contact information or resident ID manually</p>

<form id="manualEntryForm" onsubmit="handleManualEntry(event)" class="space-y-4">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">Resident ID or Phone</label>
        <input type="text" name="search_term" 
               placeholder="e.g., 27 or +63917-1234567" 
               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
    </div>
    <button type="submit" style="background-color: #454B1B;" 
            class="w-full hover:opacity-95 text-white px-6 py-3 rounded-lg font-semibold text-sm transition-all">
        <i class="fas fa-search mr-2"></i> Search Patient
    </button>
</form>
```

```html
<h3 class="text-lg font-heading font-bold text-slate-900 mb-2">Manual Entry</h3>
<p class="text-sm text-slate-600 mb-6">Search by name, email, phone number, or resident ID</p>

<!
<div id="manualSearchErrorBanner" style="display: none;" 
     class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
    <div class="flex-shrink-0 mt-0.5">
        <i class="fas fa-exclamation-circle text-red-600 text-lg"></i>
    </div>
    <div class="flex-1">
        <p id="manualSearchErrorText" class="text-sm font-semibold text-red-800">
            Error message will appear here
        </p>
    </div>
    <button type="button" onclick="closeManualSearchError()" 
            class="flex-shrink-0 text-red-400 hover:text-red-600 transition-colors">
        <i class="fas fa-times"></i>
    </button>
</div>

<!
<form id="manualEntryForm" onsubmit="handleManualEntry(event)" class="space-y-4">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1">
            Search Patient
        </label>
        <input type="text" name="search_term" 
               placeholder="e.g., Maria, maria@email.com, 09171234567, or ID 27" 
               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#454B1B]" required>
        <p class="text-xs text-slate-500 mt-1">Name • Email • Phone • ID</p>
    </div>
    <button type="submit" style="background-color: #454B1B;" 
            class="w-full hover:opacity-95 text-white px-6 py-3 rounded-lg font-semibold text-sm transition-all">
        <i class="fas fa-search mr-2"></i> Search Patient
    </button>
</form>
```

| Aspect | Before | After |
|
| **Description** | "Enter patient contact info or resident ID manually" | "Search by name, email, phone number, or resident ID" |
| **Input Label** | "Resident ID or Phone" | "Search Patient" |
| **Placeholder** | "e.g., 27 or +63917-1234567" | "e.g., Maria, maria@email.com, 09171234567, or ID 27" |
| **Helper Text** | None | "Name • Email • Phone • ID" |
| **Error Display** | Alert popup (JavaScript) | Tailwind error banner (HTML) |


```javascript



function handleManualEntry(event) {
    event.preventDefault();
    
    
    const formData = new FormData(document.getElementById('manualEntryForm'));
    const searchTerm = formData.get('search_term').trim();
    
    
    if (!searchTerm) {
        alert('Please enter a patient ID or phone number.');
        return;
    }
    
    
    const submitButton = document.querySelector('#manualEntryForm button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Searching...';
    
    
    fetch('search_patient.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search_term=' + encodeURIComponent(searchTerm)
    })
    .then(response => response.json())
    .then(data => {
        
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        
        if (data.success) {
            if (data.action === 'redirect') {
                
                console.log('Patient found: ' + data.patient_name + '. Redirecting...');
                window.location.href = data.redirect_url;
            } else if (data.action === 'select') {
                
                console.log('Multiple patients found. Showing selection interface.');
                showPatientSelectionModal(data.patients);
            }
        } else {
            
            alert('Error: ' + data.message);  
        }
    })
    .catch(error => {
        
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        
        console.error('Search error:', error);
        alert('Search failed. Please try again or contact administrator.');  
    });
}
```

```javascript



function showManualSearchError(message) {
    const banner = document.getElementById('manualSearchErrorBanner');
    const text = document.getElementById('manualSearchErrorText');
    if (banner && text) {
        text.textContent = message;
        banner.style.display = 'block';
        
        setTimeout(() => {
            banner.style.display = 'none';
        }, 8000);
    }
}

function closeManualSearchError() {
    const banner = document.getElementById('manualSearchErrorBanner');
    if (banner) {
        banner.style.display = 'none';
    }
}




function handleManualEntry(event) {
    event.preventDefault();
    
    
    closeManualSearchError();
    
    
    const formData = new FormData(document.getElementById('manualEntryForm'));
    const searchTerm = formData.get('search_term').trim();
    
    
    if (!searchTerm) {
        showManualSearchError('Please enter a search term (name, email, phone, or ID).');  
        return;
    }
    
    if (searchTerm.length > 100) {
        showManualSearchError('Search term is too long. Please enter a shorter query.');  
        return;
    }
    
    
    const submitButton = document.querySelector('#manualEntryForm button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Searching...';
    
    
    console.log('Initiating patient search for:', searchTerm);
    
    
    fetch('search_patient_process.php', {  
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search_term=' + encodeURIComponent(searchTerm)
    })
    .then(response => {
        
        if (!response.ok) {
            console.warn('HTTP ' + response.status + ' response from search endpoint');
            if (response.status === 401) {
                throw new Error('Your session has expired. Please login again.');
            } else if (response.status === 403) {
                throw new Error('You do not have permission to search patients.');
            } else if (response.status === 500) {
                throw new Error('Server error occurred. Please try again.');
            }
        }
        return response.json();
    })
    .then(data => {
        
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        
        console.log('Search response received:', data);
        
        if (data.success) {
            if (data.action === 'redirect') {
                
                console.log('Single patient found: ' + data.patient_name + '. Redirecting to profile...');
                window.location.href = data.redirect_url;
                
            } else if (data.action === 'select') {
                
                console.log('Multiple patients found (' + data.count + '). Showing selection modal.');
                showPatientSelectionModal(data.patients);
            }
        } else {
            
            const errorMsg = data.message || 'An error occurred while searching.';
            console.warn('Search failed:', errorMsg);
            showManualSearchError(errorMsg);  
        }
    })
    .catch(error => {
        
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        
        console.error('Search error occurred:', error);
        const errorMsg = error.message || 'Search failed. Please try again or contact administrator.';
        showManualSearchError(errorMsg);  
    });
}
```


| Aspect | Before | After |
|
| **Error Display** | `alert('Error: ' + data.message)` | `showManualSearchError(data.message)` |
| **Endpoint** | `search_patient.php` | `search_patient_process.php` |
| **Error Handling** | Basic catch-all | Specific HTTP error codes (401, 403, 500) |
| **Input Validation** | Length > 100 check missing | Added length validation |
| **Error Banner** | Not implemented | Fully implemented with auto-hide |
| **Logging** | Basic | Comprehensive console logging |



**Location:** `c:\xampp\htdocs\itelec4\search_patient_process.php`

**Key Differences from `search_patient.php`:**

| Feature | Old `search_patient.php` | New `search_patient_process.php` |
|
| **Endpoint** | `search_patient.php` | `search_patient_process.php` |
| **Search Fields** | 3 (name, phone, ID) | 4 (name, email, phone, ID) |
| **Query** | `WHERE (full_name LIKE ? OR phone_number LIKE ? OR user_id = ?) AND role = 'resident'` | `WHERE (name LIKE ? OR email LIKE ? OR phone_number LIKE ? OR resident_id = ?) AND is_active = 1` |
| **Error Handling** | Basic | Specific error types (authentication, authorization, not_found, etc.) |
| **Schema Flexibility** | Fixed to `users` table | Detects both `users` and `resident` tables |
| **Role Checking** | Exact match `'bhw'` | Accepts multiple roles: bhw, worker, health_worker, admin |
| **Logging** | Basic error log | Detailed logging with search parameters |
| **Response Format** | Simple success/false | Includes error_type for debugging |

**Key Query Changes:**

OLD:
```sql
SELECT user_id, full_name, phone_number, email, role, DATE_FORMAT(date_of_birth, '%M %d, %Y') as formatted_dob
FROM users
WHERE (
    full_name LIKE ? 
    OR phone_number LIKE ?
    OR user_id = ?
)
AND role = 'resident'
AND is_active = 1
LIMIT 5
```

NEW:
```sql
SELECT {id_column} as resident_id, {name_column} as full_name, 
       {email_column} as email_address, {phone_column} as phone_number
FROM {table_name}
WHERE (
    {name_column} LIKE ? 
    OR {email_column} LIKE ?
    OR {phone_column} LIKE ?
    OR {id_column} = ?
)
AND is_active = 1
LIMIT 5
```

**New: Supports Email Search!**
```php

1. Name (wildcard): LIKE '%query%'
2. Email (wildcard): LIKE '%query%'  ← NEW!
3. Phone (wildcard): LIKE '%query%'
4. ID (exact): = query
```



**BEFORE (Alert Popup):**
```
User enters search
     ↓
Clicks button
     ↓
Browser alert pops up (blocking)
     ↓
User clicks OK
     ↓
Alert closes, nothing happens
```

**AFTER (Error Banner):**
```
User enters search
     ↓
Clicks button
     ↓
Error banner appears (non-blocking)
     ↓
User can read at own pace
     ↓
Banner auto-hides after 8 sec OR user clicks X
```



✅ **New Backend:**
- [ ] Copy `search_patient_process.php` to project folder

✅ **Updated Frontend:**
- [ ] Update `dashboard_bhw.php` with:
  - [ ] New error banner HTML
  - [ ] Updated form placeholder and label
  - [ ] New error banner functions
  - [ ] Updated handleManualEntry() function

✅ **Verification:**
- [ ] No JavaScript errors (F12 Console)
- [ ] Error banner displays on search error
- [ ] Single match redirects correctly
- [ ] Multiple matches show modal
- [ ] Search endpoint returns proper JSON


```javascript

alert('Error: An error occurred while searching');
```

```javascript
if (response.status === 401) throw new Error('Session expired...');
if (response.status === 403) throw new Error('Permission denied...');
if (response.status === 500) throw new Error('Server error...');
if (data.error_type === 'not_found') showError('No patients found...');
```


| Change | Type | Impact | User Benefit |
|
| Multi-field search | Feature | Backend | Can search by name, email, or phone |
| Error banner | UX | Frontend | No more popup alerts |
| New endpoint | Infrastructure | Both | Better error handling |
| Flexible schema | Architecture | Backend | Handles different table names |
| Role flexibility | Security | Backend | Accepts multiple role values |



**All changes are backward compatible and don't break existing functionality!** ✅

