<?php
/**
 * Plugin that provides campaign management functionality with REST API support.
 * 
 * This plugin allows you to:
 * - View running campaigns
 * - Check campaign status
 * - Pause/stop campaigns
 * - Access campaign data via REST API
 */
defined('PHPLISTINIT') || die;

class campaignmanager extends phplistPlugin
{
    public $name = 'Campaign Manager';
    public $description = 'Manage running campaigns, view status, and control them via UI or REST API';
    public $version = '1.0.0';
    public $documentationUrl = '';
    public $topMenuLinks = array(
        'main' => array('category' => 'campaigns'),
    );
    public $pageTitles = array(
        'main' => 'Campaign Manager',
        'settings' => 'API beállítások'
    );

    public function __construct()
    {
        $this->coderoot = dirname(__FILE__).'/campaignmanager/';
        parent::__construct();
        
        // API kérés kezelése - hibakezelést adunk hozzá
        if (isset($_GET['page']) && $_GET['page'] == 'campaignmanager' && 
            isset($_GET['pi']) && $_GET['pi'] == 'campaignmanager' &&
            (isset($_GET['api']) || isset($_POST['api']))) {
            
            // Hibakezelés bekapcsolása fejlesztési célokra
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
            
            // Biztonságosan betöltjük az API fájlt
            if (file_exists($this->coderoot . 'api.php')) {
                include_once $this->coderoot . 'api.php';
            }
        }
    }

    public function adminmenu()
    {
        return array(
            'main' => 'Campaign Manager',
            'settings' => 'API beállítások'
        );
    }
    
    /**
     * API kulcs generálása, ha még nincs
     */
    public function activate()
    {
        parent::activate();
        
        // API kulcs generálása, ha még nincs - phpList natív függvényeket használunk
        $api_key = $this->getPluginOption('api_key');
        if (!$api_key) {
            $api_key = md5(uniqid(rand(), true));
            $this->setPluginOption('api_key', $api_key);
        }
        
        return true;
    }
}
