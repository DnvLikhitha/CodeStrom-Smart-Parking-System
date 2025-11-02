
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Smart Parking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .nav-brand { font-weight:700; color:#1f6feb; }
    .container-main { padding-top: 24px; padding-bottom: 40px; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
  <div class="container">
    <a class="navbar-brand nav-brand" href="owner_dashboard.php">SmartParking</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="entry_exit.php">Entry/Exit</a></li>
        <li class="nav-item"><a class="nav-link" href="owner_onsite_booking.php">Walk in booking</a></li>
        <li class="nav-item"><a class="nav-link" href="owner_dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="analytics.php">Analytics</a></li>
        <?php if(!empty($_SESSION['owner_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container container-main">
