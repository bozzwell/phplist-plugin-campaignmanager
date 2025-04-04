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
    
    // Menü beállítások
    public $topMenuLinks = array(
        'main' => array('category' => 'campaigns'),
    );
    
    public $pageTitles = array(
        'main' => 'Campaign REST API',
    );
    
    // Konfigurációs beállítások
    public $settings = array(
        'campaign_api_key' => array(
            'value' => '',
            'description' => 'API kulcs a Campaign REST API-hoz',
            'type' => 'text',
            'allowempty' => true,
            'category' => 'Campaign API'
        ),
        'campaign_api_enforcessl' => array(
            'description' => 'SSL megkövetelése az API hívásoknál',
            'type' => 'boolean',
            'allowempty' => true,
            'value' => false,
            'category' => 'Campaign API'
        ),
        'campaign_api_ipaddress' => array(
            'description' => 'Engedélyezett IP cím az API hozzáféréshez',
            'type' => 'text',
            'allowempty' => true,
            'value' => '',
            'category' => 'Campaign API'
        )
    );
    
    // Alapértelmezett API kulcs
    private $api_key = 'test_api_key_123456';
    
    function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        parent::__construct();
        api_log('Plugin sikeresen betöltve');
    }
    
    // Admin menü
    public function adminmenu()
    {
        return array(
            'main' => 'Campaign REST API',
        );
    }
    
    // Beállítások megjelenítése
    public function displaySettings()
    {
        // Alap URL
        $base_url = isset($_SERVER['HTTP_HOST']) ? 
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : 
            'http://localhost';
        
        echo '<h3>Campaign REST API beállítások</h3>';
        echo '<p>Ez a plugin egy egyszerű REST API-t biztosít a phpList kampányok kezeléséhez.</p>';
        echo '<p>A naplófájl elérési útja: <code>/tmp/phplist_api_debug.log</code></p>';
        
        echo '<h4>API kulcs</h4>';
        echo '<p>Az API kulcs szükséges minden API kéréshez. Küldd el a <code>X-Api-Key</code> HTTP fejlécben, vagy <code>key</code> paraméterként.</p>';
        echo '<div class="well well-sm"><code>' . htmlspecialchars($this->api_key) . '</code></div>';
        echo '<p><em>Megjegyzés: Ez egy teszt API kulcs. A valós környezetben ezt biztonságosabbra kell cserélni.</em></p>';
        
        echo '<h4>API végpontok</h4>';
        echo '<div class="well well-sm">';
        echo '<p><strong>Kampányok listázása:</strong></p>';
        echo '<pre>GET ' . $base_url . '/?page=campaignmanager&pi=campaignmanager&action=list</pre>';
        echo '<p><strong>Kampány lekérése:</strong></p>';
        echo '<pre>GET ' . $base_url . '/?page=campaignmanager&pi=campaignmanager&action=get&id={id}</pre>';
        echo '<p><strong>Kampány státusz frissítése:</strong></p>';
        echo '<pre>GET ' . $base_url . '/?page=campaignmanager&pi=campaignmanager&action=status&id={id}&status={status}</pre>';
        echo '</div>';
    }
    
    // Oldal megjelenítése
    public function sendFormats()
    {
        return array('campaignmanager' => 'Campaign REST API');
    }
    
    // Oldal tartalom
    public function sendMessageTab($messageid = 0, $data = array())
    {
        return '<p>Campaign REST API</p>';
    }
    
    // Oldal feldolgozása
    public function sendMessageTabSave($messageid = 0, $data = array())
    {
        return true;
    }
    
    // Oldal kezelése
    public function processQueueStart()
    {
        return true;
    }
    
    // API kérés kezelése
    public function parseRouteRequest()
    {
        if (isset($_GET['pi']) && $_GET['pi'] == 'campaignmanager') {
            api_log('API kérés érkezett');
            
            // API kérés kezelése a külön fájlban
            include_once $this->coderoot . 'api.php';
            return true;
        }
        return false;
    }
}
