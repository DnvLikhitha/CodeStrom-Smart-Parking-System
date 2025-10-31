"""
Diagnose Payment Update Issue
"""
import requests
import json
from datetime import datetime, timedelta
from backend.database_api import DatabaseAPI

api = DatabaseAPI()
base_url = "https://aadarshsenapati.in/api/api.php"

print("=" * 70)
print("DIAGNOSING PAYMENT UPDATE ISSUE")
print("=" * 70)

# Step 1: Create a test booking first
print("\n1️⃣  Creating a test booking...")
start_time = datetime.now()
end_time = start_time + timedelta(hours=2)

booking_result = api.book_slot(
    user_id=9,
    slot_id=1,
    start_time=start_time.strftime("%Y-%m-%d %H:%M:%S"),
    end_time=end_time.strftime("%Y-%m-%d %H:%M:%S"),
    total_amount=120.0
)

if booking_result['status'] == 'success':
    booking_uid = booking_result['booking_uid']
    print(f"✅ Booking created: {booking_uid}")
    
    # Step 2: Check initial status
    print(f"\n2️⃣  Checking initial booking status...")
    status_result = api.get_booking_status(booking_uid)
    if status_result['status'] == 'success':
        booking_data = status_result['data']
        print(f"Initial payment_status: '{booking_data.get('payment_status')}'")
        print(f"Full booking data:")
        print(json.dumps(booking_data, indent=2))
    
    # Step 3: Update payment status
    print(f"\n3️⃣  Updating payment status to 'Paid'...")
    
    # Direct API call to see exact request/response
    payment_data = {
        "booking_uid": booking_uid,
        "payment_status": "Paid",
        "transaction_id": f"txn_test_{booking_uid}",
        "amount": 120.0
    }
    
    print(f"\nPayload being sent:")
    print(json.dumps(payment_data, indent=2))
    
    response = requests.post(
        f"{base_url}?action=update_payment_status",
        json=payment_data,
        headers={'Content-Type': 'application/json'},
        timeout=10
    )
    
    print(f"\nResponse Status: {response.status_code}")
    print(f"Response Body:")
    print(response.text)
    
    if response.status_code == 200:
        try:
            result = response.json()
            print(f"\nParsed Response:")
            print(json.dumps(result, indent=2))
        except:
            print("Could not parse JSON")
    
    # Step 4: Check updated status
    print(f"\n4️⃣  Checking booking status after update...")
    status_result = api.get_booking_status(booking_uid)
    if status_result['status'] == 'success':
        booking_data = status_result['data']
        payment_status = booking_data.get('payment_status')
        print(f"After update payment_status: '{payment_status}'")
        print(f"Full booking data:")
        print(json.dumps(booking_data, indent=2))
        
        # Analysis
        print(f"\n5️⃣  ANALYSIS:")
        if payment_status == 'Paid':
            print("✅ Payment status updated correctly!")
        elif payment_status == 'Completed':
            print("⚠️  Payment status is 'Completed' (should be 'Paid')")
            print("   Valid statuses: Pending, Paid, Failed, Refunded")
        elif payment_status == '':
            print("❌ Payment status is EMPTY (removed)")
            print("   This suggests the UPDATE query is setting it to empty string")
        elif payment_status is None:
            print("❌ Payment status is NULL")
            print("   This suggests the UPDATE query is setting it to NULL")
        elif payment_status == 'Pending':
            print("❌ Payment status is still 'Pending'")
            print("   This suggests the UPDATE query didn't execute")
        else:
            print(f"⚠️  Payment status is: '{payment_status}'")
            print("   Unexpected value")
    
else:
    print(f"❌ Failed to create booking: {booking_result.get('message')}")

print("\n" + "=" * 70)
print("💡 COMMON CAUSES:")
print("=" * 70)
print("""
1. UPDATE query is setting payment_status to empty string instead of value
2. Column name mismatch (e.g., 'paymentStatus' vs 'payment_status')
3. Missing WHERE clause causing wrong row to update
4. Database user lacks UPDATE permission
5. SQL syntax error silently failing

🔍 Check your PHP update_payment code!
""")
