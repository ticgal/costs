# Costs
The cost plugin for GLPI

## [5.0.0-beta.1] - Unreleased
## Features
- GLPI 12 compatibility (GLPI 12.0.x only)
- Classes moved to `src/` (`GlpiPlugin\Costs\*`); the legacy `PluginCosts*` names are kept as deprecated aliases for other plugins
- Plugin configuration asks for re-authentication, like the core configuration
## Security
- Entity cost settings and per-profile costs can only be changed by users with the entity update right on that entity
- The billable flag can only be set by central users allowed to update tickets
- Escape profile names and values in the entity costs tab
- Costs report restricted to the active entities
## Fixed
- "Billable" search option on tickets was not registered
- Bulk delete of per-profile costs in the entity tab ("No selected items")
- "Costs" tab label shown in lowercase in English (US)
- Uninstall now removes all the plugin tables
- Task description copied to the cost comment without HTML double encoding
- Cost of an updated task uses the profile of the assigned technician
- PHP warnings when a task is updated without state or without technician
## Removed
- Unused QR code dependency

## [4.0.0] - 2026-01-27
## Features
- GLPI 11 compatibility

## [3.0.6] - 2024-11-29
## Fixed
- Task content as cost comment, cleaning tags and length control #26862

## 3.0.5 - 2024-11-29
## Bugfixes
- Avoid errors with better conditions #25572

## 3.0.4 - 2024-06-10
## Bugfixes
- Fix strong typing definition #22663

## 3.0.3 - 2024-02-05
## Bugfixes
- Fix install warning for unsigned integers #20073

## 3.0.2 - 2023-07-13
## Bugfixes
- Billable dropdown update #12572

## 3.0.1 - 2022-07-27
## Bugfixes
- Localazy integration #10666

## 3.0.0 - 2022-07-07
### Features
- GLPI 10 compatibility #10398

## 2.2.3
### Bugfixes
- Don't show Billable to self-service users.

## 2.2.2
### Bugfixes
- Filter out deleted tasks costs.
- Change ID tinyint to int.

## 2.2.1 (internal)
### Bugfixes
- Auto billable inheritance

## 2.2.0 (internal)
### Features
- Multiple cost check
### Bugfixes
- Check if isset input.

## 2.1.0 (internal)
### Features
- Check time cost.
- Add report.

## 2.0.0 (internal)
### Features
- Link cost to task.
- Limit costs to central profiles.
- Add recursive entities for costs.
