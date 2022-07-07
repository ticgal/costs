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

   static function rawSearchOptionsToAdd(){
      global $DB;

      $opt=[];

      $opt[]=[
         'id'=>'1000',
         'table'=>self::getTable(),
         'field'=>'billable',
         'name'=>__("Billable",'cost'),
         'datatype'=>'bool',
         'searchtype'=>'equals',
         'joinparams'=>[
            'jointype'=>'child'
         ]
      ];

      return $opt;
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

   static function isBillable($ticket_id){
      $cost_ticket=new self();
      $cost_ticket->getFromDBByTicket($ticket_id);
      return $cost_ticket->fields['billable'];
   }

   public function getFromDBByTicket($ticket_id) {
      global $DB;

      $req=$DB->request(['FROM' => self::getTable(),'WHERE' => ['tickets_id' => $ticket_id]]);
      if (count($req)) {
         foreach ($req as $result){
            $this->fields=$result;
         }
         return true;
      } else {
         $ticket=new Ticket();
         $ticket->getFromDB($ticket_id);
         $cost_config=new PluginCostsEntity();
         $cost_config->getFromDBByEntity($ticket->fields['entities_id']);
         if ($cost_config->fields['inheritance']) {
            $parent_id=PluginCostsEntity::getConfigID($ticket->fields['entities_id']);
            $cost_config->getFromDB($parent_id);
         }
         $DB->insert(self::getTable(), ['tickets_id'=>$ticket_id,'billable'=>$cost_config->fields['auto_cost']]);
         $this->fields=['billable'=>$cost_config->fields['auto_cost']];
         return false;
      }
   }

   static function postItemForm($params=[]){
      global $DB;

      if (Session::getCurrentInterface() != "helpdesk") {
         $item=$params['item'];
         if (!is_array($item)) {
            if ($item->getType()==Ticket::getType()) {
               if ($item->canUpdate()) {
                  $ticket_id=$item->getID();
                  echo "<tr class='tab_bg_1'>";
                  echo "<th>".__('Billable','cost')."</th>";
                  echo "<td>";
                  if ($ticket_id==0) {
                     $cost_config=new PluginCostsEntity();
                     $cost_config->getFromDBByEntity($item->input['entities_id']);
                     if ($cost_config->fields['inheritance']) {
                        $parent_id=PluginCostsEntity::getConfigID($item->fields['entities_id']);
                        $cost_config->getFromDB($parent_id);
                     }
                     $billable=$cost_config->fields['auto_cost'];
                  }else{
                     $cost_ticket=new self();
                     $cost_ticket->getFromDBByTicket($ticket_id);
                     $billable=$cost_ticket->fields['billable'];
                  }
                  Dropdown::showYesNo('cost_billable',$billable);
                  echo "</td>";
                  echo "</tr>";
               }
            }
         }
      }
   }

   static function ticketAdd(Ticket $ticket){

      if (array_key_exists('cost_billable', $ticket->input)) {
         $billable=$ticket->input['cost_billable'];
      }else{
         $cost_config=new PluginCostsEntity();
         $cost_config->getFromDBByEntity($ticket->input['entities_id']);
         if ($cost_config->fields['inheritance']) {
            $parent_id=PluginCostsEntity::getConfigID($ticket->input['entities_id']);
            $cost_config->getFromDB($parent_id);
         }
         $billable=$cost_config->fields['auto_cost'];
      }
      $cost_ticket=new self();
      $cost_ticket->add(['tickets_id'=>$ticket->fields['id'],'billable'=>$billable]);
   }

   static function ticketUpdate(Ticket $ticket){
      if (array_key_exists('cost_billable', $ticket->input)) {
         $cost_ticket=new self();
         $cost_ticket->getFromDBByTicket($ticket->fields['id']);
         $cost_ticket->update(['billable'=>$ticket->input['cost_billable'],'id'=>$cost_ticket->getID()]);
      }
   }

   static function install(Migration $migration) {
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int {$default_key_sign} NOT NULL auto_increment,
                     `tickets_id` int {$default_key_sign} NOT NULL,
                     `billable` tinyint NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`),
                     KEY `billable` (`billable`)
                  ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
      }else{
         if ($DB->fieldExists($table, 'costs_id')) {
            if (!$DB->tableExists('glpi_plugin_costs_tasks')) {
               PluginCostsTask::install($migration);
            }
            $query=[
               'SELECT'=>[
                  $table.".costs_id",
                  "glpi_ticketcosts.name",
               ],
               'FROM'=>$table,
               'INNER JOIN'=>[
                  'glpi_ticketcosts'=>[
                     'FKEY'=>[
                        'glpi_ticketcosts'=>'id',
                        $table=>'costs_id'
                     ]
                  ]
               ]
            ];
            $taskcost=new PluginCostsTask();
            foreach ($DB->request($query) as $id => $row) {
               $arr=explode("_",$row['name']);
               $task_id=$arr[0];
               $input=[
                  'tasks_id'=>$task_id,
                  'costs_id'=>$row['costs_id'],
               ];
               $taskcost->add($input);
            }

            $migration->addField($table,'billable','boolean');
            $migration->addKey($table,'billable');

            $migration->dropField($table,'costs_id');
            $migration->dropKey($table,'costs_id');

            $clear_data="TRUNCATE TABLE $table";
            $DB->query($clear_data);

         }
      }
      $migration->executeMigration();
   }
}