#app.py(For whataspp backend)
import os
import time
import math
import random
import qrcode
from io import BytesIO
from datetime import datetime, timedelta
from flask import Flask, request, jsonify, send_from_directory
from twilio.twiml.messaging_response import MessagingResponse
from twilio.rest import Client
import requests

app = Flask(__name__)

# -------------------------
# CONFIGURATION (env-overrides)
# -------------------------
API_BASE = os.environ.get("API_BASE", "https://aadarshsenapati.in/api/api.php")

AI_RECOMMEND_ENDPOINT = os.environ.get("AI_RECOMMEND_ENDPOINT", "https://dnvlikhitha-codestrom.hf.space/api/recommend-slots")  # POST path
BASE_PAY_URL = os.environ.get("BASE_PAY_URL", "https://aadarshsenapati.in/api/pay.php")
AI_PAY_BASE = BASE_PAY_URL
BASE_URL = os.environ.get("BASE_URL", "")  # public base URL to serve static files (for Twilio media)
BASE_URL_VERIFY_PAYMENT = "https://aadarshsenapati.in/api/verify_payment.php"
twilio_client = Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN)

# Ensure qrcodes folder
QR_DIR = os.path.join(os.getcwd(), 'static', 'qrcodes')
os.makedirs(QR_DIR, exist_ok=True)

# -------------------------
# In-memory session store
# -------------------------
sessions = {}
SESSION_TTL = 60 * 60  # 1 hour


def clean_sessions():
    now = time.time()
    expired = [k for k, v in sessions.items() if now - v.get('last_update', 0) > SESSION_TTL]
    for k in expired:
        del sessions[k]


def get_session(phone):
    clean_sessions()
    if phone not in sessions:
        sessions[phone] = {"state": None, "data": {}, "last_update": time.time()}
    sessions[phone]["last_update"] = time.time()
    return sessions[phone]


# -------------------------
# Helpers - PHP API wrappers
# -------------------------
def php_post(action, payload):
    try:
        return requests.post(f"{API_BASE}?action={action}", json=payload, timeout=12)
    except Exception as e:
        app.logger.exception("php_post error:")
        return None


def php_get(action, params=None):
    try:
        return requests.get(f"{API_BASE}?action={action}", params=params, timeout=12)
    except Exception as e:
        app.logger.exception("php_get error:")
        return None


def get_user_by_phone(phone):
    try:
        r = php_get("get_user_by_phone", params={"phone": phone})
        if r and r.status_code == 200:
            d = r.json()
            if d.get("status") == "success" and d.get("data"):
                return d["data"]
    except Exception as e:
        app.logger.exception("get_user_by_phone error:")
    return None


def send_whatsapp_message(to, body, media_url=None):
    """Send WhatsApp message (text or media). Return message SID or None."""
    try:
        if media_url:
            msg = twilio_client.messages.create(from_=TWILIO_FROM, body=body, to=to, media_url=[media_url])
        else:
            msg = twilio_client.messages.create(from_=TWILIO_FROM, body=body, to=to)
        return msg.sid
    except Exception as e:
        app.logger.exception("Twilio send error:")
        return None


# -------------------------
# Utility: haversine distance (km)
# -------------------------
def haversine_km(lat1, lon1, lat2, lon2):
    try:
        R = 6371.0
        phi1 = math.radians(float(lat1))
        phi2 = math.radians(float(lat2))
        dphi = math.radians(float(lat2) - float(lat1))
        dlambda = math.radians(float(lon2) - float(lon1))
        a = math.sin(dphi / 2) ** 2 + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2) ** 2
        return R * (2 * math.atan2(math.sqrt(a), math.sqrt(1 - a)))
    except Exception:
        return None


