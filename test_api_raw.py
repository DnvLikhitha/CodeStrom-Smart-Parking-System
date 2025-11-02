"""
Diagnose the actual API response structure
"""
import requests
import json

API_URL = "https://aadarshsenapati.in/api/api.php"

print("=" * 70)
print("RAW API RESPONSE DIAGNOSTIC")
print("=" * 70)

# Test 1: Get parking lots
print("\n1. GET PARKING LOTS")
print("-" * 70)
response = requests.get(f"{API_URL}?action=get_parking_lots", timeout=10)
print(f"Status Code: {response.status_code}")
print(f"\nRaw Response Text:")
print(response.text)
print(f"\nParsed JSON:")
data = response.json()
print(json.dumps(data, indent=2))

if data.get('status') == 'success' and data.get('data'):
    print(f"\n📊 First Record Fields:")
    first_record = data['data'][0]
    for key, value in first_record.items():
        print(f"   {key}: {value} (type: {type(value).__name__})")

# Test 2: Get users (if endpoint exists)
print("\n\n2. GET USERS")
print("-" * 70)
try:
    response = requests.get(f"{API_URL}?action=get_users", timeout=10)
    print(f"Status Code: {response.status_code}")
    if response.status_code == 200:
        print(json.dumps(response.json(), indent=2))
except Exception as e:
    print(f"Error: {e}")

# Test 3: Get slots (if different from lots)
print("\n\n3. GET SLOTS")
print("-" * 70)
try:
    response = requests.get(f"{API_URL}?action=get_slots", timeout=10)
    print(f"Status Code: {response.status_code}")
    if response.status_code == 200:
        data = response.json()
        print(json.dumps(data, indent=2))
        if data.get('status') == 'success' and data.get('data'):
            print(f"\n📊 First Slot Record Fields:")
            first_record = data['data'][0]
            for key, value in first_record.items():
                print(f"   {key}: {value} (type: {type(value).__name__})")
except Exception as e:
    print(f"Error: {e}")

# Test 4: Get slots by lot_id
print("\n\n4. GET SLOTS BY LOT ID")
print("-" * 70)
try:
    response = requests.get(f"{API_URL}?action=get_slots_by_lot&lot_id=1", timeout=10)
    print(f"Status Code: {response.status_code}")
    if response.status_code == 200:
        data = response.json()
        print(json.dumps(data, indent=2))
        if data.get('status') == 'success' and data.get('data'):
            print(f"\n📊 First Slot Record Fields:")
            first_record = data['data'][0]
            for key, value in first_record.items():
                print(f"   {key}: {value} (type: {type(value).__name__})")
except Exception as e:
    print(f"Error: {e}")

# Test 5: Get available slots
print("\n\n5. GET AVAILABLE SLOTS")
print("-" * 70)
try:
    response = requests.get(f"{API_URL}?action=get_available_slots", timeout=10)
    print(f"Status Code: {response.status_code}")
    if response.status_code == 200:
        data = response.json()
        print(json.dumps(data, indent=2))
        if data.get('status') == 'success' and data.get('data'):
            print(f"\n📊 First Slot Record Fields:")
            first_record = data['data'][0]
            for key, value in first_record.items():
                print(f"   {key}: {value} (type: {type(value).__name__})")
except Exception as e:
    print(f"Error: {e}")

# Test 6: Try to get parking_slots table data
print("\n\n6. GET PARKING SLOTS (alternative endpoint name)")
print("-" * 70)
try:
    response = requests.get(f"{API_URL}?action=get_parking_slots", timeout=10)
    print(f"Status Code: {response.status_code}")
    if response.status_code == 200:
        data = response.json()
        print(json.dumps(data, indent=2))
        if data.get('status') == 'success' and data.get('data'):
            print(f"\n📊 First Slot Record Fields:")
            first_record = data['data'][0]
            for key, value in first_record.items():
                print(f"   {key}: {value} (type: {type(value).__name__})")
except Exception as e:
    print(f"Error: {e}")

print("\n" + "=" * 70)
print("END DIAGNOSTIC")
print("=" * 70)
