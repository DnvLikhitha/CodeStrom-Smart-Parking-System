# 🔴 Payment Update Bug - EXACT FIX NEEDED

## Problem Identified

✅ **API returns:** `{"status":"success","message":"Payment updated"}`  
❌ **Database shows:** `payment_status` is set to **empty string** `""` instead of `"Completed"`

**This is NOT a permission issue** - the database IS being updated, but with the wrong value.

---

## 🐛 Root Cause

Your PHP `update_payment` endpoint has a bug in the SQL UPDATE query. It's likely:

1. Using wrong variable name
2. Setting to empty string instead of the passed value
3. Variable not being extracted from JSON properly

---

## 🔧 THE FIX

### ❌ **WRONG PHP Code (Current):**

```php
case 'update_payment':
    $data = json_decode(file_get_contents("php://input"), true);
    
    $booking_uid = $data['booking_uid'] ?? '';
    $status = $data['status'] ?? '';  // This might be the issue
    $transaction_id = $data['transaction_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    
    // BUG: Using wrong variable or column name
    $query = "
        UPDATE bookings 
        SET payment_status = ''  -- ❌ Setting to empty string!
        WHERE booking_uid = '$booking_uid'
    ";
    
    // OR maybe:
    $query = "
        UPDATE bookings 
        SET paymentStatus = '$status'  -- ❌ Wrong column name (camelCase)
        WHERE booking_uid = '$booking_uid'
    ";
    
    // OR maybe:
    $query = "
        UPDATE bookings 
        SET payment_status = '$payment_status'  -- ❌ Wrong variable name
        WHERE booking_uid = '$booking_uid'
    ";
```

### ✅ **CORRECT PHP Code:**

```php
case 'update_payment':
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Extract data
    $booking_uid = $data['booking_uid'] ?? '';
    $payment_status = $data['status'] ?? '';  // ✅ Note: JSON has 'status', not 'payment_status'
    $transaction_id = $data['transaction_id'] ?? '';
    $amount = $data['amount'] ?? 0;
    
    // Validate
    if (!$booking_uid || !$payment_status) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }
    
    // ✅ CORRECT UPDATE query
    $query = "
        UPDATE bookings 
        SET payment_status = '$payment_status',
            transaction_id = '$transaction_id'
        WHERE booking_uid = '$booking_uid'
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        exit;
    }
    
    // Check if row was actually updated
    if (mysqli_affected_rows($conn) > 0) {
        echo json_encode(["status" => "success", "message" => "Payment updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Booking not found or no changes made"]);
    }
    break;
```

---

## 🎯 Key Points to Check

### 1. **Variable Naming**
```php
// JSON sends this:
{
  "booking_uid": "...",
  "status": "Completed",      // ⬅️ Called 'status' in JSON
  "transaction_id": "...",
  "amount": 120.0
}

// In PHP, extract it correctly:
$payment_status = $data['status'];  // ✅ Correct
// NOT:
$status = $data['payment_status'];  // ❌ Wrong - this doesn't exist in JSON
```

### 2. **Column Name**
```sql
-- Correct (snake_case):
UPDATE bookings SET payment_status = '$payment_status'

-- Wrong (if your column is snake_case):
UPDATE bookings SET paymentStatus = '$payment_status'
```

### 3. **Debug Your Current Code**

Add this before your UPDATE query:
```php
// DEBUG: Log what you're actually getting
error_log("Booking UID: $booking_uid");
error_log("Payment Status to set: $payment_status");
error_log("SQL Query: $query");
```

---

## 🧪 How to Verify the Fix

### 1. **Check Your Current PHP Code:**
Look for the `update_payment` case in your `api.php` file and compare with the correct code above.

### 2. **Add Debug Output (Temporarily):**
```php
case 'update_payment':
    $data = json_decode(file_get_contents("php://input"), true);
    
    // ADD THIS FOR DEBUGGING:
    echo json_encode([
        "debug" => true,
        "received_data" => $data,
        "booking_uid" => $data['booking_uid'] ?? 'MISSING',
        "status_from_json" => $data['status'] ?? 'MISSING',
        "transaction_id" => $data['transaction_id'] ?? 'MISSING'
    ]);
    exit;  // Stop here to see what's received
```

### 3. **Test Again:**
```powershell
python diagnose_payment_update.py
```

### 4. **Expected Output After Fix:**
```json
{
  "payment_status": "Completed"  // ✅ Should show "Completed", not ""
}
```

---

## 📋 Complete Working Example

```php
<?php
// ... database connection ...

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'update_payment':
        try {
            // Get JSON input
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!$data) {
                echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
                exit;
            }
            
            // Extract fields (note: JSON sends 'status', not 'payment_status')
            $booking_uid = $data['booking_uid'] ?? '';
            $payment_status = $data['status'] ?? '';  // ⬅️ IMPORTANT: JSON key is 'status'
            $transaction_id = $data['transaction_id'] ?? '';
            $amount = $data['amount'] ?? 0;
            
            // Validate required fields
            if (empty($booking_uid) || empty($payment_status)) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Missing booking_uid or status"
                ]);
                exit;
            }
            
            // Sanitize inputs (basic - use prepared statements in production!)
            $booking_uid = mysqli_real_escape_string($conn, $booking_uid);
            $payment_status = mysqli_real_escape_string($conn, $payment_status);
            $transaction_id = mysqli_real_escape_string($conn, $transaction_id);
            
            // UPDATE query
            $query = "
                UPDATE bookings 
                SET payment_status = '$payment_status',
                    transaction_id = '$transaction_id'
                WHERE booking_uid = '$booking_uid'
            ";
            
            // Execute
            $result = mysqli_query($conn, $query);
            
            if (!$result) {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Database error: " . mysqli_error($conn)
                ]);
                exit;
            }
            
            // Check if any row was updated
            $affected_rows = mysqli_affected_rows($conn);
            
            if ($affected_rows > 0) {
                echo json_encode([
                    "status" => "success", 
                    "message" => "Payment updated",
                    "rows_affected" => $affected_rows
                ]);
            } else {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Booking not found or payment status already set"
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Exception: " . $e->getMessage()
            ]);
        }
        break;
        
    // ... other cases ...
}
?>
```

---

## ✅ Checklist

Before testing:
- [ ] Verify JSON sends `"status": "Completed"` (not `"payment_status"`)
- [ ] In PHP, use `$data['status']` to extract the value
- [ ] Assign it to `$payment_status` variable
- [ ] Use `payment_status` column name in SQL (verify with `DESCRIBE bookings;`)
- [ ] Check for typos in variable names
- [ ] Test with debug output first

---

## 🎯 Summary

**The Issue:**  
Your PHP code is setting `payment_status = ''` (empty string) instead of `payment_status = 'Completed'`

**The Fix:**  
Change your PHP code to correctly extract `$data['status']` and use it in the UPDATE query.

**Not a Permission Issue:**  
The database IS being updated (returns success), just with the wrong value.
