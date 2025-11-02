# 🚀 Smart Parking System - API Reference

**Base URL:** `https://dnvlikhitha-codestrom.hf.space`

**Interactive Documentation:** `https://dnvlikhitha-codestrom.hf.space/docs`

---

## � Quick Reference - All API Endpoints

| # | Method | Endpoint | Full URL | Description |
|---|--------|----------|----------|-------------|
| 1 | GET | `/` | `https://dnvlikhitha-codestrom.hf.space/` | Health check & API status |
| 2 | POST | `/api/recommend-slots` | `https://dnvlikhitha-codestrom.hf.space/api/recommend-slots` | Get AI-powered slot recommendations |
| 3 | GET | `/api/booking/{uid}` | `https://dnvlikhitha-codestrom.hf.space/api/booking/{uid}` | Get booking details by UID |
| 4 | POST | `/api/payment/create-order` | `https://dnvlikhitha-codestrom.hf.space/api/payment/create-order` | Create payment order |
| 5 | POST | `/api/payment/create-link` | `https://dnvlikhitha-codestrom.hf.space/api/payment/create-link` | Generate payment link |
| 6 | POST | `/api/payment/verify` | `https://dnvlikhitha-codestrom.hf.space/api/payment/verify` | Verify payment signature |
| 7 | POST | `/api/payment/simulate` | `https://dnvlikhitha-codestrom.hf.space/api/payment/simulate` | Simulate payment (testing) |
| 8 | POST | `/api/feedback` | `https://dnvlikhitha-codestrom.hf.space/api/feedback` | Submit user feedback |
| 9 | GET | `/api/feedback/stats` | `https://dnvlikhitha-codestrom.hf.space/api/feedback/stats` | Get feedback statistics |
| 10 | POST | `/api/retrain-model` | `https://dnvlikhitha-codestrom.hf.space/api/retrain-model` | Retrain AI model |
| 11 | GET | `/api/test-database` | `https://dnvlikhitha-codestrom.hf.space/api/test-database` | Test database connection |

