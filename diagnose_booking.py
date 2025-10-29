"""
Diagnose booking endpoint issue
"""
import requests
import json
from datetime import datetime, timedelta

base_url = "https://aadarshsenapati.in/api/api.php"

print("=" * 70)
print("DIAGNOSING book_slot ENDPOINT")
print("=" * 70)

# Prepare booking data
start_time = datetime.now()
end_time = start_time + timedelta(hours=2)

booking_data = {
    "user_id": 1,
    "slot_id": 1,
    "start_time": start_time.strftime("%Y-%m-%d %H:%M:%S"),
    "end_time": end_time.strftime("%Y-%m-%d %H:%M:%S"),
    "total_amount": 100.0
}

print("\n📤 Sending booking request...")
print(f"URL: {base_url}?action=book_slot")
print(f"\nPayload:")
print(json.dumps(booking_data, indent=2))

try:
    response = requests.post(
        f"{base_url}?action=book_slot",
        json=booking_data,
        headers={'Content-Type': 'application/json'},
        timeout=10
    )
    
    print(f"\n📥 Response Status Code: {response.status_code}")
    print(f"\nResponse Headers:")
    for key, value in response.headers.items():
        print(f"  {key}: {value}")
    
    print(f"\n📄 Response Body:")
    print(response.text[:2000])  # First 2000 chars
    
    if response.status_code == 200:
        try:
            data = response.json()
            print(f"\n✅ Parsed JSON:")
            print(json.dumps(data, indent=2))
        except:
            print("\n⚠️  Response is not valid JSON")
    else:
        print(f"\n❌ HTTP Error: {response.status_code}")
        
        # Try to see if there's any HTML error
        if "html" in response.text.lower():
            print("\n⚠️  Response appears to be HTML (likely a PHP error page)")
            # Extract title if present
            if "<title>" in response.text:
                start = response.text.find("<title>") + 7
                end = response.text.find("</title>")
                print(f"Error Title: {response.text[start:end]}")
        
except Exception as e:
    print(f"\n❌ Request failed: {e}")

# Try with different data formats
print("\n" + "=" * 70)
print("TRYING DIFFERENT DATA FORMATS")
print("=" * 70)

# Try 1: String slot_id
test_data_1 = {**booking_data, "slot_id": "1"}
print("\n1️⃣  Trying with string slot_id...")
try:
    response = requests.post(
        f"{base_url}?action=book_slot",
        json=test_data_1,
        timeout=5
    )
    print(f"   Status: {response.status_code}")
    if response.status_code == 200:
        print(f"   Result: {response.json().get('status')}")
except Exception as e:
    print(f"   Error: {e}")

# Try 2: Without total_amount
test_data_2 = {k: v for k, v in booking_data.items() if k != 'total_amount'}
print("\n2️⃣  Trying without total_amount...")
try:
    response = requests.post(
        f"{base_url}?action=book_slot",
        json=test_data_2,
        timeout=5
    )
    print(f"   Status: {response.status_code}")
    if response.status_code == 200:
        print(f"   Result: {response.json().get('status')}")
except Exception as e:
    print(f"   Error: {e}")

# Try 3: Minimal data
test_data_3 = {
    "user_id": 1,
    "slot_id": 1
}
print("\n3️⃣  Trying with minimal data (user_id, slot_id only)...")
try:
    response = requests.post(
        f"{base_url}?action=book_slot",
        json=test_data_3,
        timeout=5
    )
    print(f"   Status: {response.status_code}")
    if response.status_code == 200:
        print(f"   Result: {response.json().get('status')}")
    else:
        print(f"   Response: {response.text[:200]}")
except Exception as e:
    print(f"   Error: {e}")

print("\n" + "=" * 70)
print("💡 COMMON CAUSES OF 500 ERROR:")
print("=" * 70)
print("""
1. Table name mismatch (e.g., 'bookings' vs 'booking')
2. Column name mismatch (e.g., 'slot_id' vs 'parking_slot_id')
3. Missing table or columns in database
4. SQL syntax error in PHP code
5. Missing required fields
6. Data type mismatch (e.g., expecting INT but getting STRING)
7. Foreign key constraint violation
8. PHP syntax error or uncaught exception

📋 Check your PHP error logs for the actual error message!
""")
