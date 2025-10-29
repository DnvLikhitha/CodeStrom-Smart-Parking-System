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
        
        Returns:
            {
                "status": "success",
                "data": [
                    {
                        "id": 1,
                        "name": "Slot A1",
                        "location": "Building A",
                        "latitude": 28.6139,
                        "longitude": 77.2090,
                        "price_per_hour": 50.0,
                        "is_available": 1,
                        ...
                    }
                ]
            }
        """
        return self._make_request("get_parking_lots", "GET")
    
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
    
    def update_payment(self, booking_uid: str, status: str, 
                      transaction_id: str = "", amount: float = 0) -> Dict:
        """
        Update payment status for a booking
        
        Args:
            booking_uid: Unique booking ID (e.g., "ABC12345")
            status: Payment status ("Pending", "Completed", "Failed")
            transaction_id: Razorpay transaction ID (optional)
            amount: Payment amount (optional)
            
        Returns:
            {"status": "success", "message": "Payment updated"}
        """
        data = {
            "booking_uid": booking_uid,
            "status": status,
            "transaction_id": transaction_id,
            "amount": amount
        }
        return self._make_request("update_payment", "POST", data)
    
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
def transform_parking_lots_for_ai(parking_lots: List[Dict]) -> List[Dict]:
    """
    Transform database parking lots to AI model format
    
    Database format has fields like: id, name, location, latitude, longitude, price_per_hour, is_available
    AI model expects: slot_id, latitude, longitude, avg_feedback, popularity_score, is_available, price_factor, price_per_hour
    """
    transformed = []
    
    for lot in parking_lots:
        transformed_lot = {
            'slot_id': lot.get('name', f"SLOT_{lot.get('id')}"),
            'latitude': float(lot.get('latitude', 0)),
            'longitude': float(lot.get('longitude', 0)),
            'avg_feedback': 3.5,  # Default, will be updated with real feedback
            'popularity_score': 0.5,  # Default, will be calculated from booking history
            'is_available': bool(lot.get('is_available', 1)),
            'price_factor': min(float(lot.get('price_per_hour', 50)) / 100, 1.0),  # Normalize
            'price_per_hour': float(lot.get('price_per_hour', 50)),
            # Keep original data for reference
            'db_id': lot.get('id'),
            'name': lot.get('name', ''),
            'location': lot.get('location', '')
        }
        transformed.append(transformed_lot)
    
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
            print(f"   First lot: {result['data'][0].get('name')}")
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
    lots_result = api.get_parking_lots()
    if lots_result['status'] == 'success' and lots_result.get('data'):
        transformed = transform_parking_lots_for_ai(lots_result['data'])
        print(f"✅ Transformed {len(transformed)} lots for AI model")
        if transformed:
            print(f"   Example: {transformed[0]['slot_id']} at ({transformed[0]['latitude']}, {transformed[0]['longitude']})")
    
    print("\n" + "=" * 50)
    print("Database API client ready for integration!")
