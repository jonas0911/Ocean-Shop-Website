<?php
/**
 * Pterodactyl Configuration
 * Store your panel settings here
 */

return [
    // Pterodactyl Panel Settings
    'panel_url' => 'https://panel.tonne.dev', // Your panel URL
    'api_key' => 'ptla_Zzq8wqyewjIbhdMGvydAfdBaybQTyACN1KOYK4lZkGv',
    
    // Default Server Settings
    'default_node_id' => 1, // Your default node ID
    'default_disk_space' => 5000, // MB
    'default_cpu_limit' => 100, // Percentage
    'default_databases' => 1,
    'default_backups' => 2,
    
    // Game Configurations (basierend auf main.py)
    'games' => [
        'minecraft' => [
            'name' => 'Minecraft',
            'egg_id' => 5, // Based on your Discord bot implementation
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17',
            'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}',
            'default_port' => 25565,
            'environment' => [
                'SERVER_JARFILE' => 'server.jar',
                'VERSION' => 'latest',
                'BUILD_NUMBER' => 'latest'
            ]
        ],
        'rust' => [
            'name' => 'Rust',
            'egg_id' => 2, // Update with your Rust egg ID
            'docker_image' => 'ghcr.io/pterodactyl/games:rust',
            'startup' => './RustDedicated -batchmode +server.port {{SERVER_PORT}} +server.queryport {{QUERY_PORT}} +rcon.port {{RCON_PORT}} +rcon.web true +server.hostname "{{HOSTNAME}}" +server.level "{{LEVEL}}" +server.description "{{DESCRIPTION}}" +server.url "{{SERVER_URL}}" +server.headerimage "{{SERVER_IMG}}" +server.logoimage "{{SERVER_LOGO}}" +server.maxplayers {{MAX_PLAYERS}} +rcon.password "{{RCON_PASS}}" +server.saveinterval {{SAVEINTERVAL}} +app.port {{APP_PORT}} $( [ "$ADDITIONAL_ARGS" == "" ] || printf %s " $ADDITIONAL_ARGS" )',
            'default_port' => 28015,
            'environment' => [
                'HOSTNAME' => 'Ocean Rust Server',
                'LEVEL' => 'Procedural Map',
                'DESCRIPTION' => 'A Rust server powered by Ocean Hosting',
                'SERVER_URL' => 'https://ocean-hosting.com',
                'MAX_PLAYERS' => '100',
                'SAVEINTERVAL' => '300'
            ]
        ],
        'ark' => [
            'name' => 'ARK: Survival Evolved',
            'egg_id' => 3, // Update with your ARK egg ID
            'docker_image' => 'ghcr.io/pterodactyl/games:ark',
            'startup' => './ShooterGameServer {{GAME_MOD}}?listen?SessionName="{{SESSION_NAME}}"?ServerPassword={{SERVER_PASSWORD}}?ServerAdminPassword={{ADMIN_PASSWORD}}?Port={{SERVER_PORT}}?QueryPort={{QUERY_PORT}}?MaxPlayers={{MAX_PLAYERS}} -server -log',
            'default_port' => 7777,
            'environment' => [
                'SESSION_NAME' => 'Ocean ARK Server',
                'SERVER_PASSWORD' => '',
                'ADMIN_PASSWORD' => 'oceanadmin123',
                'MAX_PLAYERS' => '20',
                'GAME_MOD' => 'TheIsland'
            ]
        ]
    ],
    
    // Pricing per GB RAM (monthly in EUR)
    'pricing' => [
        'minecraft' => 2.99,
        'rust' => 3.99,
        'ark' => 4.99
    ],
    
    // Available RAM options (in GB)
    'ram_options' => [1, 2, 4, 6, 8, 12, 16],
    
    // Duration options
    'duration_options' => [
        '3_days' => ['days' => 3, 'discount' => 0],
        '1_week' => ['days' => 7, 'discount' => 0],
        '1_month' => ['days' => 30, 'discount' => 0.10] // 10% discount
    ]
];
?>