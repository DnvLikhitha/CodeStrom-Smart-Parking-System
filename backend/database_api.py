"""
Database API Client for Smart Parking System
Connects to the PHP backend API at https://aadarshsenapati.in/api/api.php
"""

import requests
from typing import Dict, List, Optional
import json


class DatabaseAPI:
    """Client for interacting with the parking database API"""
    
    def __init__(self, base_url: str = "https://aadarshsenapati.in/api/api.php"):
        self.base_url = base_url
        self.session = requests.Session()
        
    def _make_request(self, action: str, method: str = "GET", data: Dict = None) -> Dict:
        """Make API request"""
        try:
            url = f"{self.base_url}?action={action}"
            
            if method == "GET":
                response = self.session.get(url, timeout=10)
            else:
                response = self.session.post(
                    url,
                    json=data,
                    headers={'Content-Type': 'application/json'},
                    timeout=10
                )
            
            response.raise_for_status()
            return response.json()
            
        except requests.exceptions.RequestException as e:
            return {
                "status": "error",
                "message": f"API request failed: {str(e)}"
            }
    
    # User Management
    def register_user(self, name: str, phone: str, email: str = "") -> Dict:
        """
        Register a new user or update existing user
        
        Args:
            name: User's name
            phone: User's phone number (unique identifier)
            email: User's email address (optional)
            
        Returns:
            {"status": "success", "message": "User registered successfully"}
        """
        data = {
            "name": name,
            "phone": phone,
            "email": email
        }
        return self._make_request("register_user", "POST", data)
    
    # Parking Lots
    def get_parking_lots(self) -> Dict:
        """
        Get all available parking lots
        
        NOTE: Current API returns parking_lots table data (lot-level, not slot-level)
        For proper functionality, API should return parking_slots data or joined data.
        
        Returns:
            {
                "status": "success",
                "data": [
                    {
                        "lot_id": "1",
                        "lot_name": "A1",
                        "latitude": "16.4645659",
                        "longitude": "80.5076208",
                        "total_slots": "0",
                        "address": "SRM University AP",
                        ...
                    }
                ]
            }
        """
        return self._make_request("get_parking_lots", "GET")
    
    def get_available_slots(self, parking_lot_id: int) -> Dict:
        """
        Get available slots for a specific parking lot
        Uses the new 'get_available_slots' endpoint in the PHP API
        
        Args:
            parking_lot_id: The parking lot ID to get available slots for
            
        Returns:
            {
                "status": "success",
                "data": [
                    {
                        "id": 1,
                        "parking_lot_id": 1,
                        "slot_number": "A1",
                        "vehicle_type": "4-wheeler",
                        "is_available": 1,
                        "is_active": 1,
                        "hourly_rate": "60.00",
                        ...
                    }
                ]
            }
        """
        data = {"parking_lot_id": parking_lot_id}
        return self._make_request("get_available_slots", "GET", data)
    
    def get_all_available_slots(self) -> Dict:
        """
        Get all available slots from all parking lots
        
        Returns:
            Combined data from all parking lots
        """
        # First get all parking lots
        lots_result = self.get_parking_lots()
        
        if lots_result['status'] != 'success':
            return lots_result
        
        all_slots = []
        lots = lots_result.get('data', [])
        
        # Get available slots for each parking lot
        for lot in lots:
            lot_id = int(lot.get('lot_id', 0))
            if lot_id:
                slots_result = self.get_available_slots(lot_id)
                if slots_result['status'] == 'success':
                    # The API might return slots directly in 'data' or nested in 'available_slots'
                    slots_data = slots_result.get('data', [])
                    
                    # Handle nested structure: data is a list with lot info and available_slots array
                    if slots_data and isinstance(slots_data, list):
                        for item in slots_data:
                            # Check if slots are nested in 'available_slots' field
                            if 'available_slots' in item:
                                nested_slots = item.get('available_slots', [])
                                # Add lot information to each nested slot
                                for slot in nested_slots:
                                    slot['lot_latitude'] = lot.get('latitude')
                                    slot['lot_longitude'] = lot.get('longitude')
                                    slot['lot_name'] = lot.get('lot_name')
                                    slot['lot_address'] = lot.get('address')
                                    slot['parking_lot_id'] = lot_id
                                all_slots.extend(nested_slots)
                            else:
                                # Slots are directly in the data array
                                item['lot_latitude'] = lot.get('latitude')
                                item['lot_longitude'] = lot.get('longitude')
                                item['lot_name'] = lot.get('lot_name')
                                item['lot_address'] = lot.get('address')
                                all_slots.append(item)
        
        return {
            'status': 'success',
            'data': all_slots,
            'total_lots': len(lots),
            'total_slots': len(all_slots)
        }
    
    def get_parking_slots(self, lot_id: Optional[int] = None) -> Dict:
        """
        Get parking slots (individual parking spaces)
        
        DEPRECATED: Use get_available_slots() or get_all_available_slots() instead
        
        Args:
            lot_id: Optional lot ID to filter slots
            
        Returns:
            Slot data with availability, pricing, vehicle type
        """
        if lot_id:
            return self.get_available_slots(lot_id)
        else:
            return self.get_all_available_slots()
    
    def _generate_slots_from_lots(self, lots: List[Dict]) -> Dict:
        """
        TEMPORARY: Generate mock slot data from parking lot data
        This is a workaround until the PHP API provides proper slot endpoints
        """
        slots = []
        
        for lot in lots:
            lot_id = lot.get('lot_id')
            total_slots = int(lot.get('total_slots', 5))
            
            # If total_slots is 0, create some default slots
            if total_slots == 0:
                total_slots = 5
            
            # Create individual slots for this lot
            for i in range(1, total_slots + 1):
                slot = {
                    'slot_id': str(i + (int(lot_id) - 1) * 100),  # Generate unique slot_id
                    'lot_id': lot_id,
                    'slot_number': str(i),
                    'lot_name': lot.get('lot_name', 'Unknown'),
                    'latitude': lot.get('latitude'),
                    'longitude': lot.get('longitude'),
                    'address': lot.get('address', ''),
                    'vehicle_type': '4-wheeler' if i % 3 != 0 else '2-wheeler',
                    'is_available': '1' if i % 4 != 0 else '0',  # 75% available
                    'hourly_rate': '60.00' if i % 3 != 0 else '30.00'  # Cars more expensive
                }
                slots.append(slot)
        
        return {
            'status': 'success',
            'data': slots,
            'generated': True,  # Flag to indicate this is mock data
            'message': 'Mock slots generated from lot data'
        }
    
    
    # Booking Management
    def book_slot(self, user_id: int, slot_id: int, start_time: str, 
                  end_time: str, total_amount: float) -> Dict:
        """
        Book a parking slot
        
        Args:
            user_id: User ID from database
            slot_id: Parking slot ID
            start_time: Booking start time (format: YYYY-MM-DD HH:MM:SS)
            end_time: Booking end time (format: YYYY-MM-DD HH:MM:SS)
            total_amount: Total booking amount
            
        Returns:
            {"status": "success", "booking_uid": "ABC12345"}
        """
        data = {
            "user_id": user_id,
            "slot_id": slot_id,
            "start_time": start_time,
            "end_time": end_time,
            "total_amount": total_amount
        }
        return self._make_request("book_slot", "POST", data)
    
    def update_payment(self, booking_uid: str, payment_status: str, 
                      transaction_id: str = "", amount: float = 0) -> Dict:
        """
        Update payment status for a booking
        
        Args:
            booking_uid: Unique booking ID (e.g., "ABC12345")
            payment_status: Payment status ("Pending", "Paid", "Failed", "Refunded")
            transaction_id: Razorpay transaction ID (optional)
            amount: Payment amount (optional)
            
        Returns:
            {"status": "success", "message": "Payment status updated successfully"}
        """
        data = {
            "booking_uid": booking_uid,
            "payment_status": payment_status,
            "transaction_id": transaction_id,
            "amount": amount
        }
        return self._make_request("update_payment_status", "POST", data)
    
    def get_booking_status(self, booking_uid: str) -> Dict:
        """
        Get booking details by booking UID
        
        Args:
            booking_uid: Unique booking ID
            
        Returns:
            {
                "status": "success",
                "data": {
                    "id": 1,
                    "user_id": 1,
                    "slot_id": 5,
                    "booking_uid": "ABC12345",
                    "start_time": "2025-10-28 10:00:00",
                    "end_time": "2025-10-28 12:00:00",
                    "total_amount": 100.0,
                    "payment_status": "Completed",
                    "status": "Active"
                }
            }
        """
        url = f"{self.base_url}?action=get_booking_status&booking_uid={booking_uid}"
        try:
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            return {
                "status": "error",
                "message": f"Failed to get booking status: {str(e)}"
            }
    
    def cancel_booking(self, booking_uid: str) -> Dict:
        """
        Cancel a booking
        
        Args:
            booking_uid: Unique booking ID
            
        Returns:
            {"status": "success", "message": "Booking cancelled"}
        """
        data = {"booking_uid": booking_uid}
        return self._make_request("cancel_booking", "POST", data)
    
    # Feedback
    def add_feedback(self, user_id: int, booking_id: int, 
                    rating: float, comments: str = "") -> Dict:
        """
        Add user feedback for a booking
        
        Args:
            user_id: User ID
            booking_id: Booking ID (not booking_uid)
            rating: Rating (1-5)
            comments: Optional comments
            
        Returns:
            {"status": "success", "message": "Feedback added"}
        """
        data = {
            "user_id": user_id,
            "booking_id": booking_id,
            "rating": rating,
            "comments": comments
        }
        return self._make_request("add_feedback", "POST", data)


