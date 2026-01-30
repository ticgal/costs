# Manual Testing Checklist - GLPI Costs Plugin v3.1.0

**Purpose**: Final manual validation before release  
**Task**: 15.2 - Perform manual testing checklist  
**Requirements**: 12.1-12.6  
**Date**: 2026-01-30

## Overview

This checklist provides step-by-step manual testing procedures to validate the GLPI Costs plugin on a fresh GLPI 11 instance. While automated tests have verified code compatibility and functionality, manual testing ensures the complete user experience works correctly.

## Prerequisites

- [ ] Fresh GLPI 11.0.x instance installed and accessible
- [ ] Admin access to GLPI
- [ ] Plugin files copied to GLPI plugins directory
- [ ] Database backup taken (recommended)

## Testing Environment Setup

### Option 1: Docker Environment (Recommended)

```bash
# Start GLPI 11 test environment
docker-compose -f docker-compose.glpi11-test.yml up -d

# Wait for GLPI to be ready (60 seconds)
sleep 60

# Access GLPI at http://localhost:8081
# Default credentials: glpi/glpi (admin)
```

### Option 2: Manual GLPI Installation

- Install GLPI 11.0.x on a test server
- Configure database and basic settings
- Ensure web server is running

## Manual Testing Checklist

### 1. Plugin Installation ✓

**Requirement**: 12.1 - Plugin installation on GLPI 11

#### 1.1 Install Plugin via Web UI
- [ ] Navigate to: Setup > Plugins
- [ ] Locate "Costs" plugin in the list
- [ ] Click "Install" button
- [ ] Verify installation completes without errors
- [ ] Verify plugin status shows "Installed"

#### 1.2 Activate Plugin
- [ ] Click "Activate" button
- [ ] Verify activation completes without errors
- [ ] Verify plugin status shows "Enabled"

#### 1.3 Verify Plugin Menu
- [ ] Check that "Costs" appears in the main menu or plugin menu
- [ ] Verify no PHP errors or warnings displayed

**Expected Result**: Plugin installs and activates successfully without errors.

---

### 2. Global Configuration ✓

**Requirement**: 12.6 - Global configuration display

#### 2.1 Access Configuration Page
- [ ] Navigate to: Setup > Plugins > Costs > Configuration
- [ ] Verify configuration page loads without errors
- [ ] Verify page displays correctly (no layout issues)

#### 2.2 Configure Credit System
- [ ] Enable "Use credit system" option
- [ ] Save configuration
- [ ] Verify success message appears
- [ ] Reload page and verify setting persists

#### 2.3 Disable Credit System
- [ ] Disable "Use credit system" option
- [ ] Save configuration
- [ ] Verify success message appears
- [ ] Reload page and verify setting persists

**Expected Result**: Configuration page displays correctly and settings save/load properly.

---

### 3. Entity Cost Configuration ✓

**Requirement**: 12.2 - Cost configuration functionality, 12.5 - Entity configuration display

#### 3.1 Access Entity Configuration
- [ ] Navigate to: Administration > Entities
- [ ] Select "Root entity" or create a test entity
- [ ] Click on "Costs" tab
- [ ] Verify costs tab displays without errors

#### 3.2 Configure Fixed Cost
- [ ] Set "Fixed cost" to a value (e.g., 50.00)
- [ ] Save configuration
- [ ] Verify success message appears
- [ ] Reload entity and verify fixed cost persists

#### 3.3 Configure Time Cost
- [ ] Set "Time cost" to a value (e.g., 75.00)
- [ ] Save configuration
- [ ] Verify success message appears
- [ ] Reload entity and verify time cost persists

#### 3.4 Configure Auto-Billable
- [ ] Enable "Auto-billable" option
- [ ] Save configuration
- [ ] Verify success message appears
- [ ] Reload entity and verify auto-billable persists

#### 3.5 Test Entity Inheritance
- [ ] Create a child entity under root entity
- [ ] Navigate to child entity's Costs tab
- [ ] Enable "Inherit from parent" option
- [ ] Save configuration
- [ ] Verify child entity inherits parent's cost settings
- [ ] Disable inheritance
- [ ] Set different cost values for child entity
- [ ] Verify child entity uses its own settings

**Expected Result**: Entity cost configuration works correctly with proper inheritance behavior.

---

### 4. Ticket Cost Generation ✓

**Requirement**: 12.3 - Ticket cost generation

