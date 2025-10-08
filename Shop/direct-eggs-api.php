<?php
// Direct API file - no routing needed
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

// Clean any output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output
ob_start();

try {
    // Use same configuration as main.py
    $pterodactylConfig = [
        'panel_url' => 'https://panel.tonne.dev',
        'api_key' => 'ptla_akZcoj10lYYfT43t4t8ZeUFtaKxjQ20keTfuCY8Fq63'
    ];
    
    // Get all nests first
    $nestsUrl = rtrim($pterodactylConfig['panel_url'], '/') . '/api/application/nests';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Authorization: Bearer ' . $pterodactylConfig['api_key'],
                'Content-Type: application/json',
                'Accept: Application/vnd.pterodactyl.v1+json'
            ],
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ]);
    
    $nestsResponse = file_get_contents($nestsUrl, false, $context);
    if ($nestsResponse === false) {
        throw new Exception('Fehler beim Abrufen der Nests');
    }
    
    $nestsData = json_decode($nestsResponse, true);
    if (!$nestsData || !isset($nestsData['data'])) {
        throw new Exception('Ungültige Nest-Antwort');
    }
    
    $eggs = [];
    
    // Get eggs for each nest
    foreach ($nestsData['data'] as $nest) {
        $nestId = $nest['attributes']['id'];
        $nestName = $nest['attributes']['name'];
        
        try {
            $eggsUrl = rtrim($pterodactylConfig['panel_url'], '/') . '/api/application/nests/' . $nestId . '/eggs';
            $eggsResponse = file_get_contents($eggsUrl, false, $context);
            
            if ($eggsResponse !== false && (strpos($eggsResponse, '{') === 0 || strpos($eggsResponse, '[') === 0)) {
                $eggsData = json_decode($eggsResponse, true);
                
                if ($eggsData && isset($eggsData['data'])) {
                    foreach ($eggsData['data'] as $egg) {
                        $eggId = $egg['attributes']['id'];
                        
                        // Get detailed egg information including variables
                        $detailUrl = rtrim($pterodactylConfig['panel_url'], '/') . '/api/application/nests/' . $nestId . '/eggs/' . $eggId . '?include=variables';
                        $detailResponse = file_get_contents($detailUrl, false, $context);
                        
                        $environment = [];
                        if ($detailResponse !== false) {
                            $detailData = json_decode($detailResponse, true);
                            if ($detailData && isset($detailData['attributes']['relationships']['variables']['data'])) {
                                foreach ($detailData['attributes']['relationships']['variables']['data'] as $variable) {
                                    $varName = $variable['attributes']['env_variable'] ?? '';
                                    $defaultValue = $variable['attributes']['default_value'] ?? '';
                                    if ($varName) {
                                        $environment[$varName] = $defaultValue;
                                    }
                                }
                            }
                        }
                        
                        $eggs[] = [
                            'id' => $eggId,
                            'name' => $egg['attributes']['name'],
                            'description' => $egg['attributes']['description'] ?? '',
                            'nest_id' => $nestId,
                            'nest_name' => $nestName,
                            'docker_image' => $egg['attributes']['docker_image'] ?? '',
                            'startup' => $egg['attributes']['startup'] ?? '',
                            'environment' => $environment
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Skip this nest on error
            continue;
        }
    }
    
    // Sort eggs by nest name and then by egg name
    usort($eggs, function($a, $b) {
        $nestCompare = strcmp($a['nest_name'], $b['nest_name']);
        if ($nestCompare === 0) {
            return strcmp($a['name'], $b['name']);
        }
        return $nestCompare;
    });
    
    echo json_encode(['eggs' => $eggs]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

ob_end_flush();
exit;
?>