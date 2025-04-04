<?php
/**
 * Campaign REST API Plugin
 * 
 * Egyszerű REST API a phpList kampányok kezeléséhez
 */

// Hibakezelés beállítása
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Naplózási funkció
function api_log($message, $level = 'info') {
    $log_file = '/tmp/phplist_api_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] [$level] $message\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

// Naplózás indítása
api_log('Plugin betöltése kezdődik');

try {
    class campaignmanager extends phplistPlugin
    {
        public $name = 'Campaign REST API';
        public $description = 'Egyszerű REST API a kampányok kezeléséhez';
        public $version = '1.0.0';
        public $enabled = true;
        
        private $api_key = '';
        
        function __construct()
        {
            try {
                api_log('Konstruktor indítása');
                
                // Szülő konstruktor hívása
                parent::__construct();
                
                api_log('Szülő konstruktor sikeresen lefutott');
                
                // Kódgyökér beállítása
                $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
                
                // API kulcs betöltése
                $this->api_key = $this->getPluginOption('api_key');
                api_log('API kulcs betöltve: ' . substr($this->api_key, 0, 5) . '...');
                
                // API kérés kezelése
                if (isset($_GET['api']) && $_GET['api'] == 1) {
                    api_log('API kérés érkezett, kezelés indítása');
                    $this->handleApiRequest();
                }
                
                api_log('Konstruktor sikeresen befejezve');
            } catch (Exception $e) {
                api_log('Hiba a konstruktorban: ' . $e->getMessage(), 'error');
                // Nem dobunk tovább kivételt, hogy ne okozzon fehér képernyőt
            }
        }
        
        // API kérés kezelése
        private function handleApiRequest()
        {
            try {
                api_log('API kérés kezelése');
                header('Content-Type: application/json');
                
                // API kulcs ellenőrzése - egyszerűsített verzió
                $provided_key = '';
                
                // Különböző módokon próbáljuk megszerezni az API kulcsot
                if (function_exists('apache_request_headers')) {
                    $headers = apache_request_headers();
                    api_log('apache_request_headers() elérhető');
                    if (isset($headers['X-Api-Key'])) {
                        $provided_key = $headers['X-Api-Key'];
                    } elseif (isset($headers['x-api-key'])) {
                        $provided_key = $headers['x-api-key'];
                    }
                } else {
                    api_log('apache_request_headers() nem elérhető, HTTP_ fejléceket használunk');
                    if (isset($_SERVER['HTTP_X_API_KEY'])) {
                        $provided_key = $_SERVER['HTTP_X_API_KEY'];
                    }
                }
                
                // Ha nincs API kulcs a fejlécben, ellenőrizzük a GET/POST paramétereket
                if (empty($provided_key)) {
                    api_log('Nincs API kulcs a fejlécben, GET/POST paraméterek ellenőrzése');
                    if (isset($_GET['key'])) {
                        $provided_key = $_GET['key'];
                    } elseif (isset($_POST['key'])) {
                        $provided_key = $_POST['key'];
                    }
                }
                
                api_log('Kapott API kulcs: ' . substr($provided_key, 0, 5) . '...');
                api_log('Elvárt API kulcs: ' . substr($this->api_key, 0, 5) . '...');
                
                if (empty($this->api_key) || $provided_key != $this->api_key) {
                    api_log('Érvénytelen API kulcs', 'warning');
                    echo json_encode(['error' => 'Érvénytelen API kulcs']);
                    exit;
                }
                
                // Akció meghatározása
                $action = isset($_GET['action']) ? $_GET['action'] : 'list';
                api_log('Kért akció: ' . $action);
                
                // Egyszerű válasz minden kérésre
                $response = [
                    'status' => 'ok',
                    'action' => $action,
                    'message' => 'Az API működik'
                ];
                
                api_log('Válasz küldése: ' . json_encode($response));
                echo json_encode($response);
                exit;
            } catch (Exception $e) {
                api_log('Hiba az API kérés kezelése során: ' . $e->getMessage(), 'error');
                echo json_encode(['error' => 'Belső hiba történt: ' . $e->getMessage()]);
                exit;
            }
        }
        
        // Plugin aktiválása
        public function activate()
        {
            try {
                api_log('Plugin aktiválása');
                parent::activate();
                
                // API kulcs generálása, ha még nincs
                if (empty($this->api_key)) {
                    $this->api_key = md5(uniqid(rand(), true));
                    $this->setPluginOption('api_key', $this->api_key);
                    api_log('Új API kulcs generálva: ' . substr($this->api_key, 0, 5) . '...');
                }
                
                api_log('Plugin sikeresen aktiválva');
                return true;
            } catch (Exception $e) {
                api_log('Hiba a plugin aktiválása során: ' . $e->getMessage(), 'error');
                return false;
            }
        }
        
        // Beállítások megjelenítése
        public function displaySettings()
        {
            try {
                api_log('Beállítások megjelenítése');
                
                // API kulcs újragenerálása
                if (isset($_POST['regenerate_api_key']) && $_POST['regenerate_api_key'] == 1) {
                    $this->api_key = md5(uniqid(rand(), true));
                    $this->setPluginOption('api_key', $this->api_key);
                    api_log('API kulcs újragenerálva: ' . substr($this->api_key, 0, 5) . '...');
                    echo '<div style="padding: 10px; margin: 10px 0; background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d;">Az API kulcs sikeresen újragenerálva</div>';
                }
                
                // Alap URL
                $base_url = isset($_SERVER['HTTP_HOST']) ? 
                    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : 
                    'http://localhost';
                
                // Beállítások megjelenítése
                echo '<h3>Campaign REST API beállítások</h3>';
                
                echo '<p>Ez a plugin egy egyszerű REST API-t biztosít a phpList kampányok kezeléséhez.</p>';
                
                echo '<p>A naplófájl elérési útja: <code>/tmp/phplist_api_debug.log</code></p>';
                
                echo '<h4>API kulcs</h4>';
                echo '<p>Az API kulcs szükséges minden API kéréshez. Küldd el a <code>X-Api-Key</code> HTTP fejlécben, vagy <code>key</code> paraméterként.</p>';
                echo '<div style="padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; font-family: monospace;">' . 
                    htmlspecialchars($this->api_key) . 
                    '</div>';
                
                echo '<form method="post" style="margin-top: 10px;">';
                echo '<input type="hidden" name="regenerate_api_key" value="1">';
                echo '<button type="submit" style="padding: 5px 10px;">API kulcs újragenerálása</button>';
                echo '</form>';
                
                echo '<h4>API végpontok</h4>';
                echo '<div style="margin-top: 10px;">';
                echo '<p><strong>Teszt végpont:</strong></p>';
                echo '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;">GET ' . 
                    $base_url . '/?page=campaignmanager&pi=campaignmanager&api=1</pre>';
                echo '</div>';
                
                api_log('Beállítások sikeresen megjelenítve');
            } catch (Exception $e) {
                api_log('Hiba a beállítások megjelenítése során: ' . $e->getMessage(), 'error');
                echo '<div style="padding: 10px; margin: 10px 0; background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442;">Hiba történt: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
    
    api_log('Plugin osztály sikeresen definiálva');
} catch (Exception $e) {
    api_log('Kritikus hiba a plugin betöltése során: ' . $e->getMessage(), 'critical');
}