# -------------------------
# New endpoint: /api/recommend-slots (POST)
# A fallback internal recommender (keeps previous local behavior if needed)
# -------------------------
@app.route('/api/recommend-slots', methods=['POST'])
def recommend_slots_api():
    payload = request.get_json() or {}
    event_name = (payload.get('event_name') or "").strip()
    lat = payload.get('latitude')
    lon = payload.get('longitude')
    vehicle_type = payload.get('vehicle_type')  # optional
    top_k = int(payload.get('top_k', 3))

    if not event_name or lat is None or lon is None:
        return jsonify({"status": "error", "message": "Missing event_name or latitude/longitude"}), 400

    # Fetch special lots from PHP API (is_special=1 and keyword matches)
    r = php_get("get_parking_lots", params={"is_special": 1, "keyword": event_name})
    if not r or r.status_code != 200:
        return jsonify({"status": "error", "message": "Unable to fetch lots"}), 500

    data = r.json()
    lots = data.get('data', []) if isinstance(data, dict) else []
    scored = []
    # compute simple heuristic ranking
    for lot in lots:
        try:
            lot_lat = float(lot.get('latitude') or 0)
            lot_lon = float(lot.get('longitude') or 0)
        except:
            continue
        dist = haversine_km(lat, lon, lot_lat, lot_lon) or 9999
        available = 0
        try:
            avresp = php_get("get_available_slots")
            if avresp and avresp.status_code == 200:
                avail_data = avresp.json().get('data', [])
                for lf in avail_data:
                    if int(lf.get('lot_id')) == int(lot.get('lot_id')):
                        available = len(lf.get('available_slots', []))
                        break
        except Exception:
            available = 0

        score = dist - (available * 0.1)
        scored.append({"lot": lot, "distance_km": round(dist, 3), "available_slots": available, "score": score})

    scored.sort(key=lambda x: x['score'])
    recs = scored[:top_k]
    result = {"status": "success", "recommended": [
        {
            "lot_id": s['lot']['lot_id'],
            "lot_name": s['lot']['lot_name'],
            "distance_km": s['distance_km'],
            "available_slots": s['available_slots'],
            "lot_info": s['lot']
        } for s in recs
    ], "count": len(recs)}
    return jsonify(result), 200


# -------------------------
# Helper: generate & save QR image, return public URL (if BASE_URL set)
# -------------------------
def generate_qr_and_get_url(booking_uid):
    filename = f"{booking_uid}.png"
    path = os.path.join(QR_DIR, filename)
    # generate qrcode
    qr = qrcode.QRCode(box_size=6, border=2)
    qr.add_data(booking_uid)
    qr.make(fit=True)
    img = qr.make_image(fill_color="black", back_color="white")
    img.save(path)
    if BASE_URL:
        return BASE_URL.rstrip('/') + f"/static/qrcodes/{filename}"
    return None


