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

class PluginCostsEntity_Profile extends CommonDBRelation
{
    public static $itemtype_1 = 'Entity';
    public static $items_id_1 = 'entities_id';
    public static $itemtype_2 = 'Profile';
    public static $items_id_2 = 'profiles_id';

    /**
     * showForEntity
     *
     * @param  mixed $entity
     * @return void
     */
    public static function showForEntity(Entity $entity): void
    {
        $instID = $entity->fields['id'];

        $canedit = $entity->canUpdateItem();
        $rand    = mt_rand();

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='entityprofile_form$rand' id='entityprofile_form$rand' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='7'>" . __('Associate to a profile') . "</th></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Profile') . "</td>";
            echo "<td>";
            $used_profiles = self::getUsedProfiles($instID, true);
            Profile::dropdown(['name' => 'profiles_id','rand' => $rand,'used' => $used_profiles,'condition' => ['interface' => 'central']]);
            echo "</td>";
            echo "<td>" . __('Fixed cost') . "</td>";
            echo "<td>";
            Dropdown::showNumber('fixed_cost', ['step' => PLUGIN_COSTS_NUMBER_STEP,'rand' => $rand,'toadd' => [0 => Dropdown::EMPTY_VALUE]]);
            echo "</td>";
            echo "<td>" . __('Time cost') . "</td>";
            echo "<td>";
            Dropdown::showNumber('time_cost', ['step' => PLUGIN_COSTS_NUMBER_STEP,'rand' => $rand,'toadd' => [0 => Dropdown::EMPTY_VALUE]]);
            echo "</td>";
            echo "<td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
            echo "<input type='hidden' name='entities_id' value='$instID'>";
            echo "</td></tr>";

            echo "</table>";

            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . __('Profile') . "</th>";
        $header_end .= "<th>" . __('Fixed cost') . "</th>";
        $header_end .= "<th>" . __('Time cost') . "</th>";
        echo "<tr>";
        echo $header_begin . $header_top . $header_end;

        $list = self::getUsedProfiles($instID);

        foreach ($list as $data) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                echo "</td>";
            }
            echo "<td class='center'>";
            echo Dropdown::getDropdownName("glpi_profiles", $data['profiles_id']) . "</td>";
            echo "<td class='center'>" . $data["fixed_cost"] . "</td>";
            echo "<td class='center'>" . $data["time_cost"] . "</td>";
            echo "</tr>";
        }
        echo $header_begin . $header_bottom . $header_end;
        echo "</table>";
        if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }

    /**
     * showForParent
     *
     * @param  mixed $entities_id
     * @return void
     */
    public static function showForParent($entities_id): void
    {
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        $header_end .= "<th>" . __('Profile') . "</th>";
        $header_end .= "<th>" . __('Fixed cost') . "</th>";
        $header_end .= "<th>" . __('Time cost') . "</th>";
        echo "<tr>";
        echo $header_begin . $header_top . $header_end;

        $list = self::getUsedProfiles($entities_id);

        foreach ($list as $data) {
            echo "<tr class='tab_bg_1' style='color:rgb(34, 77, 194);padding: 5px;margin: 3px 0;border: 1px solid transparent;border-radius: 2px;background-color: rgba(34, 77, 194, .1);white-space: nowrap;font-style: italic;'>";
            echo "<td class='center'><i style='margin-right: 2px;font-size: 0.7em;' class='fas fa-level-down-alt'></i>";
            echo Dropdown::getDropdownName("glpi_profiles", $data['profiles_id']) . "</td>";
            echo "<td class='center'>" . $data["fixed_cost"] . "</td>";
            echo "<td class='center'>" . $data["time_cost"] . "</td>";
            echo "</tr>";
        }
        echo $header_begin . $header_bottom . $header_end;
        echo "</table>";
        echo "</div>";
    }

    /**
     * getUsedProfiles
     *
     * @param  mixed $entities_id
     * @param  mixed $only_id
     * @return array
     */
    public static function getUsedProfiles($entities_id, $only_id = false): array
    {
        global $DB;

        $profiles = [];

        $query = [
            'FROM' => self::getTable(),
            'WHERE' => [
                'entities_id' => $entities_id
            ]
        ];
        if ($only_id) {
            foreach ($DB->request($query) as $row) {
                $profiles[] = $row['profiles_id'];
            }
        } else {
            foreach ($DB->request($query) as $row) {
                $profiles[] = $row;
            }
        }

        return $profiles;
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

        $default_charset    = DBConnection::getDefaultCharset();
        $default_collation  = DBConnection::getDefaultCollation();
        $default_key_sign   = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = "CREATE TABLE IF NOT EXISTS `$table` (
				`id` INT {$default_key_sign} NOT NULL auto_increment,
				`entities_id` INT {$default_key_sign} NOT NULL DEFAULT '0',
				`profiles_id` INT {$default_key_sign} NOT NULL DEFAULT '0',
				`fixed_cost` float NOT NULL default '0',
				`time_cost` float NOT NULL default '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `unicity` (`entities_id`,`profiles_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
            COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->query($query) or die($DB->error());
        }
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
