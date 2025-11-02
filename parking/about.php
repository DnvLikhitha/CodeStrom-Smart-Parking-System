<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About Us | Smart Parking</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
/* === GLOBAL STYLES === */
:root {
    --primary-color: #1d3557;
    --secondary-color: #457b9d;
    --light-bg: #f9fbff;
    --text-color: #333;
}

body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background-color: var(--light-bg);
    color: var(--text-color);
    line-height: 1.7;
}

/* === NAVBAR === */
nav {
    background: var(--primary-color);
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 40px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}

nav .logo {
    font-size: 22px;
    font-weight: 600;
}

nav ul {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 28px;
    margin: 0;
    padding: 0;
}

nav ul li {
    position: relative;
}

nav ul li a {
    text-decoration: none;
    color: white;
    font-weight: 500;
    transition: color 0.3s ease;
}

nav ul li a:hover {
    color: #a8dadc;
}

nav ul li ul {
    display: none;
    position: absolute;
    top: 35px;
    left: 0;
    background: var(--primary-color);
    padding: 10px 0;
    border-radius: 6px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

nav ul li:hover ul {
    display: block;
}

nav ul li ul li {
    padding: 10px 20px;
    width: 160px;
}

nav ul li ul li a {
    color: #f1faee;
}

nav ul li ul li a:hover {
    color: #a8dadc;
}

/* === HEADER === */
header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    text-align: center;
    padding: 90px 20px 80px;
}

header h1 {
    font-size: 38px;
    font-weight: 600;
    margin-bottom: 10px;
}

header p {
    font-size: 18px;
    opacity: 0.9;
    max-width: 700px;
    margin: 0 auto;
}

/* === MAIN CONTAINER === */
.container {
    max-width: 1100px;
    margin: 60px auto;
    background: #fff;
    padding: 50px 60px;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.container:hover {
    transform: scale(1.01);
}

h2 {
    text-align: center;
    color: var(--primary-color);
    font-size: 30px;
    margin-bottom: 25px;
}

p {
    margin-bottom: 18px;
    font-size: 16px;
    color: #444;
}

/* === HIGHLIGHT BOXES === */
.mission, .vision {
    background: var(--light-bg);
    padding: 25px;
    border-left: 6px solid var(--secondary-color);
    border-radius: 8px;
    margin: 25px 0;
    transition: background 0.3s ease, transform 0.3s ease;
}

.mission:hover, .vision:hover {
    background: #eef4fb;
    transform: translateY(-2px);
}

.mission h3, .vision h3 {
    color: var(--primary-color);
    margin-top: 0;
}

/* === TEAM SECTION === */
.team {
    margin-top: 60px;
    text-align: center;
}

.team h3 {
    color: var(--primary-color);
    margin-bottom: 20px;
    font-size: 26px;
}

.team-members {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.member {
    background: #ffffff;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #e3e7ef;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.member:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    border-color: var(--secondary-color);
}

.member h4 {
    color: var(--primary-color);
    margin-bottom: 6px;
    font-size: 18px;
}

.member p {
    font-size: 15px;
    color: #555;
    margin-bottom: 12px;
}

.member a {
    text-decoration: none;
    color: var(--secondary-color);
    font-weight: 500;
}

.member a:hover {
    text-decoration: underline;
}

/* === FOOTER === */
footer {
    background: var(--primary-color);
    color: white;
    text-align: center;
    padding: 20px 10px;
    font-size: 15px;
    margin-top: 60px;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.15);
}

/* === RESPONSIVE === */
@media (max-width: 1024px) {
    .container {
        margin: 40px 30px;
        padding: 40px;
    }
}

@media (max-width: 768px) {
    nav {
        flex-direction: column;
        padding: 12px 20px;
    }
    nav ul {
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }
    header {
        padding: 70px 15px;
    }
    header h1 {
        font-size: 30px;
    }
    .container {
        margin: 30px 20px;
        padding: 30px;
    }
    h2 {
        font-size: 24px;
    }
    .team-members {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    nav ul {
        gap: 12px;
    }
    header h1 {
        font-size: 26px;
    }
    header p {
        font-size: 15px;
    }
    .container {
        padding: 25px;
        margin: 25px 15px;
    }
    .member {
        padding: 20px;
    }
}
</style>
</head>
<body>

<!-- 🔹 NAVBAR -->
<nav>
    <div class="logo">Smart Parking</div>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li>
            <a href="#">Login ▾</a>
            <ul>
                <li><a href="login.php">User Login</a></li>
                <li><a href="owner_login.php">Owner Login</a></li>
            </ul>
        </li>
        <li><a href="owner_dashboard.php">Dashboard</a></li>
        <li><a href="about.php" style="color:#a8dadc;">About</a></li>
    </ul>
</nav>

<header style="
    background-image: 
        linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
        url('src/about.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: white;
    text-align: center;
    padding: 80px 20px;
">
    <h1>About Smart Parking</h1>
</header>



<!-- 🔹 MAIN CONTENT -->
<div class="container">
    <h2>Who We Are</h2>
    <p>
        Smart Parking is a digital platform designed to revolutionize the way parking is managed and utilized in urban environments. 
        By combining intelligent automation, real-time data tracking, and secure payment integration, we make parking management 
        seamless for both users and operators.
    </p>
    <p>
        Our goal is to create sustainable and efficient mobility ecosystems where technology reduces congestion, saves time, 
        and improves the urban experience for everyone.
    </p>

    <div class="mission">
        <h3>Our Mission</h3>
        <p>
            To deliver a unified smart parking solution that promotes convenience, transparency, and operational excellence 
            through data-driven automation and real-time intelligence.
        </p>
    </div>

    <div class="vision">
        <h3>Our Vision</h3>
        <p>
            To shape the future of urban mobility by deploying intelligent, accessible, and sustainable parking systems across every Indian city.
        </p>
    </div>

    <div class="team">
        <h3>Our Team</h3>
        <div class="team-members">
            <div class="member">
                <h4>Aadarsh Senapati</h4>
                <p>Backend Developer — WhatsApp Integration</p>
                <a href="https://www.linkedin.com/in/aadarsh-senapati-b11634289/" target="_blank">View LinkedIn Profile</a>
            </div>
            <div class="member">
                <h4>DNV Likhitha</h4>
                <p>AI Specialist</p>
                <a href="https://www.linkedin.com/in/dnv-likhitha-ba12b8289/" target="_blank">View LinkedIn Profile</a>
            </div>
            <div class="member">
                <h4>Neha Kujur</h4>
                <p>Frontend Developer</p>
                <a href="https://www.linkedin.com/in/neha-kujur-896b74290/" target="_blank">View LinkedIn Profile</a>
            </div>
            <div class="member">
                <h4>Surya Teja Batchu</h4>
                <p>AI Engineer</p>
                <a href="https://www.linkedin.com/in/surya-teja-batchu-aab11227a/" target="_blank">View LinkedIn Profile</a>
            </div>
        </div>
    </div>
</div>

<!-- 🔹 FOOTER -->
<footer>
    © 2025 Smart Parking System • Designed by CodeStrom
</footer>

</body>
</html>
