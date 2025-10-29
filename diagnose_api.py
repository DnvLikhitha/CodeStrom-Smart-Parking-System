"""
Diagnose the API issue by making direct requests
"""
import requests
import json

base_url = "https://aadarshsenapati.in/api/api.php"

print("=" * 60)
print("DIAGNOSING API ENDPOINT")
print("=" * 60)

# Test get_available_slots endpoint
print("\n📡 Testing get_available_slots endpoint:")
print(f"URL: {base_url}?action=get_available_slots")

try:
    # Try POST request with parking_lot_id
    response = requests.post(
        f"{base_url}?action=get_available_slots",
        json={"parking_lot_id": 1},
        headers={'Content-Type': 'application/json'},
        timeout=10
    )
    
    print(f"\nStatus Code: {response.status_code}")
    print(f"Response Headers: {dict(response.headers)}")
    print(f"\nResponse Body:")
    print(response.text[:1000])  # First 1000 chars
    
    if response.status_code == 200:
        try:
            data = response.json()
            print(f"\nParsed JSON:")
            print(json.dumps(data, indent=2))
        except:
            print("\nCouldn't parse as JSON")
    
except Exception as e:
    print(f"❌ Error: {e}")

# Also try with different parking_lot_id formats
print("\n" + "=" * 60)
print("Testing with different parameter formats:")

formats = [
    {"parking_lot_id": 1},
    {"parking_lot_id": "1"},
    {"lot_id": 1},
]

for fmt in formats:
    print(f"\n📤 Trying: {fmt}")
    try:
        response = requests.post(
            f"{base_url}?action=get_available_slots",
            json=fmt,
            timeout=5
        )
        print(f"   Status: {response.status_code}")
        if response.status_code == 200:
            print(f"   Result: {response.json().get('status')}")
    except Exception as e:
        print(f"   Error: {e}")

print("\n" + "=" * 60)
