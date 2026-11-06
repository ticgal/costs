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

use Glpi\Event;
use GlpiPlugin\Costs\EntityProfile;

if (!Plugin::isPluginActive('costs')) {
    throw new \Glpi\Exception\Http\NotFoundHttpException();
}

Session::checkRight('entity', UPDATE);

$entity_profile = new EntityProfile();
if (isset($_POST["add"])) {
    // entity UPDATE right and access to the target entity
    $entity_profile->check(-1, CREATE, $_POST);
    if ($entity_profile->add($_POST)) {
        Event::log(
            (int) $_POST['entities_id'],
            'entity',
            4,
            "tracking",
            sprintf(__('link with %1$s'), Profile::getFriendlyNameById((int) $_POST["profiles_id"])),
        );
    }
    Html::back();
}
throw new \Glpi\Exception\Http\BadRequestHttpException();
