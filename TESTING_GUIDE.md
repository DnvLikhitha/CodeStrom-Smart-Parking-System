# 🧪 Smart Parking System - Testing Guide

## ✅ Current Status

### What's Working:
- ✅ Database API connection
- ✅ Fetching available parking slots
- ✅ Data transformation for AI model
- ✅ User registration
- ✅ FastAPI backend structure
- ✅ Payment service integration (simulated mode)

### What Needs Fixing in PHP API:
- ❌ `book_slot` endpoint returns 500 error
- ⚠️ Check table names and column names in your database

---

## 🚀 How to Test

### **1. Test Database API (Python)**

Test the database connection and slot fetching:

```powershell
python check_slots.py
```

**Expected Output:**
- ✅ Lists parking lots
- ✅ Shows available slots with details
- ✅ Displays slot number, vehicle type, hourly rate

---

### **2. Run Full Database Integration Test**

```powershell
python test_database.py
```

**This tests:**
- ✅ Fetching parking slots
- ✅ Data transformation for AI
- ✅ User registration
- ⚠️ Booking flow (currently failing - needs PHP fix)
- ⏭️ Payment update
- ⏭️ Feedback submission

---

### **3. Start FastAPI Server**

```powershell
python backend/main.py
```

**Or use uvicorn:**
```powershell
uvicorn backend.main:app --reload --host 0.0.0.0 --port 8000
```

The server will start on: `http://127.0.0.1:8000`

---

### **4. Access API Documentation**

Once the server is running, open your browser:

- **Swagger UI (Interactive):** http://127.0.0.1:8000/docs
- **ReDoc:** http://127.0.0.1:8000/redoc

---

### **5. Test Individual Endpoints**

#### **A. Health Check**
```powershell
curl http://127.0.0.1:8000/
```

**Expected:**
```json
{
  "status": "ok",
  "message": "Smart Parking API - AI + Payment Module",
  "version": "1.0.0",
  "database": "Connected to https://aadarshsenapati.in/api/api.php"
}
```

#### **B. Test Database Connection**
```powershell
curl http://127.0.0.1:8000/api/test-database
```

#### **C. Get Slot Recommendations (AI)**

Create a test file `test_recommendation.json`:
```json
{
  "user_location": {
    "latitude": 16.4645,
    "longitude": 80.5076
  },
  "slots": [],
  "top_k": 3
}
```

Then test:
```powershell
curl -X POST http://127.0.0.1:8000/api/recommend-slots `
  -H "Content-Type: application/json" `
  -d '@test_recommendation.json'
```

**Or using Invoke-WebRequest:**
```powershell
$body = @{
    user_location = @{
        latitude = 16.4645
        longitude = 80.5076
    }
    slots = @()
    top_k = 3
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/recommend-slots" `
  -Method POST `
  -ContentType "application/json" `
  -Body $body
```

#### **D. Submit Feedback**
```powershell
$feedback = @{
    booking_id = "test123"
    slot_id = "A1-1"
    rating = 4.5
    comment = "Great parking spot!"
    user_satisfaction = $true
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/feedback" `
  -Method POST `
  -ContentType "application/json" `
  -Body $feedback
```

#### **E. Create Payment Order (Simulated)**
```powershell
$payment = @{
    booking_id = "test456"
    slot_id = "A1-1"
    amount = 120.0
    duration_hours = 2
    vehicle_number = "AP39XX1234"
    customer_name = "Test User"
    customer_contact = "+919999888877"
    customer_email = "test@example.com"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/payment/create-order" `
  -Method POST `
  -ContentType "application/json" `
  -Body $payment
```

---

### **6. Test Using Swagger UI (Recommended)**

1. Start the server: `python backend/main.py`
2. Open browser: http://127.0.0.1:8000/docs
3. Click on any endpoint to expand
4. Click **"Try it out"**
5. Fill in the parameters
6. Click **"Execute"**
7. See the response below

---

## 📊 Database Schema Issues

Based on testing, here are the fixes needed in your PHP API:

### **Issue 1: `book_slot` endpoint (500 error)**

Check your PHP code for:
- Table name: Should be `bookings` or `booking`
- Column names: Ensure they match your database schema
- SQL syntax errors
- Missing required fields

### **Issue 2: Slot data structure**

Current API returns:
```json
{
  "lot_id": "1",
  "lot_name": "A1",
  "available_slots": [
    {
      "slot_id": "1",
      "slot_number": "1",
      "vehicle_type": "4-wheeler",
      "hourly_rate": "60.00"
    }
  ]
}
```

This is now handled correctly by our Python code!

---

## 🔧 Troubleshooting

### **Problem: "ModuleNotFoundError"**
```powershell
pip install fastapi uvicorn scikit-learn razorpay requests
```

### **Problem: "Model file not found"**
The AI model will auto-train on first startup. This is normal.

### **Problem: "500 Server Error from database"**
Check your PHP API logs and database table names.

### **Problem: "Port already in use"**
```powershell
# Kill the process using port 8000
netstat -ano | findstr :8000
taskkill /PID <PID> /F
```

---

## 📈 Next Steps

1. **Fix PHP `book_slot` endpoint** - Check table/column names
2. **Add more test slots** to database for better AI testing
3. **Test payment integration** with real Razorpay credentials
4. **Load test** the API with multiple concurrent requests
5. **Deploy** to production (Railway, Render, or Vercel)

---

## 🎯 Success Criteria

- [x] Database API returns slots
- [x] AI model trains successfully
- [x] Slot recommendations work
- [ ] Booking creates successfully
- [ ] Payment flow completes
- [ ] Feedback is stored

---

## 📞 Support

If you encounter issues:
1. Check the server logs
2. Verify database schema matches code expectations
3. Test PHP endpoints directly using Postman
4. Check API response formats

Good luck! 🚀
