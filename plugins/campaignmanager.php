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
    public $documentationUrl = 'https://github.com/phpList/phplist-plugin-restapi';
    
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
        'campaign_api_limit' => array(
            'description' => 'Maximum API kérések száma percenként',
            'type' => 'integer',
            'value' => 60,
            'allowempty' => false,
            'min' => 1,
            'max' => 1200,
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
    
    // Adatbázis struktúra
    public $DBstruct = array(
        'api_request_log' => array(
            'id' => array('integer not null primary key auto_increment', 'ID'),
            'url' => array('text not null', ''),
            'cmd' => array('varchar(150) not null',''),
            'ip' => array('varchar(15) not null',''),
            'request' => array('text not null', ''),
            'date' => array('timestamp not null', ''),
            'index_1' => array('dateidx (date)',''),
            'index_2' => array('cmdidx (cmd)',''),
            'index_3' => array('ipidx (ip)',''),
        ),
    );
    
    private $api_key = '';
    
    function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        parent::__construct();
        
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
    
    // Plugin aktiválása
    public function activate()
    {
        parent::activate();
        
        // Ellenőrizzük, hogy létezik-e a naplózási tábla
        if (!Sql_Table_exists($GLOBALS['tables']['campaignmanager_api_request_log'])) {
            saveConfig(md5('plugin-campaignmanager-initialised'), false, 0);
            $this->initialise();
        }
        
        return true;
    }
    
    // Plugin inicializálása
    public function initialise()
    {
        parent::initialise();
        
        // API kulcs generálása, ha még nincs
        if (empty($this->api_key)) {
            $this->api_key = md5(uniqid(rand(), true));
            saveConfig('campaign_api_key', $this->api_key);
            api_log('Új API kulcs generálva: ' . substr($this->api_key, 0, 5) . '...');
        }
        
        return true;
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
        
        // Kérés naplózása
        $this->logApiRequest();
        
        // Akció meghatározása
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        
        // Akció végrehajtása
        switch ($action) {
            case 'list':
                $this->listCampaigns();
                break;
            case 'get':
                $this->getCampaign();
                break;
            case 'status':
                $this->updateCampaignStatus();
                break;
            default:
                $this->sendError('Ismeretlen akció', 400);
                break;
        }
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
    
    // API kérés naplózása
    private function logApiRequest()
    {
        $url = $_SERVER['REQUEST_URI'];
        $cmd = isset($_GET['action']) ? $_GET['action'] : 'list';
        $ip = $_SERVER['REMOTE_ADDR'];
        $request = json_encode($_REQUEST);
        
        $sql = sprintf(
            'INSERT INTO %s (url, cmd, ip, request, date) VALUES ("%s", "%s", "%s", "%s", NOW())',
            $GLOBALS['tables']['campaignmanager_api_request_log'],
            sql_escape($url),
            sql_escape($cmd),
            sql_escape($ip),
            sql_escape($request)
        );
        
        Sql_Query($sql);
    }
    
    // Hiba küldése
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
    
    // Kampányok listázása
    private function listCampaigns()
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        $where = '';
        if (!empty($status)) {
            $where = sprintf(' WHERE status = "%s"', sql_escape($status));
        }
        
        $sql = sprintf(
            'SELECT * FROM %s %s ORDER BY id DESC LIMIT %d OFFSET %d',
            $GLOBALS['tables']['message'],
            $where,
            $limit,
            $offset
        );
        
        $campaigns = [];
        $result = Sql_Query($sql);
        while ($row = Sql_Fetch_Assoc($result)) {
            $campaigns[] = [
                'id' => $row['id'],
                'subject' => $row['subject'],
                'status' => $row['status'],
                'sent' => $row['sent'],
                'processed' => $row['processed'],
                'sendstart' => $row['sendstart'],
                'embargo' => $row['embargo']
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $campaigns,
            'total' => count($campaigns),
            'limit' => $limit,
            'offset' => $offset
        ]);
        exit;
    }
    
    // Kampány lekérése
    private function getCampaign()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (empty($id)) {
            $this->sendError('Hiányzó kampány azonosító', 400);
            return;
        }
        
        $sql = sprintf(
            'SELECT * FROM %s WHERE id = %d',
            $GLOBALS['tables']['message'],
            $id
        );
        
        $result = Sql_Query($sql);
        $campaign = Sql_Fetch_Assoc($result);
        
        if (!$campaign) {
            $this->sendError('Kampány nem található', 404);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $campaign['id'],
                'subject' => $campaign['subject'],
                'status' => $campaign['status'],
                'sent' => $campaign['sent'],
                'processed' => $campaign['processed'],
                'sendstart' => $campaign['sendstart'],
                'embargo' => $campaign['embargo'],
                'message' => $campaign['message'],
                'textmessage' => $campaign['textmessage'],
                'footer' => $campaign['footer'],
                'template' => $campaign['template']
            ]
        ]);
        exit;
    }
    
    // Kampány státusz frissítése
    private function updateCampaignStatus()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        if (empty($id)) {
            $this->sendError('Hiányzó kampány azonosító', 400);
            return;
        }
        
        if (empty($status)) {
            $this->sendError('Hiányzó státusz', 400);
            return;
        }
        
        // Ellenőrizzük, hogy létezik-e a kampány
        $sql = sprintf(
            'SELECT id FROM %s WHERE id = %d',
            $GLOBALS['tables']['message'],
            $id
        );
        
        $result = Sql_Query($sql);
        $campaign = Sql_Fetch_Assoc($result);
        
        if (!$campaign) {
            $this->sendError('Kampány nem található', 404);
            return;
        }
        
        // Státusz frissítése
        $sql = sprintf(
            'UPDATE %s SET status = "%s" WHERE id = %d',
            $GLOBALS['tables']['message'],
            sql_escape($status),
            $id
        );
        
        Sql_Query($sql);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Kampány státusz sikeresen frissítve',
            'data' => [
                'id' => $id,
                'status' => $status
            ]
        ]);
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
        
        // Beállítások megjelenítése
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading"><h3 class="panel-title">Campaign REST API beállítások</h3></div>';
        echo '<div class="panel-body">';
        
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
        
        echo '</div>'; // panel-body
        echo '</div>'; // panel
    }
}
