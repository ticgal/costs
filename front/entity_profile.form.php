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

use Glpi\Event;

include('../../../inc/includes.php');

if (!Plugin::isPluginActive('costs')) {
    Html::displayNotFoundError();
}

Session::checkLoginUser();

$entity_profile = new PluginCostsEntity_Profile();
if (isset($_POST["add"])) {
    if ($entity_profile->add($_POST)) {
        Event::log(
            $_POST['entities_id'],
            'entity',
            4,
            "tracking",
            sprintf(__('link with %1$s'), Profile::getFriendlyNameById($_POST["profiles_id"]))
        );
    }
    Html::back();
}

Html::displayErrorAndDie("lost");
