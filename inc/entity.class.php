<?php

/**
 * -------------------------------------------------------------------------
 * Costs plugin for GLPI
 * Copyright (C) 2018-2024 by the TICgal Team.
 *
 * https://github.com/ticgal/costs
 * -------------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of the Costs plugin.
 *
 * Costs plugin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Costs plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Costs. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @package   Costs
 * @author    the TICgal team
 * @copyright Copyright (c) 2018-2024 TICgal team
 * @license   AGPL License 3.0 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://tic.gal
 * @since     2018
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginCostsEntity extends CommonDBTM
{
    public static $rightname = 'entity';

    /**
     * getTypeName
     *
     * @param  mixed $nb
     * @return string
     */
    public static function getTypeName($nb = 0): string
    {
        return __('Costs', 'Costs');
    }

    /**
     * getTabNameForItem
     *
     * @param  mixed $item
     * @param  mixed $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        switch ($item::getType()) {
            case Entity::getType():
                return self::getTypeName();
            break;
        }
        return '';
    }

    /**
     * displayTabContentForItem
     *
     * @param  mixed $item
     * @param  mixed $tabnum
     * @param  mixed $withtemplate
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        switch ($item::getType()) {
            case Entity::getType():
                self::displayTabForEntity($item);
                break;
        }

        return true;
    }

    /**
     * getFromDBByEntity
     *
     * @param  mixed $entities_id
     * @return bool
     */
    public function getFromDBByEntity($entities_id): bool
    {
        global $DB;

        $req = $DB->request(['FROM' => self::getTable(),'WHERE' => ['entities_id' => $entities_id]]);
        if (count($req)) {
            foreach ($req as $result) {
                $this->fields = $result;
            }
            return true;
        } else {
            if ($entities_id > 0) {
                $id = $this->add(['entities_id' => $entities_id,'inheritance' => 1]);
            } else {
                $id = $this->add(['entities_id' => $entities_id]);
            }
            $this->getFromDB($id);
            return false;
        }
    }

    /**
     * displayTabForEntity
     *
     * @param  mixed $entity
     * @return bool
     */
    public static function displayTabForEntity(Entity $entity): bool
    {
        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return false;
        }
        $cost_config = new self();
        $cost_config->getFromDBByEntity($ID);
        $inheritance = $cost_config->fields['inheritance'];
        $config_id = $cost_config->fields['id'];

        $rand = mt_rand();
        $out = "<form name='costentity_form$rand' id='costentity_form$rand' method='post' action='";
        $out .= self::getFormUrl() . "'>";
        $out .= "<table class='tab_cadre_fixe'>";

        if ($ID > 0) {
            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td style='width: 400px;'>" . __('Inheritance of the parent entity') . "</td><td>";
            $out .= Dropdown::showYesNo("inheritance", $cost_config->fields['inheritance'], -1, ['display' => false,'use_checkbox' => true]);
            $out .= "</td></tr>\n";
        }

        if ($inheritance == 1) {
            $parent_id = self::getConfigID($entity->fields['entities_id']);
            $cost_config->getFromDB($parent_id);
            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Fixed cost') . "</td>";
            $out .= "<td><div style='color:rgb(34, 77, 194);padding: 5px;margin: 3px 0;border: 1px solid transparent;border-radius: 2px;background-color: rgba(34, 77, 194, .1);white-space: nowrap;font-style: italic;display: table;'><i style='margin-right: 2px;font-size: 0.7em;' class='fas fa-level-down-alt'></i>" . $cost_config->fields['fixed_cost'] . "</div>";
            $out .= "</td></tr>\n";

            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Time cost') . "</td><td>";
            $out .= "<div style='color:rgb(34, 77, 194);padding: 5px;margin: 3px 0;border: 1px solid transparent;border-radius: 2px;background-color: rgba(34, 77, 194, .1);white-space: nowrap;font-style: italic;display: table;'><i style='margin-right: 2px;font-size: 0.7em;' class='fas fa-level-down-alt'></i>" . $cost_config->fields['time_cost'] . "</div>";
            $out .= "</td></tr>\n";

            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Private task') . "</td><td>";
            $out .= "<div style='color:rgb(34, 77, 194);padding: 5px;margin: 3px 0;border: 1px solid transparent;border-radius: 2px;background-color: rgba(34, 77, 194, .1);white-space: nowrap;font-style: italic;display: table;'><i style='margin-right: 2px;font-size: 0.7em;' class='fas fa-level-down-alt'></i>" . Dropdown::getYesNo($cost_config->fields['cost_private']) . "</div>";
            $out .= "</td></tr>\n";

            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Auto billable ticket') . "</td><td>";
            $out .= "<div style='color:rgb(34, 77, 194);padding: 5px;margin: 3px 0;border: 1px solid transparent;border-radius: 2px;background-color: rgba(34, 77, 194, .1);white-space: nowrap;font-style: italic;display: table;'><i style='margin-right: 2px;font-size: 0.7em;' class='fas fa-level-down-alt'></i>" . Dropdown::getYesNo($cost_config->fields['auto_cost']) . "</div>";
            $out .= "</td></tr>\n";
        } else {
            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Fixed cost') . "</td><td>";
            $out .= "<input size='5' step='" . PLUGIN_COSTS_NUMBER_STEP . "' type='number' name='fixed_cost' value='" . $cost_config->fields['fixed_cost'] . "'>";
            $out .= "</td></tr>\n";

            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Time cost') . "</td><td>";
            $out .= "<input size='5' step='" . PLUGIN_COSTS_NUMBER_STEP . "' type='number' name='time_cost' value='" . $cost_config->fields['time_cost'] . "'>";
            $out .= "</td></tr>\n";

            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Private task') . "</td><td>";
            $out .= Dropdown::showYesNo("cost_private", $cost_config->fields['cost_private'], -1, ['display' => false]);
            $out .= "</td></tr>\n";

            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>" . __('Auto billable ticket') . "</td><td>";
            $out .= Dropdown::showYesNo("auto_cost", $cost_config->fields['auto_cost'], -1, ['display' => false]);
            $out .= "</td></tr>\n";
        }

        $out .= "<tr><td>";
        $out .= "<input type='hidden' name='entities_id' value='$ID'>";
        $out .= "</td></tr>\n";

        $out .= "<tr><td class='tab_bg_2 right'>";
        $out .= "<input type='submit' name='update' value='" . _sx('button', 'Update') . "' class='submit'>";
        $out .= "<input type='hidden' name='id' value='" . $config_id . "'>";
        $out .= "</td></tr>";
        $out .= "</table>";
        $out .= Html::closeForm(false);

        echo $out;

        if ($inheritance != 1) {
            PluginCostsEntity_Profile::showForEntity($entity);
        } else {
            PluginCostsEntity_Profile::showForParent($cost_config->fields['entities_id']);
        }

        return false;
    }

    /**
     * getConfigID
     *
     * @param  mixed $entities_id
     * @return mixed
     */
    public static function getConfigID($entities_id): mixed
    {

        $config = new self();
        $config->getFromDBByEntity($entities_id);
        if ($config->fields['inheritance']) {
            $entity = new Entity();
            if ($entity->getFromDB($entities_id)) {
                return self::getConfigID($entity->fields['entities_id']);
            }
        } else {
            return $config->fields['id'];
        }
    }

    /**
     * install
     *
     * @param  mixed $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $query = "CREATE TABLE IF NOT EXISTS `$table` (
                `id` int {$default_key_sign} NOT NULL auto_increment,
                `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                `fixed_cost` float NOT NULL default '0',
                `time_cost` float NOT NULL default '0',
                `cost_private` tinyint NOT NULL DEFAULT '0',
                `auto_cost` tinyint NOT NULL DEFAULT '0',
                `inheritance` tinyint NOT NULL DEFAULT '0',
                PRIMARY KEY (id),
                KEY entities_id (entities_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
            COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->query($query) or die($DB->error());
        } else {
            if (!$DB->fieldExists($table, 'auto_cost')) {
                $migration->displayMessage("Upgrading $table");
                $migration->addField($table, 'auto_cost', 'boolean');
                $migration->migrationOneTable($table);
            }
            if (!$DB->fieldExists($table, 'inheritance')) {
                $migration->displayMessage("Upgrading $table");
                $migration->addField($table, 'inheritance', 'boolean');
                $migration->migrationOneTable($table);
            }
        }
        $migration->executeMigration();
    }

    /**
     * unistall
     *
     * @param  mixed $migration
     * @return void
     */
    public static function unistall(Migration $migration): void
    {
        $table = self::getTable();
        $migration->displayMessage("Uninstalling $table");
        $migration->dropTable($table);
    }
}
