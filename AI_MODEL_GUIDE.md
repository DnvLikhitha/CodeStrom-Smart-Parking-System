# 🤖 Smart Parking AI Model - Complete Guide

## 📊 How the AI Model Works

The Smart Parking AI uses a **RandomForestClassifier** (Machine Learning model) to recommend the best parking slots to users based on multiple factors.

---

## 🎯 Model Purpose

**Goal:** Recommend the top 3 best parking slots for a user based on:
1. ✅ **Proximity** - How close to the user's location
2. ✅ **User Feedback** - Average ratings from previous users
3. ✅ **Popularity** - How often the slot is booked
4. ✅ **Availability** - Whether the slot is currently available
5. ✅ **Price** - Cost-effectiveness

---

## 📥 Required Inputs

### **1. User Location (Required)**
```json
{
  "user_location": {
    "latitude": 16.4645,
    "longitude": 80.5076
  }
}
```

### **2. Parking Slots Data**

The model needs data about each parking slot with these fields:

```json
{
  "slot_id": "A1-1",              // Unique identifier
  "latitude": 16.4645659,         // Slot GPS coordinates
  "longitude": 80.5076208,        
  "avg_feedback": 3.5,            // Average rating (0-5)
  "popularity_score": 0.5,        // Normalized booking frequency (0-1)
  "is_available": true,           // Current availability
  "price_factor": 0.6,            // Normalized price (0-1)
  "price_per_hour": 60.0          // Actual price
}
```

### **3. Top K (Optional)**
```json
{
  "top_k": 3  // Number of recommendations to return (default: 3)
}
```

---

## 🔄 How It Works (Step-by-Step)

### **Step 1: Calculate Proximity Score**
Uses **Haversine Formula** to calculate distance between user and each slot:

```python
distance = haversine(user_location, slot_location)
proximity_score = max(0, 1 - (distance / 5.0))
```

- **Score Range:** 0 to 1
- **1 = Very close** (0 km away)
- **0 = Far away** (5+ km away)

### **Step 2: Feature Extraction**
Creates a feature vector for each slot:

```
Feature Vector = [
  proximity_score,           // 0-1
  avg_feedback / 5.0,       // Normalized 0-1
  popularity_score,         // 0-1
  is_available ? 1 : 0,     // Boolean 0 or 1
  price_factor              // 0-1
]
```

### **Step 3: Model Prediction**
- If **model is trained**: Uses RandomForest ML predictions
- If **model is NOT trained**: Uses weighted scoring fallback

**Weighted Scoring Formula:**
```
score = proximity × 0.4 +       // 40% weight
        feedback × 0.3 +         // 30% weight
        popularity × 0.2 +       // 20% weight
        availability × 0.1       // 10% weight
```

### **Step 4: Ranking & Selection**
- Sorts all slots by score (highest first)
- Returns top K slots

---

## 📊 Model Features (5 Total)

| Feature | Description | Weight | Range |
|---------|-------------|--------|-------|
| **Proximity** | Distance from user | 40% | 0-1 |
| **Feedback** | Average user rating | 30% | 0-1 (from 0-5 stars) |
| **Popularity** | Booking frequency | 20% | 0-1 |
| **Availability** | Currently available | 10% | 0 or 1 |
| **Price** | Cost factor | 0%* | 0-1 |

*Price is currently a feature but not weighted in fallback mode

---

## 🔧 API Usage

### **Complete Request Example:**

```bash
POST http://127.0.0.1:8000/api/recommend-slots
Content-Type: application/json

{
  "user_location": {
    "latitude": 16.4645,
    "longitude": 80.5076
  },
  "slots": [],  // Empty = fetch from database automatically
  "top_k": 3
}
```

### **Response Example:**

```json
{
  "success": true,
  "recommendations": [
    {
      "slot_id": "A1-1",
      "latitude": 16.4645659,
      "longitude": 80.5076208,
      "proximity_score": 0.99,
      "avg_feedback": 4.5,
      "popularity_score": 0.8,
      "is_available": true,
      "price_per_hour": 60.0,
      "recommendation_score": 0.87,  // ← AI score
      "location": "SRM University AP",
      "vehicle_type": "4-wheeler"
    },
    {
      "slot_id": "A1-2",
      "recommendation_score": 0.75,
      ...
    },
    {
      "slot_id": "B2-1",
      "recommendation_score": 0.68,
      ...
    }
  ],
  "count": 3,
  "total_available": 15
}
```

---

## 🎓 Model Training

