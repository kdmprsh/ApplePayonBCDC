<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Payment Cancelled</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root {
      --accent: #eab308;
      --bg: #f4f6f8;
      --card: #ffffff;
      --text: #1a1a1a;
      --muted: #6b7280;
      --radius: 14px;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      justify-content: center;
      padding: 3rem 1rem;
      line-height: 1.5;
    }
    .page { width: 100%; max-width: 460px; }
    .card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
      padding: 2rem;
      text-align: center;
    }
    h1 { font-size: 1.4rem; margin: 0 0 .75rem; }
    .banner {
      background: color-mix(in srgb, var(--accent) 14%, white);
      border-left: 6px solid var(--accent);
      padding: 1rem 1.25rem;
      border-radius: 10px;
      color: #92650a;
      font-weight: 600;
      margin-bottom: 1.25rem;
    }
    p.muted { color: var(--muted); font-size: .95rem; margin: 0 0 1.5rem; }
    .btn {
      display: inline-block;
      background: #0070ba;
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      padding: 10px 22px;
      border-radius: 8px;
      transition: background-color 0.15s ease;
    }
    .btn:hover { background: #005c99; }
  </style>
</head>
<body>
  <div class="page">
    <div class="card">
      <h1>Payment Cancelled</h1>
      <div class="banner">⚠️ Your PayPal payment was not completed</div>
      <p class="muted">You have cancelled the PayPal payment. Please go back to the site to place your order again.</p>
      <a class="btn" href="index_js6.php">Back to Checkout</a>
    </div>
  </div>
</body>
</html>
