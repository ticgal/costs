<?php
/**
 * Plugin Installation Test Script
 * 
 * This script tests the plugin installation process by simulating
 * the GLPI environment and verifying that all installation steps
 * complete successfully.
 */

// Simulate GLPI environment
define('GLPI_ROOT', '/tmp/glpi-test');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');

// Mock GLPI classes and functions that the plugin depends on
class DB {
    private static $instance = null;
    private $queries = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql) {
        $this->queries[] = $sql;
        echo "SQL: " . substr($sql, 0, 100) . "...\n";
        return true;
    }
    
    public function tableExists($table) {
        return false; // Simulate fresh installation
    }
    
    public function getQueries() {
        return $this->queries;
    }
}

class DBConnection {
    public static function getDefaultCharset() {
        return 'utf8mb4';
    }
    
    public static function getDefaultCollation() {
        return 'utf8mb4_unicode_ci';
    }
    
    public static function getDefaultPrimaryKeySignOption() {
        return 'UNSIGNED';
    }
}

class Migration {
    private $version;
    private $operations = [];
    
    public function __construct($version) {
        $this->version = $version;
        echo "Migration initialized for version: $version\n";
    }
    
    public function addField($table, $field, $type, $options = []) {
        $this->operations[] = "ADD FIELD: $table.$field ($type)";
        echo "  - Adding field: $table.$field\n";
        return true;
    }
    
    public function dropField($table, $field) {
        $this->operations[] = "DROP FIELD: $table.$field";
        echo "  - Dropping field: $table.$field\n";
        return true;
    }
    
    public function addKey($table, $field, $name = '', $type = 'INDEX') {
        $this->operations[] = "ADD KEY: $table.$field ($type)";
        echo "  - Adding key: $table.$field\n";
        return true;
    }
    
    public function dropKey($table, $name) {
        $this->operations[] = "DROP KEY: $table.$name";
        echo "  - Dropping key: $table.$name\n";
        return true;
    }
    
    public function executeMigration() {
        echo "  - Executing migration...\n";
        return true;
    }
    
    public function getOperations() {
        return $this->operations;
    }
}

// Load plugin files
require_once __DIR__ . '/../setup.php';
require_once __DIR__ . '/../hook.php';

// Test Results
$results = [
    'version_check' => false,
    'install_function' => false,
    'uninstall_function' => false,
    'tables_created' => false,
    'errors' => []
];

echo "\n";
echo "========================================\n";
echo "GLPI Costs Plugin Installation Test\n";
echo "========================================\n";
echo "\n";

