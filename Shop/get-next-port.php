<?php
// Get next available port from Pterodactyl
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pterodactylConfig = [
        'panel_url' => 'https://panel.tonne.dev',
        'api_key' => 'ptla_akZcoj10lYYfT43t4t8ZeUFtaKxjQ20keTfuCY8Fq63'
    ];
    
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
    
    // Get all nodes to find allocations
    $nodesUrl = rtrim($pterodactylConfig['panel_url'], '/') . '/api/application/nodes';
    $nodesResponse = file_get_contents($nodesUrl, false, $context);
    
    if ($nodesResponse === false) {
        throw new Exception('Fehler beim Abrufen der Nodes');
    }
    
    $nodesData = json_decode($nodesResponse, true);
    if (!$nodesData || !isset($nodesData['data'])) {
        throw new Exception('Ungültige Node-Antwort');
    }
    
    $usedPorts = [];
    $availablePorts = [];
    
    // Collect all used and available ports from all nodes
    foreach ($nodesData['data'] as $node) {
        $nodeId = $node['attributes']['id'];
        
        $allocationsUrl = rtrim($pterodactylConfig['panel_url'], '/') . '/api/application/nodes/' . $nodeId . '/allocations';
        $allocResponse = file_get_contents($allocationsUrl, false, $context);
        
        if ($allocResponse !== false) {
            $allocData = json_decode($allocResponse, true);
            if ($allocData && isset($allocData['data'])) {
                foreach ($allocData['data'] as $allocation) {
                    $port = $allocation['attributes']['port'];
                    $assigned = $allocation['attributes']['assigned'];
                    
                    if ($assigned) {
                        $usedPorts[] = $port;
                    } else {
                        $availablePorts[] = $port;
                    }
                }
            }
        }
    }
    
    // Find the lowest available port
    $nextPort = null;
    
    if (!empty($availablePorts)) {
        // Use existing unassigned port
        sort($availablePorts);
        $nextPort = $availablePorts[0];
    } else {
        // Find the lowest unused port starting from common game ports
        $commonPorts = [25565, 25566, 25567, 25568, 25569, 27015, 27016, 27017, 28015, 28016];
        
        foreach ($commonPorts as $port) {
            if (!in_array($port, $usedPorts)) {
                $nextPort = $port;
                break;
            }
        }
        
        // If all common ports are used, find next available port
        if ($nextPort === null) {
            $startPort = 25565;
            for ($port = $startPort; $port <= 35000; $port++) {
                if (!in_array($port, $usedPorts)) {
                    $nextPort = $port;
                    break;
                }
            }
        }
    }
    
    if ($nextPort === null) {
        throw new Exception('Keine verfügbaren Ports gefunden');
    }
    
    echo json_encode([
        'success' => true,
        'port' => $nextPort,
        'used_ports_count' => count($usedPorts),
        'available_ports_count' => count($availablePorts)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>