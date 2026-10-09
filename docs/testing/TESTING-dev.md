# Costs — developer test record
<!-- testing-dev-format: 1 -->

Cumulative regression record. Append new entries; keep the old ones.

The plugin has no PHPUnit suite. Behaviour is verified with an HTTP battery run against a
GLPI 12 Docker instance (scripts kept outside the repo, in the developer's local plugin memory:
`plugins_memory/costs/tests_http/t_costs.py` + `run_all.sh`, built on the
`glpi-plugin-migration-v12` HTTP harness). Each check below names its battery id.

## 2026-10-09 — 5.0.0-beta.1: GLPI 12 port + security audit fixes

Environment: GLPI 12.0.0-rc3 (`glpi12rc3`, MariaDB 10.11), PHP 8.3, `GLPI_STRICT_ENV=true` and
`GLPI_LOG_LVL=debug` (deprecations logged). Result: **48/48 OK, `php-errors.log` empty**.
Not run: the same battery with production settings (the instance's `local_define.php` belonged to
another work session at the time).

Static checks (same as the shared CI): PHPCS, PHP-CS-Fixer, PHPStan (root `phpstan.neon`, level 5,
TICGAL custom rules + `GlobalVarTypeRule`) and TwigCS (`GlpiTwigRuleset` from `12.0/bugfixes`): all clean.

### Lifecycle
- [x] T1.1 `plugin:uninstall costs` (answer `yes`) → no `glpi_plugin_costs_%` table left (before: only `entityprofiles` was dropped, `Entity::unistall` typo).
- [x] T1.2 `plugin:install costs -u glpi` → 5 tables, one row in `glpi_plugin_costs_configs`.
- [x] T1.3 `plugin:activate costs` → state 1, version `5.0.0-beta.1`.
- [x] T1.4 Rows written as by 4.0.0 (`entities`, `tickets`), then `plugin:install --force` → data kept, config not duplicated.

### Configuration
- [x] F2.1 After re-authentication, tab `GlpiPlugin\Costs\Config$1` on Config 1 shows `taskdescription` and posts to `/plugins/costs/front/config.form.php`.
- [x] F2.2 POST `update` with `taskdescription=1` → stored.
- [x] F2.3 GET `/plugins/costs/front/config.form.php` → redirect with `forcetab=GlpiPlugin%5CCosts%5CConfig%241`.
- [x] S4.1 Same POST from a session that did not re-authenticate → redirected to `/ReAuth/…`, value unchanged (SEV-07).
- [x] S4.2 Technician POST → value unchanged.

### Entity costs (SEV-01)
- [x] F3.1 Tab `GlpiPlugin\Costs\Entity$1` on an entity renders the form (inheritance switch, action URL of the plugin).
- [x] F3.2 POST `plugin_cost_update` (inheritance 0, fixed 5, time 30, auto_cost 1) → stored; tab now shows editable inputs and the per-profile form.
- [x] F3.3 POST with `entities_id` of another entity → ignored (row stays in its entity), other fields saved.
- [x] S1.1 Self-service user POST → 403, row unchanged.
- [x] S1.2 Technician (no `entity` UPDATE) POST → 403, row unchanged.
- [x] S1.3 User with `entity` UPDATE only in sibling entity B, POST on entity A's row → 403, row unchanged.
- [x] S1.4 Same user on entity B's row → saved.
- [x] S1.5 Super-admin POST with `Sec-Fetch-Site: cross-site` → 403, row unchanged.

### Per-profile costs (SEV-02)
- [x] F4.1 POST `add` (Technician, fixed 2, time 50) → row created.
- [x] F4.2 Same profile again → no second row, no error page / exception.
- [x] F4.3 Self-Service (helpdesk) profile → rejected.
- [x] F4.4 Row listed in the entity tab.
- [x] F4.5 Massive action purge by super-admin → row deleted.
- [x] S2.1 / S2.2 / S2.3 Self-service, technician, admin of entity B adding a row in entity A → 403, nothing inserted.
- [x] S2.4 Admin of entity B adding in B → inserted.
- [x] S2.5 Admin of entity B purging entity A's row via massive action → row kept.
- [x] S9.1 Profile named `costs-t <img src=x onerror=alert(1)>` listed in the tab → output contains `&lt;img…`, never the raw tag (SEV-04).