#### 4.1 Create Billable Ticket (Auto-Billable Entity)
- [ ] Ensure entity has auto-billable enabled
- [ ] Navigate to: Assistance > Tickets > Create ticket
- [ ] Fill in ticket details (title, description, etc.)
- [ ] Submit ticket
- [ ] Verify ticket is created successfully
- [ ] Check that billable field is automatically set to "Yes"
- [ ] Verify cost entry is generated (if fixed cost configured)

#### 4.2 Create Non-Billable Ticket
- [ ] Create another ticket in the same entity
- [ ] Manually set billable field to "No"
- [ ] Submit ticket
- [ ] Verify ticket is created successfully
- [ ] Verify billable field is set to "No"
- [ ] Verify no cost entry is generated

#### 4.3 Create Ticket in Non-Billable Entity
- [ ] Create or select an entity with auto-billable disabled
- [ ] Create a ticket in this entity
- [ ] Verify billable field defaults to "No"
- [ ] Submit ticket
- [ ] Verify no cost entry is generated

#### 4.4 Manual Billable Override
- [ ] Create a ticket in non-billable entity
- [ ] Manually set billable field to "Yes"
- [ ] Submit ticket
- [ ] Verify cost entry is generated

**Expected Result**: Tickets generate costs correctly based on entity configuration and manual overrides.

---

### 5. Task Cost Calculation ✓

**Requirement**: 12.4 - Task cost calculation

#### 5.1 Add Task to Billable Ticket (30 minutes)
- [ ] Open a billable ticket
- [ ] Navigate to "Tasks" tab
- [ ] Add a new task
- [ ] Set duration to 30 minutes (0.5 hours)
- [ ] Set task as public (not private)
- [ ] Save task
- [ ] Verify task is created successfully
- [ ] Verify time-based cost is calculated (time_cost * 0.5)

#### 5.2 Add Task with Different Duration (1.5 hours)
- [ ] Add another task to the same ticket
- [ ] Set duration to 1 hour 30 minutes (1.5 hours)
- [ ] Save task
- [ ] Verify time-based cost is calculated (time_cost * 1.5)

#### 5.3 Add Task with Longer Duration (4 hours)
- [ ] Add another task to the same ticket
- [ ] Set duration to 4 hours
- [ ] Save task
- [ ] Verify time-based cost is calculated (time_cost * 4)

#### 5.4 Add Private Task
- [ ] Add a task to the ticket
- [ ] Set task as private
- [ ] Set duration to 1 hour
- [ ] Save task
- [ ] Verify cost is marked as private (cost_private flag)

#### 5.5 Update Task Duration
- [ ] Edit an existing task
- [ ] Change duration to a different value
- [ ] Save task
- [ ] Verify cost is recalculated with new duration

#### 5.6 Delete Task
- [ ] Delete a task from the ticket
- [ ] Verify task is removed
- [ ] Verify associated cost entry is removed

**Expected Result**: Task costs are calculated correctly based on duration and entity time cost configuration.

---

### 6. Search Functionality ✓

**Requirement**: 8.3 - Search options compatibility

#### 6.1 Search Tickets by Billable Status
- [ ] Navigate to: Assistance > Tickets
- [ ] Click "Search" or advanced search
- [ ] Add "Billable" field to search criteria
- [ ] Search for billable = "Yes"
- [ ] Verify search executes without errors
- [ ] Verify results show only billable tickets

#### 6.2 Search for Non-Billable Tickets
- [ ] Search for billable = "No"
- [ ] Verify search executes without errors
- [ ] Verify results show only non-billable tickets

#### 6.3 Verify Billable Field in Results
- [ ] View search results
- [ ] Verify "Billable" column displays correctly
- [ ] Verify values show "Yes" or "No" appropriately

**Expected Result**: Search functionality includes billable field and filters work correctly.

---

### 7. UI Components ✓

**Requirement**: 4.3 - Template rendering compatibility, 9.3 - Form generation compatibility

#### 7.1 Verify All Pages Load
- [ ] Plugin configuration page loads without errors
- [ ] Entity costs tab loads without errors
- [ ] Ticket form displays billable dropdown
- [ ] All forms render correctly (no broken layouts)

#### 7.2 Verify Form Submissions
- [ ] Entity cost configuration form submits correctly
- [ ] Ticket creation form submits correctly
- [ ] Task creation form submits correctly
- [ ] No JavaScript errors in browser console