// Test 1: Version Constants
echo "Test 1: Checking version constants...\n";
try {
    if (!defined('PLUGIN_COSTS_VERSION')) {
        throw new Exception("PLUGIN_COSTS_VERSION not defined");
    }
    if (!defined('PLUGIN_COSTS_MIN_GLPI')) {
        throw new Exception("PLUGIN_COSTS_MIN_GLPI not defined");
    }
    if (!defined('PLUGIN_COSTS_MAX_GLPI')) {
        throw new Exception("PLUGIN_COSTS_MAX_GLPI not defined");
    }
    
    echo "  ✓ PLUGIN_COSTS_VERSION: " . PLUGIN_COSTS_VERSION . "\n";
    echo "  ✓ PLUGIN_COSTS_MIN_GLPI: " . PLUGIN_COSTS_MIN_GLPI . "\n";
    echo "  ✓ PLUGIN_COSTS_MAX_GLPI: " . PLUGIN_COSTS_MAX_GLPI . "\n";
    
    // Verify version values
    if (PLUGIN_COSTS_VERSION !== '3.1.0') {
        throw new Exception("Expected version 3.1.0, got: " . PLUGIN_COSTS_VERSION);
    }
    if (PLUGIN_COSTS_MIN_GLPI !== '10.0') {
        throw new Exception("Expected min GLPI 10.0, got: " . PLUGIN_COSTS_MIN_GLPI);
    }
    if (PLUGIN_COSTS_MAX_GLPI !== '12.0') {
        throw new Exception("Expected max GLPI 12.0, got: " . PLUGIN_COSTS_MAX_GLPI);
    }
    
    $results['version_check'] = true;
    echo "  ✓ Version constants are correct\n";
} catch (Exception $e) {
    $results['errors'][] = "Version check failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Installation Function
echo "Test 2: Testing plugin_costs_install()...\n";
try {
    if (!function_exists('plugin_costs_install')) {
        throw new Exception("plugin_costs_install() function not found");
    }
    
    // Mock the global $DB variable
    global $DB;
    $DB = DB::getInstance();
    
    // Call installation function
    $install_result = plugin_costs_install();
    
    if ($install_result !== true) {
        throw new Exception("Installation function returned false");
    }
    
    $queries = $DB->getQueries();
    $expected_tables = [
        'glpi_plugin_costs_configs',
        'glpi_plugin_costs_entities',
        'glpi_plugin_costs_entities_profiles',
        'glpi_plugin_costs_tickets',
        'glpi_plugin_costs_tasks'
    ];
    
    $tables_found = [];
    foreach ($queries as $query) {
        foreach ($expected_tables as $table) {
            if (stripos($query, $table) !== false) {
                $tables_found[] = $table;
            }
        }
    }
    
    $tables_found = array_unique($tables_found);
    
    echo "  ✓ Installation function executed successfully\n";
    echo "  ✓ Found " . count($tables_found) . " table creation queries\n";
    
    foreach ($expected_tables as $table) {
        if (in_array($table, $tables_found)) {
            echo "    ✓ $table\n";
        } else {
            echo "    ✗ $table (not found)\n";
        }
    }
    
    if (count($tables_found) === count($expected_tables)) {
        $results['tables_created'] = true;
    }
    
    $results['install_function'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Installation test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Uninstallation Function
echo "Test 3: Testing plugin_costs_uninstall()...\n";
try {
    if (!function_exists('plugin_costs_uninstall')) {
        throw new Exception("plugin_costs_uninstall() function not found");
    }
    
    // Reset DB mock
    $DB = DB::getInstance();
    
    // Call uninstallation function
    $uninstall_result = plugin_costs_uninstall();
    
    if ($uninstall_result !== true) {
        throw new Exception("Uninstallation function returned false");
    }
    
    echo "  ✓ Uninstallation function executed successfully\n";
    $results['uninstall_function'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Uninstallation test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Database API Usage
echo "Test 4: Checking database API usage...\n";
try {
    $hook_content = file_get_contents(__DIR__ . '/../hook.php');
    
    // Check for DBConnection usage
    if (strpos($hook_content, 'DBConnection::getDefaultCharset()') !== false) {
        echo "  ✓ Uses DBConnection::getDefaultCharset()\n";
    } else {
        echo "  ⚠ DBConnection::getDefaultCharset() not found\n";
    }
    
    if (strpos($hook_content, 'DBConnection::getDefaultCollation()') !== false) {
        echo "  ✓ Uses DBConnection::getDefaultCollation()\n";
    } else {
        echo "  ⚠ DBConnection::getDefaultCollation() not found\n";
    }
    
    if (strpos($hook_content, 'DBConnection::getDefaultPrimaryKeySignOption()') !== false) {
        echo "  ✓ Uses DBConnection::getDefaultPrimaryKeySignOption()\n";
    } else {
        echo "  ⚠ DBConnection::getDefaultPrimaryKeySignOption() not found\n";
    }
} catch (Exception $e) {
    $results['errors'][] = "Database API check failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "\n";

$passed = 0;
$total = 4;

if ($results['version_check']) {
    echo "✓ Version constants check: PASSED\n";
    $passed++;
} else {
    echo "✗ Version constants check: FAILED\n";
}

if ($results['install_function']) {
    echo "✓ Installation function: PASSED\n";
    $passed++;
} else {
    echo "✗ Installation function: FAILED\n";
}

if ($results['tables_created']) {
    echo "✓ Database tables creation: PASSED\n";
    $passed++;
} else {
    echo "✗ Database tables creation: FAILED\n";
}

if ($results['uninstall_function']) {
    echo "✓ Uninstallation function: PASSED\n";
    $passed++;
} else {
    echo "✗ Uninstallation function: FAILED\n";
}

echo "\n";
echo "Results: $passed/$total tests passed\n";

if (count($results['errors']) > 0) {
    echo "\nErrors:\n";
    foreach ($results['errors'] as $error) {
        echo "  - $error\n";
    }
}

echo "\n";

// Exit with appropriate code
exit($passed === $total ? 0 : 1);
