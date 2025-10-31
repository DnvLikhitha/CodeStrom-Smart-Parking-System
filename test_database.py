"""
Test Database API Integration
Tests the connection to https://aadarshsenapati.in/api/api.php
"""

import sys
import os
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from backend.database_api import DatabaseAPI, transform_parking_lots_for_ai
from datetime import datetime, timedelta
import json


def test_database_connection():
    """Test basic database connectivity"""
    print("\n" + "=" * 70)
    print("DATABASE API CONNECTION TEST")
    print("=" * 70)
    
    api = DatabaseAPI()
    
    # Test 1: Get parking lots and slots
    print("\n1️⃣  Testing: Get Available Parking Slots")
    print("-" * 70)
    result = api.get_all_available_slots()
    
    if result['status'] == 'success':
        slots = result.get('data', [])
        total_lots = result.get('total_lots', 0)
        print(f"✅ SUCCESS: Found {len(slots)} available slots from {total_lots} parking lot(s)")
        
        if slots:
            print(f"\n📊 Sample Parking Slot:")
            sample = slots[0]
            print(f"   Slot ID: {sample.get('slot_id')}")
            print(f"   Slot Number: {sample.get('slot_number')}")
            print(f"   Lot Name: {sample.get('lot_name')}")
            print(f"   Location: {sample.get('lot_address')}")
            print(f"   Coordinates: ({sample.get('lot_latitude')}, {sample.get('lot_longitude')})")
            print(f"   Vehicle Type: {sample.get('vehicle_type')}")
            print(f"   Price/Hour: ₹{sample.get('hourly_rate')}")
            
            # Test data transformation
            print(f"\n🔄 Transforming data for AI model...")
            transformed = transform_parking_lots_for_ai(slots)
            print(f"✅ Transformed {len(transformed)} slots")
            print(f"\n📊 Transformed Sample:")
            t_sample = transformed[0]
            print(f"   Slot ID: {t_sample['slot_id']}")
            print(f"   DB Slot ID: {t_sample.get('db_slot_id')}")
            print(f"   Coordinates: ({t_sample['latitude']}, {t_sample['longitude']})")
            print(f"   Proximity Score: {t_sample['proximity_score']}")
            print(f"   Popularity Score: {t_sample['popularity_score']}")
            print(f"   Price Factor: {t_sample['price_factor']}")
            print(f"   Price/Hour: ₹{t_sample['price_per_hour']}")
    else:
        print(f"❌ FAILED: {result.get('message')}")
    
    return api


def test_user_registration(api):
    """Test user registration"""
    print("\n2️⃣  Testing: User Registration")
    print("-" * 70)
    
    test_user = {
        "name": "Test User AI",
        "phone": "+919999888877",
        "email": "aitest@smartparking.com"
    }
    
    result = api.register_user(**test_user)
    
    if result['status'] == 'success':
        print(f"✅ SUCCESS: {result.get('message')}")
        print(f"   Name: {test_user['name']}")
        print(f"   Phone: {test_user['phone']}")
    else:
        print(f"❌ FAILED: {result.get('message')}")
    
    return result['status'] == 'success'


def test_booking_flow(api):
    """Test complete booking flow"""
    print("\n3️⃣  Testing: Booking Flow")
    print("-" * 70)
    
    # Get available slots first
    slots_result = api.get_all_available_slots()
    if slots_result['status'] != 'success' or not slots_result.get('data'):
        print("❌ Cannot test booking: No available slots")
        return False
    
    # Use first available slot
    slot = slots_result['data'][0]
    slot_db_id = slot.get('slot_id') or slot.get('id') or 1
    slot_number = slot.get('slot_number', 'Unknown')
    lot_name = slot.get('lot_name', 'Unknown')
    
    # Create booking
    start_time = datetime.now()
    end_time = start_time + timedelta(hours=2)
    
    # Use user_id = 9 (known to exist in database)
    # In production, this should be the authenticated user's ID
    booking_data = {
        "user_id": 9,  # Using existing user ID from database
        "slot_id": int(slot_db_id),
        "start_time": start_time.strftime("%Y-%m-%d %H:%M:%S"),
        "end_time": end_time.strftime("%Y-%m-%d %H:%M:%S"),
        "total_amount": 120.0
    }
    
    print(f"\n📝 Creating booking...")
    print(f"   Lot: {lot_name}")
    print(f"   Slot: {slot_number} (DB ID: {slot_db_id})")
    print(f"   Duration: 2 hours")
    print(f"   Amount: ₹120")
    print(f"   User ID: 9 (existing user)")
    
    result = api.book_slot(**booking_data)
    
    if result['status'] == 'success':
        booking_uid = result.get('booking_uid')
        print(f"✅ SUCCESS: Booking created")
        print(f"   Booking UID: {booking_uid}")
        
        # Test getting booking status
        print(f"\n📊 Fetching booking status...")
        status_result = api.get_booking_status(booking_uid)
        
        if status_result['status'] == 'success':
            booking = status_result.get('data', {})
            print(f"✅ Booking found:")
            print(f"   Status: {booking.get('status')}")
            print(f"   Payment Status: {booking.get('payment_status')}")
            print(f"   Amount: ₹{booking.get('total_amount')}")
        
        return booking_uid
    else:
        print(f"❌ FAILED: {result.get('message')}")
        return None


