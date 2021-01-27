<?php

use Glpi\Event;

include('../../../inc/includes.php');

Session::checkLoginUser();

$entity_profile=new PluginCostsEntity_Profile();
if (isset($_POST["add"])) {
	if($entity_profile->add($_POST)){
		Event::log($_POST['entities_id'],'entity',4,"tracking",sprintf(__('link with %1$s'), Profile::getFriendlyNameById($_POST["profiles_id"])));
	}
	Html::back();
}

Html::displayErrorAndDie("lost");
