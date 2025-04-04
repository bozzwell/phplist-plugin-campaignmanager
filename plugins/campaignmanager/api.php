<?php
/**
 * Campaign Manager Plugin REST API
 * 
 * Ez a fájl biztosítja a REST API funkcionalitást a Campaign Manager pluginhoz
 */

// Csak phpList-en belüli hozzáférés engedélyezése
if (!defined('PHPLISTINIT')) {
    define('PHPLISTINIT', 1);
    include_once '../../../admin/commonlib/lib/userlib.php';
    include_once '../../../admin/init.php';
}

// Hibakezelés bekapcsolása fejlesztési célokra
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ellenőrizzük, hogy API kérés-e
if (isset($_GET['api']) || isset($_POST['api'])) {
    // CORS beállítások API hívásokhoz
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
    
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
    
    // Plugin példány lekérése
    global $plugins;
    $plugin = $plugins['campaignmanager'];
    
    // API hitelesítés
    $api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
    $config_api_key = $plugin->getPluginOption('api_key');
    
    if (!$config_api_key) {
        // Ha még nincs beállítva API kulcs, generáljunk egyet
        $config_api_key = md5(uniqid(rand(), true));
        $plugin->setPluginOption('api_key', $config_api_key);
    }
    
    // API kulcs ellenőrzése
    if ($api_key !== $config_api_key) {
        $response['error'] = 'Érvénytelen API kulcs';
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // API kérés feldolgozása
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    
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
                
            case 'pause':
                // Kampány szüneteltetése
                if (!isset($_REQUEST['id'])) {
                    throw new Exception('Hiányzó kampány ID');
                }
                
                $id = intval($_REQUEST['id']);
                $result = updateCampaignStatus($id, 'suspended');
                
                if (!$result) {
                    throw new Exception('A kampány szüneteltetése sikertelen');
                }
                
                $response['success'] = true;
                $response['data'] = ['message' => "A(z) $id azonosítójú kampány szüneteltetve"];
                break;
                
            case 'resume':
                // Kampány folytatása
                if (!isset($_REQUEST['id'])) {
                    throw new Exception('Hiányzó kampány ID');
                }
                
                $id = intval($_REQUEST['id']);
                $result = updateCampaignStatus($id, 'inprocess');
                
                if (!$result) {
                    throw new Exception('A kampány folytatása sikertelen');
                }
                
                $response['success'] = true;
                $response['data'] = ['message' => "A(z) $id azonosítójú kampány folytatva"];
                break;
                
            case 'stop':
                // Kampány leállítása
                if (!isset($_REQUEST['id'])) {
                    throw new Exception('Hiányzó kampány ID');
                }
                
                $id = intval($_REQUEST['id']);
                $result = updateCampaignStatus($id, 'cancelled');
                
                if (!$result) {
                    throw new Exception('A kampány leállítása sikertelen');
                }
                
                $response['success'] = true;
                $response['data'] = ['message' => "A(z) $id azonosítójú kampány leállítva"];
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
}

/**
 * Kampányok lekérdezése
 * 
 * @param string $status Opcionális állapot szűrő
 * @return array Kampányok listája
 */
function getCampaigns($status = null) {
    global $tables, $GLOBALS;
    
    // Ellenőrizzük, hogy a phpList SQL függvények elérhetőek-e
    if (!function_exists('Sql_Query')) {
        include_once dirname(__FILE__) . '/../../../admin/commonlib/lib/userlib.php';
    }
    
    $whereClause = '';
    if ($status) {
        $whereClause = sprintf(' WHERE m.status = "%s"', addslashes($status));
    }
    
    $query = sprintf('
        SELECT 
            m.id, 
            m.subject, 
            m.status, 
            m.sent, 
            m.processed, 
            m.sendstart, 
            m.sendend,
            COUNT(um.messageid) AS total,
            SUM(CASE WHEN um.status = "sent" THEN 1 ELSE 0 END) AS sent_count,
            SUM(CASE WHEN um.status = "todo" THEN 1 ELSE 0 END) AS todo_count,
            SUM(CASE WHEN um.status = "failed" THEN 1 ELSE 0 END) AS failed_count
        FROM %s AS m
        LEFT JOIN %s AS um ON m.id = um.messageid
        %s
        GROUP BY m.id
        ORDER BY m.sendstart DESC
    ',
        $tables['message'],
        $tables['usermessage'],
        $whereClause
    );
    
    $result = Sql_Query($query);
    $campaigns = [];
    
    while ($row = Sql_Fetch_Assoc($result)) {
        // Dátumok formázása
        $row['sendstart'] = $row['sendstart'] ? date('Y-m-d H:i:s', strtotime($row['sendstart'])) : null;
        $row['sendend'] = $row['sendend'] ? date('Y-m-d H:i:s', strtotime($row['sendend'])) : null;
        
        // Haladás számítása
        $total = intval($row['total']);
        $sent = intval($row['sent_count']);
        $row['progress'] = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
        
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
    
    // Ellenőrizzük, hogy a phpList SQL függvények elérhetőek-e
    if (!function_exists('Sql_Query')) {
        include_once dirname(__FILE__) . '/../../../admin/commonlib/lib/userlib.php';
    }
    
    // Alapadatok lekérdezése
    $query = sprintf('SELECT * FROM %s WHERE id = %d', $tables['message'], intval($id));
    $result = Sql_Query($query);
    $campaign = Sql_Fetch_Assoc($result);
    
    if (!$campaign) {
        return null;
    }
    
    // Dátumok formázása
    $campaign['sendstart'] = $campaign['sendstart'] ? date('Y-m-d H:i:s', strtotime($campaign['sendstart'])) : null;
    $campaign['sendend'] = $campaign['sendend'] ? date('Y-m-d H:i:s', strtotime($campaign['sendend'])) : null;
    
    // Küldési statisztikák
    $query = sprintf('
        SELECT 
            status,
            COUNT(*) as count
        FROM %s
        WHERE messageid = %d
        GROUP BY status
    ', $tables['usermessage'], intval($id));
    
    $result = Sql_Query($query);
    $stats = [];
    
    while ($row = Sql_Fetch_Assoc($result)) {
        $stats[$row['status']] = $row['count'];
    }
    
    $campaign['statistics'] = $stats;
    
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
    
    // Ellenőrizzük, hogy a phpList SQL függvények elérhetőek-e
    if (!function_exists('Sql_Query')) {
        include_once dirname(__FILE__) . '/../../../admin/commonlib/lib/userlib.php';
    }
    
    $valid_statuses = ['inprocess', 'suspended', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        return false;
    }
    
    $query = sprintf('UPDATE %s SET status = "%s" WHERE id = %d', 
        $tables['message'], 
        addslashes($status), 
        intval($id)
    );
    
    return Sql_Query($query);
}
