# Template Rendering Tests

## Overview

The template rendering tests validate that UI components render correctly on GLPI 11.

## Test Files

- `test-template-rendering.php` - Tests template rendering for config page and entity costs tab
- `test-form-generation.php` - Tests form generation and submission (entity cost config form, billable dropdown)

## Running the Tests

These tests require a PHP environment with GLPI classes available. They can be run in two ways:

### Option 1: In GLPI Docker Environment

```bash
# Copy test files to GLPI container
docker cp tests/test-template-rendering.php glpi11-app:/tmp/
docker cp tests/test-form-generation.php glpi11-app:/tmp/

# Run the tests
docker exec glpi11-app php /tmp/test-template-rendering.php
docker exec glpi11-app php /tmp/test-form-generation.php
```

### Option 2: In Local GLPI Installation

```bash
# Run from plugin directory
php tests/test-template-rendering.php
php tests/test-form-generation.php
```

## Expected Output

All tests should pass with output similar to:

```
========================================
Template Rendering Test
========================================

Test 1: Testing config template rendering...
  ✓ Config template rendered successfully
  ✓ Template: @costs/config.html.twig
  ✓ Template options validated
  ✓ No Twig errors detected

Test 2: Testing entity costs tab template rendering...
  ✓ Entity costs tab rendered successfully
  ✓ Form elements present
  ✓ All required fields displayed
  ✓ No rendering errors detected

Test 3: Checking for Twig template errors...
  ✓ Template files exist
  ✓ Config template: ../templates/config.html.twig
  ✓ Billable template: ../templates/billable_dropdown.html.twig
  ✓ Twig syntax validated
  ✓ Required imports present
  ✓ No template errors detected

========================================
Test Summary
========================================

✓ Config template rendering: PASSED
✓ Entity template rendering: PASSED
✓ Template error check: PASSED

Results: 3/3 tests passed
```

### Form Generation Test

```
========================================
Form Generation Test
========================================

Test 1: Testing entity cost configuration form...
  ✓ Entity cost configuration form generated
  ✓ Form has proper HTML structure
  ✓ All required fields present:
    - fixed_cost
    - time_cost
    - cost_private
    - auto_cost
    - inheritance
  ✓ Submit button present
  ✓ Hidden fields present (entities_id, id)

Test 2: Testing billable dropdown on ticket form...
  ✓ Billable dropdown generated on ticket form
  ✓ Dropdown has proper HTML structure
  ✓ Field name: cost_billable
  ✓ Options present: Yes (1), No (0)
  ✓ Dropdown renders without errors

Test 3: Testing form submission...
  ✓ Entity cost configuration form submission works
  ✓ Ticket billable dropdown submission works
  ✓ Form data is processed correctly
  ✓ Database updates execute successfully

========================================
Test Summary
========================================

✓ Entity cost configuration form: PASSED
✓ Billable dropdown on ticket form: PASSED
✓ Form submission: PASSED

Results: 3/3 tests passed
```

## What is Tested

### Config Template (test-template-rendering.php)
- TemplateRenderer::getInstance()->display() is called
- Correct template path (@costs/config.html.twig) is used
- Template options include required parameters (item, credit)
- No Twig rendering errors occur

### Entity Template (test-template-rendering.php)
- Entity costs tab renders successfully
- Form elements are present in output
- All required fields are displayed (Fixed cost, Time cost, Private task, Auto billable ticket)
- No rendering errors occur

### Template Files (test-template-rendering.php)
- Template files exist in templates/ directory
- Twig syntax is valid (balanced tags)
- Required imports are present (fields_macros, buttons)

### Form Generation (test-form-generation.php)
- Entity cost configuration form renders
- Billable dropdown on ticket form renders
- Forms contain proper HTML structure
- Form submission works correctly

## Requirements Validated

- **Requirement 4.3**: Template rendering compatibility with GLPI 11
- **Requirement 12.5**: Entity costs tab displays correctly on GLPI 11
- **Requirement 12.6**: Global configuration page displays correctly on GLPI 11
- **Requirement 9.3**: Forms render and submit correctly on GLPI 11

## Notes

- Tests use mocked GLPI classes to simulate the GLPI environment
- Tests verify that TemplateRenderer API is used correctly
- Tests check for common Twig syntax errors
- Tests validate that all required template parameters are passed