# -------------------------
# MAIN BOT LOGIC (webhook)
# -------------------------
@app.route('/webhook', methods=['POST'])
def webhook():
    # Twilio fields (text or location)
    incoming_msg = request.values.get('Body', '').strip()
    sender = request.values.get('From', '')
    phone = sender.replace('whatsapp:', '').strip()
    lat = request.values.get('Latitude')  # Twilio location share fields
    lon = request.values.get('Longitude')

    resp = MessagingResponse()
    msg = resp.message()

    sess = get_session(phone)
    state = sess.get("state")
    data = sess.get("data", {})

    lower_msg = (incoming_msg or "").lower().strip()

    
    # existing user
    user = get_user_by_phone(phone)

    # greet / entry
    if lower_msg in ["hi", "hello", "hey", "hii", "hiii"]:
        if user:
            data["user_id"] = user["user_id"]
            data["name"] = user["name"]
            sess["state"] = "WAIT_VEHICLE_OR_SPECIAL"
            greeting = (
                f"👋 Hello {user['name']}! Welcome back.\n\n"
                "Reply *special* for event booking 🎉\n"
                "Or choose vehicle type:\n1️⃣ 2-Wheeler\n2️⃣ 4-Wheeler\n3️⃣ EV\n4️⃣ Bus\n\n"
            )
            send_whatsapp_message(f"whatsapp:{phone}", greeting)
            return str(resp)
        else:
            msg.body("👋 Hi! Welcome to Smart Parking Finder. What's your name?")
            sess["state"] = "WAIT_NAME"
            return str(resp)

    # registration flow
    if state == "WAIT_NAME":
        data["name"] = incoming_msg.title()
        sess["state"] = "WAIT_EMAIL"
        msg.body(f"Nice to meet you, {data['name']}! Please enter your email address 📧")
        return str(resp)

    if state == "WAIT_EMAIL":
        email = incoming_msg.strip()
        data["email"] = email
        php_post("register_user", {"name": data["name"], "phone": phone, "email": email})
        sess["state"] = "WAIT_VEHICLE_OR_SPECIAL"
        msg.body("✅ Registered. Type *special* for events or choose vehicle type (1-4).")
        return str(resp)

    # WAIT_VEHICLE_OR_SPECIAL
    if state == "WAIT_VEHICLE_OR_SPECIAL":
        if lower_msg == "special":
            # fetch special events list from get_parking_lots?is_special=1
            r = php_get("get_parking_lots", params={"is_special": 1})
            events = []
            if r and r.status_code == 200:
                d = r.json()
                for lot in d.get("data", []):
                    kw = (lot.get("keyword") or "").strip()
                    if kw:
                        events.append({"keyword": kw, "lot_name": lot.get("lot_name"), "lot_id": lot.get("lot_id")})
            # deduplicate by keyword preserving order
            seen = set()
            unique_events = []
            for e in events:
                if e["keyword"].lower() not in seen:
                    seen.add(e["keyword"].lower())
                    unique_events.append(e)
            if not unique_events:
                msg.body("No special events currently available. Type 'hi' to continue with normal booking.")
                return str(resp)
            # show numbered list of events
            ev_map = {}
            lines = []
            for i, ev in enumerate(unique_events, start=1):
                ev_map[str(i)] = ev
                lines.append(f"{i}. {ev['keyword']} ({ev['lot_name']})")
            data["special_events_map"] = ev_map
            sess["state"] = "ASK_SELECT_EVENT"
            msg.body("Available special events:\n" + "\n".join(lines) + "\n\nReply with the event number to select.")
            return str(resp)
        else:
            # treat entry as vehicle selection:
            sess["state"] = "ASKED_VEHICLE_TYPE"
            # continue to vehicle handling below (fallthrough)
    # If user was asked to pick an event number
    if state == "ASK_SELECT_EVENT":
        if incoming_msg.isdigit() and data.get("special_events_map") and incoming_msg in data["special_events_map"]:
            ev = data["special_events_map"][incoming_msg]
            data["event_name"] = ev["keyword"]
            sess["state"] = "ASKED_VEHICLE_TYPE_SPECIAL"
            msg.body(f"Event selected: *{data['event_name']}*.\nNow choose vehicle type:\n1️⃣ 2W\n2️⃣ 4W\n3️⃣ EV\n4️⃣ Bus")
            return str(resp)
        else:
            msg.body("Please reply with the correct event number (e.g., 1, 2). Type 'cancel' to abort.")
            return str(resp)

    # ASK_EVENT_NAME (fallback in case user typed event name directly)
    if state == "ASK_EVENT_NAME":
        data["event_name"] = incoming_msg.strip()
        sess["state"] = "ASKED_VEHICLE_TYPE_SPECIAL"
        msg.body(f"Event: *{data['event_name']}*. Now choose your vehicle type: 1️⃣ 2W 2️⃣ 4W 3️⃣ EV 4️⃣ Bus")
        return str(resp)

    # Vehicle selection (normal or special)
    if state in ("ASKED_VEHICLE_TYPE", "ASKED_VEHICLE_TYPE_SPECIAL", "WAIT_VEHICLE_OR_SPECIAL"):
        types = {"1": "2-wheeler", "2": "4-wheeler", "3": "EV", "4": "Bus"}
        vt = types.get(incoming_msg) or incoming_msg.title()
        data["vehicle_type"] = vt

        # if this was special path, ask for location
        if state == "ASKED_VEHICLE_TYPE_SPECIAL":
            sess["state"] = "ASK_FOR_LOCATION_SPECIAL"
            msg.body("Please share your current location using WhatsApp's location share (tap + -> Location), so we can find nearest event lots.")
            return str(resp)

        # Normal flow: fetch lots and show numbered options filtered by vehicle type where possible
        r = php_get("get_parking_lots")
        if r and r.status_code == 200:
            d = r.json()
            all_lots = d.get("data", []) if isinstance(d, dict) else []
            # filter lots that likely accept this vehicle type if parking_lots had vehicle_type (not always)
            lot_map, reply = {}, []
            for i, lot in enumerate(all_lots, start=1):
                lot_map[str(i)] = lot
                addr = lot.get('address') or lot.get('location') or ''
                reply.append(f"{i}. {lot.get('lot_name')} — {addr}")
            data["lot_map"] = lot_map
            sess["state"] = "ASKED_SELECT_LOT"
            msg.body("Available parking lots:\n" + "\n".join(reply) + "\n\nReply with the lot number to choose (e.g., 1).")
            return str(resp)
        msg.body("⚠️ Could not fetch lots right now.")
        return str(resp)

    # Special path — user shared location (Twilio includes Latitude/Longitude)
    if state == "ASK_FOR_LOCATION_SPECIAL":
        # If location supplied in this webhook call
        if lat and lon:
            try:
                lat_f = float(lat)
                lon_f = float(lon)
            except:
                msg.body("Couldn't read your location. Please share location again.")
                return str(resp)

            # First try AI endpoint at AI_PAY_BASE/recommend-slot
            ai_url = AI_PAY_BASE.rstrip('/') + '/' + AI_RECOMMEND_ENDPOINT.lstrip('/')
            ai_payload = {
                "event_name": data.get("event_name", ""),
                "latitude": lat_f,
                "longitude": lon_f,
                "vehicle_type": data.get("vehicle_type", "")
            }
            try:
                event_keyword = data.get("event_name", "").strip()
                app.logger.info("AI recommend failed — attempting php get_parking_lots_key fallback for keyword: %s", event_keyword)

                if event_keyword:
                    pk_resp = php_get("get_parking_lots_key", params={"key": event_keyword})
                else:
                    pk_resp = None

                if pk_resp and pk_resp.status_code == 200:
                    try:
                        pk_json = pk_resp.json()
                    except Exception:
                        pk_json = None

                    lots_list = []
                    if isinstance(pk_json, dict) and pk_json.get("status") == "success":
                        lots_list = pk_json.get("data", []) or []
                    elif isinstance(pk_json, list):
                        lots_list = pk_json
                    else:
                        lots_list = []

                    if lots_list:
                        # Filter to ensure we have at least one entry with reasonable lat/lon (best-effort)
                        valid_lots = []
                        for l in lots_list:
                            # Ensure it's special or has matching keyword - small precaution
                            # we rely on PHP endpoint to already filter by keyword
                            valid_lots.append(l)

                        if not valid_lots:
                            app.logger.warning("No valid lots returned from get_parking_lots_key for keyword %s", event_keyword)
                        else:
                            chosen_lot = random.choice(valid_lots)
                            app.logger.info("Randomly chosen fallback lot for event '%s': %r", event_keyword, chosen_lot.get("lot_id"))
                            # compute approximate distance & availability if possible
                            lot_lat = chosen_lot.get("latitude")
                            lot_lon = chosen_lot.get("longitude")
                            distance_km = None
                            if lot_lat and lot_lon:
                                try:
                                    distance_km = haversine_km(lat_f, lon_f, float(lot_lat), float(lot_lon))
                                    if distance_km is not None:
                                        distance_km = round(distance_km, 3)
                                except Exception:
                                    distance_km = None

                            avail = 0
                            try:
                                avo = php_get("get_available_slots")
                                if avo and avo.status_code == 200:
                                    for lf in avo.json().get("data", []):
                                        if str(lf.get('lot_id')) == str(chosen_lot.get('lot_id')):
                                            avail = len(lf.get('available_slots', []))
                                            break
                            except Exception:
                                avail = 0

                            data["selected_lot"] = chosen_lot
                            sess["state"] = "ASK_CONFIRM_SPECIAL"
                            lot_name = chosen_lot.get("lot_name") or "Lot"
                            addr = chosen_lot.get("address") or chosen_lot.get("location") or ""
                            distance_text = f"{distance_km} km" if distance_km is not None else "N/A"
                            summary = (f"Best lot for {data.get('event_name')}: {lot_name} ({addr})\n"
                                        f"Distance: {distance_text} | Available: {avail}\n"
                                        "Reply *confirm* to book this lot or *cancel* to abort.")
                            send_whatsapp_message(f"whatsapp:{phone}", summary)
                            return str(resp)
                    else:
                        app.logger.info("get_parking_lots_key returned no lots for keyword: %s", event_keyword)
                else:
                    app.logger.warning("php get_parking_lots_key request failed (no response or non-200).")
            except Exception:
                app.logger.exception("Fallback get_parking_lots_key error:")

                # If we reach here, fallback could not provide a lot -> notify user
                msg.body("Could not get recommendations right now. Try again later.")
                return str(resp)
        else:
            # not a location message
            msg.body("Please share location using WhatsApp's location share (tap + -> Location).")
            return str(resp)

    # Special confirm handler
    if state == "ASK_CONFIRM_SPECIAL":
        if lower_msg in ("confirm", "yes", "y", "1"):
            sess["state"] = "ASKED_DURATION_SPECIAL"
            msg.body("How many hours would you like to park? (integer)")
            return str(resp)
        elif lower_msg in ("cancel", "no", "n"):
            msg.body("Booking cancelled. Type 'hi' to start again.")
            sess["state"] = None
            return str(resp)
        else:
            msg.body("Please reply 'confirm' to proceed or 'cancel' to abort.")
            return str(resp)

    # Duration (special)
    if state == "ASKED_DURATION_SPECIAL":
        try:
            hours = int(incoming_msg)
        except:
            msg.body("Please enter a valid integer of hours (e.g., 2).")
            return str(resp)

        data["duration"] = hours
        rate = float(data.get("selected_lot", {}).get("hourly_rate", 20))
        total = round(hours * rate, 2)
        data["amount"] = total

        uid = data.get("user_id", 0)
        slot_id = data.get("selected_lot", {}).get("lot_id")
        start = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        end = (datetime.now() + timedelta(hours=hours)).strftime("%Y-%m-%d %H:%M:%S")

        payload = {"user_id": uid, "slot_id": slot_id, "start_time": start, "end_time": end, "total_amount": total}
        r = php_post("book_slot", payload)
        if r and r.status_code == 200 and r.json().get("status") == "success":
            booking_uid = r.json().get("booking_uid")
            data["booking_uid"] = booking_uid
            data["cancel_allowed_until"] = (datetime.now() + timedelta(minutes=10)).isoformat()
            sess["state"] = "AWAIT_PAYMENT"
            pay_url = f"{AI_PAY_BASE}?booking_uid={booking_uid}&amount={total}"
            send_whatsapp_message(f"whatsapp:{phone}", f"✅ Booking {booking_uid} created. Pay ₹{total} here (test):\n{pay_url}\n\nAfter paying, reply 'Paid'. You can cancel within 10 minutes for a refund credit.")
            return str(resp)
        msg.body("Could not create booking. Try again later.")
        return str(resp)

    # Normal selection of lot (numbered)
    if state == "ASKED_SELECT_LOT":
        if incoming_msg.isdigit() and data.get("lot_map") and incoming_msg in data["lot_map"]:
            lot = data["lot_map"][incoming_msg]
            data["selected_lot"] = lot
            sess["state"] = "ASKED_DURATION"
            msg.body(f"Selected {lot.get('lot_name')}. How many hours would you like to park? (integer)")
            return str(resp)
        else:
            msg.body("Please reply with the correct lot number (e.g., 1, 2). Type 'cancel' to abort.")
            return str(resp)

    # Normal duration flow
    if state == "ASKED_DURATION":
        try:
            hours = int(incoming_msg)
        except:
            msg.body("Please enter a valid number of hours (e.g., 2).")
            return str(resp)

        data["duration"] = hours
        rate = float(data.get("selected_lot", {}).get("hourly_rate", 20))
        total = round(hours * rate, 2)
        data["amount"] = total

        uid = data.get("user_id", 0)
        slot_id = data.get("selected_lot", {}).get("lot_id")
        start = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        end = (datetime.now() + timedelta(hours=hours)).strftime("%Y-%m-%d %H:%M:%S")

        payload = {"user_id": uid, "slot_id": slot_id, "start_time": start, "end_time": end, "total_amount": total}
        r = php_post("book_slot", payload)

        if r and r.status_code == 200 and r.json().get("status") == "success":
            booking_uid = r.json().get("booking_uid")
            data["booking_uid"] = booking_uid
            data["cancel_allowed_until"] = (datetime.now() + timedelta(minutes=10)).isoformat()
            sess["state"] = "AWAIT_PAYMENT"
            pay_link = f"{BASE_PAY_URL}?booking_uid={booking_uid}&amount={total}"
            send_whatsapp_message(f"whatsapp:{phone}", f"✅ Booking {booking_uid} created. Pay ₹{total} here:\n{pay_link}\n\nAfter paying, reply 'Paid'. You can cancel within 10 minutes for a refund credit.")
            return str(resp)

        msg.body("⚠️ Could not create booking. Try again later.")
        return str(resp)

    # Payment confirmation (both flows)
    if lower_msg in ("paid", "yes") and state == "AWAIT_PAYMENT":
        uid = data.get("booking_uid")
        amount = data.get("amount", 0)
    
        try:
            # Verify payment status
            r = requests.get(f"{BASE_URL_VERIFY_PAYMENT}", params={"booking_uid": uid}, timeout=12)
    
            if not r or r.status_code != 200:
                msg.body("❌ Could not verify payment. Server error.")
                return str(resp)
    
            try:
                jr = r.json()
            except Exception as e:
                app.logger.error(f"❌ JSON decode error: {e}\nRaw text: {r.text}")
                msg.body("⚠️ Invalid response from payment server.")
                return str(resp)
    
            app.logger.info(f"✅ verify_payment response: {jr}")
    
            # Check paid flag
            if jr.get("paid") is True or jr.get("payment_status", "").lower() == "paid":
                # Update local records if needed    
                qr_url = generate_qr_and_get_url(uid)
                if qr_url:
                    send_whatsapp_message(
                        f"whatsapp:{phone}",
                        f"✅ Payment successful. Here is your booking QR for UID: {uid}",
                        media_url=qr_url
                    )
                else:
                    send_whatsapp_message(f"whatsapp:{phone}", f"✅ Payment successful. Booking UID: {uid}")
    
                msg.body("✅ Payment successful! Booking confirmed.\n\nYou can cancel within 10 minutes if needed (reply 'cancel booking').")
                sess["state"] = "BOOKING_CONFIRMED"
                return str(resp)
    
            else:
                msg.body("⚠️ Payment not yet confirmed. If you just paid, please wait a few seconds and reply 'paid' again.")
                return str(resp)
    
        except Exception as e:
            app.logger.exception(f"Payment verification error: {e}")
            msg.body(f"❌ Payment verification failed.\n\nError: {str(e)}")
            return str(resp)



    # Cancel booking (user triggered)
    if lower_msg.startswith("cancel"):
        booking_uid = data.get("booking_uid")
        if not booking_uid:
            msg.body("No recent booking found in this session to cancel.")
            return str(resp)

        allowed_until = data.get("cancel_allowed_until")
        if allowed_until:
            try:
                allowed_dt = datetime.fromisoformat(allowed_until)
            except Exception:
                allowed_dt = None
            if allowed_dt and datetime.now() > allowed_dt:
                msg.body("Cancellation window (10 minutes) expired. Please contact support for refunds.")
                return str(resp)

        r = php_post("cancel_booking", {"booking_uid": booking_uid})
        if r and r.status_code == 200 and r.json().get("status") == "success":
            send_whatsapp_message(f"whatsapp:{phone}", f"✅ Booking {booking_uid} cancelled. Your amount is added to Twilio Cash balance (withdraw via ERP or reuse on web).")
            msg.body("Booking cancelled and credit issued.")
            data.pop("booking_uid", None)
            data.pop("cancel_allowed_until", None)
            sess["state"] = None
            return str(resp)
        else:
            msg.body("Could not cancel booking. Try again or contact support.")
            return str(resp)

    msg.body("❓ I didn’t understand that. Type 'hi' to start again.")
    return str(resp)


