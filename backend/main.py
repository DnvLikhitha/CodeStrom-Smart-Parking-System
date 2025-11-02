"""
FastAPI Backend for Smart Parking System - AI & Payment Module
Serves AI model predictions and handles payment integration
Note: WhatsApp/External integrations handled by other teams
"""

from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import List, Optional, Dict, Tuple
import sys
import os
from datetime import datetime
import json
from dotenv import load_dotenv
load_dotenv()
# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from ai_model.parking_slot_classifier import ParkingSlotClassifier
from backend.payment_service import PaymentService, PaymentSimulator
from backend.database_api import DatabaseAPI, transform_parking_lots_for_ai

app = FastAPI(title="Smart Parking API", version="1.0.0")

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize services
classifier = ParkingSlotClassifier()
payment_service = None
payment_simulator = PaymentSimulator()
db_api = DatabaseAPI()

# Razorpay credentials (from environment or config)
RAZORPAY_KEY_ID = os.getenv("RAZORPAY_KEY_ID")
RAZORPAY_KEY_SECRET = os.getenv("RAZORPAY_KEY_SECRET")

# In-memory feedback cache (feedback is stored in DB, this is just for quick access)
feedback_cache = []


# Pydantic models
class ParkingSlot(BaseModel):
    slot_id: str
    latitude: float
    longitude: float
    avg_feedback: float = 3.0
    popularity_score: float = 0.5
    is_available: bool = True
    price_factor: float = 0.5
    price_per_hour: float = 50.0


class UserLocation(BaseModel):
    latitude: float
    longitude: float


class SlotRecommendationRequest(BaseModel):
    user_location: UserLocation
    slots: List[ParkingSlot]
    top_k: int = 3
    keyword: Optional[str] = None
    is_special: Optional[str] = None


class FeedbackRequest(BaseModel):
    booking_id: str
    slot_id: str
    rating: float  # 1-5
    comment: Optional[str] = None
    user_satisfaction: bool


class PaymentOrderRequest(BaseModel):
    booking_id: str
    slot_id: str
    amount: float
    duration_hours: int
    vehicle_number: str
    customer_name: str
    customer_contact: str
    customer_email: str


class PaymentVerificationRequest(BaseModel):
    order_id: str
    payment_id: str
    signature: str


# API Endpoints

@app.on_event("startup")
async def startup_event():
    """Initialize services on startup"""
    global payment_service
    
    # Load or train the AI model
    try:
        classifier.load_model('parking_model.pkl')
        print("Model loaded successfully")
    except:
        print("Training new model...")
        from ai_model.parking_slot_classifier import generate_training_data
        training_data, labels = generate_training_data(500)
        classifier.train(training_data, labels)
        classifier.save_model('parking_model.pkl')
    
    # Initialize payment service
    try:
        payment_service = PaymentService(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET)
        print("Payment service initialized")
    except Exception as e:
        print(f"Payment service initialization failed: {e}")


@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "status": "ok",
        "message": "Smart Parking API - AI + Payment Module",
        "version": "1.0.0",
        "database": "Connected to https://aadarshsenapati.in/api/api.php"
    }


@app.get("/api/test-database")
async def test_database():
    """Test database API connection"""
    try:
        result = db_api.get_parking_lots()
        return {
            "success": result['status'] == 'success',
            "message": "Database connection test",
            "parking_lots_count": len(result.get('data', [])) if result['status'] == 'success' else 0,
            "sample_data": result.get('data', [])[:2] if result['status'] == 'success' else None
        }
    except Exception as e:
        return {
            "success": False,
            "error": str(e)
        }


