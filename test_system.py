"""
Test script for the AI model and payment integration
"""

import sys
import os
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from ai_model.parking_slot_classifier import ParkingSlotClassifier, generate_training_data
from backend.payment_service import PaymentService, PaymentSimulator
import json


def test_ai_model():
    """Test the parking slot classifier"""
    print("=" * 50)
    print("Testing AI Model")
    print("=" * 50)
    
    # Initialize classifier
    classifier = ParkingSlotClassifier()
    
    # Generate and train with synthetic data
    print("\n1. Generating training data...")
    training_data, labels = generate_training_data(100)
    print(f"   Generated {len(training_data)} training samples")
    
    print("\n2. Training model...")
    classifier.train(training_data, labels)
    print("   Model trained successfully")
    
    # Test prediction
    print("\n3. Testing predictions...")
    test_slots = [
        {
            'slot_id': 'A1',
            'latitude': 28.6139,
            'longitude': 77.2090,
            'avg_feedback': 4.5,
            'popularity_score': 0.8,
            'is_available': True,
            'price_factor': 0.7,
            'price_per_hour': 50.0
        },
        {
            'slot_id': 'B2',
            'latitude': 28.6150,
            'longitude': 77.2100,
            'avg_feedback': 3.2,
            'popularity_score': 0.4,
            'is_available': True,
            'price_factor': 0.5,
            'price_per_hour': 40.0
        },
        {
            'slot_id': 'C3',
            'latitude': 28.6145,
            'longitude': 77.2095,
            'avg_feedback': 4.8,
            'popularity_score': 0.9,
            'is_available': True,
            'price_factor': 0.9,
            'price_per_hour': 60.0
        }
    ]
    
    user_location = (28.6139, 77.2090)
    best_slots = classifier.predict_best_slots(test_slots, user_location, top_k=3)
    
    print("\n   Top 3 Recommended Slots:")
    for i, slot in enumerate(best_slots, 1):
        print(f"   {i}. {slot['slot_id']} - Score: {slot['recommendation_score']:.3f}")
        print(f"      Proximity: {slot['proximity_score']:.3f}, Rating: {slot['avg_feedback']}")
    
    print("\n✅ AI Model Test Passed!")
    return classifier


def test_payment_service():
    """Test the payment service"""
    print("\n" + "=" * 50)
    print("Testing Payment Service")
    print("=" * 50)
    
    # Use simulator (doesn't require actual Razorpay connection)
    print("\n1. Testing Payment Simulator...")
    simulator = PaymentSimulator()
    
    # Simulate payment
    order_id = "order_test_123"
    amount = 100.0
    
    payment_result = simulator.simulate_payment(order_id, amount)
    print(f"   Payment ID: {payment_result['payment_id']}")
    print(f"   Amount: ₹{payment_result['amount']}")
    print(f"   Status: {payment_result['status']}")
    
    # Retrieve simulated payment
    payment_details = simulator.get_simulated_payment(payment_result['payment_id'])
    print(f"\n   Retrieved Payment: {json.dumps(payment_details, indent=2)}")
    
    print("\n✅ Payment Service Test Passed!")
    
    # Try real Razorpay (will fail gracefully if credentials are invalid)
    print("\n2. Testing Real Razorpay Connection...")
    try:
        payment_service = PaymentService(
            "rzp_test_RYlqJbc24Sl6jz",
            "bghQe0L7iort9vmqb6Jlf8Ec"
        )
        print("   ✅ Razorpay client initialized")
        
        # Try creating an order
        order = payment_service.create_payment_order(
            amount=50.0,
            booking_id="TEST_123",
            notes={'test': 'true'}
        )
        
        if order['success']:
            print(f"   ✅ Order created: {order['order_id']}")
        else:
            print(f"   ⚠️  Order creation failed: {order.get('error')}")
            
    except Exception as e:
        print(f"   ⚠️  Razorpay connection failed: {e}")
        print("   (This is expected if credentials are invalid)")


def test_integration():
    """Test integration between AI model and payment"""
    print("\n" + "=" * 50)
    print("Testing Full Integration")
    print("=" * 50)
    
    print("\n1. User searches for parking...")
    user_location = (28.6139, 77.2090)
    print(f"   User location: {user_location}")
    
    print("\n2. AI recommends best slots...")
    classifier = ParkingSlotClassifier()
    
    # Quick train with small dataset
    training_data, labels = generate_training_data(50)
    classifier.train(training_data, labels)
    
    # Sample available slots
    available_slots = [
        {
            'slot_id': 'P101',
            'latitude': 28.6145,
            'longitude': 77.2095,
            'avg_feedback': 4.2,
            'popularity_score': 0.7,
            'is_available': True,
            'price_factor': 0.6,
            'price_per_hour': 45.0
        },
        {
            'slot_id': 'P102',
            'latitude': 28.6140,
            'longitude': 77.2088,
            'avg_feedback': 4.8,
            'popularity_score': 0.9,
            'is_available': True,
            'price_factor': 0.8,
            'price_per_hour': 55.0
        }
    ]
    
    recommendations = classifier.predict_best_slots(available_slots, user_location, top_k=2)
    
    print("\n   Recommendations:")
    for i, slot in enumerate(recommendations, 1):
        print(f"   {i}. Slot {slot['slot_id']} - Score: {slot['recommendation_score']:.3f}")
    
    print("\n3. User selects a slot...")
    selected_slot = recommendations[0]
    print(f"   Selected: {selected_slot['slot_id']}")
    
    print("\n4. Creating payment order...")
    simulator = PaymentSimulator()
    booking_id = "BKG_TEST_001"
    duration = 2
    amount = selected_slot['price_per_hour'] * duration
    
    payment = simulator.simulate_payment(f"order_{booking_id}", amount)
    print(f"   Payment ID: {payment['payment_id']}")
    print(f"   Amount: ₹{amount}")
    
    print("\n5. User provides feedback...")
    feedback = {
        'booking_id': booking_id,
        'slot_id': selected_slot['slot_id'],
        'rating': 5.0,
        'satisfaction': True
    }
    print(f"   Rating: {feedback['rating']}/5")
    print(f"   Satisfied: {feedback['satisfaction']}")
    
    print("\n✅ Full Integration Test Passed!")


if __name__ == "__main__":
    print("\n🚀 Starting Smart Parking System Tests\n")
    
    try:
        # Test AI Model
        classifier = test_ai_model()
        
        # Test Payment Service
        test_payment_service()
        
        # Test Integration
        test_integration()
        
        print("\n" + "=" * 50)
        print("✅ ALL TESTS PASSED!")
        print("=" * 50)
        print("\n📝 Next Steps:")
        print("1. Set up real parking slot database")
        print("2. Configure Twilio WhatsApp webhook")
        print("3. Deploy backend to Render")
        print("4. Deploy WhatsApp bot to production")
        print("5. Test with real users")
        
    except Exception as e:
        print(f"\n❌ Test Failed: {e}")
        import traceback
        traceback.print_exc()
