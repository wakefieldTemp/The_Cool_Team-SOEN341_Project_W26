<?php
function getCalorieTip($total_calories, $current_goal) {
    require_once __DIR__ . '/../../config/api_config.php';
    global $ANTHROPIC_API_KEY;
    $prompt = "User ate $total_calories/$current_goal kcal today. Give a short motivational message + 1 tip. Be warm and concise.";
    $client = Anthropic::client($ANTHROPIC_API_KEY);

    $response = $client->messages()->create([
        'model' => 'claude-haiku-4-5-20251001',
        'max_tokens' => 300,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
    ]);

    return $response->content[0]->text ?? null;
}
?>