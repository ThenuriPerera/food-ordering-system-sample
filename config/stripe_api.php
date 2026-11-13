<?php

require_once __DIR__ . "/stripe_config.php";

function stripeApiRequest($method, $endpoint, $data = [], $idempotencyKey = null)
{
    $url = STRIPE_API_URL . $endpoint;

    if ($method === "GET" && !empty($data)) {
        $url .= "?" . http_build_query($data);
    }

    $ch = curl_init($url);

    $headers = [
        "Content-Type: application/x-www-form-urlencoded"
    ];

    if ($idempotencyKey !== null) {
        $headers[] = "Idempotency-Key: " . $idempotencyKey;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => STRIPE_SECRET_KEY . ":",
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30
    ]);

    if ($method === "POST") {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);

        throw new Exception("Stripe connection failed: " . $error);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $result["error"]["message"] ?? "Unknown Stripe error";

        throw new Exception($message);
    }

    if (!is_array($result)) {
        throw new Exception("Invalid response received from Stripe.");
    }

    return $result;
}

?>