@app.post("/api/recommend-slots")
async def recommend_slots(request: SlotRecommendationRequest):
    """
    Get recommended parking slots based on AI model
    Fetches real parking data from database
    """
    try:
        # Option 1: Use slots provided in request (for testing/custom data)
        if request.slots:
            slots_data = [slot.dict() for slot in request.slots]
        else:
            # Option 2: Fetch available slots from database
            db_result = db_api.get_all_available_slots(
                is_special=request.is_special,
                keyword=request.keyword
            )

            if db_result['status'] != 'success':
                raise HTTPException(status_code=500, detail="Failed to fetch parking slots from database")
            
            # Transform database format to AI model format
            slots_data = transform_parking_lots_for_ai(db_result.get('data', []))
            
            if not slots_data:
                return {
                    "success": False,
                    "message": "No parking slots available",
                    "recommendations": []
                }
        
        user_coords = (request.user_location.latitude, request.user_location.longitude)
        
        # Get recommendations from AI model
        recommended_slots = classifier.predict_best_slots(
            slots_data, 
            user_coords, 
            top_k=request.top_k
        )
        
        return {
            "success": True,
            "recommendations": recommended_slots,
            "count": len(recommended_slots),
            "total_available": len(slots_data)
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/feedback")
async def submit_feedback(feedback: FeedbackRequest):
    """
    Submit user feedback on slot assignment
    This data is used to retrain and improve the model
    Stores in database via API
    """
    try:
        # First get the booking details to get booking ID (not UID)
        booking_result = db_api.get_booking_status(feedback.booking_id)
        
        if booking_result['status'] != 'success' or not booking_result.get('data'):
            # Cache feedback locally if booking not found
            feedback_entry = {
                "booking_uid": feedback.booking_id,
                "slot_id": feedback.slot_id,
                "rating": feedback.rating,
                "comment": feedback.comment,
                "user_satisfaction": feedback.user_satisfaction,
                "timestamp": datetime.now().isoformat()
            }
            feedback_cache.append(feedback_entry)
            
            return {
                "success": True,
                "message": "Feedback cached (booking not found in DB)",
                "cached": True
            }
        
        booking_data = booking_result['data']
        
        # Add feedback to database
        db_result = db_api.add_feedback(
            user_id=booking_data.get('user_id', 1),
            booking_id=booking_data.get('id'),
            rating=feedback.rating,
            comments=feedback.comment or ""
        )
        
        # Also cache locally for model retraining
        feedback_entry = {
            "booking_id": feedback.booking_id,
            "slot_id": feedback.slot_id,
            "rating": feedback.rating,
            "comment": feedback.comment,
            "user_satisfaction": feedback.user_satisfaction,
            "timestamp": datetime.now().isoformat()
        }
        feedback_cache.append(feedback_entry)
        
        return {
            "success": db_result['status'] == 'success',
            "message": db_result.get('message', 'Feedback submitted successfully'),
            "database": db_result['status'] == 'success'
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/api/feedback/stats")
async def get_feedback_stats():
    """
    Get feedback statistics from cache
    """
    if not feedback_cache:
        return {
            "total_feedback": 0,
            "average_rating": 0,
            "satisfaction_rate": 0
        }
    
    total = len(feedback_cache)
    avg_rating = sum(f['rating'] for f in feedback_cache) / total
    satisfaction_rate = sum(1 for f in feedback_cache if f['user_satisfaction']) / total * 100
    
    return {
        "total_feedback": total,
        "average_rating": round(avg_rating, 2),
        "satisfaction_rate": round(satisfaction_rate, 2)
    }


@app.post("/api/payment/create-order")
async def create_payment_order(request: PaymentOrderRequest):
    """
    Create a payment order for parking booking
    Integrates with database API for booking creation
    """
    try:
        # Calculate start and end times
        from datetime import timedelta
        start_time = datetime.now()
        end_time = start_time + timedelta(hours=request.duration_hours)
        
        start_time_str = start_time.strftime("%Y-%m-%d %H:%M:%S")
        end_time_str = end_time.strftime("%Y-%m-%d %H:%M:%S")
        
        # First, ensure user is registered (or register them)
        user_result = db_api.register_user(
            name=request.customer_name,
            phone=request.customer_contact,
            email=request.customer_email
        )
        
        # Get slot ID from database (assuming slot_id in request is the slot name/identifier)
        # For now, we'll use a placeholder user_id=1 if registration fails
        user_id = 1  # This should be retrieved from user_result in production
        
        # Parse slot_id to get database ID if needed
        # If slot_id is like "A1", we need to find its DB ID
        # For now, assume slot_id is already the DB ID or can be used directly
        slot_db_id = request.slot_id
        
        # Try to parse if it's a number string
        try:
            slot_db_id = int(request.slot_id)
        except:
            # If slot_id is name like "A1", we'd need to fetch from DB
            # For now, use a default
            slot_db_id = 1
        
        # Create booking in database
        booking_result = db_api.book_slot(
            user_id=user_id,
            slot_id=slot_db_id,
            start_time=start_time_str,
            end_time=end_time_str,
            total_amount=request.amount
        )
        
        if booking_result['status'] != 'success':
            raise HTTPException(status_code=500, detail=f"Booking failed: {booking_result.get('message')}")
        
        booking_uid = booking_result.get('booking_uid')
        
        # Use simulator if payment service is not available
        use_simulator = payment_service is None
        
        if use_simulator:
            # Simulate payment
            order = {
                'success': True,
                'order_id': f'order_sim_{booking_uid}',
                'amount': request.amount,
                'currency': 'INR',
                'status': 'created',
                'mode': 'simulation'
            }
        else:
            # Real payment
            order = payment_service.create_payment_order(
                amount=request.amount,
                booking_id=booking_uid,
                notes={
                    'booking_uid': booking_uid,
                    'slot_id': request.slot_id,
                    'duration': f'{request.duration_hours} hours',
                    'vehicle_number': request.vehicle_number
                }
            )
        
        return {
            **order,
            'booking_uid': booking_uid,
            'start_time': start_time_str,
            'end_time': end_time_str
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/payment/create-link")
async def create_payment_link(request: PaymentOrderRequest):
    """
    Create a payment link for WhatsApp sharing
    """
    try:
        if payment_service is None:
            # Return simulated link
            return {
                'success': True,
                'short_url': f'https://rzp.io/l/sim_{request.booking_id}',
                'mode': 'simulation',
                'message': 'This is a simulated payment link'
            }
        
        link = payment_service.create_payment_link(
            amount=request.amount,
            description=f"Parking Slot {request.slot_id} - {request.duration_hours} hours",
            customer_name=request.customer_name,
            customer_contact=request.customer_contact,
            customer_email=request.customer_email,
            booking_id=request.booking_id
        )
        
        return link
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/payment/verify")
async def verify_payment(request: PaymentVerificationRequest):
    """
    Verify payment signature after payment completion
    Updates payment status in database
    """
    try:
        if payment_service is None:
            # Simulate verification
            # Update database payment status
            update_result = db_api.update_payment(
                booking_uid=request.order_id.replace('order_sim_', ''),
                payment_status='Paid',
                transaction_id=request.payment_id,
                amount=0
            )
            
            return {
                'success': True,
                'verified': True,
                'mode': 'simulation',
                'database_updated': update_result['status'] == 'success'
            }
        
        is_valid = payment_service.verify_payment_signature(
            request.order_id,
            request.payment_id,
            request.signature,
            RAZORPAY_KEY_SECRET
        )
        
        if is_valid:
            # Extract booking_uid from order_id if needed
            # Update database
            # Note: We'd need to store order_id -> booking_uid mapping
            pass
        
        return {
            'success': True,
            'verified': is_valid
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/payment/simulate")
async def simulate_payment(booking_uid: str, amount: float):
    """
    Simulate a payment (for testing purposes)
    Updates database with simulated payment
    """
    try:
        # Check if booking exists in database
        booking_result = db_api.get_booking_status(booking_uid)
        
        if booking_result['status'] != 'success':
            raise HTTPException(status_code=404, detail="Booking not found")
        
        # Simulate payment
        order_id = f"order_{booking_uid}"
        simulated = payment_simulator.simulate_payment(order_id, amount)
        
        # Update booking payment status in database
        update_result = db_api.update_payment(
            booking_uid=booking_uid,
            payment_status='Paid',
            transaction_id=simulated['payment_id'],
            amount=amount
        )
        
        return {
            **simulated,
            'database_updated': update_result['status'] == 'success'
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/api/booking/{booking_uid}")
async def get_booking(booking_uid: str):
    """
    Get booking details from database
    """
    try:
        result = db_api.get_booking_status(booking_uid)
        
        if result['status'] != 'success' or not result.get('data'):
            raise HTTPException(status_code=404, detail="Booking not found")
        
        return {
            "success": True,
            "booking": result['data']
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/retrain-model")
async def retrain_model():
    """
    Retrain the AI model with new feedback data from cache
    Should be called periodically or when sufficient new feedback is collected
    """
    try:
        if len(feedback_cache) < 10:
            return {
                "success": False,
                "message": f"Insufficient feedback data for retraining (need 10+, have {len(feedback_cache)})"
            }
        
        # Convert feedback to training data
        # This is simplified - in production, you'd have more sophisticated feature engineering
        training_samples = []
        labels = []
        
        for feedback in feedback_cache:
            # Create a simplified training sample
            sample = {
                'slot_id': feedback['slot_id'],
                'avg_feedback': feedback['rating'],
                'proximity_score': 0.5,  # Would come from actual data
                'popularity_score': 0.5,
                'is_available': True,
                'price_factor': 0.5
            }
            training_samples.append(sample)
            labels.append(1 if feedback['user_satisfaction'] else 0)
        
        # Retrain model
        classifier.train(training_samples, labels)
        classifier.save_model('parking_model.pkl')
        
        return {
            "success": True,
            "message": f"Model retrained with {len(training_samples)} samples",
            "samples_used": len(training_samples)
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
