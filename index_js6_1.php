<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

// Prevent caching so a fresh token is generated each load
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Seller/merchant country selected on index.php (?country=IN|AE|SG), used to
// pick the matching sandbox credentials via config.php's getMerchantCredentials().
$country = isset($_GET['country']) && is_string($_GET['country']) ? strtoupper($_GET['country']) : DEFAULT_MERCHANT_COUNTRY;

$clientToken = getClientToken($country); // from config.php

if (!$clientToken || !is_string($clientToken)) {
  http_response_code(500);
  echo "<h1>Unable to generate client token</h1>";
  exit;
}

// Client ID is public/non-secret (only the Client Secret must stay server-side).
// Needed in the page to load the v5 SDK bundle, which is authenticated via a
// client-id query param rather than a client token.
$publicClientId = getMerchantCredentials($country)['client_id'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Integration Pattern Comparison - PayPal v5/v6 + Apple Pay</title>
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
      /* The browser's built-in [hidden]{display:none} rule is easily beaten by
         any of our own class rules that set display explicitly (.radio-row,
         .buttons-container children, etc.) at the same specificity — author
         CSS wins ties over the user-agent stylesheet. Force it so the hidden
         attribute always wins, no matter what other class is on the element. */
      [hidden] { display: none !important; }
      body {
        margin: 0;
        min-height: 100vh;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        padding: 3rem 1rem 5rem;
      }
      .page { width: 100%; max-width: 880px; margin: 0 auto; }
      .intro { text-align: center; margin-bottom: 2.5rem; }
      .intro h1 { font-size: 1.6rem; margin: 0 0 .5rem; }
      .intro p { color: var(--muted); max-width: 640px; margin: 0 auto; }

      .section-heading {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 2.5rem 0 1rem;
        padding-bottom: .5rem;
        border-bottom: 2px solid var(--line);
      }
      .section-heading .tag {
        display: inline-block;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--brand);
        background: #e6f2fa;
        border-radius: 999px;
        padding: 3px 10px;
        margin-right: 8px;
        vertical-align: middle;
      }

      .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
      @media (max-width: 720px) { .row2 { grid-template-columns: 1fr; } }

      .card {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.06);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
      }
      .card[hidden] { display: none !important; }
      .card-label {
        font-weight: 700;
        font-size: .95rem;
        margin-bottom: 2px;
      }
      .card-desc {
        color: var(--muted);
        font-size: .82rem;
        margin-bottom: 1.1rem;
      }

      .buttons-container { display: flex; flex-direction: column; gap: 10px; }

      /* Apple Pay */
      apple-pay-mark { display: inline-block; height: 22px; vertical-align: middle; }
      .apple-pay-button {
        width: 100%;
        background: #000;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 11px 16px;
        font-size: .95rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background-color 0.15s ease;
      }
      .apple-pay-button:hover { background: #1a1a1a; }
      .apple-pay-button[hidden],
      paypal-button[hidden],
      paypal-pay-later-button[hidden],
      paypal-credit-button[hidden] { display: none !important; }

      /* Radio rows (patterns 1.2 and 2.2) */
      .radio-row {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: border-color 0.15s ease;
      }
      .radio-row:has(input:checked) { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand) inset; }
      .radio-row[data-disabled="true"] { opacity: .45; cursor: not-allowed; }
      .radio-row input[type="radio"] { accent-color: var(--brand); width: 16px; height: 16px; flex-shrink: 0; }
      .radio-row .paypal-badge {
        font-family: -apple-system, "Helvetica Neue", Arial, sans-serif;
        font-weight: 700;
        font-style: italic;
        color: #003087;
        font-size: 1rem;
      }
      .radio-row .paypal-badge span { color: #009cde; }

      .action-area { margin-top: 4px; }
      .action-area[hidden] { display: none !important; }
      #v5-buttons-1-1, #v5-buttons-1-2 { min-height: 44px; }

      .checkout-btn {
        width: 100%;
        background: #111827;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: .95rem;
        font-weight: 700;
        cursor: pointer;
        margin-top: 4px;
      }
      .checkout-btn:hover { background: #000; }
      .checkout-btn[hidden] { display: none !important; }

      .note {
        font-size: .78rem;
        color: var(--muted);
        background: #f8fafc;
        border: 1px dashed var(--line);
        border-radius: 8px;
        padding: 8px 10px;
        margin-top: 12px;
      }
      .empty-state { color: var(--muted); font-size: .85rem; }
    </style>
  </head>
  <body>
    <div class="page">
      <div class="intro">
        <h1>Apple Pay Integration Pattern Comparison</h1>
        <p>Reference/test page for the four layouts described in the Basic Apple Pay Integration Guide &mdash;
          same backend (Orders v2 create/capture), four different front-end presentations.</p>
      </div>

      <!-- ===================== SECTION 1: Combine v5 + v6 ===================== -->
      <div class="section-heading">1. Combine JS SDK v5 and v6</div>
      <div class="row2">
        <div class="card" id="card-1-1">
          <div class="card-label">1.1 Smart Payment Button (v5) + Apple Pay Mark (v6)</div>
          <div class="card-desc">v5 renders its own clickable Smart Button. Apple Pay is a separate, always-present standalone action powered by v6.</div>
          <div class="buttons-container">
            <div id="v5-buttons-1-1"></div>
            <div id="row-1-1-applepay" class="radio-row" data-disabled="true" style="cursor:default;" hidden>
              <apple-pay-mark></apple-pay-mark>
            </div>
            <button id="button-1-1-applepay" class="apple-pay-button" hidden>Checkout with Apple Pay</button>
          </div>
          <div class="note">v5 and v6 are loaded side-by-side using <code>data-namespace</code> so they don't collide on <code>window.paypal</code>.</div>
        </div>

        <div class="card" id="card-1-2">
          <div class="card-label">1.2 Radio Choice &mdash; PayPal (v5) / Apple Pay (v6)</div>
          <div class="card-desc">Buyer picks a method via radio; the real actionable button for that method appears below (browsers require the actual click target for popups/sheets).</div>
          <div>
            <label class="radio-row" for="radio-1-2-paypal">
              <input type="radio" id="radio-1-2-paypal" name="payment-1-2" value="paypal" checked />
              <span class="paypal-badge">Pay<span>Pal</span></span>
            </label>
            <label class="radio-row" id="row-1-2-applepay" for="radio-1-2-applepay" data-disabled="true" hidden>
              <input type="radio" id="radio-1-2-applepay" name="payment-1-2" value="applepay" disabled />
              <apple-pay-mark></apple-pay-mark>
            </label>
          </div>
          <div id="action-1-2-paypal" class="action-area">
            <div id="v5-buttons-1-2"></div>
          </div>
          <div id="action-1-2-applepay" class="action-area" hidden>
            <button id="button-1-2-applepay" class="apple-pay-button" hidden>Checkout with Apple Pay</button>
          </div>
        </div>
      </div>

      <!-- ===================== SECTION 2: Full migration to v6 ===================== -->
      <div class="section-heading">2. Full Migration to v6</div>
      <div class="row2">
        <div class="card" id="card-2-1">
          <div class="card-label">2.1 Buttons Combination + Apple Pay Mark (v6)</div>
          <div class="card-desc">Every eligible method renders as its own always-visible button/mark. This is the pattern used on index_js6.php.</div>
          <div id="section-2-1" class="buttons-container">
            <paypal-button type="pay" hidden></paypal-button>
            <paypal-pay-later-button hidden></paypal-pay-later-button>
            <paypal-credit-button hidden></paypal-credit-button>
            <div id="row-2-1-applepay" class="radio-row" data-disabled="true" style="cursor:default;" hidden>
              <apple-pay-mark></apple-pay-mark>
            </div>
            <button id="button-2-1-applepay" class="apple-pay-button" hidden>Checkout with Apple Pay</button>
            <div id="empty-2-1" class="empty-state" hidden>No eligible payment methods for this session.</div>
          </div>
        </div>

        <div class="card" id="card-2-2">
          <div class="card-label">2.2 Radio Buttons + Apple Pay Mark (v6)</div>
          <div class="card-desc">Buyer picks one method via radio; a single generic "Checkout" button starts whichever v6 session is selected.</div>
          <div id="section-2-2">
            <label class="radio-row" id="row-2-2-paypal" for="radio-2-2-paypal" hidden>
              <input type="radio" id="radio-2-2-paypal" name="payment-2-2" value="paypal" />
              <span class="paypal-badge">Pay<span>Pal</span></span>
            </label>
            <label class="radio-row" id="row-2-2-applepay" for="radio-2-2-applepay" hidden>
              <input type="radio" id="radio-2-2-applepay" name="payment-2-2" value="applepay" />
              <apple-pay-mark></apple-pay-mark>
            </label>
            <button id="checkout-2-2" class="checkout-btn" hidden>Checkout</button>
            <div id="empty-2-2" class="empty-state" hidden>No eligible payment methods for this session.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Expose the token + public client id to JS -->
    <script>
      window.__paypalClientToken = <?php echo json_encode($clientToken, JSON_UNESCAPED_SLASHES); ?>;
      window.__paypalPublicClientId = <?php echo json_encode($publicClientId, JSON_UNESCAPED_SLASHES); ?>;
      // Seller/merchant country selected on index.php — threaded through to
      // createPayment.php / executePayment.php so the backend uses matching credentials.
      const MERCHANT_COUNTRY = <?php echo json_encode($country, JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <!-- v6 core (namespaced so it can live alongside v5) + brand bundle for <apple-pay-mark> -->
    <script async src="https://www.sandbox.paypal.com/web-sdk/v6/core" data-namespace="paypalV6" onload="onPayPalV6Loaded()"></script>
    <script async src="https://www.sandbox.paypal.com/web-sdk/v6/brand"></script>

    <!-- v5 Smart Payment Buttons SDK (namespaced), client-id based -->
    <script async
      src="https://www.paypal.com/sdk/js?client-id=<?php echo urlencode($publicClientId); ?>&currency=USD&intent=capture&components=buttons"
      data-namespace="paypalV5"
      onload="onPayPalV5Loaded()"></script>

    <script>
      /* ---------------------------------------------------------------
       * Shared helpers
       * ------------------------------------------------------------- */
      function createOrderOnServer(sourceLabel) {
        return fetch("createPayment.php?country=" + encodeURIComponent(MERCHANT_COUNTRY), {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ source: sourceLabel }),
        }).then((res) => res.json());
      }

      function captureOrder({ orderId }) {
        if (typeof orderId !== "string" || !orderId) {
          return Promise.reject(new Error("captureOrder: invalid orderId"));
        }
        return fetch("executePayment.php?country=" + encodeURIComponent(MERCHANT_COUNTRY), {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ orderId }),
        })
          .then((res) => res.json())
          .then((details) => {
            if (details?.purchase_units?.[0]?.payments?.captures?.[0]?.status === "COMPLETED") {
              window.location.href = "successPayment.php?id=" + encodeURIComponent(details.id) + "&country=" + encodeURIComponent(MERCHANT_COUNTRY);
            } else {
              console.log("Capture response:", details);
              if (details?.details?.[0]?.issue === "INSTRUMENT_DECLINED") {
                alert("Instrument declined. Please try another payment method.");
              } else {
                alert("Payment Failure");
              }
            }
            return details;
          });
      }

      // Shared v6 session callbacks (paypal / pay later / credit / apple pay all use this shape)
      const paymentSessionOptions = {
        async onApprove(data) {
          console.log("v6 payment approved:", data);
          try {
            await captureOrder({ orderId: data.orderId });
          } catch (error) {
            console.error("v6 capture failed:", error);
          }
        },
        onCancel(data) {
          console.log("v6 payment cancelled:", data);
        },
        onError(error) {
          console.error("v6 payment error:", error);
        },
      };

      /* ---------------------------------------------------------------
       * v5 helpers (Section 1)
       * ------------------------------------------------------------- */
      function makeV5ButtonsConfig(sourceLabel) {
        return {
          style: { layout: "vertical", color: "gold", shape: "pill", label: "paypal" },
          createOrder: function () {
            return createOrderOnServer(sourceLabel).then(function (data) {
              const orderId = data?.id;
              if (typeof orderId !== "string" || !orderId) {
                throw new Error("createPayment.php did not return a valid string `id`");
              }
              return orderId; // v5 expects the raw orderID string
            });
          },
          onApprove: function (data) {
            console.log("v5 payment approved:", data);
            // v5 callback payload uses `orderID` (capital ID)
            return captureOrder({ orderId: data.orderID });
          },
          onCancel: function (data) {
            console.log("v5 payment cancelled:", data);
          },
          onError: function (err) {
            console.error("v5 payment error:", err);
          },
        };
      }

      function onPayPalV5Loaded() {
        try {
          // 1.1 — always-rendered Smart Payment Button
          window.paypalV5.Buttons(makeV5ButtonsConfig("v5-smart-button-1-1")).render("#v5-buttons-1-1");

          // 1.2 — rendered immediately since "PayPal" is the default-selected radio option
          window.paypalV5.Buttons(makeV5ButtonsConfig("v5-radio-button-1-2")).render("#v5-buttons-1-2");
        } catch (error) {
          console.error("v5 SDK render error:", error);
        }
      }

      /* ---------------------------------------------------------------
       * v6 Apple Pay — created ONCE per page load (per the integration guide,
       * steps 1-6 including canMakePayments() run once), then wired into
       * whichever of the four demo slots are eligible. Calling
       * createBasicApplePayOneTimePaymentSession()/canMakePayments() more than
       * once on the same page races Apple's on-demand SDK load — only the
       * last check tends to resolve true, and running multiple sessions in
       * parallel causes start() to error out. One session, reused everywhere.
       * ------------------------------------------------------------- */
      function wireApplePaySlot(applePaySession, opts) {
        const { rowId, buttonId, radioId, sourceLabel } = opts;

        const row = rowId ? document.getElementById(rowId) : null;
        const button = buttonId ? document.getElementById(buttonId) : null;
        const radio = radioId ? document.getElementById(radioId) : null;

        console.log(`[applepay:${sourceLabel}] wiring slot — row found: ${!!row}, button found: ${!!button}${radioId ? `, radio found: ${!!radio}` : ""}`);
        if (rowId && !row) console.warn(`[applepay:${sourceLabel}] element #${rowId} not found in DOM`);
        if (buttonId && !button) console.warn(`[applepay:${sourceLabel}] element #${buttonId} not found in DOM`);

        if (row) {
          row.removeAttribute("hidden");
          // Rows for 1.1/1.2/2.1 are marked data-disabled="true" in the HTML
          // (greyed out, opacity .45) until eligibility is confirmed — clear
          // it now that Apple Pay is actually available, otherwise the row
          // stays visually disabled even though the button below it works.
          row.removeAttribute("data-disabled");
          row.style.cursor = "";
        }
        if (radio) radio.disabled = false;
        if (button) {
          button.removeAttribute("hidden");
          button.addEventListener("click", async () => {
            try {
              const createOrderPromise = createOrderOnServer(sourceLabel).then((data) => {
                const orderId = data?.id;
                if (typeof orderId !== "string" || !orderId) {
                  throw new Error("createPayment.php did not return a valid string `id`");
                }
                return { orderId };
              });
              await applePaySession.start({}, createOrderPromise);
            } catch (error) {
              console.error(`Apple Pay start error (${sourceLabel}):`, error);
            }
          });
        }
      }

      /* ---------------------------------------------------------------
       * Section 1.2 — radio toggling between v5 PayPal action and v6 Apple Pay action
       * ------------------------------------------------------------- */
      function wireRadioToggle_1_2() {
        const paypalRadio = document.getElementById("radio-1-2-paypal");
        const applePayRadio = document.getElementById("radio-1-2-applepay");
        const paypalAction = document.getElementById("action-1-2-paypal");
        const applePayAction = document.getElementById("action-1-2-applepay");

        function sync() {
          const isApplePay = applePayRadio && applePayRadio.checked;
          paypalAction.toggleAttribute("hidden", !!isApplePay);
          applePayAction.toggleAttribute("hidden", !isApplePay);
        }
        paypalRadio?.addEventListener("change", sync);
        applePayRadio?.addEventListener("change", sync);
        sync();
      }
      wireRadioToggle_1_2();

      /* ---------------------------------------------------------------
       * Section 2.1 — buttons combination (paypal / pay later / credit / apple pay)
       * ------------------------------------------------------------- */
      async function setUpSection21(sdkInstance, paymentMethods, sharedApplePaySession, applePayAvailable) {
        const container = document.getElementById("section-2-1");
        let anyEligible = false;

        if (paymentMethods.isEligible("paypal")) {
          anyEligible = true;
          const paypalSession = sdkInstance.createPayPalOneTimePaymentSession(paymentSessionOptions);
          const btn = container.querySelector("paypal-button");
          btn.removeAttribute("hidden");
          btn.addEventListener("click", async () => {
            try {
              const createOrderPromise = createOrderOnServer("v6-buttons-2-1-paypal").then((data) => {
                const orderId = data?.id;
                if (typeof orderId !== "string" || !orderId) throw new Error("invalid order id");
                return { orderId };
              });
              await paypalSession.start({ presentationMode: "auto" }, createOrderPromise);
            } catch (error) {
              console.error("PayPal (2.1) start error:", error);
            }
          });
        }

        if (paymentMethods.isEligible("paylater")) {
          anyEligible = true;
          const details = paymentMethods.getDetails("paylater");
          const payLaterSession = sdkInstance.createPayLaterOneTimePaymentSession(paymentSessionOptions);
          const btn = container.querySelector("paypal-pay-later-button");
          btn.productCode = details.productCode;
          btn.countryCode = details.countryCode;
          btn.removeAttribute("hidden");
          btn.addEventListener("click", async () => {
            try {
              const createOrderPromise = createOrderOnServer("v6-buttons-2-1-paylater").then((data) => {
                const orderId = data?.id;
                if (typeof orderId !== "string" || !orderId) throw new Error("invalid order id");
                return { orderId };
              });
              await payLaterSession.start({ presentationMode: "auto" }, createOrderPromise);
            } catch (error) {
              console.error("Pay Later (2.1) start error:", error);
            }
          });
        }

        if (paymentMethods.isEligible("credit")) {
          anyEligible = true;
          const details = paymentMethods.getDetails("credit");
          const creditSession = sdkInstance.createPayPalCreditOneTimePaymentSession(paymentSessionOptions);
          const btn = container.querySelector("paypal-credit-button");
          btn.countryCode = details.countryCode;
          btn.removeAttribute("hidden");
          btn.addEventListener("click", async () => {
            try {
              const createOrderPromise = createOrderOnServer("v6-buttons-2-1-credit").then((data) => {
                const orderId = data?.id;
                if (typeof orderId !== "string" || !orderId) throw new Error("invalid order id");
                return { orderId };
              });
              await creditSession.start({ presentationMode: "auto" }, createOrderPromise);
            } catch (error) {
              console.error("Credit (2.1) start error:", error);
            }
          });
        }

        if (applePayAvailable && sharedApplePaySession) {
          anyEligible = true;
          wireApplePaySlot(sharedApplePaySession, {
            rowId: "row-2-1-applepay",
            buttonId: "button-2-1-applepay",
            sourceLabel: "v6-buttons-2-1-applepay",
          });
        }

        if (!anyEligible) {
          document.getElementById("empty-2-1").removeAttribute("hidden");
        }
      }

      /* ---------------------------------------------------------------
       * Section 2.2 — radio group + single shared "Checkout" button
       * ------------------------------------------------------------- */
      async function setUpSection22(sdkInstance, paymentMethods, sharedApplePaySession, applePayAvailable) {
        const rowPaypal = document.getElementById("row-2-2-paypal");
        const radioPaypal = document.getElementById("radio-2-2-paypal");
        const rowApplePay = document.getElementById("row-2-2-applepay");
        const radioApplePay = document.getElementById("radio-2-2-applepay");
        const checkoutBtn = document.getElementById("checkout-2-2");

        let paypalSession = null;
        let applePaySession = null;

        if (paymentMethods.isEligible("paypal")) {
          paypalSession = sdkInstance.createPayPalOneTimePaymentSession(paymentSessionOptions);
          rowPaypal.removeAttribute("hidden");
        }

        if (applePayAvailable && sharedApplePaySession) {
          applePaySession = sharedApplePaySession;
          rowApplePay.removeAttribute("hidden");
        }

        if (!paypalSession && !applePaySession) {
          document.getElementById("empty-2-2").removeAttribute("hidden");
          return;
        }

        // Pre-select the first available option
        if (paypalSession) {
          radioPaypal.checked = true;
        } else if (applePaySession) {
          radioApplePay.checked = true;
        }

        checkoutBtn.removeAttribute("hidden");
        checkoutBtn.addEventListener("click", async () => {
          const selected = document.querySelector('input[name="payment-2-2"]:checked')?.value;
          try {
            if (selected === "paypal" && paypalSession) {
              const createOrderPromise = createOrderOnServer("v6-radio-2-2-paypal").then((data) => {
                const orderId = data?.id;
                if (typeof orderId !== "string" || !orderId) throw new Error("invalid order id");
                return { orderId };
              });
              await paypalSession.start({ presentationMode: "auto" }, createOrderPromise);
            } else if (selected === "applepay" && applePaySession) {
              const createOrderPromise = createOrderOnServer("v6-radio-2-2-applepay").then((data) => {
                const orderId = data?.id;
                if (typeof orderId !== "string" || !orderId) throw new Error("invalid order id");
                return { orderId };
              });
              await applePaySession.start({}, createOrderPromise);
            } else {
              alert("Please select a payment method.");
            }
          } catch (error) {
            console.error("Checkout error (2.2):", error);
          }
        });
      }

      /* ---------------------------------------------------------------
       * v6 bootstrap — drives sections 1.1 (apple pay slot), 1.2 (apple pay slot),
       * 2.1 (buttons combination), and 2.2 (radio group)
       * ------------------------------------------------------------- */
      async function onPayPalV6Loaded() {
        const clientToken = window.__paypalClientToken;
        try {
          const clientMetadataId = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : undefined;

          const sdkInstance = await window.paypalV6.createInstance({
            clientToken,
            clientMetadataId,
            components: ["paypal-payments", "applepay-payments"],
            pageType: "checkout",
          });

          const paymentMethods = await sdkInstance.findEligibleMethods({
            currencyCode: "USD",
            //countryCode: "SG",
          });

          // Apple Pay: create the session and run canMakePayments() exactly ONCE
          // per page load (as the guide specifies), then reuse it across every
          // demo slot below. Creating/checking it more than once on the same
          // page races Apple's on-demand SDK load and causes start() to fail.
          const isApplePayEligible = paymentMethods.isEligible("basic_apple_pay");
          console.log("[applepay] isEligible('basic_apple_pay'):", isApplePayEligible);

          let applePaySession = null;
          let applePayAvailable = false;
          if (isApplePayEligible) {
            applePaySession = sdkInstance.createBasicApplePayOneTimePaymentSession(paymentSessionOptions);
            try {
              applePayAvailable = await applePaySession.canMakePayments();
              console.log("[applepay] canMakePayments():", applePayAvailable);
            } catch (error) {
              console.error("[applepay] canMakePayments() threw:", error);
            }
          }

          if (applePayAvailable) {
            // 1.1 — standalone Apple Pay slot next to the always-rendered v5 button.
            // Each slot is wrapped in its own try/catch so one bad selector can't
            // abort the rest of initialization (which would explain later slots
            // never getting wired).
            try {
              wireApplePaySlot(applePaySession, {
                rowId: "row-1-1-applepay",
                buttonId: "button-1-1-applepay",
                sourceLabel: "v6-standalone-1-1-applepay",
              });
            } catch (error) {
              console.error("[applepay] failed wiring slot 1.1:", error);
            }

            // 1.2 — Apple Pay as the second radio option
            try {
              wireApplePaySlot(applePaySession, {
                rowId: "row-1-2-applepay",
                buttonId: "button-1-2-applepay",
                radioId: "radio-1-2-applepay",
                sourceLabel: "v6-radio-1-2-applepay",
              });
              wireRadioToggle_1_2();
            } catch (error) {
              console.error("[applepay] failed wiring slot 1.2:", error);
            }
          } else {
            console.log("[applepay] not available on this device/browser — all Apple Pay slots stay hidden.");
          }

          // 2.1 — full buttons combination
          try {
            await setUpSection21(sdkInstance, paymentMethods, applePaySession, applePayAvailable);
          } catch (error) {
            console.error("[applepay] failed setting up section 2.1:", error);
          }

          // 2.2 — radio group + shared Checkout button
          try {
            await setUpSection22(sdkInstance, paymentMethods, applePaySession, applePayAvailable);
          } catch (error) {
            console.error("[applepay] failed setting up section 2.2:", error);
          }
        } catch (error) {
          console.error("v6 SDK initialization error:", error);
        }
      }
    </script>
  </body>
</html>
