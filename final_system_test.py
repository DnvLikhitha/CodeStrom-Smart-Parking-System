"""
Final System Test - Verify Everything Works
"""
from backend.database_api import DatabaseAPI, transform_parking_lots_for_ai
from datetime import datetime, timedelta
import json

def print_header(title):
    print("\n" + "="*70)
    print(f"🧪 {title}")
    print("="*70)

def main():
    print("""
╔══════════════════════════════════════════════════════════════════╗
║                   SMART PARKING SYSTEM                            ║
║                   FINAL SYSTEM TEST                               ║
╚══════════════════════════════════════════════════════════════════╝
""")
    
    api = DatabaseAPI()
    results = {}
    
    # Test 1: Get Available Slots
    print_header("TEST 1: Get Available Parking Slots")
    try:
        result = api.get_all_available_slots()
        if result['status'] == 'success' and result.get('data'):
            slots = result['data']
            print(f"✅ PASSED - Found {len(slots)} available slot(s)")
            print(f"   Sample: {slots[0].get('lot_name')}-{slots[0].get('slot_number')}")
            results['Get Slots'] = True
        else:
            print(f"❌ FAILED - No slots found")
            results['Get Slots'] = False
    except Exception as e:
        print(f"❌ FAILED - {e}")
        results['Get Slots'] = False
    
    # Test 2: AI Data Transformation
    print_header("TEST 2: AI Data Transformation")
    try:
        slots_result = api.get_all_available_slots()
        if slots_result['status'] == 'success':
            transformed = transform_parking_lots_for_ai(slots_result['data'])
            if transformed:
                print(f"✅ PASSED - Transformed {len(transformed)} slot(s) for AI")
                sample = transformed[0]
                print(f"   Slot ID: {sample['slot_id']}")
                print(f"   Coordinates: ({sample['latitude']}, {sample['longitude']})")
                print(f"   Price: ₹{sample['price_per_hour']}/hr")
                results['AI Transform'] = True
            else:
                print(f"❌ FAILED - No transformed data")
                results['AI Transform'] = False
    except Exception as e:
        print(f"❌ FAILED - {e}")
        results['AI Transform'] = False
    
    # Test 3: User Registration
    print_header("TEST 3: User Registration")
    try:
        result = api.register_user(
            name="System Test User",
            phone="+919876543210",
            email="systest@parking.com"
        )
        if result['status'] == 'success':
            print(f"✅ PASSED - {result.get('message')}")
            results['User Registration'] = True
        else:
            print(f"❌ FAILED - {result.get('message')}")
            results['User Registration'] = False
    except Exception as e:
        print(f"❌ FAILED - {e}")
        results['User Registration'] = False
    
    # Test 4: Create Booking
    print_header("TEST 4: Create Booking")
    booking_uid = None
    try:
        start_time = datetime.now()
        end_time = start_time + timedelta(hours=2)
        
        result = api.book_slot(
            user_id=9,  # Known existing user
            slot_id=1,  # Known existing slot
            start_time=start_time.strftime("%Y-%m-%d %H:%M:%S"),
            end_time=end_time.strftime("%Y-%m-%d %H:%M:%S"),
            total_amount=120.0
        )
        
        if result['status'] == 'success':
            booking_uid = result.get('booking_uid')
            print(f"✅ PASSED - Booking created")
            print(f"   Booking UID: {booking_uid}")
            results['Create Booking'] = True
        else:
            print(f"❌ FAILED - {result.get('message')}")
            results['Create Booking'] = False
    except Exception as e:
        print(f"❌ FAILED - {e}")
        results['Create Booking'] = False
    
    # Test 5: Get Booking Status
    if booking_uid:
        print_header("TEST 5: Get Booking Status")
        try:
            result = api.get_booking_status(booking_uid)
            if result['status'] == 'success' and result.get('data'):
                booking = result['data']
                print(f"✅ PASSED - Booking retrieved")
                print(f"   Status: {booking.get('status')}")
                print(f"   Payment: {booking.get('payment_status')}")
                print(f"   Amount: ₹{booking.get('total_amount')}")
                results['Get Booking'] = True
            else:
                print(f"❌ FAILED - Booking not found")
                results['Get Booking'] = False
        except Exception as e:
            print(f"❌ FAILED - {e}")
            results['Get Booking'] = False
    else:
        print_header("TEST 5: Get Booking Status")
        print("⏭️  SKIPPED - No booking created")
        results['Get Booking'] = None
    
    # Test 6: Update Payment
    if booking_uid:
        print_header("TEST 6: Update Payment Status")
        try:
            result = api.update_payment(
                booking_uid=booking_uid,
                payment_status='Paid',
                transaction_id=f'txn_test_{booking_uid}',
                amount=120.0
            )
            if result['status'] == 'success':
                print(f"✅ PASSED - Payment updated to Paid")
                results['Update Payment'] = True
            else:
                print(f"❌ FAILED - {result.get('message')}")
                results['Update Payment'] = False
        except Exception as e:
            print(f"❌ FAILED - {e}")
            results['Update Payment'] = False
    else:
        print_header("TEST 6: Update Payment Status")
        print("⏭️  SKIPPED - No booking created")
        results['Update Payment'] = None
    
    # Summary
    print("\n" + "="*70)
    print("📊 TEST SUMMARY")
    print("="*70)
    
    passed = sum(1 for v in results.values() if v is True)
    failed = sum(1 for v in results.values() if v is False)
    skipped = sum(1 for v in results.values() if v is None)
    total = passed + failed
    
    for test_name, result in results.items():
        if result is True:
            print(f"✅ PASSED: {test_name}")
        elif result is False:
            print(f"❌ FAILED: {test_name}")
        else:
            print(f"⏭️  SKIPPED: {test_name}")
    
    print("\n" + "="*70)
    print(f"Total: {passed}/{total} tests passed")
    if skipped:
        print(f"Skipped: {skipped} test(s)")
    
    if passed == total and total > 0:
        print("\n🎉 ALL TESTS PASSED! System is ready!")
    elif passed >= total * 0.8:
        print("\n✅ Most tests passed! System is functional!")
    else:
        print("\n⚠️  Some tests failed. Check the output above.")
    
    print("\n💡 To start the API server:")
    print("   python backend/main.py")
    print("\n📖 For more info, see:")
    print("   - SUCCESS_SUMMARY.md")
    print("   - TESTING_GUIDE.md")
    print("="*70)

if __name__ == "__main__":
    main()
