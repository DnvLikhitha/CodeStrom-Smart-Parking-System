"""
Test Slot Availability Update - Prevent Double Booking
"""
from backend.database_api import DatabaseAPI
from datetime import datetime, timedelta
import json

api = DatabaseAPI()

print("=" * 70)
print("TESTING SLOT AVAILABILITY UPDATE (PREVENT DOUBLE BOOKING)")
print("=" * 70)

# Step 1: Check initial slot availability
print("\n1️⃣  Checking initial slot availability...")
slots_result = api.get_all_available_slots()

if slots_result['status'] == 'success' and slots_result.get('data'):
    slot = slots_result['data'][0]
    slot_id = slot.get('slot_id') or slot.get('id')
    print(f"Slot ID: {slot_id}")
    print(f"Slot Number: {slot.get('slot_number')}")
    print(f"Initial status: Available ✅")
    
    # Step 2: Create a booking
    print(f"\n2️⃣  Creating booking for slot {slot_id}...")
    start_time = datetime.now()
    end_time = start_time + timedelta(hours=2)
    
    booking_result = api.book_slot(
        user_id=9,
        slot_id=int(slot_id),
        start_time=start_time.strftime("%Y-%m-%d %H:%M:%S"),
        end_time=end_time.strftime("%Y-%m-%d %H:%M:%S"),
        total_amount=120.0
    )
    
    if booking_result['status'] == 'success':
        booking_uid = booking_result['booking_uid']
        print(f"✅ Booking created: {booking_uid}")
        
        # Step 3: Check if slot is now unavailable
        print(f"\n3️⃣  Checking slot availability after booking...")
        
        # Direct API call to check slot status
        import requests
        response = requests.get(
            f"https://aadarshsenapati.in/api/api.php?action=get_available_slots",
            json={"parking_lot_id": 1},
            timeout=10
        )
        
        print(f"Available slots response:")
        print(json.dumps(response.json(), indent=2))
        
        # Also try to get all slots (including unavailable)
        print(f"\n4️⃣  Attempting to book the same slot again...")
        duplicate_booking = api.book_slot(
            user_id=9,
            slot_id=int(slot_id),
            start_time=start_time.strftime("%Y-%m-%d %H:%M:%S"),
            end_time=end_time.strftime("%Y-%m-%d %H:%M:%S"),
            total_amount=120.0
        )
        
        if duplicate_booking['status'] == 'success':
            print(f"❌ WARNING: Duplicate booking allowed!")
            print(f"   Duplicate booking UID: {duplicate_booking['booking_uid']}")
            print(f"   This means is_available was not updated to 0")
        else:
            print(f"✅ GOOD: Duplicate booking prevented!")
            print(f"   Message: {duplicate_booking.get('message')}")
        
        # Step 5: Test cancellation (should release slot)
        print(f"\n5️⃣  Testing booking cancellation...")
        cancel_result = api.cancel_booking(booking_uid)
        
        if cancel_result['status'] == 'success':
            print(f"✅ Booking cancelled")
            
            # Check if slot is available again
            print(f"\n6️⃣  Checking if slot is available after cancellation...")
            slots_after = api.get_all_available_slots()
            
            if slots_after['status'] == 'success':
                available_count = len(slots_after.get('data', []))
                print(f"Available slots now: {available_count}")
                
                if available_count > 0:
                    print(f"✅ GOOD: Slot released and available again!")
                else:
                    print(f"❌ WARNING: Slot not released after cancellation")
        else:
            print(f"❌ Cancellation failed: {cancel_result.get('message')}")
    else:
        print(f"❌ Booking failed: {booking_result.get('message')}")
else:
    print("❌ No slots found to test")

print("\n" + "=" * 70)
print("SUMMARY")
print("=" * 70)
print("""
✅ EXPECTED BEHAVIOR:
   1. Initial slot shows as available (is_available = 1)
   2. After booking, slot becomes unavailable (is_available = 0)
   3. Duplicate booking should be prevented
   4. After cancellation, slot becomes available again (is_available = 1)

❌ CURRENT ISSUE (if not working):
   - update_slot endpoint might not be implemented in PHP
   - is_available not being updated when booking
   - Multiple vehicles can book same slot

🔧 FIX NEEDED IN PHP:
   Implement update_slot endpoint to update is_available field
""")
