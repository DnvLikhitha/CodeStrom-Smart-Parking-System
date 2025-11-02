<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Parking System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
/* === GLOBAL STYLES === */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
    color: #1d3557;
    overflow-x: hidden;
}

/* === NAVBAR === */
nav {
    background: #1d3557;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 60px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    position: sticky;
    top: 0;
    z-index: 100;
}

nav .logo {
    font-size: 22px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

nav ul {
    list-style: none;
    display: flex;
    gap: 35px;
}

nav ul li a {
    text-decoration: none;
    color: white;
    font-weight: 500;
    transition: 0.3s;
}

nav ul li a:hover {
    color: #a8dadc;
}

/* === HERO SECTION === */
.hero {
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                url('src/index.jpg') center/cover no-repeat;
    color: white;
    text-align: center;
    padding: 140px 20px;
    position: relative;
}

.hero h1 {
    font-size: 48px;
    font-weight: 600;
    margin-bottom: 20px;
    animation: fadeInDown 1.2s ease;
}

.hero p {
    font-size: 18px;
    margin-bottom: 35px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
    animation: fadeInUp 1.2s ease;
}

.hero .btn {
    background: #f1faee;
    color: #1d3557;
    padding: 14px 28px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    margin: 0 10px;
}

.hero .btn:hover {
    background: #a8dadc;
    color: #0a203a;
}

/* === FEATURES SECTION === */
.features {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 40px;
    padding: 80px 40px;
    background: #fff;
}

.feature {
    text-align: center;
    flex: 1 1 250px;
    background: #f9fbfd;
    padding: 30px 20px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.feature img {
    width: 70px;
    margin-bottom: 20px;
}

.feature h3 {
    font-size: 20px;
    color: #1d3557;
    margin-bottom: 10px;
}

.feature p {
    color: #555;
    font-size: 15px;
    line-height: 1.6;
}

/* === HOW IT WORKS SECTION === */
.how-it-works {
    text-align: center;
    padding: 90px 30px;
    background: linear-gradient(135deg, #edf6f9, #f1faee);
}

.how-it-works h2 {
    font-size: 32px;
    font-weight: 600;
    color: #1d3557;
    margin-bottom: 50px;
}

.steps {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.step {
    width: 280px;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.step:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.step h4 {
    color: #457b9d;
    font-size: 18px;
    margin-bottom: 12px;
}

.step p {
    color: #555;
    font-size: 15px;
    line-height: 1.6;
}

/* === FOOTER === */
footer {
    background: #1d3557;
    color: white;
    text-align: center;
    padding: 18px 10px;
    font-size: 14px;
}

/* === ANIMATIONS === */
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* === RESPONSIVENESS === */
@media (max-width: 900px) {
    .hero h1 {
        font-size: 36px;
    }
    .features {
        flex-direction: column;
        align-items: center;
    }
    .steps {
        flex-direction: column;
        align-items: center;
    }
    nav {
        flex-direction: column;
        gap: 10px;
        padding: 15px;
    }
    nav ul {
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }
}
</style>
</head>
<body>

<!-- === NAVIGATION === -->
<nav>
    <div class="logo">Smart Parking</div>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="owner_login.php">Owner Login</a></li>
        <li><a href="login.php">User Login</a></li>
    </ul>
</nav>

<!-- === HERO SECTION === -->
<section class="hero">
    <h1>Smarter Parking</h1>
    <p>Seamlessly find, reserve, and manage your parking spot with real-time updates and intelligent automation — built for a smarter city experience.</p>
    <a href="login.php" class="btn">Get Started</a>
    <a href="owner_login.php" class="btn">Owner Dashboard</a>
</section>

<!-- === FEATURES === -->
<section class="features">
    <div class="feature">
        <img src="https://img.icons8.com/ios-filled/100/1d3557/parking.png" alt="Parking Icon"/>
        <h3>Live Slot Availability</h3>
        <p>Instantly view available parking spaces nearby and save valuable time every day.</p>
    </div>
    <div class="feature">
        <img src="https://img.icons8.com/ios-filled/100/1d3557/money.png" alt="Payment Icon"/>
        <h3>Secure & Flexible Payments</h3>
        <p>Pay using your preferred mode — online or offline — with secure digital transactions.</p>
    </div>
    <div class="feature">
        <img src="https://img.icons8.com/ios-filled/100/1d3557/time.png" alt="Time Icon"/>
        <h3>Real-Time Tracking</h3>
        <p>Monitor parking duration, slot status, and billing all in one unified platform.</p>
    </div>
</section>

<!-- === HOW IT WORKS === -->
<section class="how-it-works">
    <h2>How It Works</h2>
    <div class="steps">
        <div class="step">
            <h4>Step 1: Find</h4>
            <p>Search for nearby parking locations with live slot updates.</p>
        </div>
        <div class="step">
            <h4>Step 2: Book</h4>
            <p>Reserve your space in advance through a quick and simple booking process.</p>
        </div>
        <div class="step">
            <h4>Step 3: Park</h4>
            <p>Arrive and park stress-free — Smart Parking takes care of the rest.</p>
        </div>
    </div>
</section>

<!-- === FOOTER === -->
<footer>
    © 2025 Smart Parking System | Designed by CodeStrom
</footer>

</body>
</html>
