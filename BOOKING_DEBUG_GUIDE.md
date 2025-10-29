# 🔍 Booking Endpoint Debugging Guide

## ❌ Current Issue

The `book_slot` endpoint is returning **HTTP 500** with an **empty response body**.

This typically means:
- PHP fatal error or exception
- Database query failure
- Table/column name mismatch

---

## 🔧 What to Check in Your PHP Code

### **1. Check the `book_slot` Case Statement**

Your PHP API should have something like:

```php
case 'book_slot':
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Get data from request
    $user_id = $data['user_id'] ?? 0;
    $slot_id = $data['slot_id'] ?? 0;
    $start_time = $data['start_time'] ?? '';
    $end_time = $data['end_time'] ?? '';
    $total_amount = $data['total_amount'] ?? 0;
    
    // Validate required fields
    if (!$user_id || !$slot_id || !$start_time || !$end_time) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }
    
    // Generate booking UID
    $booking_uid = strtoupper(substr(md5(uniqid()), 0, 8));
    
    // INSERT query - CHECK TABLE AND COLUMN NAMES!
    $query = "
        INSERT INTO bookings (user_id, slot_id, booking_uid, start_time, end_time, total_amount, status, payment_status)
        VALUES ('$user_id', '$slot_id', '$booking_uid', '$start_time', '$end_time', '$total_amount', 'Active', 'Pending')
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        exit;
    }
    
    echo json_encode(["status" => "success", "booking_uid" => $booking_uid, "message" => "Booking created successfully"]);
    break;
```

---

## 🔍 Common Issues & Fixes

### **Issue 1: Wrong Table Name**
❌ **Wrong:**
```php
INSERT INTO booking ...
```

✅ **Correct:** (check your actual table name)
```php
INSERT INTO bookings ...
```

---

### **Issue 2: Wrong Column Names**

Based on your database schema, check if columns match:

**Expected columns in `bookings` table:**
- `id` (auto increment)
- `user_id` (FK to users)
- `slot_id` (FK to parking_slots)
- `booking_uid` (unique identifier, VARCHAR)
- `start_time` (DATETIME)
- `end_time` (DATETIME)
- `total_amount` (DECIMAL)
- `status` (VARCHAR - 'Active', 'Completed', 'Cancelled')
- `payment_status` (VARCHAR - 'Pending', 'Completed', 'Failed')
- `created_at` (TIMESTAMP)

**Common naming variations to check:**
- `slot_id` vs `parking_slot_id`
- `total_amount` vs `amount`
- `booking_uid` vs `uid` vs `booking_code`
- `payment_status` vs `paymentStatus`

---

### **Issue 3: Missing Error Handling**

Always add error handling:

```php
if (!$result) {
    // Output the actual MySQL error
    echo json_encode([
        "status" => "error", 
        "message" => "Database error: " . mysqli_error($conn),
        "query" => $query  // Remove in production!
    ]);
    exit;
}
```

---

### **Issue 4: Foreign Key Constraints**

Check if:
- `user_id = 1` exists in `users` table
- `slot_id = 1` exists in `parking_slots` table

Test with direct SQL:
```sql
SELECT * FROM users WHERE id = 1;
SELECT * FROM parking_slots WHERE id = 1;
```

---

### **Issue 5: Check if Case Exists**

Make sure the `book_slot` case is actually in your PHP file:

```php
switch ($action) {
    case 'get_parking_lots':
        // ...
        break;
    
    case 'get_available_slots':
        // ...
        break;
    
    case 'book_slot':  // ⬅️ MAKE SURE THIS EXISTS!
        // ...
        break;
    
    default:
        echo json_encode(["status" => "error", "message" => "Invalid action: $action"]);
}
```

---

## 🧪 How to Debug

### **Method 1: Enable PHP Error Display**

Add to top of your PHP file:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### **Method 2: Log Errors to File**

```php
error_log("book_slot called with data: " . print_r($data, true));
```

### **Method 3: Test Query Directly**

```php
case 'book_slot':
    // Test connection
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "DB connection failed"]);
        exit;
    }
    
    // Test data reception
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        echo json_encode(["status" => "error", "message" => "No JSON data received"]);
        exit;
    }
    
    // Continue with normal logic...
```

---

## 📋 Complete Working Example

```php
case 'book_slot':
    try {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate input
        $user_id = $data['user_id'] ?? null;
        $slot_id = $data['slot_id'] ?? null;
        $start_time = $data['start_time'] ?? null;
        $end_time = $data['end_time'] ?? null;
        $total_amount = $data['total_amount'] ?? 0;
        
        if (!$user_id || !$slot_id || !$start_time || !$end_time) {
            echo json_encode([
                "status" => "error", 
                "message" => "Missing required fields",
                "required" => ["user_id", "slot_id", "start_time", "end_time"]
            ]);
            exit;
        }
        
        // Generate unique booking UID
        $booking_uid = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        
        // Check if user exists
        $user_check = mysqli_query($conn, "SELECT id FROM users WHERE id = '$user_id'");
        if (mysqli_num_rows($user_check) == 0) {
            echo json_encode(["status" => "error", "message" => "User not found"]);
            exit;
        }
        
        // Check if slot exists
        $slot_check = mysqli_query($conn, "SELECT id FROM parking_slots WHERE id = '$slot_id'");
        if (mysqli_num_rows($slot_check) == 0) {
            echo json_encode(["status" => "error", "message" => "Slot not found"]);
            exit;
        }
        
        // Insert booking
        $query = "
            INSERT INTO bookings 
            (user_id, slot_id, booking_uid, start_time, end_time, total_amount, status, payment_status) 
            VALUES 
            ('$user_id', '$slot_id', '$booking_uid', '$start_time', '$end_time', '$total_amount', 'Active', 'Pending')
        ";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            echo json_encode([
                "status" => "error", 
                "message" => "Failed to create booking",
                "error" => mysqli_error($conn)
            ]);
            exit;
        }
        
        // Success
        echo json_encode([
            "status" => "success", 
            "booking_uid" => $booking_uid,
            "message" => "Booking created successfully"
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Exception: " . $e->getMessage()
        ]);
    }
    break;
```

---

## ✅ Checklist

Before running the test again:

- [ ] Verify table name is `bookings` (or correct name)
- [ ] Verify all column names match your database
- [ ] Check that `user_id = 1` exists in users table
- [ ] Check that `slot_id = 1` exists in parking_slots table
- [ ] Add error logging to PHP file
- [ ] Test the query directly in phpMyAdmin/MySQL
- [ ] Check PHP error logs on server

---

## 🎯 Quick Test

Once fixed, test with:
```powershell
python diagnose_booking.py
```

Expected output:
```json
{
  "status": "success",
  "booking_uid": "A1B2C3D4",
  "message": "Booking created successfully"
}
```

---

## 📞 Need the Exact Table Schema?

Run this SQL query to see your actual table structure:
```sql
SHOW CREATE TABLE bookings;
DESCRIBE bookings;
```

This will show you the exact column names and types you need to use!
