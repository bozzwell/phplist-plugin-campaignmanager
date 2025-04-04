<?php
/**
 * Campaign REST API Plugin
 * 
 * Egyszerű REST API a phpList kampányok kezeléséhez
 */

// Naplózási funkció
function api_log($message, $level = 'info') {
    $log_file = '/tmp/phplist_api_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] [$level] $message\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

// Naplózás indítása
api_log('Plugin betöltése kezdődik');

class campaignmanager extends phplistPlugin
{
    public $name = 'Campaign REST API';
    public $description = 'Egyszerű REST API a kampányok kezeléséhez';
    public $version = '1.0.0';
    public $enabled = true;
    
    // Konfigurációs beállítások
    public $settings = array(
        'campaign_api_key' => array(
            'value' => '',
            'description' => 'API kulcs a Campaign REST API-hoz',
            'type' => 'text',
            'allowempty' => true,
            'category' => 'Campaign API'
        )
    );
    
    function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        parent::__construct();
        api_log('Plugin sikeresen betöltve');
    }
    
    // Beállítások megjelenítése
    public function displaySettings()
    {
        echo '<h3>Campaign REST API beállítások</h3>';
        echo '<p>Ez a plugin egy egyszerű REST API-t biztosít a phpList kampányok kezeléséhez.</p>';
        echo '<p>A naplófájl elérési útja: <code>/tmp/phplist_api_debug.log</code></p>';
    }
}
