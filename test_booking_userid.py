"""
Test booking with valid user_id
"""
import requests
import json
from datetime import datetime, timedelta

base_url = "https://aadarshsenapati.in/api/api.php"

print("=" * 70)
print("TESTING BOOKING WITH DIFFERENT USER IDs")
print("=" * 70)

# Prepare booking data
start_time = datetime.now()
end_time = start_time + timedelta(hours=2)

# Try different user IDs
user_ids_to_try = [1, 9, 10]

for user_id in user_ids_to_try:
    print(f"\n{'='*70}")
    print(f"🧪 Testing with user_id: {user_id}")
    print('='*70)
    
    booking_data = {
        "user_id": user_id,
        "slot_id": 1,
        "start_time": start_time.strftime("%Y-%m-%d %H:%M:%S"),
        "end_time": end_time.strftime("%Y-%m-%d %H:%M:%S"),
        "total_amount": 120.0
    }
    
    try:
        response = requests.post(
            f"{base_url}?action=book_slot",
            json=booking_data,
            headers={'Content-Type': 'application/json'},
            timeout=10
        )
        
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            try:
                result = response.json()
                print(f"✅ SUCCESS!")
                print(f"Response: {json.dumps(result, indent=2)}")
                
                if result.get('status') == 'success':
                    print(f"\n🎉 Booking created successfully!")
                    print(f"   Booking UID: {result.get('booking_uid')}")
                    print(f"   User ID: {user_id} ✅ WORKS!")
                    break  # Stop after first success
            except:
                print(f"⚠️  Response is not JSON: {response.text[:200]}")
        else:
            print(f"❌ FAILED with user_id: {user_id}")
            
    except Exception as e:
        print(f"❌ Error: {e}")

print("\n" + "=" * 70)
print("💡 RECOMMENDATION:")
print("=" * 70)
print("""
The booking endpoint works, but specific user_ids may not exist.

Solutions:
1. Use user_id that exists (like 9 from your manual test)
2. Create user_id = 1 in database
3. Update Python code to use existing user_id
""")
