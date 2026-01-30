<?php
/**
 * Plugin Structure Verification Script
 * 
 * This script verifies that the plugin structure and configuration
 * are correct for GLPI 11 compatibility without requiring a full
 * GLPI installation.
 */

echo "\n";
echo "========================================\n";
echo "GLPI Costs Plugin Structure Verification\n";
echo "========================================\n";
echo "\n";

$errors = [];
$warnings = [];
$passed = 0;
$total = 0;

// Test 1: Check required files exist
echo "Test 1: Checking required files...\n";
$total++;
$required_files = [
    'setup.php',
    'hook.php',
    'inc/config.class.php',
    'inc/entity.class.php',
    'inc/entity_profile.class.php',
    'inc/ticket.class.php',
    'inc/task.class.php',
    'front/config.form.php',
    'front/entity.form.php',
    'front/entity_profile.form.php',
    'templates/config.html.twig',
    'templates/billable_dropdown.html.twig'
];

$all_files_exist = true;
foreach ($required_files as $file) {
    if (file_exists(__DIR__ . '/../' . $file)) {
        echo "  ✓ $file\n";
    } else {
        echo "  ✗ $file (missing)\n";
        $errors[] = "Required file missing: $file";
        $all_files_exist = false;
    }
}

if ($all_files_exist) {
    $passed++;
    echo "  ✓ All required files exist\n";
} else {
    echo "  ✗ Some required files are missing\n";
}
echo "\n";

// Test 2: Check version constants in setup.php
echo "Test 2: Checking version constants...\n";
$total++;
$setup_content = file_get_contents(__DIR__ . '/../setup.php');

$version_checks = [
    "PLUGIN_COSTS_VERSION', '3.1.0'" => "Plugin version is 3.1.0",
    'PLUGIN_COSTS_MIN_GLPI", "10.0"' => "Min GLPI version is 10.0",
    'PLUGIN_COSTS_MAX_GLPI", "12.0"' => "Max GLPI version is 12.0"
];

$version_ok = true;
foreach ($version_checks as $pattern => $description) {
    if (strpos($setup_content, $pattern) !== false) {
        echo "  ✓ $description\n";
    } else {
        echo "  ✗ $description (not found)\n";
        $errors[] = $description . " - check failed";
        $version_ok = false;
    }
}

if ($version_ok) {
    $passed++;
    echo "  ✓ Version constants are correct\n";
} else {
    echo "  ✗ Version constants check failed\n";
}
echo "\n";

// Test 3: Check for GLPI 11 compatible hook usage
echo "Test 3: Checking hook registration...\n";
$total++;

$hook_patterns = [
    'use Glpi\Plugin\Hooks;' => "Uses Glpi\\Plugin\\Hooks namespace",
    'Hooks::CSRF_COMPLIANT' => "Uses Hooks::CSRF_COMPLIANT",
    'Hooks::POST_ITEM_FORM' => "Uses Hooks::POST_ITEM_FORM",
    'Hooks::PRE_ITEM_UPDATE' => "Uses Hooks::PRE_ITEM_UPDATE",
    'Hooks::ITEM_ADD' => "Uses Hooks::ITEM_ADD",
    'Hooks::ITEM_PURGE' => "Uses Hooks::ITEM_PURGE"
];

$hooks_ok = true;
foreach ($hook_patterns as $pattern => $description) {
    if (strpos($setup_content, $pattern) !== false) {
        echo "  ✓ $description\n";
    } else {
        echo "  ✗ $description (not found)\n";
        $errors[] = $description . " - not found in setup.php";
        $hooks_ok = false;
    }
}

if ($hooks_ok) {
    $passed++;
    echo "  ✓ Hook registration is GLPI 11 compatible\n";
} else {
    echo "  ✗ Hook registration check failed\n";
}
echo "\n";

// Test 4: Check for database API usage in hook.php
echo "Test 4: Checking database API usage...\n";
$total++;

