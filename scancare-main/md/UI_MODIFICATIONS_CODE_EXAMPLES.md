

**Version**: 1.0  
**Last Updated**: June 4, 2026

1. [Dashboard Integration Examples](#dashboard-integration-examples)
2. [User Name Formatting Examples](#user-name-formatting-examples)
3. [Navigation Component Usage](#navigation-component-usage)
4. [Complete Implementation Samples](#complete-implementation-samples)



**After successful login, update the navbar with resident's name:**

```php
<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'resident') {
    header('Location: login.php');
    exit;
}


$firstName = $_SESSION['first_name'] ?? 'Resident';
$lastName = $_SESSION['last_name'] ?? 'User';
$middleInitial = $_SESSION['middle_initial'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <!
</head>
<body>
    <!
    <!
    <?php include 'navbar-component.html'; ?>

    <!
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            updateNavbarName({
                firstName: '<?php echo htmlspecialchars($firstName); ?>',
                lastName: '<?php echo htmlspecialchars($lastName); ?>',
                middleInitial: '<?php echo htmlspecialchars($middleInitial); ?>'
            });
        });
    </script>
</body>
</html>
```

**Expected Result in Navbar**: "Tala, Gabriel C." (or whatever the user's name is)


**For Barangay Health Workers:**

```php
<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'bhw') {
    header('Location: login.php');
    exit;
}


$bhw_id = $_SESSION['user_id'];
$bhw_query = "SELECT first_name, last_name, middle_initial FROM bhw_staff WHERE id = ?";
$stmt = $db->prepare($bhw_query);
$stmt->execute([$bhw_id]);
$bhw = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <!
</head>
<body>
    <!
    <?php include 'navbar-component.html'; ?>

    <!
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            updateNavbarName({
                firstName: '<?php echo htmlspecialchars($bhw['first_name']); ?>',
                lastName: '<?php echo htmlspecialchars($bhw['last_name']); ?>',
                middleInitial: '<?php echo htmlspecialchars($bhw['middle_initial'] ?? ''); ?>'
            });
        });
    </script>
</body>
</html>
```

**Expected Result in Navbar**: "Santos, Maria A." (BHW name formatted)


**For System Administrators:**

```php
<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}


$admin_info = [
    'firstName' => $_SESSION['admin_first_name'] ?? 'Administrator',
    'lastName' => $_SESSION['admin_last_name'] ?? 'Account',
    'middleInitial' => $_SESSION['admin_middle_initial'] ?? 'A'
];
?>

<!DOCTYPE html>
<html>
<head>
    <!
</head>
<body>
    <!
    <?php include 'navbar-component.html'; ?>

    <!
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            updateNavbarName({
                firstName: '<?php echo htmlspecialchars($admin_info['firstName']); ?>',
                lastName: '<?php echo htmlspecialchars($admin_info['lastName']); ?>',
                middleInitial: '<?php echo htmlspecialchars($admin_info['middleInitial']); ?>'
            });
        });
    </script>
</body>
</html>
```



**Use the `formatUserName()` function directly:**

```javascript

const name1 = formatUserName('Gabriel', 'Tala', 'C');
console.log(name1);  


const name2 = formatUserName('Maria', 'Santos');
console.log(name2);  


const name3 = formatUserName('Juan', 'Dela Cruz', 'R');
console.log(name3);  


const name4 = formatUserName('José', 'García', 'L');
console.log(name4);  
```


**Create a PHP equivalent function in your backend:**

```php
<?php

function formatUserNamePHP($firstName, $lastName, $middleInitial = '') {
    $formatted = $lastName . ', ' . $firstName;
    if (!empty($middleInitial)) {
        $initial = strtoupper(substr(trim($middleInitial), 0, 1));
        $formatted .= ' ' . $initial . '.';
    }
    return $formatted;
}


echo formatUserNamePHP('Gabriel', 'Tala', 'C');


echo formatUserNamePHP('Maria', 'Santos', 'A');

?>
```



**Method 1: HTML Include (Static)**

```html
<!DOCTYPE html>
<html>
<head>
    <title>My Dashboard</title>
</head>
<body>
    <!
    <iframe src="navbar-component.html" style="width:100%; height:80px; border:none;"></iframe>
    
    <!
    <main>
        <!
    </main>
</body>
</html>
```

**Method 2: PHP Include (Recommended)**

```php
<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Dashboard</title>
</head>
<body>
    <!
    <?php include 'navbar-component.html'; ?>
    
    <!
    <main style="margin-top: 80px;">
        <!
    </main>
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof updateNavbarName === 'function') {
                updateNavbarName({
                    firstName: '<?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Guest'); ?>',
                    lastName: '<?php echo htmlspecialchars($_SESSION['last_name'] ?? 'User'); ?>',
                    middleInitial: '<?php echo htmlspecialchars($_SESSION['middle_initial'] ?? ''); ?>'
                });
            }
        });
    </script>
</body>
</html>
```

**Method 3: JavaScript Dynamic Loading**

```html
<!DOCTYPE html>
<html>
<head>
    <title>My Dashboard</title>
</head>
<body>
    <div id="navbar-container"></div>
    
    <script>
        
        fetch('navbar-component.html')
            .then(response => response.text())
            .then(html => {
                document.getElementById('navbar-container').innerHTML = html;
                
                
                if (typeof initializeNavbar === 'function') {
                    initializeNavbar();
                }
                
                
                if (typeof updateNavbarName === 'function') {
                    updateNavbarName({
                        firstName: 'Gabriel',
                        lastName: 'Tala',
                        middleInitial: 'C'
                    });
                }
            });
    </script>
</body>
</html>
```



**On `login.php` (after successful authentication):**

```php
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    
    $user = authenticateUser($email, $password);
    
    if ($user) {
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['middle_initial'] = $user['middle_initial'] ?? '';
        $_SESSION['user_role'] = $user['role'];
        
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
```

**On `dashboard.php` (page that user is redirected to):**

```php
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard — ScanCare</title>
</head>
<body>
    <!
    <?php include 'navbar-component.html'; ?>
    
    <main>
        <h1>Welcome to Your Dashboard</h1>
        <!
    </main>
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            const userFirstName = '<?php echo htmlspecialchars($_SESSION['first_name']); ?>';
            const userLastName = '<?php echo htmlspecialchars($_SESSION['last_name']); ?>';
            const userMiddleInitial = '<?php echo htmlspecialchars($_SESSION['middle_initial'] ?? ''); ?>';
            
            
            updateNavbarName({
                firstName: userFirstName,
                lastName: userLastName,
                middleInitial: userMiddleInitial
            });
        });
    </script>
</body>
</html>
```


**In `patient-id-card.php`:**

```php
<?php
session_start();

$patientData = [
    'firstName' => 'Gabriel',
    'lastName' => 'Tala',
    'middleInitial' => 'C',
    'patientId' => 'SC-2026-00847',
    
];


function formatNameFormal($firstName, $lastName, $middleInitial = '') {
    $formal = $lastName . ', ' . $firstName;
    if (!empty($middleInitial)) {
        $formal .= ' ' . strtoupper(substr($middleInitial, 0, 1)) . '.';
    }
    return $formal;
}


$patientData['formalName'] = formatNameFormal(
    $patientData['firstName'],
    $patientData['lastName'],
    $patientData['middleInitial']
);
?>

<!
<div class="detail-group">
    <span class="detail-label">Full Name</span>
    <div class="detail-value">
        <?php echo htmlspecialchars($patientData['formalName']); ?>
        <!
    </div>
</div>
```


**Handling different user roles with proper name formatting:**

```php
<?php
session_start();


function getFormattedUserName($user_id, $role) {
    global $db;
    
    $table = match($role) {
        'resident' => 'residents',
        'bhw' => 'bhw_staff',
        'admin' => 'system_admins',
        default => 'users'
    };
    
    $query = "SELECT first_name, last_name, middle_initial FROM {$table} WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        return formatUserNamePHP(
            $user['first_name'],
            $user['last_name'],
            $user['middle_initial'] ?? ''
        );
    }
    return 'Guest User';
}

function formatUserNamePHP($firstName, $lastName, $middleInitial = '') {
    $formatted = $lastName . ', ' . $firstName;
    if (!empty($middleInitial)) {
        $initial = strtoupper(substr(trim($middleInitial), 0, 1));
        $formatted .= ' ' . $initial . '.';
    }
    return $formatted;
}


$userDisplayName = getFormattedUserName($_SESSION['user_id'], $_SESSION['user_role']);
?>

<!
<script>
    updateNavbarName('<?php echo htmlspecialchars($userDisplayName); ?>');
</script>
```


- Always sanitize user input with `htmlspecialchars()` in PHP
- Use the `formatUserName()` function for consistent formatting
- Include `navbar-component.html` once per page (not multiple times)
- Call `updateNavbarName()` after page load completes
- Store formatted name in sessionStorage for persistence

- Pass unsanitized user names directly to `updateNavbarName()`
- Hardcode user names instead of fetching from database
- Include the navbar component multiple times on same page
- Forget the middle initial when user data is available
- Assume user data exists without checking session variables


**Solution**: Check that `updateNavbarName()` is called after DOM loads:
```javascript
document.addEventListener('DOMContentLoaded', function() {
    updateNavbarName({
        firstName: 'Gabriel',
        lastName: 'Tala',
        middleInitial: 'C'
    });
});
```

**Solution**: Verify the function is called with an object (not a string):
```javascript

updateNavbarName({
    firstName: 'Gabriel',
    lastName: 'Tala',
    middleInitial: 'C'
});


updateNavbarName('Gabriel Tala');
```

**Solution**: Ensure navbar HTML is included before your page content:
```php
<!
<?php include 'navbar-component.html'; ?>
<main>Your content here</main>

<!
<main>Your content here</main>
<?php include 'navbar-component.html'; ?>
```


- [UI_MODIFICATIONS_INTEGRATION_GUIDE.md](UI_MODIFICATIONS_INTEGRATION_GUIDE.md) — Complete integration guide
- [navbar-component.html](navbar-component.html) — Navbar component with all functions
- [patient-id-card.php](patient-id-card.php) — Example of formal name formatting
- [dashboard.php](dashboard.php) — Resident dashboard example



**End of Code Examples**

