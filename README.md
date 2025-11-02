# Smart Parking System - AI & Payment Module

An intelligent parking management system with AI-powered slot recommendations and integrated payment processing in sandbox mode.

## 🚀 Features

### 1. AI-Powered Slot Recommendation
- **Basic Classification Model** using Random Forest
- Considers multiple factors:
  - **Proximity** to user location (Haversine distance calculation)
  - **User feedback** scores (historical ratings)
  - **Slot popularity** (booking frequency and usage patterns)
  - **Real-time availability**
  - **Price factors**
- Smart ranking algorithm with weighted scoring
- Continuous learning from user feedback

### 2. Payment Integration (Sandbox Mode)
- **Razorpay** integration in test/sandbox mode
- Payment order creation
- Payment link generation
- Payment verification with signature validation
- Refund support
- Payment simulation for development/testing
- Complete transaction tracking

### 3. Database Integration
- **PHP API Backend** at `https://aadarshsenapati.in/api/api.php`
- Real-time parking slot data
- User registration and management
- Booking system with unique UIDs
- Payment status tracking
- Feedback storage and retrieval
- Complete CRUD operations

### 4. Feedback System
- Collect user ratings (1-5 stars)
- Track user satisfaction metrics
- Store feedback in database
- Use feedback for AI model improvement
- Automated model retraining with new data
- Feedback analytics and reporting

## 📁 Project Structure

```
CodeStrom/
├── ai_model/
│   └── parking_slot_classifier.py    # ML model for slot recommendation
├── backend/
│   ├── main.py                       # FastAPI backend API
│   ├── payment_service.py            # Razorpay payment integration
│   └── database_api.py               # Database API client
├── requirements.txt                   # Project dependencies
├── test_system.py                    # Comprehensive test suite
├── test_database.py                  # Database integration tests
├── .env.example                      # Environment variables template
├── .gitignore
├── render.yaml                       # Render deployment config
├── railway.json                      # Railway deployment config
└── vercel.json                       # Vercel deployment config
```

## 🛠️ Installation & Setup

### Prerequisites
- Python 3.11+
- pip (Python package manager)
- Razorpay account with test credentials

### Step 1: Install Dependencies
```bash
# Navigate to project directory
cd CodeStrom

# Install required packages
pip install -r requirements.txt
```

### Step 2: Configure Environment
```bash
# Create .env file from template
copy .env.example .env

# Edit .env and add your Razorpay test credentials
# RAZORPAY_KEY_ID=rzp_test_RYlqJbc24Sl6jz
# RAZORPAY_KEY_SECRET=bghQe0L7iort9vmqb6Jlf8Ec
```

### Step 3: Test Database Connection
```bash
# Test database API integration
python test_database.py
```

### Step 4: Test the Complete System
```bash
# Run comprehensive test suite
python test_system.py
```

### Step 5: Start Development Server
```bash
# Start FastAPI backend
uvicorn backend.main:app --reload --host 0.0.0.0 --port 8000

# API will be available at: http://localhost:8000
# Interactive docs at: http://localhost:8000/docs
# Database test: http://localhost:8000/api/test-database
```

## 🔑 Configuration

### Database API
- **Endpoint:** `https://aadarshsenapati.in/api/api.php`
- Managed by team member - handles all database operations
- Supports: Users, Parking Lots, Bookings, Payments, Feedback

### Razorpay Test Credentials
```env
RAZORPAY_KEY_ID=rzp_test_RYlqJbc24Sl6jz
RAZORPAY_KEY_SECRET=bghQe0L7iort9vmqb6Jlf8Ec
```

These are **sandbox/test mode** credentials. Use Razorpay test cards for testing:
- **Card Number:** 4111 1111 1111 1111
- **CVV:** Any 3 digits
- **Expiry:** Any future date
- **OTP:** 123456 (for test mode)

## 🚀 Deployment

### Option 1: Render
```yaml
# render.yaml is already configured
# Steps:
1. Create account on render.com
2. Connect GitHub repository
3. Create new Web Service
4. Render will auto-detect render.yaml
5. Add environment variables (RAZORPAY keys)
6. Deploy!
```

### Option 2: Railway
```bash
# Install Railway CLI
npm i -g @railway/cli

# Login and deploy
railway login
railway init
railway up
```

### Option 3: Vercel (Serverless)
```bash
# Install Vercel CLI
npm i -g vercel

# Deploy
vercel --prod
```

### Post-Deployment
- Test all API endpoints
- Verify payment integration
- Monitor error logs
- Set up health checks

## 📡 API Endpoints

### Database & Health
```bash
GET  /                      # Health check
GET  /api/test-database     # Test database connection
```

### AI Recommendations
```bash
POST /api/recommend-slots
{
  "user_location": {"latitude": 28.6139, "longitude": 77.2090},
  "slots": [],  # Optional - if empty, fetches from database
  "top_k": 3
}
```

### Booking Management
```bash
POST /api/payment/create-order
{
  "booking_id": "custom_id",  # Optional
  "slot_id": "1",             # Database slot ID or name
  "amount": 100.0,
  "duration_hours": 2,
  "vehicle_number": "DL01AB1234",
  "customer_name": "John Doe",
  "customer_contact": "+919876543210",
  "customer_email": "john@example.com"
}

GET /api/booking/{booking_uid}  # Get booking details
```

### Payment Operations
```bash
POST /api/payment/create-link     # Create payment link
POST /api/payment/verify          # Verify payment signature
POST /api/payment/simulate        # Simulate payment (testing)
  ?booking_uid=ABC12345&amount=100
```

