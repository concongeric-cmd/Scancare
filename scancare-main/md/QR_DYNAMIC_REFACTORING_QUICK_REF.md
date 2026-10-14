

Refactored QR code URL generation from IP-based detection to host-based detection for full network adaptability.


```php
$local_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
$port = $_SERVER['SERVER_PORT'] ?? '80';
$protocol = $_SERVER['REQUEST_SCHEME'] ?? 'http';
$port_suffix = ($port == '80' || $port == '443') ? '' : ':' . $port;
$record_url = $protocol . ':
```

```php

$is_https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    $_SERVER['SERVER_PORT'] == 443 ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
);
$protocol = $is_https ? 'https' : 'http';


$host = $_SERVER['HTTP_HOST'] ?? 'localhost';


$record_url = $protocol . ':


$qr_code_url = "https:
```


| Aspect | Old | New |
|
| **Host Source** | `$_SERVER['SERVER_ADDR']` (server's internal IP) | `$_SERVER['HTTP_HOST']` (client's accessed host) |
| **Port Handling** | Manual (separate variable + logic) | Automatic (included in HTTP_HOST) |
| **HTTPS Detection** | Single check (`REQUEST_SCHEME`) | Triple check (Apache, port, proxy headers) |
| **Proxy Support** | ❌ No | ✅ Yes |
| **Port Inference** | Error-prone | Reliable |
| **Lines of Code** | 7 | 14 (more explicit, well-commented) |


**`$_SERVER['HTTP_HOST']`** comes from the HTTP `Host:` header:
```
GET /itelec4/ HTTP/1.1
Host: 192.168.1.45:8080  ← This becomes $_SERVER['HTTP_HOST']
```

**Benefits**:
- ✅ Always matches how the client accessed the server
- ✅ Already includes port if non-standard
- ✅ Works with localhost, IPs, domains, Docker, proxies
- ✅ Industry standard (used in frameworks like Laravel, Symfony)


| Scenario | QR Encodes |
|
| `http:
| `http:
| `http:
| `https:
| `https:


✅ The "Your Patient ID" card is **completely unchanged**  
✅ Only the URL generation logic was refactored  
✅ QR image display remains the same  
✅ Patient ID display remains the same  
✅ All styling preserved  


1. Access `http:
2. Log in as resident
3. Find QR code on "Your Patient ID" card
4. Scan with phone
5. Should redirect to your network's IP/hostname + /view_record.php?id=...


Just upload updated `dashboard_resident.php` - that's it!

- ✅ No database changes
- ✅ No configuration needed
- ✅ Works immediately
- ✅ Backward compatible



**File**: dashboard_resident.php (Lines 31-64)  
**Status**: ✅ Complete  
**Date**: June 16, 2026

