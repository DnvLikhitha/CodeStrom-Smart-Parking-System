# Database Schema Analysis & Issues

## Current Database Schema (from screenshots)

### Table 1: `owner` (or `users`)
```
- owner_id (or user_id)
- name
- email
- phone
- password
- created_on
```

### Table 2: `parking_lots`
```
- lot_id (PRIMARY KEY)
- owner_id (FOREIGN KEY)
- lot_name (e.g., "A1")
- latitude
- longitude
- total_slots
- address
- created_on
```

### Table 3: `parking_slots`
```
- slot_id (PRIMARY KEY)
- lot_id (FOREIGN KEY)
- slot_number (e.g., "1")
- vehicle_type (e.g., "4-wheeler")
- is_available (0 or 1)
- hourly_rate (e.g., 60.00)
```

### Other Tables (expected):
- `bookings` - For parking bookings
- `payments` - For payment tracking
- `feedback` - For user ratings

---

## Issues Found

### ❌ Issue 1: API Returns Parking Lots, Not Parking Slots
**Problem:** 
- Current API endpoint `get_parking_lots` returns LOT data (building/area level)
- Our AI model needs SLOT data (individual parking spaces)
- Database has slots in a separate `parking_slots` table

**Impact:**
- Cannot get slot-level information (availability, hourly_rate, vehicle_type)
- Cannot book individual slots (only have lot_id, not slot_id)

**Solution Required:**
Either:
1. **PHP API needs new endpoint:** `get_parking_slots` or `get_available_slots`
2. **PHP API should JOIN tables:** Return slots with their lot information
3. **Frontend calls multiple endpoints:** Get lots, then get slots for each lot

---

### ❌ Issue 2: Missing Fields in Current API Response
**Problem:**
Current `get_parking_lots` returns:
```json
{
  "lot_id": "1",
  "owner_id": "1", 
  "lot_name": "A1",
  "latitude": "16.4645659",
  "longitude": "80.5076208",
  "total_slots": "0",
  "address": "SRM University AP Parking lot background",
  "created_on": "2025-10-29 21:17:10"
}
```

Our code expects (from AI model):
```json
{
  "slot_id": "A1-1",
  "latitude": 16.4645659,
  "longitude": 80.5076208,
  "is_available": true,
  "price_per_hour": 60.0,
  "hourly_rate": 60.0,
  "vehicle_type": "4-wheeler"
}
```

**Missing Critical Fields:**
- ❌ `slot_id` (only have lot_id)
- ❌ `is_available` (availability status)
- ❌ `hourly_rate` / `price_per_hour` (pricing)
- ❌ `vehicle_type` (car/bike/truck)

---

### ❌ Issue 3: Booking Endpoint Expects slot_id
**Problem:**
- `book_slot` endpoint expects `slot_id` parameter
- Current API only provides `lot_id`
- Test fails with 500 error because slot_id is None

**Code Location:**
`backend/main.py` line ~320:
```python
booking_result = db_api.book_slot(
    user_id=user_id,
    slot_id=slot_db_id,  # ❌ This is None or wrong value
    ...
)
```

---

## Recommended Solutions

### 🔧 Solution 1: Update PHP API (RECOMMENDED)
Add a new endpoint that returns joined data:

**New Endpoint:** `get_available_slots`

**SQL Query (example):**
```sql
SELECT 
    ps.slot_id,
    ps.lot_id,
    ps.slot_number,
    ps.vehicle_type,
    ps.is_available,
    ps.hourly_rate,
    pl.lot_name,
    pl.latitude,
    pl.longitude,
    pl.address
FROM parking_slots ps
INNER JOIN parking_lots pl ON ps.lot_id = pl.lot_id
WHERE ps.is_available = 1
ORDER BY pl.lot_id, ps.slot_number
```

**Expected Response:**
```json
{
  "status": "success",
  "data": [
    {
      "slot_id": "1",
      "lot_id": "1",
      "slot_number": "1",
      "vehicle_type": "4-wheeler",
      "is_available": "1",
      "hourly_rate": "60.00",
      "lot_name": "A1",
      "latitude": "16.4645659",
      "longitude": "80.5076208",
      "address": "SRM University AP Parking lot background"
    }
  ]
}
```

---

### 🔧 Solution 2: Update Python Code (TEMPORARY FIX)
Modify code to work with current API structure:

**Changes Needed:**

1. **Update `database_api.py`:**
   - Create mock slots from lot data
   - Use lot_id as slot_id temporarily
   - Add hardcoded hourly_rate

2. **Update `main.py`:**
   - Handle lot_id instead of slot_id
   - Generate unique slot identifiers from lot data

**Trade-offs:**
- ✅ Works with current API
- ❌ Cannot track individual slot availability
- ❌ Cannot differentiate vehicle types
- ❌ All slots in a lot have same price

---

### 🔧 Solution 3: Database Schema Recommendations

**Add missing data:**
1. Insert actual parking slots into `parking_slots` table
2. Set proper `hourly_rate` values
3. Set `total_slots` in parking_lots table

**Example Data Insertion:**
```sql
-- For Lot A1 (lot_id=1), create 10 slots
INSERT INTO parking_slots (lot_id, slot_number, vehicle_type, is_available, hourly_rate) VALUES
(1, '1', '4-wheeler', 1, 60.00),
(1, '2', '4-wheeler', 1, 60.00),
(1, '3', '2-wheeler', 1, 30.00),
(1, '4', '2-wheeler', 1, 30.00),
(1, '5', '4-wheeler', 1, 60.00);

-- Update total_slots count
UPDATE parking_lots SET total_slots = 5 WHERE lot_id = 1;
```

---

## Required Actions

### For Backend Team (PHP API):
- [ ] Add `get_parking_slots` endpoint
- [ ] Add `get_available_slots` endpoint  
- [ ] Ensure `book_slot` accepts and validates `slot_id`
- [ ] Return joined lot+slot data in responses

### For Database Team:
- [ ] Insert sample parking slots data
- [ ] Verify foreign key relationships
- [ ] Update `total_slots` counts
- [ ] Add indexes for performance

### For Python/AI Team (Us):
- [x] Update `transform_parking_lots_for_ai()` to handle current schema
- [x] Add `proximity_score` field
- [ ] Update booking logic to use correct slot_id
- [ ] Add error handling for missing fields
- [ ] Create fallback data if API incomplete

---

## Quick Fix for Testing (Right Now)

Since we can't modify the PHP API immediately, here's what we can do:

1. **Mock the slot data in Python code**
2. **Generate virtual slots from lot data**
3. **Use lot_id as temporary slot_id**
4. **Add dummy pricing and availability**

This will let us test the AI model and payment flow while waiting for proper API updates.

---

## Test Results Summary

✅ **Working:**
- User registration
- Database connection
- Data transformation (after fixes)

❌ **Broken:**
- Slot availability checking (no slot data)
- Booking creation (missing slot_id)
- Payment flow (depends on booking)
- Feedback (depends on booking)

**Root Cause:** Mismatch between database schema (lots vs slots) and API implementation.
