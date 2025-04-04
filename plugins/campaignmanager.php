<?php
/**
 * Plugin that provides campaign management functionality.
 * 
 * This plugin allows you to:
 * - View running campaigns
 * - Check campaign status
 * - Pause/stop campaigns
 */
defined('PHPLISTINIT') || die;

class campaignmanager extends phplistPlugin
{
    public $name = 'Campaign Manager';
    public $description = 'Manage running campaigns, view status, and control them';
    public $version = '1.0.0';
    public $documentationUrl = '';
    public $topMenuLinks = array(
        'main' => array('category' => 'campaigns'),
    );
    public $pageTitles = array(
        'main' => 'Campaign Manager',
    );

    public function __construct()
    {
        $this->coderoot = dirname(__FILE__).'/campaignmanager/';
        parent::__construct();
    }

    public function adminmenu()
    {
        return array(
            'main' => 'Campaign Manager',
        );
    }
}
