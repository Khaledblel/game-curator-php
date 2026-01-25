<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
class IGDBClient {
    private $client_id;
    private $client_secret;
    private $access_token;
    private $token_expiry;
    private $base_url = "https://api.igdb.com/v4";

    public function __construct() {
        $this->client_id = "YOUR_IGDB_CLIENT_ID_HERE";
        $this->client_secret = "YOUR_IGDB_CLIENT_SECRET_HERE";

        if (!$this->client_id || $this->client_id === "YOUR_IGDB_CLIENT_ID_HERE") {
            error_log("IGDB Client ID or Secret not found in environment variables.");
        }
        $this->access_token = $_SESSION['igdb_access_token'] ?? null;
        $this->token_expiry = $_SESSION['igdb_token_expiry'] ?? null;
    }

    private function getAccessToken() {
        $url = "https://id.twitch.tv/oauth2/token";
        $params = http_build_query([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials'
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status_code == 200) {
            $data = json_decode($response, true);
            $this->access_token = $data['access_token'];
            // Set expiry time (subtract 1 hour for safety margin)
            $this->token_expiry = time() + $data['expires_in'] - 3600;
            
            // Store token in session (or cache)
            $_SESSION['igdb_access_token'] = $this->access_token;
            $_SESSION['igdb_token_expiry'] = $this->token_expiry;
            
            return true;
        } else {
            error_log("Failed to get IGDB access token: $status_code - $response");
            return false;
        }
    }

    private function ensureValidToken() {
        if (!$this->access_token || !$this->token_expiry || time() >= $this->token_expiry) {
            return $this->getAccessToken();
        }
        return true;
    }

    public function makeRequest($endpoint, $query) {
        if (!$this->ensureValidToken()) {
            return null;
        }

        $url = "{$this->base_url}/{$endpoint}";
        $headers = [
            "Client-ID: {$this->client_id}",
            "Authorization: Bearer {$this->access_token}",
            "Content-Type: text/plain" // IGDB API expects query as plain text
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status_code == 200) {
            return json_decode($response, true);
        } else {
            error_log("IGDB API request failed: $status_code - $response");
            // If token expired (401), try refreshing it once
            if ($status_code == 401) {
                error_log("IGDB token might have expired. Attempting refresh...");
                unset($_SESSION['igdb_access_token']); // Force refresh
                $this->access_token = null;
                if ($this->ensureValidToken()) {
                    error_log("Retrying IGDB request with new token...");
                    return $this->makeRequest($endpoint, $query); // Retry the request
                }
            }
            return null;
        }
    }
}

/**
 * Get detailed information about games from IGDB API
 *
 * @param array $game_names List of game names to search for
 * @return array Dictionary containing main game and similar games details
 */
function get_game_details_php($game_names) {
    $client = new IGDBClient();

    if (empty($game_names)) {
        return ['main_game' => null, 'similar_games' => []];
    }

    $main_game_name = $game_names[0];
    $similar_game_names = array_slice($game_names, 1);

    // Get detailed information for the main game
    $main_game = search_and_get_game_details($client, $main_game_name);

    // Get detailed information for similar games
    $similar_games = [];
    foreach ($similar_game_names as $game_name) {
        $game = search_and_get_game_details($client, $game_name);
        if ($game) {
            $similar_games[] = $game;
        }
        // Add a small delay to avoid hitting rate limits too quickly
        usleep(250000); // 250ms delay
    }

    return [
        'main_game' => $main_game,
        'similar_games' => $similar_games
    ];
}

/**
 * Search for a game by name and get its detailed information
 *
 * @param IGDBClient $client IGDB API client
 * @param string $game_name Name of the game to search for
 * @return array|null Detailed game information or null if not found
 */
function search_and_get_game_details($client, $game_name) {
    error_log("IGDB: Searching for game: '{$game_name}'"); // Log search term

    // First, search for the game to get its ID
    // Prioritize exact matches and games with covers
    $search_query = sprintf(
        'search "%s"; fields name, id, cover; where version_parent = null & category = 0; limit 5;',
        $game_name
    );
    error_log("IGDB: Search Query: " . $search_query); // Log search query
    
    $search_results = $client->makeRequest("games", $search_query);

    if (empty($search_results)) {
        error_log("IGDB Search: No results found for '{$game_name}'");
        return null;
    }
    error_log("IGDB Search: Found results for '{$game_name}': " . json_encode($search_results)); // Log search results
    
    // Find the best match (prefer exact name match with a cover)
    $game_id = null;
    $match_reason = "No suitable match found initially.";
    foreach ($search_results as $result) {
        if (strtolower($result['name']) === strtolower($game_name) && isset($result['cover'])) {
            $game_id = $result['id'];
            $match_reason = "Exact name match with cover.";
            break;
        }
    }
    // Fallback: take the first result if no exact match found
    if ($game_id === null && !empty($search_results)) {
        $game_id = $search_results[0]['id'];
        $match_reason = "Fallback to first result (ID: {$search_results[0]['id']}, Name: {$search_results[0]['name']}).";
    }

    if ($game_id === null) {
        error_log("IGDB Search: Could not determine a game ID for '{$game_name}'. Match attempt reason: {$match_reason}");
        return null;
    }
    error_log("IGDB Search: Selected Game ID {$game_id} for '{$game_name}'. Reason: {$match_reason}"); // Log chosen ID and reason

    // Get detailed information for the game with a focused set of fields
    $details_query = sprintf(
        'fields name, summary, storyline, first_release_date, rating, cover.url, screenshots.url, genres.name, platforms.name, involved_companies.company.name, involved_companies.developer, involved_companies.publisher, game_modes.name, themes.name, total_rating, total_rating_count, websites.url, websites.category, alternative_names.name, dlcs, expansions, franchise, franchises, age_ratings.rating, age_ratings.category, language_supports.language.name, language_supports.language.native_name, language_supports.language_support_type.name; where id = %d; limit 1;',
        $game_id
    );
    error_log("IGDB: Details Query for ID {$game_id}: " . $details_query); // Log details query

    $game_details = $client->makeRequest("games", $details_query);

    if (empty($game_details)) {
        error_log("IGDB Details: Could not fetch details for game ID {$game_id} ('{$game_name}')");
        return null;
    }
    error_log("IGDB Details: Successfully fetched details for game ID {$game_id} ('{$game_name}')"); // Log success

    $game = $game_details[0];

    // Get time-to-beat data
    $time_to_beat = get_time_to_beat($client, $game_id);
    if ($time_to_beat) {
        $game['time_to_beat'] = $time_to_beat;
    }

    // Process image URLs
    if (isset($game['cover']['url'])) {
        $game['cover']['url'] = process_igdb_image_url($game['cover']['url'], 't_cover_big');
    }
    if (isset($game['screenshots'])) {
        foreach ($game['screenshots'] as &$screenshot) { // Use reference to modify array directly
            if (isset($screenshot['url'])) {
                $screenshot['url'] = process_igdb_image_url($screenshot['url'], 't_screenshot_big');
            }
        }
        unset($screenshot); // Unset reference after loop
    }

    // Extract developers and publishers
    $developers = [];
    $publishers = [];
    if (isset($game['involved_companies'])) {
        foreach ($game['involved_companies'] as $company_info) {
            if (isset($company_info['company']['name'])) {
                if (!empty($company_info['developer'])) {
                    $developers[] = $company_info['company']['name'];
                }
                if (!empty($company_info['publisher'])) {
                    $publishers[] = $company_info['company']['name'];
                }
            }
        }
    }
    $game['developers'] = array_unique($developers);
    $game['publishers'] = array_unique($publishers);

    // Extract simple name arrays
    $game['game_mode_names'] = extract_names($game, 'game_modes');
    $game['theme_names'] = extract_names($game, 'themes');
    $game['genre_names'] = extract_names($game, 'genres');
    $game['platform_names'] = extract_names($game, 'platforms');
    $game['alt_names'] = extract_names($game, 'alternative_names');

    // Format release date
    if (isset($game['first_release_date'])) {
        $release_date = DateTime::createFromFormat('U', $game['first_release_date']);
        if ($release_date) {
            $game['release_year'] = $release_date->format('Y');
            $game['formatted_release_date'] = $release_date->format('F j, Y');
        }
    }

    // Find official website and store URLs
    $official_website = null;
    $stores = [];
    if (isset($game['websites'])) {
        foreach ($game['websites'] as $website) {
            $category = $website['category'] ?? null;
            $url = $website['url'] ?? null;
            if ($url) {
                if ($category == 1) { // 1=official
                    $official_website = $url;
                }
                // 13=steam, 15=itch, 16=epicgames, 17=gog
                if (in_array($category, [13, 15, 16, 17])) {
                    $stores[] = $url;
                }
            }
        }
    }
    $game['official_website'] = $official_website;
    $game['stores'] = $stores;

    // Process age ratings
    $esrb_rating_url = null;
    $pegi_rating_url = null;
    if (isset($game['age_ratings'])) {
        foreach ($game['age_ratings'] as $rating_info) {
            $category = $rating_info['category'] ?? null;
            $rating_id = $rating_info['rating'] ?? null;
            if ($category == 1) { // ESRB
                $esrb_rating_url = get_esrb_rating_url($rating_id);
            } elseif ($category == 2) { // PEGI
                $pegi_rating_url = get_pegi_rating_url($rating_id);
            }
        }
    }
    $game['esrb_rating_cover_url'] = $esrb_rating_url;
    $game['pegi_rating_cover_url'] = $pegi_rating_url;

    // Process language support
    $language_support = [];
    if (isset($game['language_supports'])) {
        foreach ($game['language_supports'] as $lang_support) {
            $support_type = $lang_support['language_support_type']['name'] ?? null;
            $lang_name = $lang_support['language']['name'] ?? null;
            $native_name = $lang_support['language']['native_name'] ?? null;

            if ($support_type && $lang_name) {
                if (!isset($language_support[$support_type])) {
                    $language_support[$support_type] = [];
                }
                $language_support[$support_type][] = [
                    'name' => $lang_name,
                    'native_name' => $native_name
                ];
            }
        }
    }
    $game['language_support'] = $language_support;

    // Process DLCs and expansions
    $add_on_ids = [];
    if (!empty($game['dlcs'])) {
        $add_on_ids = array_merge($add_on_ids, $game['dlcs']);
    }
    if (!empty($game['expansions'])) {
        $add_on_ids = array_merge($add_on_ids, $game['expansions']);
    }
    $game['add_on_details'] = !empty($add_on_ids) ? get_add_on_details($client, $add_on_ids) : [];

    // Process franchise data
    $franchise_id = $game['franchise'] ?? ($game['franchises'][0] ?? null);
    $game['franchise_details'] = $franchise_id ? get_franchise_details($client, $franchise_id) : null;

    // Clean up raw fields that have been processed into simpler formats
    unset($game['involved_companies'], $game['game_modes'], $game['themes'], $game['genres'], $game['platforms'], $game['websites'], $game['alternative_names'], $game['age_ratings'], $game['language_supports'], $game['dlcs'], $game['expansions'], $game['franchise'], $game['franchises']);

    return $game;
}

/**
 * Helper function to extract names from an array of objects
 */
function extract_names($game, $field) {
    $names = [];
    if (isset($game[$field])) {
        foreach ($game[$field] as $item) {
            if (isset($item['name'])) {
                $names[] = $item['name'];
            }
        }
    }
    return $names;
}

/**
 * Helper function to process IGDB image URLs
 */
function process_igdb_image_url($url, $size = 't_cover_big') {
    // Replace size identifier (e.g., t_thumb -> t_cover_big)
    $url = preg_replace('/t_[a-z_]+/', $size, $url);
    // Ensure URL starts with https:
    if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    return $url;
}

/**
 * Get time-to-beat data for a game
 */
function get_time_to_beat($client, $game_id) {
    $query = sprintf("fields hastily, normally, completely, count; where game = %d; limit 1;", $game_id);
    // Note: IGDB endpoint is time_to_beats, not game_time_to_beats
    $time_to_beat_data = $client->makeRequest("time_to_beats", $query);

    if (empty($time_to_beat_data)) {
        return null;
    }

    $result = $time_to_beat_data[0];
    unset($result['id']); // Remove the time_to_beat ID itself

    // Format times
    if (isset($result['hastily'])) {
        $result['hastily_formatted'] = format_playtime($result['hastily']);
    }
    if (isset($result['normally'])) {
        $result['normally_formatted'] = format_playtime($result['normally']);
    }
    if (isset($result['completely'])) {
        $result['completely_formatted'] = format_playtime($result['completely']);
    }

    return $result;
}

/**
 * Map ESRB rating ID to its cover image URL
 */
function get_esrb_rating_url($rating_id) {
    $esrb_map = [
        6 => "https://www.esrb.org/wp-content/uploads/2019/05/RP.svg",  // Rating Pending
        7 => "https://www.esrb.org/wp-content/uploads/2019/05/EC.svg",  // Early Childhood
        8 => "https://www.esrb.org/wp-content/uploads/2019/05/E.svg",   // Everyone
        9 => "https://www.esrb.org/wp-content/uploads/2019/05/E10plus.svg",  // Everyone 10+
        10 => "https://www.esrb.org/wp-content/uploads/2019/05/T.svg",  // Teen
        11 => "https://www.esrb.org/wp-content/uploads/2019/05/M.svg",  // Mature
        12 => "https://www.esrb.org/wp-content/uploads/2019/05/AO.svg"  // Adults Only
    ];
    return $esrb_map[$rating_id] ?? null;
}

/**
 * Map PEGI rating ID to its cover image URL
 */
function get_pegi_rating_url($rating_id) {
    $pegi_map = [
        1 => "https://rating.pegi.info/assets/images/games/age_threshold_icons/3.png",  // PEGI 3
        2 => "https://rating.pegi.info/assets/images/games/age_threshold_icons/7.png",  // PEGI 7
        3 => "https://rating.pegi.info/assets/images/games/age_threshold_icons/12.png", // PEGI 12
        4 => "https://rating.pegi.info/assets/images/games/age_threshold_icons/16.png", // PEGI 16
        5 => "https://rating.pegi.info/assets/images/games/age_threshold_icons/18.png"  // PEGI 18
    ];
    return $pegi_map[$rating_id] ?? null;
}

/**
 * Get details for DLCs and expansions
 */
function get_add_on_details($client, $add_on_ids) {
    if (empty($add_on_ids)) {
        return [];
    }

    // Limit to avoid large requests
    $add_on_ids = array_slice(array_unique($add_on_ids), 0, 15);
    $ids_string = implode(',', $add_on_ids);

    $add_on_query = sprintf(
        'fields name, summary, cover.url, first_release_date, websites.url, websites.category, category; where id = (%s); limit 15;',
        $ids_string
    );

    $add_on_details = $client->makeRequest("games", $add_on_query);

    if (empty($add_on_details)) {
        return [];
    }

    // Process add-on information
    $processed_details = [];
    foreach ($add_on_details as $add_on) {
        // Determine type
        $category = $add_on['category'] ?? 1; // Default to DLC if category missing
        // 0=main_game, 1=dlc_addon, 2=expansion, 3=bundle, 4=standalone_expansion
        $add_on['type'] = ($category == 2 || $category == 4) ? 'Expansion' : 'DLC';

        // Process cover URL
        if (isset($add_on['cover']['url'])) {
            $add_on['cover']['url'] = process_igdb_image_url($add_on['cover']['url'], 't_cover_big');
        }

        // Format release date
        if (isset($add_on['first_release_date'])) {
            $release_date = DateTime::createFromFormat('U', $add_on['first_release_date']);
            if ($release_date) {
                $add_on['release_year'] = $release_date->format('Y');
                $add_on['formatted_release_date'] = $release_date->format('F j, Y');
            }
        }

        // Find store URLs
        $stores = [];
        if (isset($add_on['websites'])) {
            foreach ($add_on['websites'] as $website) {
                if (in_array($website['category'] ?? null, [13, 15, 16, 17]) && isset($website['url'])) {
                    $stores[] = $website['url'];
                }
            }
        }
        $add_on['stores'] = $stores;
        
        // Clean up raw fields
        unset($add_on['websites'], $add_on['category']);
        $processed_details[] = $add_on;
    }

    return $processed_details;
}

/**
 * Get franchise details including games
 */
function get_franchise_details($client, $franchise_id) {
    $franchise_query = sprintf("fields name, slug, url, games; where id = %d; limit 1;", $franchise_id);
    $franchise_results = $client->makeRequest("franchises", $franchise_query);

    if (empty($franchise_results)) {
        return null;
    }

    $franchise = $franchise_results[0];

    // Get games in the franchise
    if (!empty($franchise['games'])) {
        $game_ids = array_slice($franchise['games'], 0, 20);
        $ids_string = implode(',', $game_ids);

        $games_query = sprintf(
            'fields name, cover.url, first_release_date, rating, total_rating, category; where id = (%s) & category = 0 & version_parent = null; sort first_release_date asc; limit 20;',
            $ids_string
        );

        $franchise_games = $client->makeRequest("games", $games_query);

        if (!empty($franchise_games)) {
            $processed_games = [];
            foreach ($franchise_games as $game) {
                // Process cover URL
                if (isset($game['cover']['url'])) {
                    $game['cover']['url'] = process_igdb_image_url($game['cover']['url'], 't_cover_big');
                }
                // Format release date
                if (isset($game['first_release_date'])) {
                    $release_date = DateTime::createFromFormat('U', $game['first_release_date']);
                    if ($release_date) {
                        $game['release_year'] = $release_date->format('Y');
                        $game['formatted_release_date'] = $release_date->format('F j, Y');
                    }
                }
                $game['type'] = 'Main Game'; // Set type for timeline display
                unset($game['category']);
                $processed_games[] = $game;
            }
            // Ensure sorting by release date
            usort($processed_games, function($a, $b) {
                return ($a['first_release_date'] ?? 0) <=> ($b['first_release_date'] ?? 0);
            });
            $franchise['games_details'] = $processed_games;
        } else {
            $franchise['games_details'] = [];
        }
    } else {
        $franchise['games_details'] = [];
    }
    
    unset($franchise['games']); // Remove raw game IDs

    return $franchise;
}

/**
 * Format seconds into a readable playtime string (e.g., "15h 30m")
 */
function format_playtime($seconds) {
    if (empty($seconds) || !is_numeric($seconds)) {
        return null;
    }
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    } elseif ($minutes > 0) {
        return $minutes . 'm';
    } else {
        return null; // Or maybe 'Less than a minute'?
    }
}

?>