<?php

$ch = curl_init('http://localhost:8080/api/v1/auth/register');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'name' => 'Abhilash Test',
        'email' => 'abhilash@test.com',
        'password' => 'SecurePass123!'
    ])
]);
$result = curl_exec($ch);
echo "REGISTER:\n" . $result . "\n\n";
curl_close($ch);

// Login with the same user
$ch = curl_init('http://localhost:8080/api/v1/auth/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'abhilash@test.com',
        'password' => 'SecurePass123!'
    ])
]);
$result = curl_exec($ch);
echo "LOGIN:\n" . $result . "\n\n";

$data = json_decode($result, true);
$accessToken = $data['data']['access_token'] ?? '';

// Get members
$ch = curl_init('http://localhost:8080/api/v1/members');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);
$result = curl_exec($ch);
echo "MEMBERS:\n" . $result . "\n\n";

// Get family tree for member 4004
$ch = curl_init('http://localhost:8080/api/v1/families/4004/tree');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);
$result = curl_exec($ch);
echo "FAMILY TREE:\n" . $result . "\n";
