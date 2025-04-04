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
    
    private $api_key = '';
    
    function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        parent::__construct();
        api_log('Plugin sikeresen betöltve');
        
        // API kulcs betöltése
        $this->api_key = getConfig('campaign_api_key');
        
        // Ha nincs API kulcs, generáljunk egyet
        if (empty($this->api_key)) {
            $this->api_key = md5(uniqid(rand(), true));
            saveConfig('campaign_api_key', $this->api_key);
            api_log('Új API kulcs generálva: ' . substr($this->api_key, 0, 5) . '...');
        }
        
        // API kérés kezelése
        if (isset($_GET['pi']) && $_GET['pi'] == 'campaignmanager' && isset($_GET['api']) && $_GET['api'] == 1) {
            $this->handleApiRequest();
        }
    }
    
    // Admin menü
    public function adminmenu()
    {
        return array(
            'main' => 'Campaign REST API',
        );
    }
    
    // API kérés kezelése
    private function handleApiRequest()
    {
        api_log('API kérés kezelése');
        header('Content-Type: application/json');
        
        // SSL ellenőrzése
        if (getConfig('campaign_api_enforcessl') && empty($_SERVER['HTTPS'])) {
            $this->sendError('SSL szükséges az API hívásokhoz', 403);
            return;
        }
        
        // IP cím ellenőrzése
        $allowed_ip = getConfig('campaign_api_ipaddress');
        if (!empty($allowed_ip) && $_SERVER['REMOTE_ADDR'] != $allowed_ip) {
            $this->sendError('Nem engedélyezett IP cím', 403);
            return;
        }
        
        // API kulcs ellenőrzése
        $provided_key = $this->getProvidedApiKey();
        if (empty($provided_key) || $provided_key != $this->api_key) {
            $this->sendError('Érvénytelen API kulcs', 403);
            return;
        }
        
        // Akció meghatározása
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        
        // Akció végrehajtása
        switch ($action) {
            case 'list':
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Kampányok listázása funkció hamarosan elérhető',
                    'data' => []
                ]);
                break;
            case 'get':
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Kampány lekérése funkció hamarosan elérhető',
                    'data' => []
                ]);
                break;
            case 'status':
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Kampány státusz frissítése funkció hamarosan elérhető',
                    'data' => []
                ]);
                break;
            default:
                $this->sendError('Ismeretlen akció', 400);
                break;
        }
        exit;
    }
    
    // API kulcs lekérése a kérésből
    private function getProvidedApiKey()
    {
        $provided_key = '';
        
        // Különböző módokon próbáljuk megszerezni az API kulcsot
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['X-Api-Key'])) {
                $provided_key = $headers['X-Api-Key'];
            } elseif (isset($headers['x-api-key'])) {
                $provided_key = $headers['x-api-key'];
            }
        } else {
            if (isset($_SERVER['HTTP_X_API_KEY'])) {
                $provided_key = $_SERVER['HTTP_X_API_KEY'];
            }
        }
        
        // Ha nincs API kulcs a fejlécben, ellenőrizzük a GET/POST paramétereket
        if (empty($provided_key)) {
            if (isset($_GET['key'])) {
                $provided_key = $_GET['key'];
            } elseif (isset($_POST['key'])) {
                $provided_key = $_POST['key'];
            }
        }
        
        return $provided_key;
    }
    
    // Hiba küldése
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
    
    // Beállítások megjelenítése
    public function displaySettings()
    {
        // API kulcs újragenerálása
        if (isset($_POST['regenerate_api_key']) && $_POST['regenerate_api_key'] == 1) {
            $this->api_key = md5(uniqid(rand(), true));
            saveConfig('campaign_api_key', $this->api_key);
            echo '<div class="alert alert-success">Az API kulcs sikeresen újragenerálva</div>';
        }
        
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
        
        echo '<form method="post">';
        echo '<input type="hidden" name="regenerate_api_key" value="1">';
        echo '<button type="submit" class="btn btn-default">API kulcs újragenerálása</button>';
        echo '</form>';
        
        echo '<h4>API végpontok</h4>';
        echo '<div class="well well-sm">';
        echo '<p><strong>Kampányok listázása:</strong></p>';
        echo '<pre>GET ' . $base_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=list</pre>';
        echo '<p><strong>Kampány lekérése:</strong></p>';
        echo '<pre>GET ' . $base_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=get&id={id}</pre>';
        echo '<p><strong>Kampány státusz frissítése:</strong></p>';
        echo '<pre>GET ' . $base_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=status&id={id}&status={status}</pre>';
        echo '</div>';
    }
}
