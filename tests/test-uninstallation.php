<?php
/**
 * Plugin Uninstallation Test Script
 * 
 * This script tests the plugin uninstallation process by simulating
 * the GLPI environment and verifying that all uninstallation steps
 * complete successfully and all tables are removed.
 */

// Simulate GLPI environment
define('GLPI_ROOT', '/tmp/glpi-test');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');

// Mock GLPI base classes
class CommonDBTM {
    public $fields = [];
    
    public function getFromDB($id) {
        return false;
    }
    
    public function getEmpty() {
        $this->fields = [];
    }
    
    public static function getTable() {
        // Return table name based on class name
        $classname = get_called_class();
        
        // Convert PluginCostsEntity_Profile to glpi_plugin_costs_entities_profiles
        $table = strtolower($classname);
        $table = str_replace('plugincosts', 'glpi_plugin_costs_', $table);
        $table = str_replace('_', 's_', $table);
        
        // Handle special cases
        if (strpos($classname, 'Entity_Profile') !== false) {
            return 'glpi_plugin_costs_entities_profiles';
        } elseif (strpos($classname, 'Entity') !== false) {
            return 'glpi_plugin_costs_entities';
        } elseif (strpos($classname, 'Config') !== false) {
            return 'glpi_plugin_costs_configs';
        } elseif (strpos($classname, 'Ticket') !== false) {
            return 'glpi_plugin_costs_tickets';
        } elseif (strpos($classname, 'Task') !== false) {
            return 'glpi_plugin_costs_tasks';
        }
        
        return '';
    }
    
    public function getID() {
        return $this->fields['id'] ?? 0;
    }
}

class CommonGLPI {
}

class CommonDBRelation extends CommonDBTM {
}

// Mock GLPI classes and functions that the plugin depends on
class DB {
    private static $instance = null;
    private $queries = [];
    private $tables = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql) {
        $this->queries[] = $sql;
        
        // Track DROP TABLE queries
        if (preg_match('/DROP TABLE.*`(glpi_plugin_costs_[^`]+)`/i', $sql, $matches)) {
            $table = $matches[1];
            if (isset($this->tables[$table])) {
                unset($this->tables[$table]);
                echo "  ✓ Dropped table: $table\n";
            }
        }
        
        return true;
    }
    
    public function tableExists($table) {
        return isset($this->tables[$table]);
    }
    
    public function createTable($table) {
        $this->tables[$table] = true;
    }
    
    public function getQueries() {
        return $this->queries;
    }
    
    public function getTables() {
        return array_keys($this->tables);
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
    private $dropped_tables = [];
    
    public function __construct($version) {
        $this->version = $version;
        echo "Migration initialized for version: $version\n";
    }
    
    public function displayMessage($message) {
        echo "  $message\n";
    }
    
    public function dropTable($table) {
        global $DB;
        $this->operations[] = "DROP TABLE: $table";
        $this->dropped_tables[] = $table;
        echo "  - Dropping table: $table\n";
        
        // Actually drop the table in the mock DB
        $DB->query("DROP TABLE IF EXISTS `$table`");
        return true;
    }
    
    public function executeMigration() {
        echo "  - Executing migration...\n";
        return true;
    }
    
    public function getOperations() {
        return $this->operations;
    }
    
    public function getDroppedTables() {
        return $this->dropped_tables;
    }
}

// Load plugin files
require_once __DIR__ . '/../setup.php';
require_once __DIR__ . '/../hook.php';

// Test Results
$results = [
    'uninstall_function' => false,
    'tables_dropped' => false,
    'no_errors' => true,
    'errors' => []
];

echo "\n";
echo "========================================\n";
echo "GLPI Costs Plugin Uninstallation Test\n";
echo "========================================\n";
echo "\n";

// Setup: Simulate installed plugin with tables
echo "Setup: Simulating installed plugin...\n";
global $DB;
$DB = DB::getInstance();

$expected_tables = [
    'glpi_plugin_costs_configs',
    'glpi_plugin_costs_entities',
    'glpi_plugin_costs_entities_profiles',
    'glpi_plugin_costs_tickets',
    'glpi_plugin_costs_tasks'
];

// Create mock tables
foreach ($expected_tables as $table) {
    $DB->createTable($table);
}

echo "  ✓ Created " . count($expected_tables) . " mock tables\n";
echo "  Tables: " . implode(", ", $DB->getTables()) . "\n";
echo "\n";

// Test 1: Uninstallation Function Exists
echo "Test 1: Checking uninstallation function...\n";
try {
    if (!function_exists('plugin_costs_uninstall')) {
        throw new Exception("plugin_costs_uninstall() function not found");
    }
    
    echo "  ✓ plugin_costs_uninstall() function exists\n";
} catch (Exception $e) {
    $results['errors'][] = "Uninstallation function check failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
    $results['no_errors'] = false;
}
echo "\n";

