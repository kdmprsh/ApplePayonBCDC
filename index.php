<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>PayPal + Apple Pay Test Launcher</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root {
      --brand: #0070ba;
      --bg: #f4f6f8;
      --card: #ffffff;
      --text: #1a1a1a;
      --muted: #6b7280;
      --radius: 14px;
      --line: #e5e7eb;
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
    }
    .page { width: 100%; max-width: 480px; }
    .card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
      padding: 2rem;
    }
    h1 { font-size: 1.35rem; margin: 0 0 .25rem; text-align: center; }
    .subtitle { text-align: center; color: var(--muted); font-size: .9rem; margin: 0 0 1.5rem; }

    .radio-row {
      display: block;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: border-color 0.15s ease;
    }
    .radio-row:has(input:checked) { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand) inset; }
    .radio-row .option-head {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .radio-row input[type="radio"] { accent-color: var(--brand); width: 16px; height: 16px; flex-shrink: 0; }
    .radio-row .option-title { font-weight: 700; font-size: .95rem; }
    .radio-row .option-desc {
      margin: 6px 0 0 26px;
      color: var(--muted);
      font-size: .8rem;
    }
    .radio-row .option-file {
      margin: 2px 0 0 26px;
      color: var(--brand);
      font-size: .75rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }

    .go-btn {
      width: 100%;
      background: #111827;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px 16px;
      font-size: .95rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 8px;
      transition: background-color 0.15s ease;
    }
    .go-btn:hover { background: #000; }

    .section-label {
      font-size: .75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: var(--muted);
      margin: 0 0 8px;
    }
    .section-label:not(:first-child) { margin-top: 20px; }

    .country-select {
      width: 100%;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 12px 14px;
      font-size: .95rem;
      font-family: inherit;
      color: var(--text);
      background: #fff;
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'><path d='M5.5 7.5l4.5 4.5 4.5-4.5z'/></svg>");
      background-repeat: no-repeat;
      background-position: right 14px center;
      background-size: 16px;
    }
    .country-select:focus {
      outline: none;
      border-color: var(--brand);
      box-shadow: 0 0 0 1px var(--brand) inset;
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="card">
      <h1>PayPal + Apple Pay Testing</h1>
      <div class="subtitle">Choose an integration to try</div>

      <form action="" method="get" id="launcher-form">
        <div class="section-label">Seller / Merchant Country</div>
        <select class="country-select" name="country" id="country-select">
          <option value="IN">India (IN)</option>
          <option value="AE">UAE (AE)</option>
          <option value="SG" selected>Singapore (SG)</option>
          <option value="ZA">South Africa (ZA)</option>
          <option value="MA">Morocco (MA)</option>
        </select>

        <div class="section-label">Integration</div>
        <label class="radio-row" for="opt-js6">
          <div class="option-head">
            <input type="radio" id="opt-js6" name="target" value="index_js6.php" checked />
            <span class="option-title">JS SDK v6 only</span>
          </div>
          <div class="option-desc">Standard buttons combination &mdash; PayPal, Pay Later, Credit, and Apple Pay, each rendered as its own button.</div>
          <div class="option-file">index_js6.php</div>
        </label>

        <label class="radio-row" for="opt-js6-1">
          <div class="option-head">
            <input type="radio" id="opt-js6-1" name="target" value="index_js6_1.php" />
            <span class="option-title">Button rendering combinations</span>
          </div>
          <div class="option-desc">Side-by-side comparison of all four layouts from the integration guide &mdash; v5+v6 combined and full v6, each as buttons or radio choice.</div>
          <div class="option-file">index_js6_1.php</div>
        </label>

        <button type="submit" class="go-btn">Continue</button>
      </form>
    </div>
  </div>

  <script>
    document.getElementById("launcher-form").addEventListener("submit", function (e) {
      e.preventDefault();
      const selectedTarget = document.querySelector('input[name="target"]:checked');
      const countrySelect = document.getElementById("country-select");
      if (selectedTarget && selectedTarget.value) {
        const country = countrySelect ? countrySelect.value : "SG";
        window.location.href = selectedTarget.value + "?country=" + encodeURIComponent(country);
      }
    });
  </script>
</body>
</html>