# -------------------------
# Existing send_otp route preserved
# -------------------------
@app.route('/send_otp', methods=['POST'])
def send_otp():
    data = request.get_json()
    if not data or "phone" not in data or "OTP" not in data:
        return jsonify({"status": "error", "message": "Missing phone or OTP"}), 400

    phone = data["phone"].replace(" ", "")
    otp = str(data["OTP"]).strip()

    try:
        message = twilio_client.messages.create(
            from_=TWILIO_FROM,
            to=f"whatsapp:{phone}",
            body=f"🔐 Your Smart Parking OTP is: *{otp}*\n\nUse this OTP to complete your login. Do not share it with anyone."
        )
        return jsonify({"status": "sent", "sid": message.sid}), 200
    except Exception as e:
        app.logger.exception("send_otp error")
        return jsonify({"status": "error", "message": str(e)}), 500


# -------------------------
# Serve QR images for external fetching (if BASE_URL points to this app)
# -------------------------
@app.route('/static/qrcodes/<filename>')
def serve_qr(filename):
    return send_from_directory(QR_DIR, filename)


# -------------------------
# Health Check
# -------------------------
@app.route('/')
def index():
    return "🚗 Smart Parking Flask API — Special Event + AI recommendation + QR + Cancel (10min) ready"


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
