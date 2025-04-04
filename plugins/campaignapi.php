<?php
/**
 * Campaign API Plugin
 */

class campaignapi extends phplistPlugin
{
    public $name = 'Campaign API';
    public $description = 'REST API for campaigns';
    public $version = '1.0.0';
    public $enabled = true;
    
    function __construct()
    {
        // Fontos: először a szülő konstruktort hívjuk meg
        parent::__construct();
        $this->coderoot = dirname(__FILE__) . '/campaignapi/';
    }
}
