"""
Razorpay Payment Integration Service
Handles payment processing in sandbox mode
"""

import razorpay
from typing import Dict, Optional
import json
import os
from datetime import datetime


class PaymentService:
    """
    Payment service using Razorpay in test mode
    """
    
    def __init__(self, key_id: str, key_secret: str):
        """
        Initialize Razorpay client with test credentials
        """
        self.client = razorpay.Client(auth=(key_id, key_secret))
        self.client.set_app_details({
            "title": "Smart Parking System",
            "version": "1.0.0"
        })
        
    def create_payment_order(self, amount: float, currency: str = "INR",
                           booking_id: str = None, 
                           notes: Dict = None) -> Dict:
        """
        Create a payment order for parking booking
        
        Args:
            amount: Amount in rupees (will be converted to paise)
            currency: Currency code (default: INR)
            booking_id: Reference booking ID
            notes: Additional notes/metadata
            
        Returns:
            Order details including order_id
        """
        try:
            # Convert amount to paise (smallest currency unit)
            amount_paise = int(amount * 100)
            
            # Prepare order data
            order_data = {
                'amount': amount_paise,
                'currency': currency,
                'receipt': f'booking_{booking_id}_{datetime.now().strftime("%Y%m%d%H%M%S")}',
                'payment_capture': 1  # Auto capture payment
            }
            
            # Add notes if provided
            if notes:
                order_data['notes'] = notes
            
            # Create order
            order = self.client.order.create(data=order_data)
            
            return {
                'success': True,
                'order_id': order['id'],
                'amount': amount,
                'currency': order['currency'],
                'receipt': order['receipt'],
                'status': order['status'],
                'created_at': order['created_at']
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }
    
    def verify_payment_signature(self, order_id: str, payment_id: str, 
                                signature: str, key_secret: str) -> bool:
        """
        Verify the payment signature for security
        
        Args:
            order_id: Razorpay order ID
            payment_id: Razorpay payment ID
            signature: Signature to verify
            key_secret: Your Razorpay key secret
            
        Returns:
            True if signature is valid, False otherwise
        """
        try:
            params_dict = {
                'razorpay_order_id': order_id,
                'razorpay_payment_id': payment_id,
                'razorpay_signature': signature
            }
            
            # Verify signature
            self.client.utility.verify_payment_signature(params_dict)
            return True
            
        except razorpay.errors.SignatureVerificationError:
            return False
        except Exception as e:
            print(f"Signature verification error: {e}")
            return False
    
    def fetch_payment_details(self, payment_id: str) -> Optional[Dict]:
        """
        Fetch payment details by payment ID
        """
        try:
            payment = self.client.payment.fetch(payment_id)
            return {
                'payment_id': payment['id'],
                'amount': payment['amount'] / 100,  # Convert back to rupees
                'currency': payment['currency'],
                'status': payment['status'],
                'method': payment.get('method', 'unknown'),
                'email': payment.get('email', ''),
                'contact': payment.get('contact', ''),
                'created_at': payment['created_at']
            }
        except Exception as e:
            print(f"Error fetching payment: {e}")
            return None
    
    def refund_payment(self, payment_id: str, amount: Optional[float] = None,
                      notes: Dict = None) -> Dict:
        """
        Initiate a refund for a payment
        
        Args:
            payment_id: Razorpay payment ID
            amount: Refund amount (if partial, otherwise full refund)
            notes: Additional notes
            
        Returns:
            Refund details
        """
        try:
            refund_data = {}
            
            if amount:
                refund_data['amount'] = int(amount * 100)  # Convert to paise
            
            if notes:
                refund_data['notes'] = notes
            
            refund = self.client.payment.refund(payment_id, refund_data)
            
            return {
                'success': True,
                'refund_id': refund['id'],
                'payment_id': refund['payment_id'],
                'amount': refund['amount'] / 100,
                'status': refund['status'],
                'created_at': refund['created_at']
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }
    
    def create_payment_link(self, amount: float, description: str,
                          customer_name: str, customer_contact: str,
                          customer_email: str, booking_id: str) -> Dict:
        """
        Create a payment link for WhatsApp sharing
        
        Args:
            amount: Amount in rupees
            description: Payment description
            customer_name: Customer name
            customer_contact: Customer phone number
            customer_email: Customer email
            booking_id: Reference booking ID
            
        Returns:
            Payment link details
        """
        try:
            link_data = {
                'amount': int(amount * 100),
                'currency': 'INR',
                'description': description,
                'customer': {
                    'name': customer_name,
                    'contact': customer_contact,
                    'email': customer_email
                },
                'notify': {
                    'sms': True,
                    'email': True
                },
                'reminder_enable': True,
                'callback_url': f'https://your-domain.com/payment/callback/{booking_id}',
                'callback_method': 'get'
            }
            
            payment_link = self.client.payment_link.create(data=link_data)
            
            return {
                'success': True,
                'link_id': payment_link['id'],
                'short_url': payment_link['short_url'],
                'amount': amount,
                'status': payment_link['status'],
                'created_at': payment_link['created_at']
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }
    
    def capture_payment(self, payment_id: str, amount: float) -> Dict:
        """
        Manually capture a payment (if auto-capture is disabled)
        """
        try:
            amount_paise = int(amount * 100)
            payment = self.client.payment.capture(payment_id, amount_paise)
            
            return {
                'success': True,
                'payment_id': payment['id'],
                'amount': payment['amount'] / 100,
                'status': payment['status']
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }


class PaymentSimulator:
    """
    Simulator for testing payments without actual transactions
    Useful for development and testing
    """
    
    def __init__(self):
        self.simulated_payments = {}
        self.payment_counter = 1000
        
    def simulate_payment(self, order_id: str, amount: float, 
                        status: str = "captured") -> Dict:
        """
        Simulate a payment transaction
        """
        payment_id = f"pay_sim_{self.payment_counter}"
        self.payment_counter += 1
        
        payment_data = {
            'payment_id': payment_id,
            'order_id': order_id,
            'amount': amount,
            'status': status,
            'method': 'simulated',
            'timestamp': datetime.now().isoformat()
        }
        
        self.simulated_payments[payment_id] = payment_data
        
        return {
            'success': True,
            'payment_id': payment_id,
            'amount': amount,
            'status': status,
            'message': 'Payment simulated successfully'
        }
    
    def get_simulated_payment(self, payment_id: str) -> Optional[Dict]:
        """
        Retrieve simulated payment details
        """
        return self.simulated_payments.get(payment_id)


# Example usage
if __name__ == "__main__":
    # Test credentials from CSV
    KEY_ID = "rzp_test_RYlqJbc24Sl6jz"
    KEY_SECRET = "bghQe0L7iort9vmqb6Jlf8Ec"
    
    # Initialize payment service
    payment_service = PaymentService(KEY_ID, KEY_SECRET)
    
    # Example 1: Create payment order
    print("Creating payment order...")
    order = payment_service.create_payment_order(
        amount=50.00,
        booking_id="BKG123",
        notes={
            'slot_id': 'A1',
            'duration': '2 hours',
            'vehicle_number': 'DL01AB1234'
        }
    )
    print(json.dumps(order, indent=2))
    
    # Example 2: Create payment link (for WhatsApp)
    print("\nCreating payment link...")
    payment_link = payment_service.create_payment_link(
        amount=50.00,
        description="Parking Slot A1 - 2 hours",
        customer_name="John Doe",
        customer_contact="+919876543210",
        customer_email="john@example.com",
        booking_id="BKG123"
    )
    print(json.dumps(payment_link, indent=2))
    
    # Example 3: Simulate payment (for testing)
    print("\nSimulating payment...")
    simulator = PaymentSimulator()
    simulated = simulator.simulate_payment(
        order_id=order.get('order_id', 'test_order'),
        amount=50.00
    )
    print(json.dumps(simulated, indent=2))
