<?php
/*
 -------------------------------------------------------------------------
 Costs plugin for GLPI
 Copyright (C) 2018 by the TICgal Team.

 https://github.com/ticgal/costs
 -------------------------------------------------------------------------

 LICENSE

 This file is part of the Costs plugin.

 Costs plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 Costs plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Costs. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   Costs
 @author    the TICgal team
 @copyright Copyright (c) 2018 TICgal team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://tic.gal
 @since     2018
 ---------------------------------------------------------------------- */

class PluginCostsTicket extends CommonDBTM{

   public static $rightname = 'ticket';

   static function getTypeName($nb = 0) {
      return __('Costs', 'Costs');
   }

   static function deleteOldCosts($ID) {
      global $DB;

      $query=[
         'FROM'=>self::getTable(),
         'WHERE'=>[
            'tickets_id'=>$ID,
         ]
      ];
      foreach ($DB->request($query) as $id => $row) {
         $DB->delete('glpi_ticketcosts', ['id'=>$row['costs_id']]);
         $DB->delete(self::getTable(), ['id'=>$row['id']]);
      }
   }

   static function generateCosts($item) {
      global $DB;

      if ($item->input['status']==5) {

         $ticket_id=$item->input['id'];
         $entities_id=$item->fields['entities_id'];

         self::deleteOldCosts($ticket_id);

         $req=$DB->request(['FROM' => 'glpi_plugin_costs_entities','WHERE' => ['entities_id' => $entities_id]]);
         if (count($req)) {

            $entity=$req->next();
            if ($entity['time_cost']>0) {

               if ($entity['cost_private']==0) {

                  $query=[
                     'SELECT'=>[
                        'id',
                        'users_id_tech',
                        'begin',
                        'end',
                        'actiontime',
                     ],
                     'FROM'=>'glpi_tickettasks',
                     'WHERE'=>[
                        'tickets_id'=>$ticket_id,
                        'is_private'=>0,
                     ]
                  ];
               } else {
                  $query=[
                     'SELECT'=>[
                        'id',
                        'users_id_tech',
                        'begin',
                        'end',
                        'actiontime',
                     ],
                     'FROM'=>'glpi_tickettasks',
                     'WHERE'=>[
                        'tickets_id'=>$ticket_id,
                     ]
                  ];
               }

               foreach ($DB->request($query) as $id => $row) {
                  $DB->insert(
                     'glpi_ticketcosts', [
                        'tickets_id'=>$ticket_id,
                        'name'=>$row['id']."_".$row['users_id_tech'],
                        'comment'=>__('Generated by Plugin_Costs'),
                        'begin_date'=>$row['begin'],
                        'end_date'=>$row['end'],
                        'actiontime'=>$row['actiontime'],
                        'cost_time'=>$entity['time_cost'],
                        'cost_fixed'=>$entity['fixed_cost'],
                        'entities_id'=>$entities_id,
                     ]
                  );
                  $DB->insert(
                     'glpi_plugin_costs_tickets', [
                        'tickets_id'=>$ticket_id,
                        'costs_id'=>$DB->insert_id(),
                     ]
                  );
               }
            }
         }
      }
   }

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `tickets_id` int(11) NOT NULL,
                     `costs_id` int(11) NOT NULL,
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`),
                     KEY `costs_id` (`costs_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      }
   }
}