### Feedback System
```bash
POST /api/feedback
{
  "booking_id": "ABC12345",  # Booking UID from database
  "slot_id": "A1",
  "rating": 4.5,
  "comment": "Great spot!",
  "user_satisfaction": true
}

GET /api/feedback/stats  # Get feedback statistics
```

### AI Model Management
```bash
POST /api/retrain-model  # Retrain model with feedback data
```

## 🤖 AI Model Details

### Features Used
1. **Proximity Score** (0-1): Distance from user to parking slot
2. **Average Feedback** (0-1): Normalized user ratings
3. **Popularity Score** (0-1): Historical booking frequency
4. **Availability** (0-1): Current availability status
5. **Price Factor** (0-1): Normalized pricing

### Algorithm
- **Random Forest Classifier** (100 estimators)
- Trained on user satisfaction data
- Predicts probability of user satisfaction
- Falls back to weighted scoring if not trained

### Continuous Improvement
- Collects feedback after each booking
- Retrains model periodically
- Updates slot rankings based on new data

## 💳 Payment Flow

1. User books a slot via WhatsApp
2. Backend creates Razorpay order
3. Payment link sent to user
4. User completes payment
5. Webhook verifies payment
6. Booking confirmed
7. Feedback collected after use

## 🧪 Testing

### Pre-Production Testing

#### 1. Run Test Suite
```bash
python test_system.py
```
This will test:
- ✅ AI model training and predictions
- ✅ Payment service (simulated and real)
- ✅ Full integration flow
- ✅ Feedback collection
- ✅ Model retraining

#### 2. API Testing with Interactive Docs
```bash
# Start server
uvicorn backend.main:app --reload --port 8000

# Open browser to:
http://localhost:8000/docs
```

Test each endpoint:
- `/api/recommend-slots` - AI recommendations
- `/api/payment/create-order` - Create payment
- `/api/payment/simulate` - Simulate payment
- `/api/feedback` - Submit feedback
- `/api/retrain-model` - Retrain AI model

#### 3. Manual Testing Scenarios

**Scenario 1: Find Best Parking Spot**
```bash
curl -X POST "http://localhost:8000/api/recommend-slots" \
  -H "Content-Type: application/json" \
  -d '{
    "user_location": {"latitude": 28.6139, "longitude": 77.2090},
    "slots": [
      {
        "slot_id": "A1",
        "latitude": 28.6145,
        "longitude": 77.2095,
        "avg_feedback": 4.5,
        "popularity_score": 0.8,
        "is_available": true,
        "price_factor": 0.7,
        "price_per_hour": 50.0
      }
    ],
    "top_k": 3
  }'
```

**Scenario 2: Create Payment Order**
```bash
curl -X POST "http://localhost:8000/api/payment/create-order" \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": "BKG001",
    "slot_id": "A1",
    "amount": 100.0,
    "duration_hours": 2,
    "vehicle_number": "DL01AB1234",
    "customer_name": "Test User",
    "customer_contact": "+919876543210",
    "customer_email": "test@example.com"
  }'
```

**Scenario 3: Simulate Payment**
```bash
curl -X POST "http://localhost:8000/api/payment/simulate?booking_id=BKG001&amount=100"
```

**Scenario 4: Submit Feedback**
```bash
curl -X POST "http://localhost:8000/api/feedback" \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": "BKG001",
    "slot_id": "A1",
    "rating": 5.0,
    "comment": "Great spot!",
    "user_satisfaction": true
  }'
```

### Post-Production Testing

#### 1. Health Check
```bash
curl http://your-deployed-url.com/
```

#### 2. Load Testing
```bash
# Install hey (load testing tool)
# Run load test
hey -n 1000 -c 10 http://your-deployed-url.com/api/recommend-slots
```

#### 3. Monitor Performance
- Response times < 500ms
- Error rate < 1%
- Model accuracy > 80%
- Payment success rate > 98%

#### 4. Test Payment Integration
- Test with Razorpay test cards
- Verify payment webhooks
- Check payment verification
- Test refund functionality

## 📊 Next Steps

### Phase 1: Core Implementation ✅
- [x] AI classification model with feedback learning
- [x] Razorpay payment integration (sandbox)
- [x] FastAPI backend with all endpoints
- [x] Comprehensive testing suite
- [x] Deployment configurations

### Phase 2: Testing & Validation (Current)
- [ ] Run local test suite (`python test_system.py`)
- [ ] Test all API endpoints with Postman/curl
- [ ] Verify payment flows with test credentials
- [ ] Load testing and performance optimization
- [ ] Security testing and validation

### Phase 3: Production Deployment
- [ ] Deploy backend to Render/Railway
- [ ] Configure production environment variables
- [ ] Set up monitoring and logging
- [ ] Configure auto-scaling
- [ ] Set up backup and disaster recovery

### Phase 4: Integration & Enhancement
- [ ] Connect to real parking database
- [ ] Integrate with external systems (to be handled by other teams)
- [ ] Add real-time slot updates
- [ ] Implement caching for performance
- [ ] Add analytics dashboard

### Phase 5: Continuous Improvement
- [ ] Collect production feedback data
- [ ] Retrain AI model with real data
- [ ] A/B test different recommendation algorithms
- [ ] Optimize model performance
- [ ] Add advanced features based on usage patterns

## 🤝 Integration Points

This module provides REST APIs that can be integrated with:
- **Frontend applications** (Web/Mobile)
- **WhatsApp/Telegram bots** (handled by other teams)
- **Admin dashboards**
- **IoT parking sensors**
- **Third-party booking systems**

## 📄 License

MIT License

## 📧 Support

For issues and questions, please create an issue in the repository.

---

**AI + Payment Module** | Smart Parking System v1.0