### **Initial Training (Synthetic Data)**
On first startup, the model trains itself with 500 synthetic samples:

```python
# Automatically runs on startup
training_data, labels = generate_training_data(500)
classifier.train(training_data, labels)
classifier.save_model('parking_model.pkl')
```

### **Continuous Learning (User Feedback)**
The model improves over time using real user feedback:

```python
# After collecting 10+ feedback entries
POST /api/retrain-model
```

**Feedback data includes:**
- Which slot was recommended
- Which slot user actually chose
- User satisfaction (yes/no)
- Rating (1-5 stars)

---

## 📈 How the Model Learns

### **Training Data Format:**
```python
{
  'slot_id': 'A1',
  'proximity_score': 0.95,
  'avg_feedback': 4.5,
  'popularity_score': 0.8,
  'is_available': True,
  'price_factor': 0.7
}

# Label: 1 if user was satisfied, 0 if not
```

### **Learning Process:**
1. User gets recommendations
2. User books a slot
3. User provides feedback
4. System stores: (features, satisfaction)
5. After 10+ feedbacks → retrain model
6. Model learns patterns of successful recommendations

---

## 💡 Example Scenarios

### **Scenario 1: User Very Close to Lot A**
```
User Location: (16.4645, 80.5076)
Slot A1: (16.4645, 80.5076) - Distance: 0 km
Slot B1: (16.4700, 80.5100) - Distance: 0.8 km

Result: Slot A1 gets higher score (proximity_score = 1.0)
```

### **Scenario 2: User Far But Slot Has Great Reviews**
```
Slot A1: Distance 2km, Rating 3.0 → Score ~0.55
Slot B1: Distance 3km, Rating 5.0 → Score ~0.62

Result: Slot B1 recommended (better reviews compensate for distance)
```

### **Scenario 3: Slot Unavailable**
```
Slot A1: All factors great BUT is_available = False
Slot B1: Slightly worse BUT is_available = True

Result: Slot B1 recommended (availability is crucial)
```

---

## 🔍 Current Data Flow

```
User Request
    ↓
FastAPI (/api/recommend-slots)
    ↓
Get Available Slots from Database
    ↓
Transform to AI Format
    ↓
Calculate Proximity for Each Slot
    ↓
AI Model Prediction
    ↓
Sort by Score
    ↓
Return Top 3 Slots
```

---

## 📊 Data Sources

### **From Database API:**
- Slot ID, Location (lat/long)
- Vehicle type
- Hourly rate → converted to price_factor
- Current availability

### **Generated/Default Values:**
- `avg_feedback`: Default 3.5 (updated with real feedback)
- `popularity_score`: Default 0.5 (updated from booking history)
- `proximity_score`: Calculated on-the-fly

### **From User Feedback:**
- Actual ratings (1-5 stars)
- User satisfaction
- Comments

---

## 🚀 Automatic vs Manual Slot Data

### **Option 1: Automatic (Recommended)**
```json
{
  "user_location": {"latitude": 16.4645, "longitude": 80.5076},
  "slots": [],  // Empty array
  "top_k": 3
}
```
- ✅ Automatically fetches from database
- ✅ Always up-to-date
- ✅ Includes availability status

### **Option 2: Manual (Testing)**
```json
{
  "user_location": {"latitude": 16.4645, "longitude": 80.5076},
  "slots": [
    {
      "slot_id": "TEST1",
      "latitude": 16.4645,
      "longitude": 80.5076,
      "avg_feedback": 4.0,
      "popularity_score": 0.6,
      "is_available": true,
      "price_factor": 0.5,
      "price_per_hour": 50.0
    }
  ],
  "top_k": 3
}
```
- ⚠️ For testing only
- ✅ Custom slot data

---

## 🎯 Summary

### **Required Inputs:**
1. ✅ User's GPS location (latitude, longitude)
2. ✅ Parking slots data (auto-fetched from database)

### **Model Outputs:**
- Top 3 recommended slots
- Each with recommendation score (0-1)
- Sorted by best match first

### **Key Features:**
- **Smart:** Considers distance, ratings, popularity, availability
- **Self-improving:** Learns from user feedback
- **Fast:** Returns results in milliseconds
- **Flexible:** Works with or without training data

### **How to Use:**
```bash
# Simple request - just send user location
curl -X POST http://localhost:8000/api/recommend-slots \
  -H "Content-Type: application/json" \
  -d '{
    "user_location": {"latitude": 16.4645, "longitude": 80.5076},
    "slots": [],
    "top_k": 3
  }'
```

**The AI does the rest!** 🚀
