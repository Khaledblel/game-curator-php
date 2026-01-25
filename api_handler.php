<?php
session_start();

// Include necessary API files
require_once 'gemini_api.php';
require_once 'igdb_api.php';

// Basic security check: Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Get the JSON payload from the request body
$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

// Check if prompt exists
if (!isset($request_data['prompt']) || empty(trim($request_data['prompt']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Prompt is required.']);
    exit;
}

$user_prompt = trim($request_data['prompt']);

// --- Step 1: Get game names from Gemini ---
$recommended_game_names = get_game_recommendations($user_prompt, 4); // Get 1 main + 3 similar

if (empty($recommended_game_names)) {
    error_log("Gemini API did not return any game names for prompt: " . $user_prompt);
    echo json_encode(['success' => false, 'error' => 'Could not get game recommendations. The AI might be busy or the prompt too specific.']);
    exit;
}

// --- Step 2: Get game details from IGDB ---
$game_details = get_game_details_php($recommended_game_names);

// --- Step 3: Prepare and return the response ---
if ($game_details && $game_details['main_game']) {
    // Successfully got details for the main game at least
    echo json_encode([
        'success' => true,
        'main_game' => $game_details['main_game'],
        'similar_games' => $game_details['similar_games'] ?? [] // Ensure similar_games is always an array
    ]);
} else {
    // Failed to get details even for the main game
    error_log("IGDB API failed to get details for games: " . implode(', ', $recommended_game_names));
    echo json_encode([
        'success' => false,
        'error' => 'Found game names, but could not retrieve details. The IGDB API might be unavailable or the game names were not found.'
    ]);
}

exit; // Ensure script termination
?>