#### 7.3 Verify Twig Templates
- [ ] Configuration page template renders correctly
- [ ] Billable dropdown template renders correctly
- [ ] No Twig syntax errors displayed
- [ ] All template variables display correctly

**Expected Result**: All UI components render correctly and forms submit without errors.

---

### 8. Rights Management ✓

**Requirement**: 5.4 - Session and rights management compatibility

#### 8.1 Test with Admin User
- [ ] Login as admin user
- [ ] Verify access to plugin configuration
- [ ] Verify access to entity cost configuration
- [ ] Verify ability to create/edit tickets
- [ ] Verify ability to add/edit tasks

#### 8.2 Test with Technician User
- [ ] Create or login as technician user
- [ ] Verify access to tickets
- [ ] Verify ability to add tasks
- [ ] Verify limited access to configuration (if appropriate)

#### 8.3 Test with Limited User
- [ ] Create or login as user with limited rights
- [ ] Verify appropriate access restrictions
- [ ] Verify no unauthorized access to configuration
- [ ] Verify appropriate error messages for denied access

**Expected Result**: Rights management works correctly based on user permissions.

---

### 9. Plugin Uninstallation ✓

**Requirement**: 10.2 - Plugin uninstallation

#### 9.1 Deactivate Plugin
- [ ] Navigate to: Setup > Plugins
- [ ] Click "Deactivate" for Costs plugin
- [ ] Verify deactivation completes without errors
- [ ] Verify plugin status shows "Installed" (not enabled)

#### 9.2 Uninstall Plugin
- [ ] Click "Uninstall" for Costs plugin
- [ ] Confirm uninstallation
- [ ] Verify uninstallation completes without errors
- [ ] Verify plugin status shows "Not installed"

#### 9.3 Verify Database Cleanup
- [ ] Check database for plugin tables
- [ ] Verify all plugin tables are removed:
  - glpi_plugin_costs_configs
  - glpi_plugin_costs_entities
  - glpi_plugin_costs_entities_profiles
  - glpi_plugin_costs_tickets
  - glpi_plugin_costs_tasks
- [ ] Verify no orphaned data remains

**Expected Result**: Plugin uninstalls cleanly and removes all database tables.

---

## Test Results Summary

### Test Execution

- **Date**: _________________
- **Tester**: _________________
- **GLPI Version**: _________________
- **Plugin Version**: 3.1.0

### Results

| Test Category | Status | Notes |
|---------------|--------|-------|
| 1. Plugin Installation | ☐ Pass ☐ Fail | |
| 2. Global Configuration | ☐ Pass ☐ Fail | |
| 3. Entity Cost Configuration | ☐ Pass ☐ Fail | |
| 4. Ticket Cost Generation | ☐ Pass ☐ Fail | |
| 5. Task Cost Calculation | ☐ Pass ☐ Fail | |
| 6. Search Functionality | ☐ Pass ☐ Fail | |
| 7. UI Components | ☐ Pass ☐ Fail | |
| 8. Rights Management | ☐ Pass ☐ Fail | |
| 9. Plugin Uninstallation | ☐ Pass ☐ Fail | |

### Overall Assessment

- [ ] All tests passed - Ready for release
- [ ] Some tests failed - Issues need to be addressed
- [ ] Critical issues found - Release blocked

### Issues Found

| Issue # | Description | Severity | Status |
|---------|-------------|----------|--------|
| | | | |
| | | | |
| | | | |

### Notes

_Add any additional observations or comments here_

---

## Automated Testing Reference

This manual testing checklist complements the automated test suite. For automated test results, see:

- `tests/TASK-15.1-TEST-SUITE-REPORT.md` - Complete test suite execution
- `tests/CHECKPOINT-12-VERIFICATION.md` - GLPI 11 compatibility verification
- `tests/TASK-13-COMPLETION-REPORT.md` - GLPI 10 backward compatibility

## Conclusion

Manual testing provides final validation that the plugin works correctly in a real GLPI environment. Complete this checklist before proceeding to release.

**Next Steps After Manual Testing**:
1. Document any issues found
2. Fix critical issues before release
3. Proceed to Task 15.3 (Code Review)
4. Proceed to Task 16 (Final Checkpoint)

---

**Checklist Version**: 1.0  
**Last Updated**: 2026-01-30  
**Status**: Ready for execution
