"""
Interactive AI Model Demo - Test the Recommendation System
"""
from backend.database_api import DatabaseAPI, transform_parking_lots_for_ai
from ai_model.parking_slot_classifier import ParkingSlotClassifier
import json

print("=" * 70)
print("🤖 SMART PARKING AI MODEL - INTERACTIVE DEMO")
print("=" * 70)

# Initialize
api = DatabaseAPI()
classifier = ParkingSlotClassifier()

# Load or train model
try:
    classifier.load_model('parking_model.pkl')
    print("\n✅ AI Model loaded successfully")
except:
    print("\n🔄 Training new AI model...")
    from ai_model.parking_slot_classifier import generate_training_data
    training_data, labels = generate_training_data(500)
    classifier.train(training_data, labels)
    classifier.save_model('parking_model.pkl')
    print("✅ Model trained and saved")

print("\n" + "=" * 70)
print("📍 STEP 1: GET USER LOCATION")
print("=" * 70)

# Example user location (SRM University AP)
user_lat = 16.4645
user_lon = 80.5076

print(f"User Location: ({user_lat}, {user_lon})")
print(f"Location: Near SRM University AP, Guntur")

print("\n" + "=" * 70)
print("🅿️  STEP 2: FETCH AVAILABLE PARKING SLOTS FROM DATABASE")
print("=" * 70)

# Get slots from database
slots_result = api.get_all_available_slots()

if slots_result['status'] == 'success':
    raw_slots = slots_result.get('data', [])
    print(f"✅ Found {len(raw_slots)} available slot(s) in database")
    
    # Show raw data
    print(f"\n📊 Raw Database Data:")
    for slot in raw_slots:
        print(f"  - Slot {slot.get('slot_number')} at {slot.get('lot_name')}")
        print(f"    Type: {slot.get('vehicle_type')}, Rate: ₹{slot.get('hourly_rate')}/hr")
    
    print("\n" + "=" * 70)
    print("🔄 STEP 3: TRANSFORM DATA FOR AI MODEL")
    print("=" * 70)
    
    # Transform to AI format
    ai_slots = transform_parking_lots_for_ai(raw_slots)
    
    print(f"✅ Transformed {len(ai_slots)} slots for AI processing")
    print(f"\n📊 AI Model Input Features:")
    
    if ai_slots:
        sample = ai_slots[0]
        print(f"  Slot ID: {sample['slot_id']}")
        print(f"  Location: ({sample['latitude']}, {sample['longitude']})")
        print(f"  Features:")
        print(f"    - avg_feedback: {sample['avg_feedback']} (0-5 scale)")
        print(f"    - popularity_score: {sample['popularity_score']} (0-1)")
        print(f"    - is_available: {sample['is_available']}")
        print(f"    - price_factor: {sample['price_factor']} (0-1)")
        print(f"    - price_per_hour: ₹{sample['price_per_hour']}")
        print(f"    - proximity_score: Will be calculated based on user location")
    
    print("\n" + "=" * 70)
    print("🧠 STEP 4: AI MODEL PREDICTION")
    print("=" * 70)
    
    # Get AI recommendations
    user_coords = (user_lat, user_lon)
    recommendations = classifier.predict_best_slots(
        ai_slots, 
        user_coords, 
        top_k=3
    )
    
    print(f"✅ AI Model processed {len(ai_slots)} slot(s)")
    print(f"📍 Calculated proximity from user location")
    print(f"🎯 Generated {len(recommendations)} recommendation(s)")
    
    print("\n" + "=" * 70)
    print("🏆 STEP 5: TOP RECOMMENDATIONS")
    print("=" * 70)
    
    for i, slot in enumerate(recommendations, 1):
        print(f"\n#{i} Recommended Slot: {slot['slot_id']}")
        print(f"  📍 Location: {slot.get('location', 'N/A')}")
        print(f"  🚗 Vehicle Type: {slot.get('vehicle_type', 'N/A')}")
        print(f"  💰 Price: ₹{slot['price_per_hour']}/hour")
        print(f"  📊 AI Scores:")
        print(f"     - Recommendation Score: {slot['recommendation_score']:.3f} (FINAL)")
        print(f"     - Proximity Score: {slot['proximity_score']:.3f}")
        print(f"     - Feedback Score: {slot['avg_feedback']:.1f}/5.0")
        print(f"     - Popularity: {slot['popularity_score']:.2f}")
        print(f"     - Available: {'✅ Yes' if slot['is_available'] else '❌ No'}")
        
        # Calculate distance
        from ai_model.parking_slot_classifier import ParkingSlotClassifier
        temp_classifier = ParkingSlotClassifier()
        slot_coords = (slot['latitude'], slot['longitude'])
        proximity = temp_classifier.calculate_proximity_score(slot_coords, user_coords)
        
        # Estimate distance
        distance_km = (1 - proximity) * 5
        if distance_km < 0.1:
            distance_str = f"{distance_km * 1000:.0f} meters"
        else:
            distance_str = f"{distance_km:.2f} km"
        
        print(f"     - Estimated Distance: {distance_str}")
    
    print("\n" + "=" * 70)
    print("📊 HOW THE AI DECIDED")
    print("=" * 70)
    
    print(f"""
The AI considered these factors for each slot:

1. PROXIMITY (40% weight)
   - Calculated distance from your location using GPS
   - Closer slots get higher scores

2. USER FEEDBACK (30% weight)
   - Average ratings from previous users
   - Based on actual user experiences

3. POPULARITY (20% weight)
   - How often this slot is booked
   - Popular slots are often better maintained

4. AVAILABILITY (10% weight)
   - Currently available = higher priority
   - Unavailable slots get lower scores

5. PRICE (indirect factor)
   - Considered in the overall calculation
   - Balanced with other factors
    """)
    
    print("\n" + "=" * 70)
    print("🔄 MODEL LEARNING")
    print("=" * 70)
    
    print(f"""
The AI model improves over time:

1. User receives recommendations ✅
2. User books a slot ✅
3. User provides feedback (rating + satisfaction) 📝
4. System stores the feedback
5. After 10+ feedbacks → Model retrains 🔄
6. Model learns patterns of good recommendations 🧠

Current training: {500} synthetic samples
Real feedback needed: 10+ samples for retraining
    """)
    
else:
    print(f"❌ Failed to fetch slots: {slots_result.get('message')}")

print("\n" + "=" * 70)
print("✅ DEMO COMPLETE")
print("=" * 70)
print(f"""
To use the AI model in your application:

1. GET user's GPS location
2. POST to /api/recommend-slots with:
   {{
     "user_location": {{"latitude": {user_lat}, "longitude": {user_lon}}},
     "slots": [],
     "top_k": 3
   }}

3. Receive top 3 recommended slots
4. Display to user
5. Collect feedback after booking
6. Use feedback to improve model

For more details, see: AI_MODEL_GUIDE.md
""")
