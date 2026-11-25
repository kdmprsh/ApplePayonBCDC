<?php
    /*
        * Config for PayPal specific values
    */

    //Whether Sandbox environment is being used, Keep it true for testing
    define("SANDBOX_FLAG", true);


    //PayPal REST API endpoints
    define("SANDBOX_ENDPOINT", "https://api-m.sandbox.paypal.com");
    //define("SANDBOX_ENDPOINT", "https://api.stage2d0515.qa.paypal.com:11888");
    define("LIVE_ENDPOINT", "https://api-m.paypal.com");

    //Merchant ID
    //define("MERCHANT_ID","E9GCL5FX4TU2C");

    // Seller/merchant country used when the page is loaded without an explicit
    // ?country= selection (see index.php's country picker).
    define("DEFAULT_MERCHANT_COUNTRY", "SG");

    // Sandbox Client ID / Secret per seller/merchant country. Selected on
    // index.php and threaded through as ?country=IN|AE|SG to the pages and
    // backend endpoints, which resolve credentials via getMerchantCredentials().
    $MERCHANT_CREDENTIALS = [
        // PayPal REST App IN Merchant (Basic ApplePay) (sb-59jyk50838267@business.example.com / AKW3E5QYBKJFN / 5322134387254530393)
        "IN" => [
            "client_id"     => "AT_W3sUjdaNygQpHgVCSK1oL7ijAVFOdwfC0LYUox_XAnk3nA30HAOnx7YzhK4JedHtgtS8Jxrxnz2su",
            "client_secret" => "EObfS7YktqK8eQFkqfIS247DYVHRBW9SFQO9k0y9eNfxLLb94-q1Mk9915mGytEWaINY7YHSIFbLYP9_",
        ],
        // PayPal REST App UAE Merchant (sb-47iakt33259923@business.example.com / 5331081855009800909 / CTQXUT6GPHXTW)
        "AE" => [
            "client_id"     => "ASBeI30CVv6YmdEM8fdeev5WTePPb9nI4WtYCji6kbVWLM0ZzQy5-KcL1Yns-CwRb7H7MMB9bqKQicdI",
            "client_secret" => "EE0SOqBN42_qFYqNRjaAMYUagHSZXPo5bjIHMMDrdqHEco5koPScmjS0CaxXK8xV_ar9TwupZn2M4tR8",
        ],
        // PayPal REST App SG Merchant (Basic ApplePay) (sg_seller_appay@paresh.com / 2ZT622GH64U2Q)
        "SG" => [
            "client_id"     => "AaC8rG_01y0isV79HWx5QK2_cPLC58JE2RekM7SOF1ZNGWe6JNIf0kXgnWIbRJhupSMn3B19SqqD5ZmS",
            "client_secret" => "EHslNykrNNlJrXA4GowLSmNRat97tRWiOaARGqh-qoIkXKgKoJfJW9QLuYWkTCNbD7GPeRfT1JPCSmR4",
        ],
		// PayPal REST App ZA Merchant (Basic ApplePay) (southafrica_applepay@paresh.com / NG4VT9LAX88UQ)
        "ZA" => [
            "client_id"     => "BAANGmH-lUY0NunVb0GXKulJuIc8PFu7UexcdwX319jvNiTTbVNOPmXFYq-Vyf9lTvTSMUDpnb3aRo30I8",
            "client_secret" => "EAvIkNjQi1sUC-R4MeP78i5uIitsnQk5T2VIVGNvrMgDTQdtirSTyUXqgoVy4H-6PL8Pjns7SvtZIc5U",
        ],
		// PayPal REST App MA Merchant (Morocco Seller) (morocco_business@paresh.com / 77MAYVTHFSKNU)
        "MA" => [
            "client_id"     => "BAAUeoyI2hmRYSYKkeSc05vwGX-JoUi4fZ5XWqPHMQbXeO9rz4MZYSR8srJo9M8aMm6uxiZi8EzJA9UNDg",
            "client_secret" => "EB2ZhQhERd9uOowhUuKV1DwWjGRpYSwj6e4OiA0xPyWiBSnMW_YGbZ5-cviyQDI0ZW1Oe3CereH8fL7o",
        ],
    ];

    /**
     * Resolve sandbox Client ID/Secret for a given seller/merchant country code.
     * Falls back to DEFAULT_MERCHANT_COUNTRY when $countryCode is missing or
     * doesn't match a configured merchant.
     */
    function getMerchantCredentials(?string $countryCode = null): array {
        global $MERCHANT_CREDENTIALS;
        $countryCode = strtoupper($countryCode ?? DEFAULT_MERCHANT_COUNTRY);
        return $MERCHANT_CREDENTIALS[$countryCode] ?? $MERCHANT_CREDENTIALS[DEFAULT_MERCHANT_COUNTRY];
    }

    //Environments -Sandbox and Production/Live
    define("SANDBOX_ENV", "sandbox");
    define("LIVE_ENV", "production");


    //ButtonSource Tracker Code
    define("SBN_CODE","PP-ECJSv4Sample");

	$randomnumber=mt_rand();
	define("UUID",$randomnumber);

	/*
	* Contains common useful functions

	* Purpose: 	To make a cURL call to REST API
	* Inputs:
	*		curlServiceUrl    : the service URL for the REST api
	*       curlHeader        : the header parameters specific to the REST api call
	*       curlPostData      : the post parameters encoded in the form required by the api (json_encode or http_build_query)
	* Returns:
	*		array["http_code"]   : the http status code
	*		array["jason"]       : the response string
	*/
