<?php

class PluginCostsTask extends CommonDBTM {
	public static $rightname='task';

	static function getTypeName($nb = 0) {
      return __('Costs', 'Costs');
   }

   static function taskAdd(TicketTask $task){
      global $DB;

      if (PluginCostsTicket::isBillable($task->fields['tickets_id'])) {
         $ticket=new Ticket();
         $ticket->getFromDB($task->fields['tickets_id']);

         if (array_key_exists('state', $task->input)) {
            if ($task->fields['state']==Planning::DONE) {
               $cost_config=new PluginCostsEntity();
               $cost_config->getFromDBByCrit(["entities_id"=>$ticket->fields['entities_id']]);
               if ($cost_config->fields['inheritance']) {
                  $parent_id=PluginCostsEntity::getConfigID($ticket->fields['entities_id']);
                  $cost_config->getFromDB($parent_id);
               }

               $entity_profile=new PluginCostsEntity_Profile();
               $user=new User();
               $user->getFromDB($task->fields['users_id_tech']);
               if ($entity_profile->getFromDBByCrit(['entities_id'=>$cost_config->fields['entities_id'],'profiles_id'=>$user->fields['profiles_id']])) {
                  $cost_time=$entity_profile->fields['time_cost'];
                  $cost_fixed=$entity_profile->fields['fixed_cost'];
               }else{
                  $cost_time=$cost_config->fields['time_cost'];
                  $cost_fixed=$cost_config->fields['fixed_cost'];
               }

               if ($cost_time>0) {
                  if (!$task->fields['is_private'] || $cost_config->fields['cost_private']) {
                     
                     $config=PluginCostsConfig::getConfig();
                     $comment=__('Automatically generated by GLPI').' -> Costs Plugin';
                     if ($config->fields['taskdescription']) {
                        $comment=$task->fields['content']." \n".__('Automatically generated by GLPI').' -> Costs Plugin';
                     }

                     $cost=new TicketCost();
                     $cost_id=$cost->add([
                        'tickets_id'=>$task->fields['tickets_id'],
                        'name'=>$task->fields['id']."_".$task->fields['users_id_tech'],
                        'comment'=>$comment,
                        'begin_date'=>(array_key_exists('begin', $task->fields)) ? $task->fields['begin'] : NULL,
                        'end_date'=>(array_key_exists('end', $task->fields)) ? $task->fields['end'] : NULL,
                        'actiontime'=>$task->fields['actiontime'],
                        'cost_time'=>$cost_time,
                        'cost_fixed'=>$cost_fixed,
                        'entities_id'=>$ticket->fields['entities_id'],
                     ]);

                     $taskcost=new self();
                     $taskcost->add(['tasks_id'=>$task->fields['id'],'costs_id'=>$cost_id]);
                  }
               }
            }
         }
      }
   }