### Tickets and billable flag (SEV-03)
- [x] F5.1 New ticket form (`Ticket$main`, id 0) contains `cost_billable`.
- [x] F5.2 Super-admin creates a ticket with `cost_billable=1` → `glpi_plugin_costs_tickets.billable=1`.
- [x] F5.3 Existing ticket form contains `cost_billable`.
- [x] F5.4 Update `cost_billable` 0 then 1 → stored each time.
- [x] S3.1 Self-service user creates a ticket with `cost_billable=0` in an entity with auto billable → stored as 1; updating own ticket with `cost_billable=0` → still 1.
- [x] S3.2 Self-service user's ticket form has no `cost_billable`.
- [x] F7.1 `ajax/search.php` with criteria option 1000 = 1 and the column in display preferences → "Billable" column and the ticket listed, no "invalid search options" warning (before: the option was registered through the legacy hook with the new format and never existed).

### Task costs
- [x] F6.1 Done task (1 h, tech = super-admin) → `glpi_ticketcosts` with entity rates (30/h, 5 fixed) linked in `glpi_plugin_costs_tasks`; with `taskdescription=1` the comment is the plain task text, no `&amp;`.
- [x] F6.2 Rate for the technician's default profile in the entity (70/7) takes precedence.
- [x] F6.3 Updating the task (2 h, new content) updates the same cost.
- [x] F6.4 Update without `state` in the input → no PHP warning.
- [x] F6.5 To-do task → no cost.
- [x] F6.6 Private task with `cost_private=0` → no cost.
- [x] F6.7 Purging the task → link and cost deleted.
- [x] F6.8 Non-billable ticket → no cost.

### Other
- [x] F8.1 `report/coststech/coststech.php` without the `reports` plugin → 404.
- [x] S5.1 Anonymous POST to the three `front/` scripts → redirected/denied.
- [x] S5.2 `tools/` not served.
- [x] C1.1 Inside a real request: `PluginCostsTicket`, `PluginCostsTask`, `PluginCostsEntity`, `PluginCostsEntityProfile`, `PluginCostsConfig` exist; `PluginCostsTask::getTable()` = `glpi_plugin_costs_tasks`; `PluginCostsTicket::isBillable()` works (used by `gappextended`).

### Browser (Playwright, Chromium, locale en-GB) — `plugins_memory/costs/tests_browser/run.sh`
Needs the data left by the HTTP battery. Result: **10/10 OK, no JS errors, `php-errors.log` empty**.
- [x] B1 Plugin config page: re-authentication prompt, untick/tick "Add task description on cost", Save → stored each time.
- [x] B2 Entity A > Costs tab: change Time cost 30 → 35 → 30 with the Update button → stored.
- [x] B3 Add a per-profile cost choosing Supervisor, 3, 40 in the select2 dropdowns (profile and the AJAX number dropdowns) → row stored and listed.
- [x] B4 Tick that row, Actions > Delete permanently → row deleted. Found and fixed: the massive actions container id contained the namespaced class (`massGlpiPlugin\Costs\EntityProfile…`); the backslashes broke the selectors and GLPI answered "No selected items".
- [x] B5 New ticket form in entity A: Billable checkbox present; create with it ticked → `billable=1`.
- [x] B6 Existing ticket: untick, Save → 0; tick, Save → 1.
- [x] B7 Timeline > Task (answer dropdown), content with `&`, status Done, 1 h, Add → cost 30/h + 5 created, comment `Tarea & navegador` (no double encoding).
- [x] B8 Ticket > Costs tab shows the generated cost.
- [x] B9 Self-service user opens own ticket → no Billable field.

Also found while looking at the screenshots: `locales/en_US.po` translated "Costs" as "costs" (lowercase tab label); fixed and recompiled.

### Pending
- [ ] Same batteries with production settings (no `local_define.php`).
- [ ] Report with the `reports` plugin installed: totals limited to the active entities (SEV-06).
