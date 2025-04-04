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
}