   static function preTaskUpdate(TicketTask $task){
      global $DB;

      if (PluginCostsTicket::isBillable($task->fields['tickets_id'])) {
         $ticket=new Ticket();
         $ticket->getFromDB($task->fields['tickets_id']);

         if ($task->input['state']==Planning::DONE) {
            if (isset($task->input['is_private'])) {
               $is_private = $task->input['is_private'];
            } else {
               $is_private = $task->fields['is_private'];
            }
            if (isset($task->input['content'])) {
               $content = $task->input['content'];
            } else {
               $content = $task->fields['content'];
            }
            if (isset($task->input['actiontime'])) {
               $actiontime = $task->input['actiontime'];
            } else {
               $actiontime = $task->fields['actiontime'];
            }
            if (isset($task->input['users_id_tech'])) {
               $users_id_tech = $task->input['users_id_tech'];
            } else {
               $users_id_tech = $task->fields['users_id_tech'];
            }
            
            $cost_config=new PluginCostsEntity();
            $cost_config->getFromDBByEntity($ticket->fields['entities_id']);
            if ($cost_config->fields['inheritance']) {
               $parent_id=PluginCostsEntity::getConfigID($ticket->fields['entities_id']);
               $cost_config->getFromDB($parent_id);
            }

            $entity_profile=new PluginCostsEntity_Profile();
            $user=new User();
            $user->getFromDB($task->fields['users_id_tech']);
            if ($entity_profile->getFromDBByCrit(['entities_id'=>$cost_config->fields['entities_id'],'profiles_id'=>$user->fields['profiles_id']])) {
               $cost_time=$entity_profile->fields['time_cost'];
               $cost_fixed=$entity_profile->fields['fixed_cost'];
            }else{
               $cost_time=$cost_config->fields['time_cost'];
               $cost_fixed=$cost_config->fields['fixed_cost'];
            }

            if ($cost_time>0) {
               if (!$is_private || $cost_config->fields['cost_private']) {
                  $query=[
                     'FROM'=>self::getTable(),
                     'WHERE'=>[
                        'tasks_id'=>$task->fields['id']
                     ]
                  ];
                  $req=$DB->request($query);
                 
                     if(count($req)){
                        foreach($req as $row) {
                           $cost_id=$row['costs_id'];

                           $config=PluginCostsConfig::getConfig();
                           $comment=__('Automatically generated by GLPI').' -> Costs Plugin';
                           if ($config->fields['taskdescription']) {
                              $comment=$content." \n".__('Automatically generated by GLPI').' -> Costs Plugin';
                           }
                           $input=[
                              'id'=>$cost_id,
                              'comment'=>$comment,
                              'actiontime'=>$actiontime,
                           ];
                           if (array_key_exists('begin', $task->input)) {
                              $input['begin_date']=$task->input['begin'];
                           }
                           if (array_key_exists('end', $task->input)) {
                              $input['end_date']=$task->input['end'];
                           }

                           $cost=new TicketCost();
                           $cost->update($input);
                        }
                     }else{
                        $config=PluginCostsConfig::getConfig();
                        $comment=__('Automatically generated by GLPI').' -> Costs Plugin';
                        if ($config->fields['taskdescription']) {
                           $comment=$content." \n".__('Automatically generated by GLPI').' -> Costs Plugin';
                        }
                        $cost=new TicketCost();
                        $input=[
                           'tickets_id'=>$task->fields['tickets_id'],
                           'name'=>$task->fields['id']."_".$users_id_tech,
                           'comment'=>$comment,
                           'actiontime'=>$actiontime,
                           'cost_time'=>$cost_time,
                           'cost_fixed'=>$cost_fixed,
                           'entities_id'=>$ticket->fields['entities_id'],
                        ];
                        if (array_key_exists('begin', $task->input)) {
                           $input['begin_date']=$task->input['begin'];
                        }else{
                           $input['begin_date']=$task->fields['begin'];
                        }
                        if (array_key_exists('end', $task->input)) {
                           $input['end_date']=$task->input['end'];
                        }else{
                           $input['end_date']=$task->fields['end'];
                        }
                        $cost_id=$cost->add($input);

                        $taskcost=new self();
                        $taskcost->add(['tasks_id'=>$task->fields['id'],'costs_id'=>$cost_id]);
                     }
                  
               }
            }
         }
      }
   }

   static function taskPurge(TicketTask $task){
      global $DB;

      $query=[
         'FROM'=>self::getTable(),
         'WHERE'=>[
            'tasks_id'=>$task->fields['id']
         ]
      ];
      $req=$DB->request($query);
      foreach ($req as $row) {
         $cost = new TicketCost();
         $cost->delete(['id'=>$row['costs_id']], 1);
         $taskcost=new self();
         $taskcost->deleteByCriteria(['id'=>$row['id']]);
      }
   }

   static function install(Migration $migration){
   	global $DB;

   	$table=self::getTable();

   	if (!$DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `tasks_id` int(11) NOT NULL,
                     `costs_id` int(11) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `tasks_id` (`tasks_id`),
                     KEY `costs_id` (`costs_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      } else {
         $migration->changeField($table,'costs_id','costs_id','int');
      }

      $migration->executeMigration();
   }
}