function curlCall($curlServiceUrl, $curlHeader, $curlPostData=NULL) {

	// response container
	$resp = array(
		"http_code" => 0,
		"jason"     => ""
	);

	//set the cURL parameters
	$ch = curl_init($curlServiceUrl);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);

	//turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	curl_setopt($ch, CURLOPT_SSLVERSION , 'CURL_SSLVERSION_TLSv1_2');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	//curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeader);

	if(isset($curlPostData)){
	if(!is_null($curlPostData)) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPostData);
	}
	}else{
		curl_setopt($ch, CURLOPT_HTTPGET, true);
	}
	//getting response from server
	$response = curl_exec($ch);

	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	curl_close($ch); // close cURL handler

	// some kind of an error happened
	if (empty($response)) {
		return $resp;
	}

	$resp["http_code"] = $http_code;
	$resp["json"] = json_decode($response, true);

	return $resp;
}


/**
 * Prevents Cross-Site Scripting Forgery
 * @return boolean
 */
function verify_nonce() {
	if( isset($_GET['csrf']) && $_GET['csrf'] == $_SESSION['csrf'] ) {
		return true;
	}
	if( isset($_POST['csrf']) && $_POST['csrf'] == $_SESSION['csrf'] ) {
		return true;
	}
	return false;
}

/************** Access Token ****************/
function getAccessToken($countryCode = null){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v1/oauth2/token";
	$creds = getMerchantCredentials($countryCode);
	$clientId = $creds['client_id'];
	$clientSecret = $creds['client_secret'];
	$curlHeader = array(
		 "Content-type" => "application/json",
		 "Authorization: Basic ". base64_encode( $clientId .":". $clientSecret)

		 );
	$postData = array(
		 "grant_type" => "client_credentials",
		 "response_type" => "id_token" //id_token, client_token
		 );
//var_dump($curlHeader);
	$curlPostData = http_build_query($postData);
	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $curlPostData);
	$access_token = $curlResponse['json']['access_token'];
	//$id_token = $curlResponse['json']['id_token'];	commented on 05th Nov25
	//$access_token = $curlResponse['access_token'];
    return $access_token;
	//return $id_token; commented on 05th Nov25
	//echo($access_token);


}

/************** Client Token (used for Web SDK v6 createInstance, incl. Apple Pay) ****************/
function getClientToken($countryCode = null){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v1/oauth2/token";
	$creds = getMerchantCredentials($countryCode);
	$clientId = $creds['client_id'];
	$clientSecret = $creds['client_secret'];
	$curlHeader = array(
		 "Content-type" => "application/json",
		 "Authorization: Basic ". base64_encode( $clientId .":". $clientSecret)

		 );
	$postData = array(
		 "grant_type" => "client_credentials",
		 "response_type" => "client_token", //id_token, client_token
		 // Basic Apple Pay integration guide recommends scoping the client token
		 // for SDK initialization via intent=sdk_init. Safe to send for all v6
		 // components (paypal-payments, applepay-payments, etc.).
		 "intent" => "sdk_init"
		 );
//var_dump($curlHeader);
	$curlPostData = http_build_query($postData);
	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $curlPostData);
	$access_token = $curlResponse['json']['access_token'];
	//$id_token = $curlResponse['json']['id_token'];	commented on 05th Nov25
	//$access_token = $curlResponse['access_token'];
    return $access_token;
	//return $id_token; commented on 05th Nov25
	//echo($access_token);


}