# Helper functions for data transformation
def transform_parking_lots_for_ai(parking_slots: List[Dict]) -> List[Dict]:
    """
    Transform database parking slots to AI model format
    
    Database format (from get_available_slots):
    - id (slot_id), parking_lot_id, slot_number, vehicle_type, 
      is_available, is_active, hourly_rate
    - lot_latitude, lot_longitude, lot_name, lot_address (added by get_all_available_slots)
    
    AI model expects: 
    - slot_id, latitude, longitude, avg_feedback, popularity_score, 
      is_available, price_factor, price_per_hour, proximity_score
    """
    transformed = []
    
    for slot in parking_slots:
        # Get slot identifier - prefer slot_number, fallback to id
        slot_db_id = slot.get('id') or slot.get('slot_id')
        slot_number = slot.get('slot_number', '')
        lot_name = slot.get('lot_name', '')
        
        # Create readable slot ID
        if lot_name and slot_number:
            slot_id_str = f"{lot_name}-{slot_number}"
        elif slot_number:
            slot_id_str = f"SLOT-{slot_number}"
        else:
            slot_id_str = f"SLOT-{slot_db_id}"
        
        # Get coordinates - prefer lot_latitude/longitude, fallback to direct fields
        latitude = float(slot.get('lot_latitude') or slot.get('latitude') or 0)
        longitude = float(slot.get('lot_longitude') or slot.get('longitude') or 0)
        
        # Get price - could be 'hourly_rate' or 'price_per_hour'
        price = float(slot.get('hourly_rate') or slot.get('price_per_hour') or 50)
        
        # Get availability - could be 'is_available' (0/1) or boolean
        is_available = slot.get('is_available')
        if is_available is not None:
            is_available = bool(int(is_available)) if isinstance(is_available, (str, int)) else bool(is_available)
        else:
            is_available = True
        
        # Check if slot is active
        is_active = slot.get('is_active', 1)
        is_active = bool(int(is_active)) if isinstance(is_active, (str, int)) else bool(is_active)
        
        # Only include active slots
        if not is_active:
            continue
        
        transformed_slot = {
            'slot_id': slot_id_str,
            'latitude': latitude,
            'longitude': longitude,
            'avg_feedback': 3.5,  # Default, will be updated with real feedback
            'popularity_score': 0.5,  # Default, will be calculated from booking history
            'proximity_score': 0.5,  # Default, will be calculated based on user location
            'is_available': is_available,
            'price_factor': min(price / 100, 1.0),  # Normalize to 0-1 range
            'price_per_hour': price,
            # Keep original data for reference
            'db_slot_id': slot_db_id,
            'db_lot_id': slot.get('parking_lot_id'),
            'slot_number': slot_number,
            'lot_name': lot_name,
            'location': slot.get('lot_address') or slot.get('address') or slot.get('location') or '',
            'vehicle_type': slot.get('vehicle_type', 'all')
        }
        transformed.append(transformed_slot)
    
    return transformed


