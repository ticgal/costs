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

use Glpi\Plugin\Hooks;

define('PLUGIN_COSTS_VERSION', '3.0.3');
// Minimal GLPI version, inclusive
define("PLUGIN_COSTS_MIN_GLPI", "10.0");
// Maximum GLPI version, exclusive
define("PLUGIN_COSTS_MAX_GLPI", "11.0");

global $CFG_GLPI;
if (!defined('PLUGIN_COSTS_NUMBER_STEP')) {
    define("PLUGIN_COSTS_NUMBER_STEP", 1 / pow(1, $CFG_GLPI["decimal_number"]));
}

/**
 * plugin_version_costs
 *
 * @return array
 */
function plugin_version_costs(): array
{
    return [
        'name'          => 'Costs',
        'version'       => PLUGIN_COSTS_VERSION,
        'author'        => '<a href="https://tic.gal">TICgal</a>',
        'homepage'      => 'https://tic.gal/en/project/costs-control-plugin-glpi/',
        'license'       => 'GPLv3+',
        'requirements'  => [
            'glpi'  => [
                'min'   => PLUGIN_COSTS_MIN_GLPI,
                'max'   => PLUGIN_COSTS_MAX_GLPI,
            ]
        ]
    ];
}

/**
 * plugin_init_costs
 *
 * @return void
 */
function plugin_init_costs(): void
{
    global $PLUGIN_HOOKS;

    if (Session::haveRight('entity', UPDATE)) {
        Plugin::registerClass('PluginCostsEntity', ['addtabon' => 'Entity']);
    }

    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsConfig', ['addtabon' => 'Config']);

        $PLUGIN_HOOKS['config_page']['costs'] = 'front/config.form.php';
    }

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['costs'] = true;

    $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['costs'] = ['PluginCostsTicket','postItemForm'];

    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['costs'] = [
        Ticket::class       => ['PluginCostsTicket','ticketUpdate'],
        TicketTask::class   => ['PluginCostsTask','preTaskUpdate']
    ];

    $PLUGIN_HOOKS[Hooks::ITEM_ADD]['costs'] = [
        Ticket::class       => ['PluginCostsTicket','ticketAdd'],
        TicketTask::class   => ['PluginCostsTask','taskAdd']
    ];

    $PLUGIN_HOOKS[Hooks::ITEM_PURGE]['costs'] = [
        TicketTask::class   => ['PluginCostsTask','taskPurge']
    ];
}
