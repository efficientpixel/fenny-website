<?php

$handle = curl_init($_POST['file']);
curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

$response = curl_exec($handle);

/* Check for 404 (file not found). */
$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

echo $httpCode;

curl_close($handle);