if (file_exists(__DIR__ . '/../hook.php')) {
    $hook_content = file_get_contents(__DIR__ . '/../hook.php');
    
    $db_patterns = [
        'DBConnection::getDefaultCharset()' => "Uses DBConnection::getDefaultCharset()",
        'DBConnection::getDefaultCollation()' => "Uses DBConnection::getDefaultCollation()",
        'DBConnection::getDefaultPrimaryKeySignOption()' => "Uses DBConnection::getDefaultPrimaryKeySignOption()"
    ];
    
    $db_ok = true;
    foreach ($db_patterns as $pattern => $description) {
        if (strpos($hook_content, $pattern) !== false) {
            echo "  ✓ $description\n";
        } else {
            echo "  ⚠ $description (not found)\n";
            $warnings[] = $description . " - not found in hook.php";
        }
    }
    
    $passed++;
    echo "  ✓ Database API check completed\n";
} else {
    echo "  ✗ hook.php not found\n";
    $errors[] = "hook.php file not found";
}
echo "\n";

// Test 5: Check for template renderer usage
echo "Test 5: Checking template renderer usage...\n";
$total++;

$template_files = [
    'inc/config.class.php',
    'inc/ticket.class.php'
];

$template_ok = true;
foreach ($template_files as $file) {
    $file_path = __DIR__ . '/../' . $file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        if (strpos($content, 'TemplateRenderer::getInstance()') !== false) {
            echo "  ✓ $file uses TemplateRenderer\n";
        } else {
            echo "  ⚠ $file may not use TemplateRenderer\n";
            $warnings[] = "$file - TemplateRenderer usage not detected";
        }
    } else {
        echo "  ✗ $file not found\n";
        $errors[] = "$file not found";
        $template_ok = false;
    }
}

if ($template_ok) {
    $passed++;
    echo "  ✓ Template renderer check completed\n";
}
echo "\n";

// Test 6: Check for deprecated patterns
echo "Test 6: Checking for deprecated patterns...\n";
$total++;

$php_files = glob(__DIR__ . '/../inc/*.php');
$php_files = array_merge($php_files, glob(__DIR__ . '/../front/*.php'));
$php_files[] = __DIR__ . '/../setup.php';
$php_files[] = __DIR__ . '/../hook.php';

$deprecated_patterns = [
    '@var \DBmysql' => "DBmysql type hint (deprecated)",
    'DBmysql::' => "DBmysql static calls (deprecated)"
];

$deprecated_found = false;
foreach ($php_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        foreach ($deprecated_patterns as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                echo "  ⚠ Found $description in " . basename($file) . "\n";
                $warnings[] = "Found $description in " . basename($file);
                $deprecated_found = true;
            }
        }
    }
}

if (!$deprecated_found) {
    echo "  ✓ No deprecated patterns found\n";
    $passed++;
} else {
    echo "  ⚠ Some deprecated patterns found (may need review)\n";
    $passed++; // Not a critical error
}
echo "\n";

// Test 7: Check Twig templates
echo "Test 7: Checking Twig templates...\n";
$total++;

$template_files = glob(__DIR__ . '/../templates/*.twig');
$templates_ok = true;

if (count($template_files) > 0) {
    foreach ($template_files as $template) {
        $content = file_get_contents($template);
        // Basic syntax check
        if (strpos($content, '{{') !== false || strpos($content, '{%') !== false) {
            echo "  ✓ " . basename($template) . " appears valid\n";
        } else {
            echo "  ⚠ " . basename($template) . " may be empty or invalid\n";
            $warnings[] = basename($template) . " may be empty or invalid";
        }
    }
    $passed++;
    echo "  ✓ Template files check completed\n";
} else {
    echo "  ✗ No template files found\n";
    $errors[] = "No template files found";
}
echo "\n";

// Summary
echo "========================================\n";
echo "Verification Summary\n";
echo "========================================\n";
echo "\n";

echo "Tests passed: $passed/$total\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n";
echo "\n";

if (count($errors) > 0) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
    echo "\n";
}

if ($passed === $total && count($errors) === 0) {
    echo "✓ Plugin structure verification PASSED\n";
    echo "  The plugin appears to be correctly configured for GLPI 11.\n";
    exit(0);
} elseif (count($errors) === 0) {
    echo "⚠ Plugin structure verification PASSED with warnings\n";
    echo "  The plugin appears functional but has some warnings to review.\n";
    exit(0);
} else {
    echo "✗ Plugin structure verification FAILED\n";
    echo "  Please fix the errors above before proceeding.\n";
    exit(1);
}
