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

use GlpiPlugin\Costs\Config as CostsConfig;
use GlpiPlugin\Costs\Entity as CostsEntity;
use GlpiPlugin\Costs\EntityProfile;
use GlpiPlugin\Costs\Task as CostsTask;
use GlpiPlugin\Costs\Ticket as CostsTicket;

/**
 * Plugin classes owning a table, in install order
 *
 * @return array<class-string>
 */
function plugin_costs_getClasses(): array
{
    return [
        CostsConfig::class,
        CostsEntity::class,
        EntityProfile::class,
        CostsTask::class,
        CostsTicket::class,
    ];
}

/**
 * plugin_costs_install
 *
 * @return bool
 */
function plugin_costs_install(): bool
{
    $migration = new Migration(PLUGIN_COSTS_VERSION);

    foreach (plugin_costs_getClasses() as $classname) {
        $classname::install($migration);
    }
    return true;
}

/**
 * plugin_costs_uninstall
 *
 * @return bool
 */
function plugin_costs_uninstall(): bool
{
    $migration = new Migration(PLUGIN_COSTS_VERSION);

    foreach (plugin_costs_getClasses() as $classname) {
        $classname::uninstall($migration);
    }
    return true;
}

/**
 * plugin_costs_getAddSearchOptionsNew
 *
 * @param  mixed $itemtype
 * @return array
 */
function plugin_costs_getAddSearchOptionsNew($itemtype): array
{
    if ($itemtype == Ticket::getType()) {
        return CostsTicket::rawSearchOptionsToAdd();
    }

    return [];
}