/************** Repeate User ID Token ****************/
function getAccessTokenReturn($countryCode = null){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v1/oauth2/token";
	$creds = getMerchantCredentials($countryCode);
	$clientId = $creds['client_id'];
	$clientSecret = $creds['client_secret'];
	$curlHeader = array(
		 "Content-type" => "application/json",
		 "Authorization: Basic ". base64_encode( $clientId .":". $clientSecret)

		 );
	$postData = array(
		 "grant_type" => "client_credentials",
		 "response_type" => "id_token",
		 "target_customer_id" => "PJIQJmspOX"
		 );
//var_dump($curlHeader);
	$curlPostData = http_build_query($postData);
	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $curlPostData);
	$access_token = $curlResponse['json']['access_token'];
	$id_token = $curlResponse['json']['id_token'];

	//$access_token = $curlResponse['access_token'];
    //return $access_token;
	return $id_token;
	echo($id_token);
}

/***************** Get Client token(UCC) *********/
/*function getClientToken($access_token, $postData){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v1/identity/generate-token";
	$curlHeader = array("Content-Type:application/json", "Authorization:Bearer ".$access_token, "Accept: application/json");
	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData); //var_dump($curlResponse);
	$jsonResponse = $curlResponse['json']['client_token'];
	return $jsonResponse;
}*/

/************** WebProfileID *********/
function getWebProfileID($access_token, $postData){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v1/payment-experience/web-profiles";
	$curlHeader = array("Content-Type:application/json", "Authorization:Bearer ".$access_token);
	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
	$webprofileid = $curlResponse['json']['id'];
    return $webprofileid;
}

/************** STC Call *********
function callSTC($access_token, $postData){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "v1/risk/transaction-contexts/";
	$curlHeader = array("Content-Type:application/json", "Authorization:Bearer ".$access_token);
	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
	$webprofileid = $curlResponse['json']['id'];
    return $webprofileid;
}

/************** Get payment id *********/
function getApprovalURL($access_token, $postData){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v2/checkout/orders";
	$curlHeader = array("Content-Type:application/json", "Authorization:Bearer ".$access_token, "PayPal-Request-Id:".UUID,"PayPal-Request-Id:".UUID,); // "PayPal-Client-Metadata-Id:"."476384348b3fy8bf1234-initialize_1");

	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
	$jsonResponse = $curlResponse['json'];
	return $jsonResponse;
}

/************** Patch Order *********/
function patchOrder($access_token, $postData, $PAYMENT_ID){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v2/checkout/orders/".$PAYMENT_ID."";
	$curlHeader = array("Content-Type:application/json", "Authorization:Bearer ".$access_token, "PayPal-Request-Id:".UUID,"PayPal-Request-Id:".UUID);

	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
	$jsonResponse = $curlResponse['json'];
	return $jsonResponse;
}

/**************** Execute Payment ***********/
function executePayment($access_token, $postData,$PAYMENT_ID){
$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v2/checkout/orders/".$PAYMENT_ID."/capture";
	$curlHeader = array("Content-Type:application/json", "Authorization:Bearer ".$access_token, "PayPal-Request-Id:".UUID, "PayPal-Client-Metadata-Id:"."476384348b3fy8bf1234-initialize_1"); //, "PayPal-Client-Metadata-Id:"."339a2567-f2b4-40e5-9de1-c4b9b84d7522"
	//"PayPal-Client-Metadata-Id:"."1X0007305B389790S"
	$curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
	//$paymentid = $curlResponse['json']['id'];
   // return $paymentid;
	$jsonResponse = $curlResponse['json'];
	//print_r($jsonResponse);exit;
	return $jsonResponse;
}

/*************** Show Payment Details ***********/
function getPaymentDetails($access_token,$PAYMENT_ID){
	$curlServiceUrl = (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
	$curlServiceUrl = $curlServiceUrl. "/v2/checkout/orders/".$PAYMENT_ID;
	$curlHeader = array("Content-Type:application/json", "Authorization:Bearer ".$access_token, "PayPal-Partner-Attribution-Id:".SBN_CODE);

	$curlResponse = curlCall($curlServiceUrl, $curlHeader);
	$jsonResponse = $curlResponse['json'];
	return $jsonResponse;
}
?>