def test_payment_update(api, booking_uid):
    """Test payment status update"""
    if not booking_uid:
        print("\n⏭️  Skipping payment test (no booking UID)")
        return
    
    print("\n4️⃣  Testing: Payment Update")
    print("-" * 70)
    
    payment_data = {
        "booking_uid": booking_uid,
        "status": "Completed",
        "transaction_id": f"txn_test_{booking_uid}",
        "amount": 100.0
    }
    
    print(f"💳 Updating payment status to 'Completed'...")
    result = api.update_payment(**payment_data)
    
    if result['status'] == 'success':
        print(f"✅ SUCCESS: {result.get('message')}")
        
        # Verify update
        print(f"\n✓ Verifying update...")
        status_result = api.get_booking_status(booking_uid)
        if status_result['status'] == 'success':
            booking = status_result.get('data', {})
            print(f"   Payment Status: {booking.get('payment_status')}")
    else:
        print(f"❌ FAILED: {result.get('message')}")


def test_feedback_submission(api, booking_uid):
    """Test feedback submission"""
    if not booking_uid:
        print("\n⏭️  Skipping feedback test (no booking UID)")
        return
    
    print("\n5️⃣  Testing: Feedback Submission")
    print("-" * 70)
    
    # Get booking ID (not UID) first
    status_result = api.get_booking_status(booking_uid)
    if status_result['status'] != 'success':
        print("❌ Cannot get booking details for feedback")
        return
    
    booking = status_result.get('data', {})
    booking_id = booking.get('id')
    user_id = booking.get('user_id', 1)
    
    feedback_data = {
        "user_id": user_id,
        "booking_id": booking_id,
        "rating": 5.0,
        "comments": "Excellent parking spot! Very convenient location."
    }
    
    print(f"⭐ Submitting feedback...")
    print(f"   Rating: {feedback_data['rating']}/5.0")
    print(f"   Comment: {feedback_data['comments']}")
    
    result = api.add_feedback(**feedback_data)
    
    if result['status'] == 'success':
        print(f"✅ SUCCESS: {result.get('message')}")
    else:
        print(f"❌ FAILED: {result.get('message')}")


def test_booking_cancellation(api, booking_uid):
    """Test booking cancellation"""
    if not booking_uid:
        print("\n⏭️  Skipping cancellation test (no booking UID)")
        return
    
    print("\n6️⃣  Testing: Booking Cancellation")
    print("-" * 70)
    
    print(f"❌ Cancelling booking {booking_uid}...")
    result = api.cancel_booking(booking_uid)
    
    if result['status'] == 'success':
        print(f"✅ SUCCESS: {result.get('message')}")
        
        # Verify cancellation
        status_result = api.get_booking_status(booking_uid)
        if status_result['status'] == 'success':
            booking = status_result.get('data', {})
            print(f"   Status: {booking.get('status')}")
    else:
        print(f"❌ FAILED: {result.get('message')}")


def main():
    """Run all database tests"""
    print("\n" + "=" * 70)
    print("🚀 SMART PARKING - DATABASE API INTEGRATION TESTS")
    print("=" * 70)
    print(f"Database URL: https://aadarshsenapati.in/api/api.php")
    print(f"Test Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    try:
        # Initialize API
        api = test_database_connection()
        
        # Test user registration
        test_user_registration(api)
        
        # Test booking flow
        booking_uid = test_booking_flow(api)
        
        # Test payment update
        test_payment_update(api, booking_uid)
        
        # Test feedback
        test_feedback_submission(api, booking_uid)
        
        # Don't cancel in tests - keep the data
        # test_booking_cancellation(api, booking_uid)
        
        print("\n" + "=" * 70)
        print("✅ DATABASE API INTEGRATION TESTS COMPLETED")
        print("=" * 70)
        print("\n💡 Next Steps:")
        print("   1. Verify data in database")
        print("   2. Test AI recommendations with real data")
        print("   3. Integrate with payment system")
        print("   4. Deploy to production")
        
    except Exception as e:
        print(f"\n❌ ERROR: {e}")
        import traceback
        traceback.print_exc()


if __name__ == "__main__":
    main()
