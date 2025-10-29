# Database Integration Guide

## Overview
The Smart Parking AI + Payment Module is now integrated with the PHP backend database API managed by your team member.

**Database API Endpoint:** `https://aadarshsenapati.in/api/api.php`

---

## ✅ What's Been Implemented

### 1. Database API Client (`backend/database_api.py`)
A Python client that communicates with your team's PHP API. Supports:

- ✅ User registration and management
- ✅ Fetching parking lots (with real-time availability)
- ✅ Booking slot creation
- ✅ Payment status updates
- ✅ Booking status retrieval
- ✅ Booking cancellation
- ✅ Feedback submission

### 2. Backend Integration (`backend/main.py`)
All endpoints now use the database:

- **Slot Recommendations:** Fetches real parking data from database
- **Bookings:** Creates bookings in database with unique UIDs
- **Payments:** Updates payment status in database
- **Feedback:** Stores user feedback in database
- **AI Model:** Trains on real feedback data

### 3. Data Transformation
Automatic conversion between:
- Database format → AI model format
- API requests → Database format
- Database responses → API responses

---

## 🔄 API Action Mapping

### Your Team's PHP API Actions

```php
// All available actions in api.php
action=register_user          // Register/update user
action=get_parking_lots       // Get all parking lots
action=book_slot             // Create new booking
action=update_payment        // Update payment status
action=get_booking_status    // Get booking by UID
action=cancel_booking        // Cancel a booking
action=add_feedback          // Add user feedback
```

### Our Python API Client Methods

```python
from backend.database_api import DatabaseAPI

api = DatabaseAPI()

# User management
api.register_user(name, phone, email)

# Parking operations
api.get_parking_lots()

# Booking operations
api.book_slot(user_id, slot_id, start_time, end_time, total_amount)
api.get_booking_status(booking_uid)
api.cancel_booking(booking_uid)

# Payment operations
api.update_payment(booking_uid, status, transaction_id, amount)

# Feedback
api.add_feedback(user_id, booking_id, rating, comments)
```

---

## 📊 Database Schema (Expected)

### Tables Used

**users**
- id (primary key)
- name
- phone (unique)
- email

**parking_lots**
- id (primary key)
- name
- location
- latitude
- longitude
- price_per_hour
- is_available

**bookings**
- id (primary key)
- user_id (foreign key)
- slot_id (foreign key)
- booking_uid (unique identifier - ABC12345 format)
- start_time
- end_time
- total_amount
- payment_status (Pending/Completed/Failed)
- status (Active/Cancelled/Completed)

**feedback**
- id (primary key)
- user_id (foreign key)
- booking_id (foreign key)
- rating (1-5)
- comments

---

## 🧪 Testing

### Test Database Connection
```bash
python test_database.py
```

This will test:
1. ✅ Fetch parking lots
2. ✅ Register a test user
3. ✅ Create a booking
4. ✅ Update payment status
5. ✅ Submit feedback
6. ✅ Data transformation for AI model

### Test via API Endpoint
```bash
# Start server
uvicorn backend.main:app --reload

# Test database connection
curl http://localhost:8000/api/test-database
```

---

## 🔌 Integration Flow

### Complete Booking Flow

```
1. USER SEARCHES FOR PARKING
   ↓
   FastAPI: POST /api/recommend-slots
   ↓
   Database API: get_parking_lots()
   ↓
   PHP API: ?action=get_parking_lots
   ↓
   Returns: List of available parking lots
   ↓
   AI Model: Ranks slots based on proximity, feedback, popularity
   ↓
   Returns: Top 3 recommended slots

2. USER SELECTS A SLOT & BOOKS
   ↓
   FastAPI: POST /api/payment/create-order
   ↓
   Database API: register_user() + book_slot()
   ↓
   PHP API: ?action=register_user + ?action=book_slot
   ↓
   Returns: booking_uid (e.g., "ABC12345")
   ↓
   Payment Service: create_payment_order()
   ↓
   Returns: Razorpay order_id and payment link

3. USER COMPLETES PAYMENT
   ↓
   FastAPI: POST /api/payment/verify OR POST /api/payment/simulate
   ↓
   Database API: update_payment()
   ↓
   PHP API: ?action=update_payment
   ↓
   Updates: payment_status = "Completed"

4. USER PROVIDES FEEDBACK
   ↓
   FastAPI: POST /api/feedback
   ↓
   Database API: get_booking_status() + add_feedback()
   ↓
   PHP API: ?action=get_booking_status + ?action=add_feedback
   ↓
   Stores: Feedback in database
   ↓
   AI Model: Uses feedback for retraining
```

