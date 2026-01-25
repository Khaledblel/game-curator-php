<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
function get_game_recommendations($prompt, $count = 4) {
    $api_key = "YOUR_GEMINI_API_KEY_HERE";
    
    if (empty($api_key) || $api_key === "YOUR_GEMINI_API_KEY_HERE") {
        error_log("Google API Key not found");
        return [];
    }
    
    // Prepare system instruction
    $system_instruction = "
    You are a video game recommendation expert. When given a description or request,
    recommend exactly $count video games that match the criteria.
    
    The FIRST game should be the BEST match for the user's request.
    The remaining " . ($count-1) . " games should be SIMILAR to the first game but with interesting variations.
    
    ONLY return the exact titles of the games. DO NOT include any other information.
    Your response should be ONLY a valid JSON array of strings with JUST the game names.
    
    Example response format:
    [\"Game Title 1\", \"Game Title 2\", \"Game Title 3\", \"Game Title 4\"]
    
    DO NOT include any explanation, description, or additional text in your response.
    IMPORTANT: Make sure to return ONLY valid complete JSON array of strings.
    ";
    
    // Prepare the API request data
    $request_data = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => $prompt
                    ]
                ]
            ]
        ],
        "systemInstruction" => [
            "parts" => [
                [
                    "text" => $system_instruction
                ]
            ]
        ],
        "tools" => [
            [
                "google_search" => new stdClass() // Empty object for google_search
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 2048
        ]
    ];
    
    // Convert data to JSON
    $json_data = json_encode($request_data);
    
    // Set up cURL request
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json_data
    ]);
    
    // Execute the request
    $response = curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Process the response
    if ($status_code == 200) {
        $result = json_decode($response, true);
        
        if (isset($result['candidates']) && !empty($result['candidates'])) {
            // Extract text from the first candidate
            $response_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Check for and extract JSON array from the response
            if (preg_match('/(\[[\s\S]*\])/', $response_text, $matches)) {
                $json_text = $matches[1];
            } else {
                $json_text = $response_text;
            }
            
            // Try to parse the JSON
            try {
                $game_names = json_decode($json_text, true);
                
                // Ensure we have exactly 'count' games (or fewer if that's all we got)
                if (is_array($game_names) && count($game_names) > $count) {
                    $game_names = array_slice($game_names, 0, $count);
                }
                
                return is_array($game_names) ? $game_names : [];
            } catch (Exception $e) {
                error_log("Error parsing JSON from Gemini response: " . $e->getMessage());
                error_log("Raw response: " . $response_text);
                return [];
            }
        }
    }
    
    // Log error if request failed
    error_log("Failed to get recommendations from Gemini API. Status code: $status_code");
    error_log("Response: " . $response);
    
    return [];
}

/**
 * Test function for the Gemini API - for debugging purposes
 */
function test_gemini_api() {
    $prompt = "I'm looking for an open-world RPG with dragons";
    $games = get_game_recommendations($prompt);
    header('Content-Type: application/json');
    echo json_encode(['games' => $games]);
    exit;
}