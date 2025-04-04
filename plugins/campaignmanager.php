<?php

/**
 * Plugin that provides campaign management functionality
 */

class campaignmanager extends phplistPlugin
{
    public $name = 'Campaign Manager';
    public $description = 'Manage running campaigns';
    public $version = '1.0.0';
    public $enabled = true;
    public $authors = 'phpList Ltd';
    
    // Kategóriák beállítása
    public $topMenuLinks = array(
        'main' => array('category' => 'campaigns')
    );
    
    // Oldalcímek beállítása
    public $pageTitles = array(
        'main' => 'Campaign Manager'
    );
    
    // Konstruktor
    public function __construct()
    {
        // Kódgyökér beállítása
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        
        // Szülő konstruktor hívása
        parent::__construct();
    }
    
    // Admin menü beállítása
    public function adminmenu()
    {
        return array(
            'main' => 'Campaign Manager'
        );
    }
    
    // API kulcs generálása aktiváláskor
    public function activate()
    {
        parent::activate();
        
        // API kulcs generálása, ha még nincs
        $api_key = $this->getPluginOption('api_key');
        if (!$api_key) {
            $api_key = md5(uniqid(rand(), true));
            $this->setPluginOption('api_key', $api_key);
        }
        
        return true;
    }
    
    // Plugin beállítások hozzáadása a Settings oldalhoz
    public function displaySettings()
    {
        $api_key = $this->getPluginOption('api_key');
        if (!$api_key) {
            $api_key = md5(uniqid(rand(), true));
            $this->setPluginOption('api_key', $api_key);
        }
        
        // API kulcs újragenerálása
        if (isset($_POST['regenerate_api_key']) && $_POST['regenerate_api_key'] == 1) {
            $api_key = md5(uniqid(rand(), true));
            $this->setPluginOption('api_key', $api_key);
            $_SESSION['campaignmanager_api_message'] = 'Az API kulcs sikeresen újragenerálva';
        }
        
        // Üzenet megjelenítése
        if (isset($_SESSION['campaignmanager_api_message'])) {
            echo '<div class="actionresult">' . htmlspecialchars($_SESSION['campaignmanager_api_message']) . '</div>';
            unset($_SESSION['campaignmanager_api_message']);
        }
        
        // Alapértelmezett webhely URL
        $website_url = '';
        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_SCHEME'])) {
            $website_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $website_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'];
        }
        
        // API beállítások megjelenítése
        echo '<h3>Campaign Manager API beállítások</h3>';
        
        echo '<div class="note">
            <p>A Campaign Manager plugin REST API-t biztosít, amellyel távolról is lekérdezheted és kezelheted a kampányokat.</p>
            <p>Az API használatához szükséges egy API kulcs, amelyet minden kéréshez csatolni kell.</p>
        </div>';
        
        echo '<h4>API kulcs</h4>';
        echo '<p>Az API kulcsot a <code>X-API-KEY</code> HTTP fejlécben kell elküldeni minden API kéréshez.</p>';
        echo '<div class="apikey">' . htmlspecialchars($api_key) . '</div>';
        
        echo '<form method="post">';
        echo '<input type="hidden" name="regenerate_api_key" value="1">';
        echo '<button type="submit" class="button">API kulcs újragenerálása</button>';
        echo '</form>';
        
        echo '<h4>API végpontok</h4>';
        echo '<div class="endpoints">';
        echo '<h5>Kampányok listázása</h5>';
        echo '<pre>GET ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=list</pre>';
        
        echo '<h5>Egy kampány részletes adatai</h5>';
        echo '<pre>GET ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=get&id={kampány_id}</pre>';
        
        echo '<h5>Kampány szüneteltetése</h5>';
        echo '<pre>POST ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=pause&id={kampány_id}</pre>';
        
        echo '<h5>Kampány folytatása</h5>';
        echo '<pre>POST ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=resume&id={kampány_id}</pre>';
        
        echo '<h5>Kampány leállítása</h5>';
        echo '<pre>POST ' . $website_url . '/?page=campaignmanager&pi=campaignmanager&api=1&action=stop&id={kampány_id}</pre>';
        echo '</div>';
        
        // Stílusok
        echo '<style>
            .apikey {
                padding: 10px;
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                font-family: monospace;
                margin: 10px 0;
            }
            .endpoints {
                margin: 20px 0;
            }
            .endpoints h5 {
                margin-top: 15px;
                margin-bottom: 5px;
            }
            .endpoints pre {
                background-color: #f8f9fa;
                padding: 10px;
                border: 1px solid #ddd;
                overflow-x: auto;
            }
            .note {
                padding: 10px;
                margin: 10px 0;
                background-color: #d9edf7;
                border: 1px solid #bce8f1;
                color: #31708f;
                border-radius: 4px;
            }
            .actionresult {
                padding: 10px;
                margin: 10px 0;
                background-color: #dff0d8;
                border: 1px solid #d6e9c6;
                color: #3c763d;
                border-radius: 4px;
            }
            .button {
                padding: 5px 10px;
                margin: 2px;
                cursor: pointer;
            }
        </style>';
    }
}