// Test 2: Execute Uninstallation
echo "Test 2: Executing plugin_costs_uninstall()...\n";
try {
    // Call uninstallation function
    $uninstall_result = plugin_costs_uninstall();
    
    if ($uninstall_result !== true) {
        throw new Exception("Uninstallation function returned false");
    }
    
    echo "  ✓ Uninstallation function executed successfully\n";
    $results['uninstall_function'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Uninstallation execution failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
    $results['no_errors'] = false;
}
echo "\n";

// Test 3: Verify Tables Were Dropped
echo "Test 3: Verifying tables were dropped...\n";
try {
    $remaining_tables = $DB->getTables();
    
    if (count($remaining_tables) > 0) {
        echo "  ✗ Some tables were not dropped:\n";
        foreach ($remaining_tables as $table) {
            echo "    - $table\n";
        }
        throw new Exception("Not all tables were dropped during uninstallation");
    }
    
    echo "  ✓ All plugin tables were successfully dropped\n";
    $results['tables_dropped'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Table cleanup verification failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
    $results['no_errors'] = false;
}
echo "\n";

// Test 4: Verify Uninstall Methods Were Called
echo "Test 4: Verifying uninstall methods were called...\n";
try {
    $queries = $DB->getQueries();
    
    $drop_table_count = 0;
    foreach ($queries as $query) {
        if (stripos($query, 'DROP TABLE') !== false) {
            $drop_table_count++;
        }
    }
    
    if ($drop_table_count !== count($expected_tables)) {
        echo "  ⚠ Expected " . count($expected_tables) . " DROP TABLE queries, found $drop_table_count\n";
    } else {
        echo "  ✓ All expected DROP TABLE queries were executed\n";
    }
    
    // Verify each expected table was dropped
    $all_dropped = true;
    foreach ($expected_tables as $table) {
        $found = false;
        foreach ($queries as $query) {
            if (stripos($query, "DROP TABLE") !== false && stripos($query, $table) !== false) {
                $found = true;
                break;
            }
        }
        
        if ($found) {
            echo "  ✓ $table was dropped\n";
        } else {
            echo "  ✗ $table was NOT dropped\n";
            $all_dropped = false;
        }
    }
    
    if (!$all_dropped) {
        throw new Exception("Not all expected tables were dropped");
    }
    
} catch (Exception $e) {
    $results['errors'][] = "Uninstall method verification failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
    $results['no_errors'] = false;
}
echo "\n";

// Test 5: Check for Uninstallation Errors
echo "Test 5: Checking for uninstallation errors...\n";
try {
    // In a real scenario, we would check GLPI logs
    // For this test, we verify no exceptions were thrown
    
    if ($results['no_errors']) {
        echo "  ✓ No errors occurred during uninstallation\n";
    } else {
        echo "  ✗ Errors occurred during uninstallation (see above)\n";
    }
} catch (Exception $e) {
    $results['errors'][] = "Error check failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Verify Class Uninstall Methods
echo "Test 6: Verifying class uninstall methods exist...\n";
try {
    $class_files = [
        'inc/config.class.php' => 'PluginCostsConfig',
        'inc/entity.class.php' => 'PluginCostsEntity',
        'inc/entity_profile.class.php' => 'PluginCostsEntity_Profile',
        'inc/ticket.class.php' => 'PluginCostsTicket',
        'inc/task.class.php' => 'PluginCostsTask'
    ];
    
    $classes_with_uninstall = 0;
    $classes_without_uninstall = [];
    
    foreach ($class_files as $file => $classname) {
        if (class_exists($classname)) {
            // Check for 'uninstall' method
            if (method_exists($classname, 'uninstall')) {
                echo "  ✓ $classname has uninstall method\n";
                $classes_with_uninstall++;
            } else {
                echo "  ⚠ $classname does not have uninstall method\n";
                $classes_without_uninstall[] = $classname;
            }
        }
    }
    
    echo "  ✓ Found uninstall methods in $classes_with_uninstall classes\n";
    
    if (count($classes_without_uninstall) > 0) {
        echo "  ⚠ Classes without uninstall methods: " . implode(", ", $classes_without_uninstall) . "\n";
    }
    
} catch (Exception $e) {
    $results['errors'][] = "Class method verification failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "\n";

$passed = 0;
$total = 3; // Main tests: uninstall function, tables dropped, no errors

if ($results['uninstall_function']) {
    echo "✓ Uninstallation function: PASSED\n";
    $passed++;
} else {
    echo "✗ Uninstallation function: FAILED\n";
}

if ($results['tables_dropped']) {
    echo "✓ Tables cleanup: PASSED\n";
    $passed++;
} else {
    echo "✗ Tables cleanup: FAILED\n";
}

if ($results['no_errors']) {
    echo "✓ No errors during uninstallation: PASSED\n";
    $passed++;
} else {
    echo "✗ Errors occurred during uninstallation: FAILED\n";
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

// Requirements validation
echo "========================================\n";
echo "Requirements Validation\n";
echo "========================================\n";
echo "\n";

echo "Requirement 10.2: Plugin uninstallation on GLPI 11\n";
if ($results['uninstall_function'] && $results['tables_dropped'] && $results['no_errors']) {
    echo "  ✓ PASSED - plugin_costs_uninstall() executes successfully\n";
    echo "  ✓ PASSED - All database tables are removed\n";
    echo "  ✓ PASSED - No uninstallation errors occurred\n";
} else {
    echo "  ✗ FAILED - See test results above\n";
}

echo "\n";

// Exit with appropriate code
exit($passed === $total ? 0 : 1);
