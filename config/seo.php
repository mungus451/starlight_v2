<?php

/**
 * SEO & Social Meta Tag Configuration
 *
 * Defines global defaults for the application's metadata.
 * These values are used when a specific controller does not provide its own.
 */

return [
    // The main name of the application (e.g. "Starlight Dominion")
    'site_name' => 'Starlight Dominion',

    // The separator used between the page title and site name (e.g. "Home | Starlight Dominion")
    'separator' => ' | ',

    // Default description used for search engines and social shares
    'description' => 'A persistent browser-based space strategy MMO. Build your empire, forge alliances, and conquer the galaxy in a real-time persistent universe.',

    // High-Ranking Keyword List (40+ terms)
    // Targeted Niches: Browser MMO, 4X Strategy, Space Sim, PvP, Idle Games
    'keywords' => [
        // Core Genre
        'browser game',
        'space strategy mmo',
        '4x space game',
        'browser based game',
        'pbbg',
        'persistent browser based game',
        'text based rpg',
        'text mmo',
        
        // Gameplay Mechanics
        'real time strategy',
        'empire building game',
        'resource management game',
        'idle space game',
        'incremental game',
        'grand strategy browser',
        'tactical space combat',
        'fleet commander',
        'colony builder',
        
        // Themes
        'sci-fi mmo',
        'space warfare',
        'galactic conquest',
        'starship battles',
        'interstellar war',
        'space empire builder',
        'galaxy strategy',
        'future warfare',
        
        // Multiplayer/Social
        'pvp browser game',
        'alliance wars',
        'guild pvp',
        'clan wars',
        'online multiplayer strategy',
        'competitive multiplayer',
        'diplomacy game',
        'social strategy game',
        'mmo rts',
        
        // Accessibility/Platform
        'free to play strategy',
        'free browser mmorpg',
        'no download game',
        'play in browser',
        'mobile friendly strategy',
        'html5 strategy game',
        'indie browser game',
        'web based game',
        'pc strategy game',
        'mac strategy game',
        'lightweight mmo'
    ],

    // Default social share image (Open Graph / Twitter)
    // Should be an absolute URL or relative to public root. 
    'image' => '/background.avif',

    // Twitter Card configuration
    'twitter' => [
        'card' => 'summary_large_image', // summary, summary_large_image, app, player
        'site' => '@StarlightGame',      // The official Twitter handle of the game
    ],

    // Application base URL (fallback if APP_URL env is missing)
    // Used to generate canonical tags.
    'base_url_fallback' => 'http://localhost:8000'
];