> 💡 **Tip:** Click on any URL above to test it directly in your browser (GET requests) or use the [Interactive Docs](https://dnvlikhitha-codestrom.hf.space/docs) for all endpoints.

---

## �📋 Table of Contents

- [Quick Reference Table](#-quick-reference---all-api-endpoints)
- [Health & Status](#health--status)
- [Parking Slots & Recommendations](#parking-slots--recommendations)
- [Booking Management](#booking-management)
- [Payment Processing](#payment-processing)
- [User Feedback](#user-feedback)
- [AI Model Management](#ai-model-management)
- [Database Testing](#database-testing)
- [Complete Documentation](#-complete-endpoint-summary)

---

## 🏥 Health & Status

### 1. Health Check
**Endpoint:** `GET /`

**URL:** `https://dnvlikhitha-codestrom.hf.space/`

**Description:** Check if the API is running and healthy

**Response:**
```json
{
  "status": "ok",
  "message": "Smart Parking API - AI + Payment Module",
  "version": "1.0.0",
  "database": "Connected to https://aadarshsenapati.in/api/api.php"
}
```

**Example:**
```bash
curl https://dnvlikhitha-codestrom.hf.space/
```

---

## 🅿️ Parking Slots & Recommendations

### 2. Get AI-Powered Slot Recommendations
**Endpoint:** `POST /api/recommend-slots`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/recommend-slots`

**Description:** Get personalized parking slot recommendations based on user location using AI

**Request Body:**
```json
{
  "user_location": {
    "latitude": 16.4645659,
    "longitude": 80.5076208
  },
  "slots": [],
  "top_k": 5
}
```

**Parameters:**
- `user_location` (required): GPS coordinates of the user
  - `latitude` (float): User's latitude
  - `longitude` (float): User's longitude
- `slots` (optional): Array of custom slots (leave empty to fetch from database)
- `top_k` (optional, default: 3): Number of recommendations to return

**Response:**
```json
{
  "success": true,
  "recommendations": [
    {
      "slot_id": "A1-1",
      "db_slot_id": 1,
      "lot_name": "A1",
      "slot_number": "1",
      "location": "SRM University AP Parking lot background",
      "latitude": 16.4645659,
      "longitude": 80.5076208,
      "vehicle_type": "4-wheeler",
      "price_per_hour": 60.0,
      "avg_feedback": 4.5,
      "popularity_score": 0.75,
      "is_available": true,
      "proximity_score": 0.95,
      "recommendation_score": 0.89
    }
  ],
  "count": 1,
  "total_available": 1
}
```

**Example:**
```bash
curl -X POST "https://dnvlikhitha-codestrom.hf.space/api/recommend-slots" \
  -H "Content-Type: application/json" \
  -d '{
    "user_location": {
      "latitude": 16.4645659,
      "longitude": 80.5076208
    },
    "slots": [],
    "top_k": 5
  }'
```

**Python Example:**
```python
import requests

response = requests.post(
    "https://dnvlikhitha-codestrom.hf.space/api/recommend-slots",
    json={
        "user_location": {
            "latitude": 16.4645659,
            "longitude": 80.5076208
        },
        "slots": [],
        "top_k": 5
    }
)

recommendations = response.json()
print(f"Found {recommendations['count']} recommendations")
```

---

## 📅 Booking Management

### 3. Get Booking Details
**Endpoint:** `GET /api/booking/{booking_uid}`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/booking/{booking_uid}`

**Description:** Retrieve booking information using booking UID

**Path Parameters:**
- `booking_uid` (string): Unique booking identifier

**Response:**
```json
{
  "success": true,
  "booking": {
    "id": 123,
    "booking_uid": "ABC12345",
    "user_id": 9,
    "slot_id": 1,
    "start_time": "2025-10-31 10:00:00",
    "end_time": "2025-10-31 12:00:00",
    "total_amount": 120.0,
    "payment_status": "Paid",
    "status": "Active"
  }
}
```

**Example:**
```bash
curl https://dnvlikhitha-codestrom.hf.space/api/booking/ABC12345
```

**Python Example:**
```python
import requests

booking_uid = "ABC12345"
response = requests.get(f"https://dnvlikhitha-codestrom.hf.space/api/booking/{booking_uid}")
booking = response.json()
print(f"Booking Status: {booking['booking']['payment_status']}")
```

---

## 💳 Payment Processing

### 4. Create Payment Order
**Endpoint:** `POST /api/payment/create-order`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/payment/create-order`

**Description:** Create a new payment order for parking booking

**Request Body:**
```json
{
  "booking_id": "ABC12345",
  "slot_id": "1",
  "amount": 120.0,
  "duration_hours": 2,
  "vehicle_number": "AP39AB1234",
  "customer_name": "John Doe",
  "customer_contact": "9876543210",
  "customer_email": "john@example.com"
}
```

**Parameters:**
- `booking_id` (string): Unique booking identifier
- `slot_id` (string): Parking slot ID
- `amount` (float): Total payment amount
- `duration_hours` (int): Parking duration in hours
- `vehicle_number` (string): Vehicle registration number
- `customer_name` (string): Customer full name
- `customer_contact` (string): Customer phone number
- `customer_email` (string): Customer email address

**Response:**
```json
{
  "success": true,
  "order_id": "order_ABC12345",
  "amount": 120.0,
  "currency": "INR",
  "status": "created",
  "mode": "simulation",
  "booking_uid": "ABC12345",
  "start_time": "2025-10-31 10:00:00",
  "end_time": "2025-10-31 12:00:00"
}
```

**Example:**
```bash
curl -X POST "https://dnvlikhitha-codestrom.hf.space/api/payment/create-order" \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": "ABC12345",
    "slot_id": "1",
    "amount": 120.0,
    "duration_hours": 2,
    "vehicle_number": "AP39AB1234",
    "customer_name": "John Doe",
    "customer_contact": "9876543210",
    "customer_email": "john@example.com"
  }'
```

**Python Example:**
```python
import requests

response = requests.post(
    "https://dnvlikhitha-codestrom.hf.space/api/payment/create-order",
    json={
        "booking_id": "ABC12345",
        "slot_id": "1",
        "amount": 120.0,
        "duration_hours": 2,
        "vehicle_number": "AP39AB1234",
        "customer_name": "John Doe",
        "customer_contact": "9876543210",
        "customer_email": "john@example.com"
    }
)

order = response.json()
print(f"Order ID: {order['order_id']}")
```

---

### 5. Create Payment Link
**Endpoint:** `POST /api/payment/create-link`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/payment/create-link`

**Description:** Generate a payment link for WhatsApp or SMS sharing

**Request Body:**
```json
{
  "booking_id": "ABC12345",
  "slot_id": "1",
  "amount": 120.0,
  "duration_hours": 2,
  "vehicle_number": "AP39AB1234",
  "customer_name": "John Doe",
  "customer_contact": "9876543210",
  "customer_email": "john@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "short_url": "https://rzp.io/l/sim_ABC12345",
  "mode": "simulation",
  "message": "This is a simulated payment link"
}
```

**Example:**
```bash
curl -X POST "https://dnvlikhitha-codestrom.hf.space/api/payment/create-link" \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": "ABC12345",
    "slot_id": "1",
    "amount": 120.0,
    "duration_hours": 2,
    "vehicle_number": "AP39AB1234",
    "customer_name": "John Doe",
    "customer_contact": "9876543210",
    "customer_email": "john@example.com"
  }'
```

---

### 6. Verify Payment
**Endpoint:** `POST /api/payment/verify`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/payment/verify`

**Description:** Verify payment signature after completion

**Request Body:**
```json
{
  "order_id": "order_ABC12345",
  "payment_id": "pay_XYZ789",
  "signature": "abc123def456"
}
```

**Parameters:**
- `order_id` (string): Razorpay order ID
- `payment_id` (string): Razorpay payment ID
- `signature` (string): Payment signature for verification

**Response:**
```json
{
  "success": true,
  "verified": true,
  "mode": "simulation",
  "database_updated": true
}
```

**Example:**
```bash
curl -X POST "https://dnvlikhitha-codestrom.hf.space/api/payment/verify" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "order_ABC12345",
    "payment_id": "pay_XYZ789",
    "signature": "abc123def456"
  }'
```

---

### 7. Simulate Payment (Testing Only)
**Endpoint:** `POST /api/payment/simulate`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/payment/simulate?booking_uid={booking_uid}&amount={amount}`

**Description:** Simulate a payment for testing purposes

**Query Parameters:**
- `booking_uid` (string): Booking unique identifier
- `amount` (float): Payment amount

**Response:**
```json
{
  "success": true,
  "order_id": "order_ABC12345",
  "payment_id": "sim_pay_123456",
  "status": "success",
  "mode": "simulation",
  "database_updated": true
}
```

**Example:**
```bash
curl -X POST "https://dnvlikhitha-codestrom.hf.space/api/payment/simulate?booking_uid=ABC12345&amount=120.0"
```

---

## ⭐ User Feedback

### 8. Submit Feedback
**Endpoint:** `POST /api/feedback`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/feedback`

**Description:** Submit user feedback and rating for a parking experience

**Request Body:**
```json
{
  "booking_id": "ABC12345",
  "slot_id": "1",
  "rating": 5.0,
  "comment": "Great parking spot, very convenient!",
  "user_satisfaction": true
}
```

**Parameters:**
- `booking_id` (string): Booking UID
- `slot_id` (string): Parking slot ID
- `rating` (float): Rating from 1-5
- `comment` (string, optional): User comments
- `user_satisfaction` (boolean): Overall satisfaction (true/false)

**Response:**
```json
{
  "success": true,
  "message": "Feedback submitted successfully",
  "database": true
}
```

**Example:**
```bash
curl -X POST "https://dnvlikhitha-codestrom.hf.space/api/feedback" \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": "ABC12345",
    "slot_id": "1",
    "rating": 5.0,
    "comment": "Great parking spot!",
    "user_satisfaction": true
  }'
```

**Python Example:**
```python
import requests

response = requests.post(
    "https://dnvlikhitha-codestrom.hf.space/api/feedback",
    json={
        "booking_id": "ABC12345",
        "slot_id": "1",
        "rating": 5.0,
        "comment": "Excellent location and service!",
        "user_satisfaction": True
    }
)

result = response.json()
print(f"Feedback submitted: {result['success']}")
```

---

### 9. Get Feedback Statistics
**Endpoint:** `GET /api/feedback/stats`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/feedback/stats`

**Description:** Retrieve aggregated feedback statistics

**Response:**
```json
{
  "total_feedback": 150,
  "average_rating": 4.3,
  "satisfaction_rate": 87.5
}
```

**Example:**
```bash
curl https://dnvlikhitha-codestrom.hf.space/api/feedback/stats
```

**Python Example:**
```python
import requests

response = requests.get("https://dnvlikhitha-codestrom.hf.space/api/feedback/stats")
stats = response.json()
print(f"Average Rating: {stats['average_rating']}/5.0")
print(f"Satisfaction Rate: {stats['satisfaction_rate']}%")
```

---

## 🤖 AI Model Management

### 10. Retrain AI Model
**Endpoint:** `POST /api/retrain-model`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/retrain-model`

**Description:** Retrain the AI model with new user feedback data

**Response:**
```json
{
  "success": true,
  "message": "Model retrained with 50 samples",
  "samples_used": 50
}
```

**Note:** Requires at least 10 feedback entries to retrain

**Example:**
```bash
curl -X POST "https://dnvlikhitha-codestrom.hf.space/api/retrain-model"
```

**Python Example:**
```python
import requests

response = requests.post("https://dnvlikhitha-codestrom.hf.space/api/retrain-model")
result = response.json()

if result['success']:
    print(f"Model retrained with {result['samples_used']} samples")
else:
    print(f"Retraining failed: {result['message']}")
```

---

## 🔬 Database Testing

### 11. Test Database Connection
**Endpoint:** `GET /api/test-database`

**URL:** `https://dnvlikhitha-codestrom.hf.space/api/test-database`

**Description:** Test connection to the database API and retrieve sample data

**Response:**
```json
{
  "success": true,
  "message": "Database connection test",
  "parking_lots_count": 1,
  "sample_data": [
    {
      "lot_id": 1,
      "lot_name": "A1",
      "latitude": 16.4645659,
      "longitude": 80.5076208,
      "location": "SRM University AP Parking lot background"
    }
  ]
}
```

**Example:**
```bash
curl https://dnvlikhitha-codestrom.hf.space/api/test-database
```

**Python Example:**
```python
import requests

response = requests.get("https://dnvlikhitha-codestrom.hf.space/api/test-database")
result = response.json()

if result['success']:
    print(f"Database connected! Found {result['parking_lots_count']} parking lots")
else:
    print(f"Database connection failed: {result.get('error')}")
```

---

## 📊 Complete Endpoint Summary

| # | Method | Endpoint | Purpose |
|---|--------|----------|---------|
| 1 | GET | `/` | Health check |
| 2 | POST | `/api/recommend-slots` | Get AI recommendations |
| 3 | GET | `/api/booking/{booking_uid}` | Get booking details |
| 4 | POST | `/api/payment/create-order` | Create payment order |
| 5 | POST | `/api/payment/create-link` | Generate payment link |
| 6 | POST | `/api/payment/verify` | Verify payment |
| 7 | POST | `/api/payment/simulate` | Simulate payment (testing) |
| 8 | POST | `/api/feedback` | Submit user feedback |
| 9 | GET | `/api/feedback/stats` | Get feedback statistics |
| 10 | POST | `/api/retrain-model` | Retrain AI model |
| 11 | GET | `/api/test-database` | Test database connection |

---

## 🔐 Authentication

Currently, the API does not require authentication. For production use, implement:
- API key authentication
- JWT tokens
- Rate limiting

---

## 🌐 CORS

CORS is enabled for all origins (`*`). Modify in production for security.

---

## 📝 Response Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 404 | Resource not found |
| 500 | Internal server error |

---

## 🧪 Testing Tools

### Postman Collection
Import the base URL and test all endpoints: `https://dnvlikhitha-codestrom.hf.space`

### Swagger UI
Interactive API testing: `https://dnvlikhitha-codestrom.hf.space/docs`

### cURL Examples
All endpoints include cURL examples above.

---

## 💡 Best Practices

1. **Always check the `success` field** in responses
2. **Handle errors gracefully** - Check for error messages
3. **Use meaningful booking IDs** - Generate unique UIDs
4. **Validate user input** - GPS coordinates, amounts, etc.
5. **Test with simulate endpoints** before going live
6. **Monitor feedback stats** regularly for insights

---

## 📞 Support

- **Interactive Docs:** https://dnvlikhitha-codestrom.hf.space/docs
- **Health Check:** https://dnvlikhitha-codestrom.hf.space/
- **Database API:** https://aadarshsenapati.in/api/api.php

---

## 🚀 Quick Start Example

### Complete Booking Flow (Python)

```python
import requests

BASE_URL = "https://dnvlikhitha-codestrom.hf.space"

# Step 1: Get recommendations
recommendations = requests.post(
    f"{BASE_URL}/api/recommend-slots",
    json={
        "user_location": {"latitude": 16.4645659, "longitude": 80.5076208},
        "slots": [],
        "top_k": 3
    }
).json()

print(f"Found {recommendations['count']} slots")
best_slot = recommendations['recommendations'][0]

# Step 2: Create payment order
payment_order = requests.post(
    f"{BASE_URL}/api/payment/create-order",
    json={
        "booking_id": "BOOKING123",
        "slot_id": str(best_slot['db_slot_id']),
        "amount": best_slot['price_per_hour'] * 2,  # 2 hours
        "duration_hours": 2,
        "vehicle_number": "AP39AB1234",
        "customer_name": "John Doe",
        "customer_contact": "9876543210",
        "customer_email": "john@example.com"
    }
).json()

print(f"Order created: {payment_order['order_id']}")

# Step 3: After parking, submit feedback
feedback = requests.post(
    f"{BASE_URL}/api/feedback",
    json={
        "booking_id": payment_order['booking_uid'],
        "slot_id": str(best_slot['db_slot_id']),
        "rating": 5.0,
        "comment": "Perfect spot!",
        "user_satisfaction": True
    }
).json()

print(f"Feedback submitted: {feedback['success']}")
```

---

**Last Updated:** October 31, 2025  
**API Version:** 1.0.0  
**Base URL:** https://dnvlikhitha-codestrom.hf.space
