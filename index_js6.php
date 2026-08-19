<?php
//include('config.php');

declare(strict_types=1);
require_once __DIR__ . '/config.php';

// Prevent caching so a fresh token is generated each load
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Seller/merchant country selected on index.php (?country=IN|AE|SG), used to
// pick the matching sandbox credentials via config.php's getMerchantCredentials().
$country = isset($_GET['country']) && is_string($_GET['country']) ? strtoupper($_GET['country']) : DEFAULT_MERCHANT_COUNTRY;

$clientToken = getClientToken($country); // your function from config.php

// Basic guard if token generation failed
if (!$clientToken || !is_string($clientToken)) {
  http_response_code(500);
  echo "<h1>Unable to generate client token</h1>";
  exit;
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>One-Time Payment - Recommended Integration - PayPal JavaScript SDK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
      :root {
        --brand: #0070ba;
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
      }

      .page {
        width: 100%;
        max-width: 460px;
      }

      .card {
        background: var(--card);
        border-radius: var(--radius);
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
        padding: 2rem;
      }

      h1 {
        font-size: 1.35rem;
        line-height: 1.4;
        margin: 0 0 1.5rem;
        text-align: center;
        color: var(--text);
      }

      .subtitle {
        text-align: center;
        color: var(--muted);
        font-size: 0.9rem;
        margin: -1rem 0 1.5rem;
      }

      .buttons-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      /* Apple Pay */
      .applepay-container {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        margin-top: 4px;
      }

      .applepay-mark-row {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 2px;
      }

      apple-pay-mark {
        display: block;
        height: 24px;
      }

      .apple-pay-button {
        width: 100%;
        background: #000;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background-color 0.15s ease;
      }

      .apple-pay-button:hover { background: #1a1a1a; }
      .apple-pay-button:active { background: #333; }

      .apple-pay-button[hidden],
      .applepay-container[hidden],
      paypal-button[hidden],
      paypal-pay-later-button[hidden],
      paypal-credit-button[hidden],
      paypal-basic-card-container[hidden],
      paypal-basic-card-button[hidden] {
        display: none !important;
      }

      .footnote {
        margin-top: 1.5rem;
        text-align: center;
        font-size: 0.75rem;
        color: var(--muted);
      }
    </style>
  </head>
  <body>
    <div class="page">
      <div class="card">
        <h1>One-Time Payment</h1>
        <div class="subtitle">Recommended Integration &mdash; PayPal JavaScript SDK v6</div>
        <paypal-message></paypal-message>
        <div class="buttons-container">
          <!-- All buttons are hidden until eligibility is confirmed -->
          <paypal-button type="pay" hidden></paypal-button>
          <paypal-pay-later-button hidden></paypal-pay-later-button>
          <paypal-credit-button hidden></paypal-credit-button>
          <!-- PayPal Guest (basic credit/debit card, v5's black "Debit or Credit Card" /
               FUNDING.CARD equivalent): renders as a PayPal-hosted button; clicking it opens
               the guest card entry modal/popup hosted by PayPal, not inline on this page. -->
          <paypal-basic-card-container hidden>
            <paypal-basic-card-button id="paypal-basic-card-button" hidden></paypal-basic-card-button>
          </paypal-basic-card-container>
          <!-- Basic Apple Pay: hidden until isEligible() + canMakePayments() both succeed -->
          <div id="applepay-container" class="applepay-container" hidden>
            <div class="applepay-mark-row">
              <apple-pay-mark></apple-pay-mark>
            </div>
            <button id="apple-pay-button" type="button" class="apple-pay-button" hidden>
              Checkout with Apple Pay
            </button>
          </div>


        </div>
        <div class="footnote">Secure checkout powered by PayPal</div>
      </div>
    </div>

    <!-- Expose the token to JS safely -->
    <script>
      // PHP injects the fresh client token here:
      window.__paypalClientToken = <?php echo json_encode($clientToken, JSON_UNESCAPED_SLASHES); ?>;
      // Seller/merchant country selected on index.php — threaded through to
      // createPayment.php / executePayment.php so the backend uses matching credentials.
      const MERCHANT_COUNTRY = <?php echo json_encode($country, JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <!--<script src="app.js"></script>-->

    <!-- Load PayPal SDK core (createInstance, paypal-payments, applepay-payments) -->
    <script async src="https://www.sandbox.paypal.com/web-sdk/v6/core" onload="onPayPalWebSdkLoaded()">
      </script>
    <!-- Load the PayPal brand bundle for the official <apple-pay-mark> element -->
    <script async src="https://www.sandbox.paypal.com/web-sdk/v6/brand"></script>
    <!-- Load the PayPal Messages script
      <script src="https://www.paypal.com/web-sdk/v6/paypal-messages"></script>-->
        <script>
      async function onPayPalWebSdkLoaded() {

      const clientToken = window.__paypalClientToken;
      console.log("Client Token from Server: ", clientToken);

  try {

    // Get client token for authentication
    // Create PayPal SDK instance
    // clientMetadataId: unique per checkout session/page load (recommended for Apple Pay)
    const clientMetadataId = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : undefined;

    const sdkInstance = await window.paypal.createInstance({
      clientToken,
      clientMetadataId,
      components: ["paypal-payments", "applepay-payments", "paypal-guest-payments"],
      //components: ["paypal-messages"],
      pageType: "checkout",
    });

    // Check eligibility for all payment methods
    const paymentMethods = await sdkInstance.findEligibleMethods({
      currencyCode: "USD",
      //countryCode: "SG",
      //amount: "11.00"
    });

    // Set up PayPal button if eligible
    if (paymentMethods.isEligible("paypal")) {
      setUpPayPalButton(sdkInstance);
    }


    // Set up Pay Later button if eligible
    if (paymentMethods.isEligible("paylater")) {
      const payLaterPaymentMethodDetails = paymentMethods.getDetails("paylater");
      setUpPayLaterButton(sdkInstance, payLaterPaymentMethodDetails);
    }//else{
      //console.log("PL Button not eligible.")
    //}

    // Set up PayPal Credit button if eligible
    if (paymentMethods.isEligible("credit")) {
      const paypalCreditPaymentMethodDetails = paymentMethods.getDetails("credit");
      setUpPayPalCreditButton(sdkInstance, paypalCreditPaymentMethodDetails);
    }

    // Set up Basic Apple Pay if eligible (merchant/region enabled + currency/country supported)
    if (paymentMethods.isEligible("basic_apple_pay")) {
      setUpApplePayButton(sdkInstance);
    } else {
      console.log("Apple Pay not eligible for this merchant/region/currency.");
    }

    // Set up the PayPal Guest (basic credit/debit card) button. Per PayPal's
    // v6 reference integration, this is wired up unconditionally rather than
    // gated on findEligibleMethods() — actual availability depends on
    // risk/location factors resolved when the buyer clicks the button.
    setUpGuestCardButton(sdkInstance);
  } catch (error) {
    console.error("SDK initialization error:", error);
  }
}

// Shared order-creation helper: all payment methods (PayPal, Pay Later, Credit,
// Apple Pay) create the order against the seller/merchant country selected on
// index.php, so the backend uses the matching sandbox credentials.
function createOrder(sourceLabel) {
  return fetch("createPayment.php?country=" + encodeURIComponent(MERCHANT_COUNTRY), {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ source: sourceLabel }),
  })
    .then((res) => res.json())
    .then((data) => {
      const orderId = data?.id;
      if (typeof orderId !== "string" || !orderId) {
        throw new Error("createPayment.php did not return a valid string `id`");
      }
      return { orderId };
    });
}

function captureOrder({ orderId }) {
    if (typeof orderId !== "string" || !orderId) {
      return Promise.reject(new Error("captureOrder: invalid orderId"));
    }
    return fetch("executePayment.php?country=" + encodeURIComponent(MERCHANT_COUNTRY), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ orderId })
    })
      .then(function(res) {
					console.log("Within Capture order function: ",res);
                    return res.json();
                    console.log("Within Capture order function...1: ",res.json());
                }).then(function(details) {
					console.log("json response" + JSON.stringify(details));
					 if(details.purchase_units[0].payments.captures[0].status=="COMPLETED") {
						 //alert('payment success');
             //Console.log('payment success');
                        window.location.href="successPayment.php?id=" + encodeURIComponent(details.id) + "&country=" + encodeURIComponent(MERCHANT_COUNTRY);
                    } else {
						console.log("error" + details);
                        if(details.details[0].issue=="INSTRUMENT_DECLINED") {
                            return actions.restart();
                        } else {
                            alert("Payment Failure");
                        }
                    }
                });
  }

