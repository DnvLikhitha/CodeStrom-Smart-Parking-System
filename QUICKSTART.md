# 🚀 Quick Start Guide - Smart Parking AI + Payment Module

## ✨ What You Have Now

A **complete AI-powered parking management system** with:
- ✅ AI slot recommendations (proximity, feedback, popularity)
- ✅ Razorpay payment integration (sandbox mode)
- ✅ Real database integration (PHP API)
- ✅ Feedback collection and AI retraining
- ✅ Ready for deployment

**No WhatsApp/Twilio integration** - That's handled by other teams!

---

## 📋 Prerequisites

- Python 3.11+
- pip package manager
- Internet connection (for database API)

---

## ⚡ Quick Setup (5 Minutes)

### 1. Install Dependencies
```powershell
# Navigate to project folder
cd C:\Users\chdnv\Desktop\CodeStrom

# Install packages
pip install -r requirements.txt
```

### 2. Configure Environment (Optional)
```powershell
# Copy environment template
copy .env.example .env

# Edit .env if needed (Razorpay keys already set)
```

### 3. Test Database Connection
```powershell
# Test integration with your team's database
python test_database.py
```

Expected output:
```
✅ Found X parking lots
✅ User registered
✅ Booking created: ABC12345
✅ Payment updated
✅ Feedback submitted
```

### 4. Test Complete System
```powershell
# Run full test suite
python test_system.py
```

### 5. Start API Server
```powershell
# Start development server
uvicorn backend.main:app --reload --port 8000
```

Server will start at: `http://localhost:8000`
- **API Docs:** http://localhost:8000/docs
- **Health Check:** http://localhost:8000/
- **Database Test:** http://localhost:8000/api/test-database

---

## 🧪 Test the APIs

### Using Browser (Interactive Docs)
1. Open http://localhost:8000/docs
2. Try each endpoint with the "Try it out" button
3. See real-time responses

### Using PowerShell (curl)

**Test 1: Get Recommendations**
```powershell
$body = @{
    user_location = @{
        latitude = 28.6139
        longitude = 77.2090
    }
    slots = @()
    top_k = 3
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/recommend-slots" -Method POST -Body $body -ContentType "application/json"
```

**Test 2: Create Booking**
```powershell
$body = @{
    booking_id = "TEST001"
    slot_id = "1"
    amount = 100.0
    duration_hours = 2
    vehicle_number = "DL01AB1234"
    customer_name = "Test User"
    customer_contact = "+919876543210"
    customer_email = "test@example.com"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/payment/create-order" -Method POST -Body $body -ContentType "application/json"
```

**Test 3: Simulate Payment**
```powershell
# Replace ABC12345 with actual booking_uid from step 2
Invoke-RestMethod -Uri "http://localhost:8000/api/payment/simulate?booking_uid=ABC12345&amount=100" -Method POST
```

**Test 4: Submit Feedback**
```powershell
$body = @{
    booking_id = "ABC12345"
    slot_id = "A1"
    rating = 5.0
    comment = "Excellent spot!"
    user_satisfaction = $true
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8000/api/feedback" -Method POST -Body $body -ContentType "application/json"
```

---

## 📊 System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    External Clients                      │
│         (Web App, Mobile App, Other Services)            │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│              FastAPI Backend (Your Module)               │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │  AI Model    │  │  Payment     │  │  Database    │ │
│  │  (Sklearn)   │  │  (Razorpay)  │  │  API Client  │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└────────────┬────────────────────────────────┬──────────┘
             │                                 │
             │                                 │
        (Sandbox)                              │
             │                                 │
      ┌──────▼──────┐                  ┌──────▼────────┐
      │  Razorpay   │                  │  PHP Backend  │
      │  Test API   │                  │  (Team DB)    │
      └─────────────┘                  └───────┬───────┘
                                               │
                                        ┌──────▼────────┐
                                        │   MySQL DB    │
                                        │   (Remote)    │
                                        └───────────────┘
```

---

## 🎯 Key Features Explained

### 1. AI Slot Recommendation
```
User Location → Fetch Available Slots → AI Analysis → Ranked Results
                                             ↓
                            Considers: Proximity, Ratings,
                            Popularity, Price, Availability
```

**How it works:**
- Fetches real parking slots from database
- Calculates distance using Haversine formula
- Ranks based on multiple weighted factors
- Returns top 3 best matches

### 2. Payment Processing
```
Create Order → Generate Payment Link → User Pays → Verify Payment → Update DB
                                                        ↓
                                              Razorpay Callback/Webhook
```

**Modes:**
- **Production:** Real Razorpay integration
- **Sandbox:** Test mode with test cards
- **Simulation:** No payment gateway (for testing)

### 3. Database Integration
```
API Request → DatabaseAPI Client → PHP API → MySQL → Response
                                       ↓
                           https://aadarshsenapati.in/api/api.php
```

**Operations:**
- User registration
- Slot booking with unique UID
- Payment tracking
- Feedback storage

### 4. Feedback Loop
```
User Rates Experience → Store in DB → Cache Locally → Retrain AI Model
                                            ↓
                                Better Recommendations Next Time
