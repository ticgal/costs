<?php

/**
 * -------------------------------------------------------------------------
 * Costs plugin for GLPI
 * Copyright (C) 2018 - 2026 by the TICGAL Team.
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
 * @author    the TICGAL team
 * @copyright Copyright (C) 2018 - 2026 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://tic.gal
 * @since     2018
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Costs;

use CommonDBRelation;
use DBConnection;
use Dropdown;
use Html;
use Migration;
use Profile;
use Session;
use Toolbox;

class EntityProfile extends CommonDBRelation
{
    public static ?string $itemtype_1 = 'Entity';
    public static ?string $items_id_1 = 'entities_id';
    public static ?string $itemtype_2 = 'Profile';
    public static ?string $items_id_2 = 'profiles_id';

    /**
     * Same right as the entity tab where the rates are managed.
     */
    public static function canCreate(): bool
    {
        return Session::haveRight('entity', UPDATE);
    }

    public static function canPurge(): bool
    {
        return Session::haveRight('entity', UPDATE);
    }

    public function canCreateItem(): bool
    {
        return $this->canUpdateEntity();
    }

    public function canPurgeItem(): bool
    {
        return $this->canUpdateEntity();
    }

    /**
     * The profile is only referenced: rights and visibility come from the entity.
     */
    private function canUpdateEntity(): bool
    {
        if (!isset($this->fields['entities_id'])) {
            return false;
        }
        $entity = new \Entity();

        return $entity->can((int) $this->fields['entities_id'], UPDATE);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareInputForAdd($input)
    {
        $profile = new Profile();
        if (
            !isset($input['entities_id'], $input['profiles_id'])
            || !$profile->getFromDB((int) $input['profiles_id'])
            || $profile->fields['interface'] !== 'central'
        ) {
            return false;
        }

        $input = [
            'entities_id' => (int) $input['entities_id'],
            'profiles_id' => (int) $input['profiles_id'],
            'fixed_cost'  => (float) ($input['fixed_cost'] ?? 0),
            'time_cost'   => (float) ($input['time_cost'] ?? 0),
        ];

        // unicity key (entities_id, profiles_id)
        if (countElementsInTable(self::getTable(), ['entities_id' => $input['entities_id'], 'profiles_id' => $input['profiles_id']]) > 0) {
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    public function getForbiddenStandardMassiveAction(): array
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'clone';
        $forbidden[] = 'add_transfer_list';

        return $forbidden;
    }

    /**
     * showForEntity
     *
     * @param  \Entity $entity
     * @return bool
     */
    public static function showForEntity(\Entity $entity): bool
    {
        $instID = (int) $entity->fields['id'];

        $canedit = $entity->can($instID, UPDATE);
        $rand    = mt_rand();

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='entityprofile_form$rand' id='entityprofile_form$rand' method='post' action='" . htmlescape(Toolbox::getItemTypeFormURL(__CLASS__)) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='7'>" . htmlescape(__('Associate to a profile')) . "</th></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . htmlescape(__('Profile')) . "</td>";
            echo "<td>";
            $used_profiles = self::getUsedProfiles($instID, true);
            Profile::dropdown(['name' => 'profiles_id','rand' => $rand,'used' => $used_profiles,'condition' => ['interface' => 'central']]);
            echo "</td>";
            echo "<td>" . htmlescape(__('Fixed cost')) . "</td>";
            echo "<td>";
            Dropdown::showNumber('fixed_cost', ['step' => PLUGIN_COSTS_NUMBER_STEP,'rand' => $rand,'toadd' => [0 => Dropdown::EMPTY_VALUE], 'max' => 1000]);
            echo "</td>";
            echo "<td>" . htmlescape(__('Time cost')) . "</td>";
            echo "<td>";
            Dropdown::showNumber('time_cost', ['step' => PLUGIN_COSTS_NUMBER_STEP,'rand' => $rand,'toadd' => [0 => Dropdown::EMPTY_VALUE], 'max' => 1000]);
            echo "</td>";
            echo "<td class='center'>";
            echo "<input type='submit' name='add' value=\"" . htmlescape(_sx('button', 'Add')) . "\" class='btn btn-primary'>";
            echo "<input type='hidden' name='entities_id' value='$instID'>";
            echo "</td></tr>";

            echo "</table>";

            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        // no namespace backslashes in the HTML id: they break the massive actions selectors
        $container = 'masscostsentityprofile' . $rand;
        if ($canedit) {
            Html::openMassiveActionsForm($container);
            $massiveactionparams = ['container' => $container];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox($container);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox($container);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . htmlescape(__('Profile')) . "</th>";
        $header_end .= "<th>" . htmlescape(__('Fixed cost')) . "</th>";
        $header_end .= "<th>" . htmlescape(__('Time cost')) . "</th>";
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
            echo htmlescape(Dropdown::getDropdownName("glpi_profiles", $data['profiles_id'])) . "</td>";
            echo "<td class='center'>" . htmlescape((string) $data["fixed_cost"]) . "</td>";
            echo "<td class='center'>" . htmlescape((string) $data["time_cost"]) . "</td>";
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

        return true;
    }

    /**
     * showForParent
     *
     * @param  int $entities_id
     * @return bool
     */
    public static function showForParent(int $entities_id): bool
    {
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        $header_end .= "<th>" . htmlescape(__('Profile')) . "</th>";
        $header_end .= "<th>" . htmlescape(__('Fixed cost')) . "</th>";
        $header_end .= "<th>" . htmlescape(__('Time cost')) . "</th>";
        echo "<tr>";
        echo $header_begin . $header_top . $header_end;

        $list = self::getUsedProfiles($entities_id);

        foreach ($list as $data) {
            echo "<tr class='tab_bg_1' style='color:rgb(34, 77, 194);padding: 5px;margin: 3px 0;border: 1px solid transparent;border-radius: 2px;background-color: rgba(34, 77, 194, .1);white-space: nowrap;font-style: italic;'>";
            echo "<td class='center'><i style='margin-right: 2px;' class='ti ti-corner-left-down'></i>";
            echo htmlescape(Dropdown::getDropdownName("glpi_profiles", $data['profiles_id'])) . "</td>";
            echo "<td class='center'>" . htmlescape((string) $data["fixed_cost"]) . "</td>";
            echo "<td class='center'>" . htmlescape((string) $data["time_cost"]) . "</td>";
            echo "</tr>";
        }
        echo $header_begin . $header_bottom . $header_end;
        echo "</table>";
        echo "</div>";

        return true;
    }

    /**
     * getUsedProfiles
     *
     * @param  int $entities_id
     * @param  bool $only_id
     * @return array
     */
    public static function getUsedProfiles(int $entities_id, bool $only_id = false): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $profiles = [];

        $query = [
            'FROM' => self::getTable(),
            'WHERE' => [
                'entities_id' => $entities_id,
            ],
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
     * @param  Migration $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);
        }
    }

    /**
     * uninstall
     *
     * @param  Migration $migration
     * @return void
     */
    public static function uninstall(Migration $migration): void
    {
        $table = self::getTable();
        $migration->displayMessage("Uninstalling $table");
        $migration->dropTable($table);
    }
}
