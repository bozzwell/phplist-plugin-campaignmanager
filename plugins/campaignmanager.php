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
        'main' => 'Campaign Manager',
        'settings' => 'API beállítások'
    );
    
    // Konstruktor
    public function __construct()
    {
        // Kódgyökér beállítása
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        
        // API kérés kezelése
        if (isset($_GET['page']) && $_GET['page'] == 'campaignmanager' && 
            isset($_GET['pi']) && $_GET['pi'] == 'campaignmanager' &&
            (isset($_GET['api']) || isset($_POST['api']))) {
            
            // Biztonságosan betöltjük az API fájlt
            if (file_exists($this->coderoot . 'api.php')) {
                include_once $this->coderoot . 'api.php';
            }
        }
        
        // Szülő konstruktor hívása
        parent::__construct();
    }
    
    // Admin menü beállítása
    public function adminmenu()
    {
        return array(
            'main' => 'Campaign Manager',
            'settings' => 'API beállítások'
        );
    }
    
    // API kulcs generálása, ha még nincs
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
}
