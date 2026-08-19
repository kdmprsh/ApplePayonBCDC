<?php
/*include('config.php');
$access_token = getAccessToken();
$request_body = file_get_contents('php://input');

if(isset($_REQUEST['orderId'])){
	$PAYMENT_ID=$_REQUEST['orderId'];
}else{
$data = json_decode($request_body); //print_r($data); 
	$PAYMENT_ID = $PAYMENT_ID?$data->orderID:$_REQUEST['orderId'];
}
$res = executePayment($access_token, '',$PAYMENT_ID);
print json_encode($res);	*/

//Commented on 06th Nov 2025
/*include('config.php');
$access_token = getAccessToken();
$request_body = file_get_contents('php://input');
if(isset($_GET['orderId'])){
	$PAYMENT_ID=$_GET['orderId'];
}else{
$data = json_decode($request_body);
//print_r($data);
$PAYMENT_ID=$data->orderId; // JS SDK
//$PAYMENT_ID=$_POST[orderID]; // Jsv4
//$PAYMENT_ID=$_GET['paymentId'];
//$PAYER_ID=$data->payerID;
//$PAYER_ID=$_GET['PayerID'];
}
$postData = '{

}';
$res = executePayment($access_token, $postData,$PAYMENT_ID);
print json_encode($res);*/

declare(strict_types=1);

/**
 * Execute capture for a PayPal order and return JSON only.
 * - Accepts orderId via GET (?orderId=...) OR JSON body { "orderId": "..." }.
 * - Emits exactly one JSON document (no stray HTML/whitespace).
 */

ini_set('display_errors', '0'); // never print HTML warnings/notices
header('Content-Type: application/json; charset=utf-8');

// Capture any accidental output (from config.php or includes) and discard later
ob_start();

$out = null;

try {
    require_once __DIR__ . '/config.php'; // make sure this file does NOT echo anything

    // 1) Parse input
    $raw = file_get_contents('php://input') ?: '';
    $json = json_decode($raw, true); // assoc array (not object)

    // Prefer explicit GET ?orderId=..., else JSON body { orderId: ... }
    $orderId = null;
    if (isset($_GET['orderId']) && is_string($_GET['orderId']) && $_GET['orderId'] !== '') {
        $orderId = $_GET['orderId'];
    } elseif (is_array($json) && isset($json['orderId']) && is_string($json['orderId']) && $json['orderId'] !== '') {
        $orderId = $json['orderId'];       // v6 sends orderId (lowercase d)
    }

    if (!$orderId) {
        http_response_code(400);
        $out = ['error' => true, 'message' => 'Missing or invalid orderId'];
    } else {
        // Seller/merchant country threaded through from index_js6.php / index_js6_1.php
        // (?country=IN|AE|SG), so capture uses the same credentials the order was created with.
        $countryCode = isset($_GET['country']) && is_string($_GET['country']) ? strtoupper($_GET['country']) : DEFAULT_MERCHANT_COUNTRY;

        // 2) Get access token (your helper)
        $access_token = getAccessToken($countryCode);
        if (!$access_token) {
            http_response_code(500);
            $out = ['error' => true, 'message' => 'Unable to obtain access token'];
        } else {
            // 3) Execute capture (your helper). For Orders v2 /capture, an empty JSON body is typical: {}
            $postData = '{}';
            $res = executePayment($access_token, $postData, $orderId);

            // Ensure $res is an array; if it's JSON string, decode it
            if (is_string($res)) {
                $decoded = json_decode($res, true);
                $out = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['raw' => $res];
            } else {
                $out = $res; // assume helper already returns an array
            }
        }
    }
} catch (Throwable $e) {
    error_log('executePayment error: ' . $e->getMessage());
    http_response_code(500);
    $out = ['error' => true, 'message' => 'Server error'];
}

// 4) Discard any stray output and emit only JSON
ob_end_clean();
echo json_encode($out, JSON_UNESCAPED_SLASHES);
exit;



?>