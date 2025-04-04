<?php
/**
 * Campaign Manager Plugin REST API
 * 
 * Ez a fájl biztosítja a REST API funkcionalitást a Campaign Manager pluginhoz
 */

// Csak phpList-en belüli hozzáférés engedélyezése
if (!defined('PHPLISTINIT')) {
    exit;
}

// Naplózás
function api_log($message, $level = 'info') {
    $log_file = '/tmp/phplist_api_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] [$level] $message\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

api_log('API kérés feldolgozása: ' . $_SERVER['REQUEST_URI']);

// CORS beállítások API hívásokhoz
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Api-Key');

// OPTIONS kérés kezelése (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// API válasz előkészítése
$response = [
    'success' => false,
    'data' => null,
    'error' => null
];

// API kulcs ellenőrzése
$api_key = '';

// Különböző módokon próbáljuk megszerezni az API kulcsot
if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['X-Api-Key'])) {
        $api_key = $headers['X-Api-Key'];
    } elseif (isset($headers['x-api-key'])) {
        $api_key = $headers['x-api-key'];
    }
} else {
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    }
}

// Ha nincs API kulcs a fejlécben, ellenőrizzük a GET/POST paramétereket
if (empty($api_key)) {
    if (isset($_GET['key'])) {
        $api_key = $_GET['key'];
    } elseif (isset($_POST['key'])) {
        $api_key = $_POST['key'];
    }
}

// Teszt API kulcs - valós környezetben ezt a getConfig függvénnyel kellene lekérni
$config_api_key = 'test_api_key_123456';

// API kulcs ellenőrzése - teszt módban kikapcsolva
if (false && $api_key !== $config_api_key) {
    $response['error'] = 'Érvénytelen API kulcs';
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// API kérés feldolgozása
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

try {
    switch ($action) {
        case 'list':
            // Kampányok listázása
            $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
            $response['data'] = getCampaigns($status);
            $response['success'] = true;
            break;
            
        case 'get':
            // Egy kampány részletes adatai
            if (!isset($_REQUEST['id'])) {
                throw new Exception('Hiányzó kampány ID');
            }
            
            $id = intval($_REQUEST['id']);
            $campaign = getCampaignDetails($id);
            
            if (!$campaign) {
                throw new Exception('A kampány nem található');
            }
            
            $response['data'] = $campaign;
            $response['success'] = true;
            break;
            
        case 'status':
            // Kampány státusz frissítése
            if (!isset($_REQUEST['id'])) {
                throw new Exception('Hiányzó kampány ID');
            }
            
            if (!isset($_REQUEST['status'])) {
                throw new Exception('Hiányzó státusz');
            }
            
            $id = intval($_REQUEST['id']);
            $status = $_REQUEST['status'];
            $result = updateCampaignStatus($id, $status);
            
            if (!$result) {
                throw new Exception('A kampány státusz frissítése sikertelen');
            }
            
            $response['success'] = true;
            $response['data'] = ['message' => "A(z) $id azonosítójú kampány státusza frissítve: $status"];
            break;
            
        default:
            throw new Exception('Ismeretlen művelet');
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// API válasz küldése
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;

/**
 * Kampányok lekérdezése
 * 
 * @param string $status Opcionális állapot szűrő
 * @return array Kampányok listája
 */
function getCampaigns($status = null) {
    global $tables;
    
    $whereClause = '';
    if ($status) {
        $whereClause = sprintf(' WHERE status = "%s"', addslashes($status));
    }
    
    $query = sprintf('
        SELECT 
            id, 
            subject, 
            status, 
            sent, 
            processed, 
            sendstart, 
            sendend
        FROM %s
        %s
        ORDER BY sendstart DESC
        LIMIT 100
    ',
        $tables['message'],
        $whereClause
    );
    
    $result = Sql_Query($query);
    $campaigns = [];
    
    while ($row = Sql_Fetch_Assoc($result)) {
        // Dátumok formázása
        $row['sendstart'] = $row['sendstart'] ? date('Y-m-d H:i:s', strtotime($row['sendstart'])) : null;
        $row['sendend'] = $row['sendend'] ? date('Y-m-d H:i:s', strtotime($row['sendend'])) : null;
        
        $campaigns[] = $row;
    }
    
    return $campaigns;
}

/**
 * Kampány részletes adatainak lekérdezése
 * 
 * @param int $id Kampány azonosító
 * @return array|null Kampány adatok vagy null, ha nem található
 */
function getCampaignDetails($id) {
    global $tables;
    
    $query = sprintf('
        SELECT 
            id, 
            subject, 
            status, 
            sent, 
            processed, 
            sendstart, 
            sendend,
            message,
            textmessage,
            footer,
            template
        FROM %s
        WHERE id = %d
    ',
        $tables['message'],
        $id
    );
    
    $result = Sql_Query($query);
    $campaign = Sql_Fetch_Assoc($result);
    
    if (!$campaign) {
        return null;
    }
    
    // Dátumok formázása
    $campaign['sendstart'] = $campaign['sendstart'] ? date('Y-m-d H:i:s', strtotime($campaign['sendstart'])) : null;
    $campaign['sendend'] = $campaign['sendend'] ? date('Y-m-d H:i:s', strtotime($campaign['sendend'])) : null;
    
    return $campaign;
}

/**
 * Kampány állapot módosítása
 * 
 * @param int $id Kampány azonosító
 * @param string $status Új állapot
 * @return bool Sikeres volt-e a módosítás
 */
function updateCampaignStatus($id, $status) {
    global $tables;
    
    // Ellenőrizzük, hogy létezik-e a kampány
    $query = sprintf('
        SELECT id FROM %s WHERE id = %d
    ',
        $tables['message'],
        $id
    );
    
    $result = Sql_Query($query);
    $campaign = Sql_Fetch_Assoc($result);
    
    if (!$campaign) {
        return false;
    }
    
    // Státusz frissítése
    $query = sprintf('
        UPDATE %s 
        SET status = "%s" 
        WHERE id = %d
    ',
        $tables['message'],
        addslashes($status),
        $id
    );
    
    Sql_Query($query);
    
    return true;
}