// Shared payment session options for all payment methods (PayPal, Pay Later, Credit, Apple Pay)
const paymentSessionOptions = {
  // Called when user approves a payment
  async onApprove(data) {
    console.log("Payment approved:", data);
    try {
      console.log("Data of Approved transaction:",data);
      const orderData = await captureOrder({
        orderId: data.orderId,
      });
      console.log("Payment captured successfully:", orderData);
    } catch (error) {
      console.error("Payment capture failed:", error);
    }


  },

  // Called when user cancels a payment
  onCancel(data) {
    console.log("Payment cancelled:", data);
  },

  // Called when an error occurs during payment
  onError(error) {
    console.error("Payment error:", error);
  },
};

// Set up standard PayPal button
async function setUpPayPalButton(sdkInstance) {
  const paypalPaymentSession = sdkInstance.createPayPalOneTimePaymentSession(paymentSessionOptions);

  const paypalButton = document.querySelector("paypal-button");
  paypalButton.removeAttribute("hidden");

  paypalButton.addEventListener("click", async () => {
    try {
      // Create the order against the selected seller/merchant country.
      const createOrderPromise = createOrder("paypal");

      // Start the PayPal payment session
      await paypalPaymentSession.start(
        { presentationMode: "auto" }, // auto-detect popup vs redirect
        createOrderPromise
      );
    } catch (error) {
      console.error("PayPal payment start error:", error);
    }
  });
}


