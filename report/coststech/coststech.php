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

$USEDBREPLICATE = 1;
$DBCONNECTION_REQUIRED = 0;

include("../../../../inc/includes.php");

if (!Plugin::isPluginActive('reports')) {
    exit;
}

$report = new PluginReportsAutoReport(__('CostsTech'));
//Filtro fecha
new PluginReportsDateIntervalCriteria(
    $report,
    'glpi_tickets.closedate',
    __("Close date")
);
//Filtro entity
new PluginReportsDropdownCriteria(
    $report,
    'glpi_tickets.entities_id',
    'Entity',
    __('Entity')
);

$report->displayCriteriasForm();
$report->setColumns([
    new PluginReportsColumnLink(
        'entities_id',
        __('Entity'),
        'Entity',
        [
            'with_navigate' => true
        ]
    ),
    new PluginReportsColumnLink(
        'profiles_id',
        __('Profile'),
        'Profile',
        [
            'with_navigate' => true
        ]
    ),
    new PluginReportsColumnTimestamp(
        'duration',
        __("Total duration")
    ),
    new PluginReportsColumnFloat(
        'totalcost',
        __("Total cost"),
        ['decimal' => 2]
    )
]);

$query = "SELECT glpi_tickets.entities_id,glpi_users.profiles_id,SUM(glpi_tickettasks.actiontime) AS duration, SUM(`glpi_ticketcosts`.`actiontime` * `glpi_ticketcosts`.`cost_time`/3600 + `glpi_ticketcosts`.`cost_fixed` + `glpi_ticketcosts`.`cost_material`) AS totalcost FROM glpi_tickettasks INNER JOIN glpi_tickets ON glpi_tickets.id=glpi_tickettasks.tickets_id INNER JOIN glpi_plugin_costs_tasks ON glpi_plugin_costs_tasks.tasks_id=glpi_tickettasks.id INNER JOIN glpi_ticketcosts ON glpi_ticketcosts.id=glpi_plugin_costs_tasks.costs_id INNER JOIN glpi_users ON glpi_users.id=glpi_tickettasks.users_id_tech WHERE glpi_tickets.status>=" . Ticket::SOLVED . " AND glpi_tickets.is_deleted=0 ";
$group = " GROUP BY glpi_tickets.entities_id,glpi_users.profiles_id";

$query .= $report->addSqlCriteriasRestriction();
$query .= $group;

$report->setSqlRequest($query);
$report->execute();
