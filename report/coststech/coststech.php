<?php

$USEDBREPLICATE = 1;
$DBCONNECTION_REQUIRED = 0;

include("../../../../inc/includes.php");

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