// Set up Pay Later button
async function setUpPayLaterButton(sdkInstance, payLaterPaymentMethodDetails) {
  const payLaterPaymentSession = sdkInstance.createPayLaterOneTimePaymentSession(
    paymentSessionOptions
  );

  const { productCode, countryCode } = payLaterPaymentMethodDetails;
  const payLaterButton = document.querySelector("paypal-pay-later-button");

  // Configure button with Pay Later specific details
  payLaterButton.productCode = productCode;
  payLaterButton.countryCode = countryCode;
  payLaterButton.removeAttribute("hidden");

  payLaterButton.addEventListener("click", async () => {
    try {
      await payLaterPaymentSession.start(
        { presentationMode: "auto" },
        createOrder("paylater"),
      );
    } catch (error) {
      console.error("Pay Later payment start error:", error);
    }
  });
}

// Set up PayPal Credit button
async function setUpPayPalCreditButton(sdkInstance, paypalCreditPaymentMethodDetails) {
  const paypalCreditPaymentSession = sdkInstance.createPayPalCreditOneTimePaymentSession(
    paymentSessionOptions
  );

  const { countryCode } = paypalCreditPaymentMethodDetails;
  const paypalCreditButton = document.querySelector("paypal-credit-button");

  // Configure button with PayPal Credit specific details
  paypalCreditButton.countryCode = countryCode;
  paypalCreditButton.removeAttribute("hidden");

  paypalCreditButton.addEventListener("click", async () => {
    try {
      await paypalCreditPaymentSession.start(
        { presentationMode: "auto" },
        createOrder("credit"),
      );
    } catch (error) {
      console.error("PayPal Credit payment start error:", error);
    }
  });
}

// Set up Basic Apple Pay
// Steps: create session -> confirm device support (canMakePayments) -> only then reveal
// the Apple Pay mark + button -> on click, create order -> start the session.
async function setUpApplePayButton(sdkInstance) {
  const applePayPaymentSession = sdkInstance.createBasicApplePayOneTimePaymentSession(
    paymentSessionOptions
  );

  let canMakePayments = false;
  try {
    canMakePayments = await applePayPaymentSession.canMakePayments();
  } catch (error) {
    console.error("Apple Pay canMakePayments error:", error);
  }

  if (!canMakePayments) {
    // Browser isn't Safari, or device has no cards provisioned in Wallet.
    console.log("Apple Pay is not available on this device/browser.");
    return;
  }

  const applePayContainer = document.getElementById("applepay-container");
  const applePayButton = document.getElementById("apple-pay-button");

  applePayContainer.removeAttribute("hidden");
  applePayButton.removeAttribute("hidden");

  applePayButton.addEventListener("click", async () => {
    try {
      // Same create-order endpoint used by the PayPal button; intent must be CAPTURE.
      const createOrderPromise = createOrder("applepay");

      // start() must be invoked synchronously within this click handler so the
      // Apple Pay sheet isn't blocked as a popup; the order-creation promise
      // is passed in and resolved once the backend responds.
      await applePayPaymentSession.start({}, createOrderPromise);
    } catch (error) {
      console.error("Apple Pay payment start error:", error);
    }
  });
}

// Set up the PayPal Guest button (basic credit/debit card — the v6 equivalent
// of v5's black "Debit or Credit Card" / FUNDING.CARD button). Unlike Card
// Fields, this does not render fields inline: clicking the button opens a
// PayPal-hosted popup/modal containing the card entry form, billing address,
// and pay button — matching the v5 SPB guest checkout experience.
async function setUpGuestCardButton(sdkInstance) {
  const paypalGuestPaymentSession = sdkInstance.createPayPalGuestOneTimePaymentSession({
    onApprove: async (data) => {
      console.log("Guest card payment approved:", data);
      try {
        const orderData = await captureOrder({ orderId: data.orderId });
        console.log("Guest card payment captured successfully:", orderData);
      } catch (error) {
        console.error("Guest card payment capture failed:", error);
      }
    },
    onCancel(data) {
      console.log("Guest card payment cancelled:", data);
    },
    onComplete(data) {
      console.log("Guest card payment complete:", data);
    },
    onError(error) {
      console.error("Guest card payment error:", error);
    },
    onWarn(data) {
      // Fires when the buyer hits a card/address/name validation error after
      // submitting the form inside PayPal's hosted popup.
      console.warn("Guest card payment warning:", data);
    },
  });

  const guestCardContainer = document.querySelector("paypal-basic-card-container");
  const guestCardButton = document.getElementById("paypal-basic-card-button");

  guestCardContainer.removeAttribute("hidden");
  guestCardButton.removeAttribute("hidden");

  guestCardButton.addEventListener("click", async () => {
    try {
      // Same create-order endpoint used by the other buttons; intent must be CAPTURE.
      const createOrderPromise = createOrder("guestcard");

      await paypalGuestPaymentSession.start(
        { presentationMode: "auto" },
        createOrderPromise
      );
    } catch (error) {
      console.error("Guest card payment start error:", error);
    }
  });
}

    </script>
  </body>
</html>
