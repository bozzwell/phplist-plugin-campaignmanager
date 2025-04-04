<?php

/**
 * Campaign Manager Plugin
 */

class campaignmanager extends phplistPlugin
{
    public $name = 'Campaign Manager';
    public $description = 'Manage running campaigns';
    public $version = '1.0.0';
    public $enabled = true;
    public $authors = 'phpList Ltd';
    
    function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/campaignmanager/';
        parent::__construct();
    }
    
    function adminMenu()
    {
        return array(
            'main' => 'Campaign Manager'
        );
    }
}
