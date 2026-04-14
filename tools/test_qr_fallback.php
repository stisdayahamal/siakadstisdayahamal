<?php
// tools/test_qr_fallback.php

function generateQR($data) {
    $qr_img = '';
    
    // Step 1: Try local (should fail if library missing)
    if (class_exists('QRcode')) {
        echo "Local QRcode class found.\n";
        // ... local logic
    } else {
        echo "Local QRcode class NOT found. Using fallback...\n";
    }
    
    // Step 2: Fallback to Cloud QR API
    if (empty($qr_img)) {
        $api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($data);
        $api_content = @file_get_contents($api_url);
        if ($api_content) {
            echo "Cloud API success. Image size: " . strlen($api_content) . " bytes\n";
            $qr_img = base64_encode($api_content);
        } else {
            echo "Cloud API failed.\n";
        }
    }
    
    return $qr_img;
}

$test_data = "https://example.com/verify";
$result = generateQR($test_data);

if (!empty($result)) {
    echo "SUCCESS: QR Code generated (Base64 length: " . strlen($result) . ")\n";
} else {
    echo "FAILURE: Could not generate QR Code\n";
}
