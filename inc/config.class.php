<?php

use Glpi\Application\View\TemplateRenderer;

if(!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginCostsConfig extends CommonDBTM
{
    private static $_instance = null;

    public function __construct()
    {
        global $DB;
        if ($DB->tableExists(self::getTable())) {
            $this->getFromDB(1);
        }
    }
    /**
    * Summary of canCreate
    * @return boolean
    */
    public static function canCreate()
    {
        return Session::haveRight('config', UPDATE);
    }

    /**
    * Summary of canView
    * @return boolean
    */
    public static function canView()
    {
        return Session::haveRight('config', READ);
    }

    /**
    * Summary of canUpdate
    * @return boolean
    */
    public static function canUpdate()
    {
        return Session::haveRight('config', UPDATE);
    }

    /**
    * Summary of getTypeName
    * @param mixed $nb plural
    * @return mixed
    */
    public static function getTypeName($nb = 0)
    {
        return __("Costs", "costs");
    }

    public static function getInstance()
    {

        if (!isset(self::$_instance)) {
            self::$_instance = new self();
            if (!self::$_instance->getFromDB(1)) {
                self::$_instance->getEmpty();
            }
        }
        return self::$_instance;
    }

    public static function getConfig($update = false)
    {
        static $config = null;
        if (is_null($config)) {
            $config = new self();
        }
        if ($update) {
            $config->getFromDB(1);
        }
        return $config;
    }

    /**
    * Summary of showConfigForm
    * @param mixed $item is the config
    * @return boolean
    */
    public static function showConfigForm()
    {
        $config = self::getInstance();

        $plugin = new Plugin();
        $template = "@costs/config.html.twig";
        $template_options = [
            'item' 		=> $config,
            'credit'	=> ($plugin->isInstalled('credit') && $plugin->isActivated('credit')),
        ];
        TemplateRenderer::getInstance()->display($template, $template_options);

        return false;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $LANG;

        if ($item->getType() == 'Config') {
            return __("Costs", "costs");
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Config') {
            self::showConfigForm($item);
        }
        return true;
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset 	= DBConnection::getDefaultCharset();
        $default_collation 	= DBConnection::getDefaultCollation();
        $default_key_sign 	= DBConnection::getDefaultPrimaryKeySignOption();

        $table  = self::getTable();
        $config = new self();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            //Install

            $query = "CREATE TABLE `$table` (
				`id` int {$default_key_sign} NOT NULL auto_increment,
				`taskdescription` tinyint NOT NULL default '0',
				PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->query($query) or die($DB->error());
            $config->add([
                'id' => 1,
                'taskdescription' => 0,
            ]);
        }
    }
}