# Example usage and testing
if __name__ == "__main__":
    # Initialize API client
    api = DatabaseAPI()
    
    print("Testing Database API Integration\n")
    print("=" * 50)
    
    # Test 1: Get parking lots
    print("\n1. Fetching parking lots...")
    result = api.get_parking_lots()
    if result['status'] == 'success':
        print(f"✅ Found {len(result.get('data', []))} parking lots")
        if result.get('data'):
            print(f"   First lot: {result['data'][0].get('lot_name')}")
    else:
        print(f"❌ Error: {result.get('message')}")
    
    # Test 1b: Get available slots
    print("\n1b. Fetching available slots from all lots...")
    result = api.get_all_available_slots()
    if result['status'] == 'success':
        print(f"✅ Found {result.get('total_slots')} available slots from {result.get('total_lots')} lots")
        if result.get('data'):
            slot = result['data'][0]
            print(f"   First slot: {slot.get('slot_number')} (Lot: {slot.get('lot_name')})")
            print(f"   Type: {slot.get('vehicle_type')}, Rate: ₹{slot.get('hourly_rate')}/hr")
    else:
        print(f"❌ Error: {result.get('message')}")
    
    # Test 2: Register user (example)
    print("\n2. Testing user registration...")
    result = api.register_user(
        name="Test User",
        phone="+919876543210",
        email="test@example.com"
    )
    print(f"   Status: {result.get('status')} - {result.get('message')}")
    
    # Test 3: Transform data for AI
    print("\n3. Testing data transformation...")
    slots_result = api.get_all_available_slots()
    if slots_result['status'] == 'success' and slots_result.get('data'):
        transformed = transform_parking_lots_for_ai(slots_result['data'])
        print(f"✅ Transformed {len(transformed)} slots for AI model")
        if transformed:
            slot = transformed[0]
            print(f"   Example: {slot['slot_id']} at ({slot['latitude']}, {slot['longitude']})")
            print(f"   Price: ₹{slot['price_per_hour']}/hr, Available: {slot['is_available']}")
    
    
    print("\n" + "=" * 50)
    print("Database API client ready for integration!")
