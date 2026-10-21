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

if (!Plugin::isPluginActive('costs')) {
    throw new \Glpi\Exception\Http\NotFoundHttpException();
}

Session::checkRight("entity", UPDATE);

$PluginCostsEntity  = new PluginCostsEntity();

if (isset($_POST["plugin_cost_update"])) {
    if (!$PluginCostsEntity->getFromDB((int) ($_POST['id'] ?? 0))) {
        throw new \Glpi\Exception\Http\NotFoundHttpException();
    }
    // Check the entity of the stored row, never the one sent by the client
    $entities_id = (int) $PluginCostsEntity->fields['entities_id'];
    if (!Session::haveAccessToEntity($entities_id)) {
        throw new \Glpi\Exception\Http\AccessDeniedHttpException();
    }

    // Only the editable columns; entities_id cannot be changed
    $input = ['id' => $PluginCostsEntity->getID()];
    foreach (['fixed_cost', 'time_cost'] as $field) {
        if (isset($_POST[$field])) {
            $input[$field] = (float) $_POST[$field];
        }
    }
    foreach (['cost_private', 'auto_cost', 'inheritance'] as $field) {
        if (isset($_POST[$field])) {
            $input[$field] = (int) (bool) $_POST[$field];
        }
    }
    // The root entity has no parent to inherit from
    if ($entities_id == 0) {
        unset($input['inheritance']);
    }

    $PluginCostsEntity->update($input);
    Html::back();
}

throw new \Glpi\Exception\Http\BadRequestHttpException();
