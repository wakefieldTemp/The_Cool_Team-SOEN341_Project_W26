<?php
function getCalorieTip($total_calories, $current_goal) {
    require 'api_config.php';
    $prompt = "User ate $total_calories/$current_goal kcal today. Give a short motivational message + 1 tip. Be warm and concise.";
    $data = [
        "model" => "claude-haiku-4-5-20251001",
        "max_tokens" => 1000,
        "messages" => [
            [
                "role" => "user",
                "content" => $prompt
            ]
        ]
    ];

    $ch = curl_init("https://api.anthropic.com/v1/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: " . $ANTHROPIC_API_KEY,
        "anthropic-version: 2023-06-01"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    if ($response === false) {
        die("cURL error: " . curl_error($ch));
    }

    return json_decode($response, true);
}
?>