```

---

## 📝 API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/` | GET | Health check |
| `/api/test-database` | GET | Test DB connection |
| `/api/recommend-slots` | POST | Get AI recommendations |
| `/api/payment/create-order` | POST | Create booking + payment |
| `/api/payment/create-link` | POST | Generate payment link |
| `/api/payment/verify` | POST | Verify payment |
| `/api/payment/simulate` | POST | Simulate payment (test) |
| `/api/booking/{uid}` | GET | Get booking details |
| `/api/feedback` | POST | Submit user feedback |
| `/api/feedback/stats` | GET | Feedback statistics |
| `/api/retrain-model` | POST | Retrain AI model |

---

## 🔧 Configuration Files

### `.env.example` - Environment Variables
```env
RAZORPAY_KEY_ID=rzp_test_RYlqJbc24Sl6jz
RAZORPAY_KEY_SECRET=bghQe0L7iort9vmqb6Jlf8Ec
```

### `render.yaml` - Render Deployment
Auto-deploy to Render.com

### `railway.json` - Railway Deployment
Auto-deploy to Railway.app

### `vercel.json` - Vercel Deployment
Auto-deploy to Vercel (serverless)

---

## 🚀 Deployment Options

### Option 1: Render (Recommended)
```bash
1. Push code to GitHub
2. Connect repo on render.com
3. Create "Web Service"
4. Render auto-detects render.yaml
5. Add environment variables
6. Deploy!
```

### Option 2: Railway
```powershell
npm i -g @railway/cli
railway login
railway init
railway up
```

### Option 3: Vercel
```powershell
npm i -g vercel
vercel --prod
```

---

## 📚 Documentation Files

| File | Description |
|------|-------------|
| `README.md` | Main documentation |
| `DATABASE_INTEGRATION.md` | Database API guide |
| `NEXT_STEPS.md` | Testing & deployment plan |
| `test_database.py` | Database tests |
| `test_system.py` | System tests |

---

## 🎓 How It Works - Example Flow

### Scenario: User Books a Parking Spot

1. **User opens app** → Sends location (28.6139, 77.2090)

2. **App calls API:**
```
POST /api/recommend-slots
{
  "user_location": {"latitude": 28.6139, "longitude": 77.2090},
  "slots": [],
  "top_k": 3
}
```

3. **Backend:**
   - Fetches all parking lots from database
   - AI model analyzes and ranks
   - Returns top 3 recommendations

4. **User selects Slot A1** → App shows ₹100 for 2 hours

5. **App calls API:**
```
POST /api/payment/create-order
{
  "slot_id": "A1",
  "amount": 100,
  "duration_hours": 2,
  "vehicle_number": "DL01AB1234",
  "customer_name": "John Doe",
  "customer_contact": "+919876543210",
  "customer_email": "john@example.com"
}
```

6. **Backend:**
   - Registers user in database (if new)
   - Creates booking → Gets unique UID "ABC12345"
   - Creates Razorpay order
   - Returns payment link

7. **User pays via Razorpay** → Payment successful

8. **Backend receives webhook:**
```
POST /api/payment/verify
```
   - Verifies payment signature
   - Updates database: payment_status = "Completed"

9. **After parking, user rates experience:**
```
POST /api/feedback
{
  "booking_id": "ABC12345",
  "rating": 5.0,
  "user_satisfaction": true
}
```
   - Stores in database
   - Used to improve AI model

---

## ⚠️ Troubleshooting

### Issue: Database API not responding
```powershell
# Test connection manually
python -c "from backend.database_api import DatabaseAPI; api = DatabaseAPI(); print(api.get_parking_lots())"
```

### Issue: Import errors
```powershell
# Reinstall dependencies
pip install -r requirements.txt --upgrade
```

### Issue: Port already in use
```powershell
# Use different port
uvicorn backend.main:app --reload --port 8001
```

### Issue: Module not found
```powershell
# Make sure you're in correct directory
cd C:\Users\chdnv\Desktop\CodeStrom
python backend/main.py
```

---

## 📞 Integration Points

### For Other Team Members

**To integrate with this module:**

1. **Get Recommendations:**
```javascript
fetch('http://your-deployed-url.com/api/recommend-slots', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        user_location: {latitude: 28.6139, longitude: 77.2090},
        slots: [],
        top_k: 3
    })
})
```

2. **Create Booking:**
```javascript
fetch('http://your-deployed-url.com/api/payment/create-order', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        slot_id: "1",
        amount: 100,
        duration_hours: 2,
        // ... other fields
    })
})
```

3. **Check Booking:**
```javascript
fetch('http://your-deployed-url.com/api/booking/ABC12345')
```

---

## ✅ Success Checklist

- [ ] Dependencies installed (`pip install -r requirements.txt`)
- [ ] Database tests pass (`python test_database.py`)
- [ ] System tests pass (`python test_system.py`)
- [ ] Server starts (`uvicorn backend.main:app --reload`)
- [ ] Can access http://localhost:8000/docs
- [ ] Database connection works (/api/test-database)
- [ ] Can get recommendations
- [ ] Can create booking
- [ ] Can simulate payment
- [ ] Can submit feedback

---

## 🎉 You're Ready!

Your AI + Payment module is fully functional and integrated with the database. 

**Next Steps:**
1. ✅ Test locally (Done above)
2. 🚀 Deploy to production (Render/Railway/Vercel)
3. 📱 Integrate with frontend/mobile app
4. 📊 Monitor usage and performance
5. 🔄 Collect feedback and retrain model

**Questions?** Check:
- `README.md` - Full documentation
- `DATABASE_INTEGRATION.md` - Database details
- `NEXT_STEPS.md` - Deployment guide

**Happy Coding!** 🚀
