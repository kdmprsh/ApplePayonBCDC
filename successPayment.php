<?php
/*include('config.php');
$paymentID = $_GET['id'];
$access_token = getAccessToken(); //echo $access_token;
$res = getPaymentDetails($access_token, $paymentID);
echo"<pre>";var_dump($res);echo"</pre>";*/

declare(strict_types=1);

// Render HTML
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config.php';

// --- Inputs ---
$orderId = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : null;
// Seller/merchant country threaded through from the checkout page, so the
// lookup uses the same credentials the order was created/captured with.
$country = isset($_GET['country']) && is_string($_GET['country']) ? strtoupper($_GET['country']) : DEFAULT_MERCHANT_COUNTRY;

// --- Helpers ---
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function asArray($maybeJson) {
  if (is_array($maybeJson)) return $maybeJson;
  if (is_string($maybeJson)) {
    $decoded = json_decode($maybeJson, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
  }
  return ['raw' => $maybeJson];
}

// --- Fetch details ---
$details = [];
$errorMsg = null;

if (!$orderId) {
  $errorMsg = 'Missing order id.';
} else {
  try {
    $accessToken = getAccessToken($country);             // your helper
    $resp        = getPaymentDetails($accessToken, $orderId); // your helper
    $details     = asArray($resp);
  } catch (Throwable $e) {
    $errorMsg = 'Failed to load payment details: ' . $e->getMessage();
  }
}

// --- Determine capture status (ONLY nested capture status) ---
$captureStatus = $details['purchase_units'][0]['payments']['captures'][0]['status'] ?? null;
$issue         = $details['details'][0]['issue'] ?? null;

// Banner (based on capture status only)
if ($errorMsg) {
  $bannerText  = '❌ Payment Details Error';
  $bannerColor = '#ef4444';
} elseif ($captureStatus === 'COMPLETED') {
  $bannerText  = '✅ Payment Completed Successfully';
  $bannerColor = '#22c55e';
} elseif ($issue === 'INSTRUMENT_DECLINED') {
  $bannerText  = '⚠️ Payment Instrument Declined';
  $bannerColor = '#eab308';
} else {
  // Covers PENDING, DECLINED, VOIDED, or missing captures
  $bannerText  = '❌ Payment Not Completed';
  $bannerColor = '#ef4444';
}

// Optional: show key fields
$payerName  = $details['payment_source']['paypal']['name']['given_name']
              ?? $details['payer']['name']['given_name'] ?? null;
$payerEmail = $details['payment_source']['paypal']['email_address']
              ?? $details['payer']['email_address'] ?? null;

$amount   = $details['purchase_units'][0]['payments']['captures'][0]['amount']['value']
            ?? $details['purchase_units'][0]['amount']['value'] ?? null;
$currency = $details['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code']
            ?? $details['purchase_units'][0]['amount']['currency_code'] ?? null;

// Which payment method was used (best-effort, for display only)
$paymentSourceUsed = null;
if (isset($details['payment_source']) && is_array($details['payment_source'])) {
  $paymentSourceUsed = array_key_first($details['payment_source']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Payment Status – <?php echo h($orderId ?? 'N/A'); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root {
      --accent: <?php echo $bannerColor; ?>;
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
    .page { width: 100%; max-width: 640px; }
    .card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
      padding: 2rem;
    }
    h1 { font-size: 1.5rem; margin: 0 0 .25rem; text-align: center; }
    .muted { color: var(--muted); margin-bottom: 1.25rem; text-align: center; font-size: .9rem; }
    .banner {
      background: color-mix(in srgb, var(--accent) 14%, white);
      border-left: 6px solid var(--accent);
      padding: 1rem 1.25rem;
      border-radius: 10px;
      color: var(--accent);
      font-weight: 600;
      margin-bottom: 1.25rem;
      text-align: center;
    }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .card-field { background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: .75rem 1rem; }
    .label { color: #555; font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; }
    .value { font-weight: 600; margin-top: 2px; }
    details { margin-top: .5rem; }
    summary { cursor: pointer; color: var(--muted); font-size: .85rem; margin-bottom: .5rem; }
    pre { background: #f6f6f6; border-radius: 10px; padding: 1rem; overflow: auto; font-size: .85rem; }
    .actions { margin-top: 1.5rem; text-align: center; }
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
      <h1>Payment Status</h1>
      <div class="muted">Order ID: <?php echo h($orderId ?? 'N/A'); ?><?php if ($paymentSourceUsed) { echo ' &middot; Paid via ' . h($paymentSourceUsed); } ?></div>

      <div class="banner"><?php echo h($bannerText); ?></div>

      <div class="grid">
        <div class="card-field">
          <div class="label">Capture Status</div>
          <div class="value"><?php echo h($captureStatus ?? 'Unknown'); ?></div>
        </div>
        <?php if ($issue): ?>
          <div class="card-field">
            <div class="label">Issue</div>
            <div class="value"><?php echo h($issue); ?></div>
          </div>
        <?php endif; ?>
        <?php if ($amount && $currency): ?>
          <div class="card-field">
            <div class="label">Amount</div>
            <div class="value"><?php echo h($amount . ' ' . $currency); ?></div>
          </div>
        <?php endif; ?>
        <?php if ($payerName || $payerEmail): ?>
          <div class="card-field">
            <div class="label">Payer</div>
            <div class="value">
              <?php echo h(trim(($payerName ?? '') . ' ' . ($payerEmail ? "({$payerEmail})" : '')) ?: '—'); ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($errorMsg): ?>
        <div class="card-field" style="border-color:#fde68a;background:#fffbeb;">
          <div class="label">Error</div>
          <div class="value"><?php echo h($errorMsg); ?></div>
        </div>
      <?php endif; ?>

      <details>
        <summary>View full response</summary>
        <pre><?php echo h(json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
      </details>

      <div class="actions">
        <a class="btn" href="index_js6.php?country=<?php echo h($country); ?>">Continue Shopping</a>
      </div>
    </div>
  </div>
</body>
</html>
