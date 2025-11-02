"""
Quick script to check what's in the database
"""
from backend.database_api import DatabaseAPI
import json

api = DatabaseAPI()

print("=" * 60)
print("CHECKING DATABASE CONTENT")
print("=" * 60)

# Check parking lots
print("\n1️⃣  PARKING LOTS:")
lots_result = api.get_parking_lots()
print(f"Status: {lots_result['status']}")
if lots_result.get('data'):
    for lot in lots_result['data']:
        print(f"\n📍 Lot {lot.get('lot_id')}: {lot.get('lot_name')}")
        print(f"   Location: {lot.get('address')}")
        print(f"   Coordinates: ({lot.get('latitude')}, {lot.get('longitude')})")
        print(f"   Total Slots: {lot.get('total_slots')}")
        print(f"   Raw data: {json.dumps(lot, indent=2)}")
else:
    print("No parking lots found")

# Check available slots for lot 1
print("\n" + "=" * 60)
print("2️⃣  AVAILABLE SLOTS FOR LOT 1:")
slots_result = api.get_available_slots(1)
print(f"Status: {slots_result['status']}")
print(f"Message: {slots_result.get('message', 'N/A')}")
if slots_result.get('data'):
    print(f"Found {len(slots_result['data'])} slots:")
    for slot in slots_result['data']:
        print(f"\n🅿️  Slot {slot.get('id')}: {slot.get('slot_number')}")
        print(f"   Parking Lot ID: {slot.get('parking_lot_id')}")
        print(f"   Vehicle Type: {slot.get('vehicle_type')}")
        print(f"   Available: {slot.get('is_available')}")
        print(f"   Active: {slot.get('is_active')}")
        print(f"   Hourly Rate: ₹{slot.get('hourly_rate')}")
        print(f"   Raw data: {json.dumps(slot, indent=2)}")
else:
    print("No available slots found")
    print(f"Full response: {json.dumps(slots_result, indent=2)}")

# Check all available slots
print("\n" + "=" * 60)
print("3️⃣  ALL AVAILABLE SLOTS:")
all_slots = api.get_all_available_slots()
print(f"Status: {all_slots['status']}")
print(f"Total Lots: {all_slots.get('total_lots', 0)}")
print(f"Total Slots: {all_slots.get('total_slots', 0)}")
if all_slots.get('data'):
    for slot in all_slots['data'][:3]:  # Show first 3
        print(f"\n🅿️  Slot: {slot.get('slot_number')} ({slot.get('lot_name')})")
        print(f"   Vehicle: {slot.get('vehicle_type')}, Rate: ₹{slot.get('hourly_rate')}/hr")

print("\n" + "=" * 60)
