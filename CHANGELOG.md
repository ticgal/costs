# Costs
<!-- changelog-format: 2 -->
The cost plugin for GLPI

## [Unreleased]
_Working version: 5.0.0-beta.1. Includes the security fixes from 4.0.1._

### Features
- Compatible with GLPI 12.0.
- The plugin settings ask for your password again before they can be changed, like the GLPI configuration.

### Bugs
- Only users who can update an entity can change its costs and its per-profile rates.
- Per-profile rates can only be set for profiles of the standard interface.
- Only users with the right to update tickets can change whether a ticket is billable.
- Users who cannot change whether a ticket is billable see it as read-only.
- Changing the billable setting of a ticket for the first time is no longer lost.
- Profile names and rates are displayed as plain text in the entity costs tab.
- The Costs Tech report only shows the entities the user has access to.
- Uninstalling the plugin removes all its data.
- Searching tickets by "Billable" works.
- Deleting several per-profile rates at once works.
- The task description copied into a cost no longer shows HTML codes such as "&amp;".
- When a task is reassigned, its cost uses the rates of the new technician's profile.
- The "Costs" tab is no longer shown in lowercase in English (US).

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
