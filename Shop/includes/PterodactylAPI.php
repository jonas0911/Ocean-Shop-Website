<?php
/**
 * Pterodactyl Panel API Integration
 * Handles server creation, management and monitoring
 */
class PterodactylAPI {
    private $apiUrl;
    private $apiKey;
    private $httpHeaders;

    public function __construct() {
        // Load configuration from database
        require_once __DIR__ . '/SettingsManager.php';
        $settings = new SettingsManager();
        $config = $settings->getPterodactylConfig();
        
        $this->apiUrl = rtrim($config['panel_url'], '/') . '/api';
        $this->apiKey = $config['api_key'];
        
        $this->httpHeaders = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }

    /**
     * Create a new server
     */
    public function createServer($userId, $serverData) {
        $endpoint = '/application/servers';
        
        $data = [
            'name' => $serverData['name'],
            'user' => $serverData['user_email'], // Server Owner ist die Email vom User
            'egg' => $serverData['egg_id'], // Game type (Minecraft, etc.)
            'docker_image' => $serverData['docker_image'],
            'startup' => $serverData['startup_command'],
            'environment' => $serverData['environment'] ?? [],
            'limits' => [
                'memory' => $serverData['memory'], // in MB - vom User gewählt
                'swap' => $serverData['memory'], // Swap = RAM
                'disk' => 20480, // IMMER 20GB (20480 MB) für alle Server
                'io' => 500,
                'cpu' => 0, // IMMER 0 = Unlimited CPU für alle Server
                'threads' => '0-19' // IMMER 0-19 CPU Threads für alle Server
            ],
            'feature_limits' => [
                'databases' => 20, // Standard 20 Datenbanken
                'allocations' => 20, // Standard 20 Allocations 
                'backups' => 20 // Standard 20 Backups
            ],
            'allocation' => [
                'default' => $serverData['allocation_id']
            ]
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Get server information
     */
    public function getServer($serverId) {
        $endpoint = '/application/servers/' . $serverId;
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Delete a server
     */
    public function deleteServer($serverId, $force = false) {
        $endpoint = '/application/servers/' . $serverId;
        if ($force) {
            $endpoint .= '/force';
        }
        return $this->makeRequest('DELETE', $endpoint);
    }

    /**
     * Get available eggs (game types)
     */
    public function getEggs() {
        $endpoint = '/application/eggs';
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get available nodes
     */
    public function getNodes() {
        $endpoint = '/application/nodes';
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get available allocations for a node
     */
    public function getNodeAllocations($nodeId) {
        $endpoint = '/application/nodes/' . $nodeId . '/allocations';
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get all allocations with unassigned filter
     */
    public function getUnassignedAllocations($nodeId = null) {
        if ($nodeId) {
            $endpoint = '/application/nodes/' . $nodeId . '/allocations?filter[assigned]=false';
        } else {
            // Get first available node
            $nodes = $this->getNodes();
            if (!$nodes['success'] || empty($nodes['data']['data'])) {
                throw new Exception('No nodes available');
            }
            $nodeId = $nodes['data']['data'][0]['attributes']['id'];
            $endpoint = '/application/nodes/' . $nodeId . '/allocations?filter[assigned]=false';
        }
        
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Create a new user in Pterodactyl
     */
    public function createUser($userData) {
        $endpoint = '/application/users';
        
        $data = [
            'email' => $userData['email'],
            'username' => $userData['username'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'password' => $userData['password'] ?? null
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $endpoint = '/application/users?filter[email]=' . urlencode($email);
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Start server
     */
    public function startServer($serverId) {
        return $this->serverPowerAction($serverId, 'start');
    }

    /**
     * Stop server
     */
    public function stopServer($serverId) {
        return $this->serverPowerAction($serverId, 'stop');
    }

    /**
     * Restart server
     */
    public function restartServer($serverId) {
        return $this->serverPowerAction($serverId, 'restart');
    }

    /**
     * Server power actions (client API)
     */
    private function serverPowerAction($serverId, $action) {
        $endpoint = '/client/servers/' . $serverId . '/power';
        $data = ['signal' => $action];
        
        // Use client API headers
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        return $this->makeRequest('POST', $endpoint, $data, $headers, '/client');
    }

    /**
     * Make HTTP request to Pterodactyl API
     */
    private function makeRequest($method, $endpoint, $data = null, $customHeaders = null, $apiType = '/application') {
        $url = $this->apiUrl . $apiType . $endpoint;
        $headers = $customHeaders ?? $this->httpHeaders;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = isset($decodedResponse['errors']) 
                ? json_encode($decodedResponse['errors']) 
                : 'HTTP Error ' . $httpCode;
            throw new Exception('API Error: ' . $errorMessage);
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => $decodedResponse,
            'http_code' => $httpCode
        ];
    }

    /**
     * Get game-specific configurations
     */
    public function getGameConfig($gameName) {
        $config = require __DIR__ . '/../config/pterodactyl.php';
        $gameKey = strtolower($gameName);
        
        if (!isset($config['games'][$gameKey])) {
            return null;
        }
        
        $gameConfig = $config['games'][$gameKey];
        return [
            'egg_id' => $gameConfig['egg_id'],
            'docker_image' => $gameConfig['docker_image'],
            'startup_command' => $gameConfig['startup'],
            'environment' => $gameConfig['environment'],
            'default_port' => $gameConfig['default_port']
        ];
    }
}
?>