---

## 🎯 Key Features

### 1. Automatic Data Fetching
```python
# No need to provide slots manually
POST /api/recommend-slots
{
  "user_location": {"latitude": 28.6139, "longitude": 77.2090},
  "slots": [],  # Empty - will fetch from database
  "top_k": 3
}
```

### 2. Unique Booking UIDs
- Database generates unique 8-character UIDs (e.g., "ABC12345")
- Used to track bookings across all systems
- Simplifies integration with external systems

### 3. Payment Status Tracking
```python
# Update payment after Razorpay confirmation
api.update_payment(
    booking_uid="ABC12345",
    status="Completed",
    transaction_id="pay_razorpay123",
    amount=100.0
)
```

### 4. Real-time Feedback Loop
```python
# Feedback automatically stored in DB and used for AI
POST /api/feedback
{
  "booking_id": "ABC12345",
  "rating": 5.0,
  "user_satisfaction": true
}
```

---

## 🔧 Configuration

### Environment Variables
```env
# No database credentials needed!
# All database access through PHP API

# Only need Razorpay credentials
RAZORPAY_KEY_ID=rzp_test_RYlqJbc24Sl6jz
RAZORPAY_KEY_SECRET=bghQe0L7iort9vmqb6Jlf8Ec
```

---

## 🚨 Error Handling

The system gracefully handles database errors:

```python
# If database API is unavailable
result = api.get_parking_lots()

if result['status'] == 'error':
    # Falls back to test data or returns error
    return {
        "success": False,
        "message": "Database unavailable",
        "error": result.get('message')
    }
```

---

## 📝 Example Usage

### Complete Example Script

```python
from backend.database_api import DatabaseAPI
from datetime import datetime, timedelta

api = DatabaseAPI()

# 1. Get available parking
lots = api.get_parking_lots()
print(f"Found {len(lots['data'])} parking lots")

# 2. Register user
user = api.register_user(
    name="John Doe",
    phone="+919876543210",
    email="john@example.com"
)

# 3. Book a slot
slot = lots['data'][0]
booking = api.book_slot(
    user_id=1,
    slot_id=slot['id'],
    start_time=datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    end_time=(datetime.now() + timedelta(hours=2)).strftime("%Y-%m-%d %H:%M:%S"),
    total_amount=100.0
)

booking_uid = booking['booking_uid']
print(f"Booking created: {booking_uid}")

# 4. Simulate payment
payment = api.update_payment(
    booking_uid=booking_uid,
    status="Completed",
    transaction_id="pay_test_123",
    amount=100.0
)

# 5. Add feedback
feedback = api.add_feedback(
    user_id=1,
    booking_id=1,  # Database booking ID
    rating=5.0,
    comments="Great spot!"
)

print("✅ Complete flow successful!")
```

---

## 🤝 Team Coordination

### Your Responsibilities (AI + Payment)
- ✅ AI model for slot recommendations
- ✅ Payment integration (Razorpay)
- ✅ FastAPI backend with endpoints
- ✅ Database API client integration
- ✅ Testing and deployment

### Other Team Member's Responsibilities (Database)
- ✅ PHP backend API
- ✅ MySQL database management
- ✅ User authentication (future)
- ✅ Admin panel (future)

### Shared
- 📊 Database schema design
- 🔄 API contract/interface
- 🧪 Integration testing
- 📝 Documentation

---

## 🎉 Summary

You now have a **complete integrated system** that:

1. ✅ Fetches real parking data from database
2. ✅ Uses AI to recommend best slots
3. ✅ Creates bookings in database with unique UIDs
4. ✅ Processes payments via Razorpay (sandbox)
5. ✅ Updates payment status in database
6. ✅ Collects and stores user feedback
7. ✅ Retrains AI model with real feedback

**Ready for testing and deployment!** 🚀
