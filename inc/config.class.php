<?php

if(!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
}

class PluginCostsConfig extends CommonDBTM {
	static private $_instance = null;

	public function __construct() {
		global $DB;
		if ($DB->tableExists(self::getTable())) {
			$this->getFromDB(1);
		}
	}
	/**
	* Summary of canCreate
	* @return boolean
	*/
	static function canCreate() {
		return Session::haveRight('config', UPDATE);
	}

	/**
	* Summary of canView
	* @return boolean
	*/
	static function canView() {
		return Session::haveRight('config', READ);
	}

	/**
	* Summary of canUpdate
	* @return boolean
	*/
	static function canUpdate() {
		return Session::haveRight('config', UPDATE);
	}

	/**
	* Summary of getTypeName
	* @param mixed $nb plural
	* @return mixed
	*/
	static function getTypeName($nb = 0) {
		return __("Costs", "costs");
	}

	/**
	* Summary of getInstance
	* @return PluginProcessmakerConfig
	*/
	static function getInstance() {

		if (!isset(self::$_instance)) {
			self::$_instance = new self();
			if (!self::$_instance->getFromDB(1)) {
				self::$_instance->getEmpty();
			}
		}
		return self::$_instance;
	}

	public static function getConfig($update = false) {
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
	static function showConfigForm() {
		global $CFG_GLPI;

		$config = new self();
		$config->getFromDB(1);

		$config->showFormHeader(['colspan' => 4]);

		echo "<tr class='tab_bg_1'>";
		echo "<td >".__("Add task description on cost", "cost")."</td><td >";
		Dropdown::showYesNo("taskdescription", $config->fields["taskdescription"]);
		echo "</td></tr>\n";

		$config->showFormButtons(['candel'=>false]);

		return false;
	}

	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		global $LANG;

		if ($item->getType()=='Config') {
			return __("Costs", "costs");
		}
		return '';
	}

	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

		if ($item->getType()=='Config') {
			self::showConfigForm($item);
		}
		return true;
	}

	public static function install(Migration $migration) {
		global $DB;

		$default_charset = DBConnection::getDefaultCharset();
		$default_collation = DBConnection::getDefaultCollation();
		$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

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

			$DB->query($query) or die ($DB->error());
			$config->add([
				'id' => 1,
				'taskdescription' => 0,
			]);
		}
	}
}