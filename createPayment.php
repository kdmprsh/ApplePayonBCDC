<?php
include('config.php');
// Seller/merchant country threaded through from index_js6.php / index_js6_1.php
// (?country=IN|AE|SG), so the order is created with matching sandbox credentials.
$country = isset($_GET['country']) && is_string($_GET['country']) ? strtoupper($_GET['country']) : DEFAULT_MERCHANT_COUNTRY;
$access_token = getAccessToken($country); //echo $access_token;

/********** Create payment Pay Load ****/
/**"soft_descriptor": "HighFashions",
"return_url":"http://localhost/return",
	"cancel_url":"http://localhost/return",
	 "invoice_id":"1234567890",**/
/*
,
      "shipping": {
        "address": {
            "address_line_1": "2211 N First Street",
            "address_line_2": "Building 17",
            "admin_area_2": "San Jose",
            "admin_area_1": "CA",
            "postal_code": "95131",
            "country_code": "US"
        }
        }
        
*/
function generateInvoiceId(): string {
  // Example: INV-20251106-153045-3f9a2b7c8d
  return 'INV-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(6)), 0, 10);
}
$invoiceId = generateInvoiceId();
$order = [
      'intent' => 'CAPTURE',
  'purchase_units' => [[
    'description' => 'ApplePay Testing via BCDC',
    'custom_id'   => 'CUST-101',
    'invoice_id'  => $invoiceId, // <— unique per request
    'soft_descriptor' => 'SD_2019',
    'amount' => [
      'currency_code' => 'USD',
      'value' => '1.00',
    ],
  ]],
    ];

$postData = json_encode($order, JSON_UNESCAPED_SLASHES);    
//Create Payment method

$res = getApprovalURL($access_token, $postData);
print json_encode($res);
/*$approval_url = $res['links']['1']['href'];
$token = substr($approval_url, -20);
print json_encode($token); */
//var_dump($res); exit;
?>