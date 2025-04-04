<?php
/**
 * Campaign REST API Plugin
 * 
 * Egyszerű REST API a phpList kampányok kezeléséhez
 */

class campaignmanager extends phplistPlugin
{
    public $name = 'Campaign REST API';
    public $description = 'Egyszerű REST API a kampányok kezeléséhez';
    public $version = '1.0.0';
    public $enabled = true;
    
    private $api_key = '';
    
    function __construct()
    {
        parent::__construct();
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        
        // API kulcs betöltése
        $this->api_key = $this->getPluginOption('api_key');
        
        // API kérés kezelése
        if (isset($_GET['api']) && $_GET['api'] == 1) {
            $this->handleApiRequest();
        }
    }
    
    // API kérés kezelése
    private function handleApiRequest()
    {
        header('Content-Type: application/json');
        
        // API kulcs ellenőrzése
        $headers = apache_request_headers();
        $provided_key = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : '';
        
        if (empty($this->api_key) || $provided_key != $this->api_key) {
            echo json_encode(['error' => 'Érvénytelen API kulcs']);
            exit;
        }
        
        // Akció meghatározása
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        
        // Egyszerű válasz minden kérésre
        $response = [
            'status' => 'ok',
            'action' => $action,
            'message' => 'Az API működik'
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // Plugin aktiválása
    public function activate()
    {
        parent::activate();
        
        // API kulcs generálása, ha még nincs
        if (empty($this->api_key)) {
            $this->api_key = md5(uniqid(rand(), true));
            $this->setPluginOption('api_key', $this->api_key);
        }
        
        return true;
    }
    
    // Beállítások megjelenítése
    public function displaySettings()
    {
        // API kulcs újragenerálása
        if (isset($_POST['regenerate_api_key']) && $_POST['regenerate_api_key'] == 1) {
            $this->api_key = md5(uniqid(rand(), true));
            $this->setPluginOption('api_key', $this->api_key);
            echo '<div class="alert alert-success">Az API kulcs sikeresen újragenerálva</div>';
        }
        
        // Alap URL
        $base_url = isset($_SERVER['HTTP_HOST']) ? 
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : 
            'http://localhost';
        
        // Beállítások megjelenítése
        echo '<h3>Campaign REST API beállítások</h3>';
        
        echo '<p>Ez a plugin egy egyszerű REST API-t biztosít a phpList kampányok kezeléséhez.</p>';
        
        echo '<h4>API kulcs</h4>';
        echo '<p>Az API kulcs szükséges minden API kéréshez. Küldd el a <code>X-Api-Key</code> HTTP fejlécben.</p>';
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
    }
}
