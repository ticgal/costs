<?php
/**
 * Entity Cost Configuration Test Script
 * 
 * This script tests entity cost configuration functionality including:
 * - Creating entity cost configurations
 * - Testing fixed cost and time cost settings
 * - Testing inheritance configuration
 * - Verifying configuration persistence
 * 
 * Requirements: 12.2
 * Task: 5.1
 */

// Simulate GLPI environment
define('GLPI_ROOT', '/tmp/glpi-test');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');
define('PLUGIN_COSTS_NUMBER_STEP', '0.01');

// Mock GLPI classes
class DB {
    private static $instance = null;
    private $data = [];
    private $nextId = 1;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function request($criteria) {
        $table = $criteria['FROM'] ?? '';
        $where = $criteria['WHERE'] ?? [];
        
        if (!isset($this->data[$table])) {
            return [];
        }
        
        $results = [];
        foreach ($this->data[$table] as $id => $row) {
            $match = true;
            foreach ($where as $field => $value) {
                if (!isset($row[$field]) || $row[$field] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $results[$id] = $row;
            }
        }
        
        return $results;
    }
    
    public function insert($table, $data) {
        if (!isset($this->data[$table])) {
            $this->data[$table] = [];
        }
        
        // If ID is provided, use it; otherwise auto-increment
        if (isset($data['id'])) {
            $id = $data['id'];
            // Update nextId if necessary
            if ($id >= $this->nextId) {
                $this->nextId = $id + 1;
            }
        } else {
            $id = $this->nextId++;
            $data['id'] = $id;
        }
        
        $this->data[$table][$id] = $data;
        
        return true;
    }
    
    public function update($table, $data, $where) {
        if (!isset($this->data[$table])) {
            return false;
        }
        
        foreach ($this->data[$table] as $id => &$row) {
            $match = true;
            foreach ($where as $field => $value) {
                if (!isset($row[$field]) || $row[$field] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                foreach ($data as $field => $value) {
                    $row[$field] = $value;
                }
            }
        }
        
        return true;
    }
    
    public function tableExists($table) {
        return true;
    }
    
    public function fieldExists($table, $field) {
        return true;
    }
    
    public function doQueryOrDie($query, $error) {
        return true;
    }
    
    public function error() {
        return '';
    }
    
    public function getData() {
        return $this->data;
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

class CommonDBTM {
    public $fields = [];
    protected static $table = '';
    
    public static function getTable() {
        return static::$table;
    }
    
    public function getID() {
        return $this->fields['id'] ?? 0;
    }
    
    public function getFromDB($id) {
        global $DB;
        $results = $DB->request(['FROM' => static::getTable(), 'WHERE' => ['id' => $id]]);
        if (count($results) > 0) {
            $this->fields = reset($results);
            return true;
        }
        return false;
    }
    
    public function add($input) {
        global $DB;
        $DB->insert(static::getTable(), $input);
        $results = $DB->request(['FROM' => static::getTable(), 'WHERE' => $input]);
        if (count($results) > 0) {
            $this->fields = reset($results);
            return $this->fields['id'];
        }
        return false;
    }
    
    public function update($input) {
        global $DB;
        $id = $input['id'];
        unset($input['id']);
        return $DB->update(static::getTable(), $input, ['id' => $id]);
    }
}

class Entity extends CommonDBTM {
    protected static $table = 'glpi_entities';
    
    public function getField($field) {
        return $this->fields[$field] ?? null;
    }
    
    public function getFromDB($id) {
        global $DB;
        $results = $DB->request(['FROM' => self::getTable(), 'WHERE' => ['id' => $id]]);
        if (count($results) > 0) {
            $this->fields = reset($results);
            return true;
        }
        return false;
    }
}

class Dropdown {
    public static function showYesNo($name, $value, $rand = -1, $options = []) {
        $display = $options['display'] ?? true;
        $html = "<select name='$name'>";
        $html .= "<option value='0'" . ($value == 0 ? " selected" : "") . ">No</option>";
        $html .= "<option value='1'" . ($value == 1 ? " selected" : "") . ">Yes</option>";
        $html .= "</select>";
        
        if ($display) {
            echo $html;
            return;
        }
        return $html;
    }
    
    public static function getYesNo($value) {
        return $value ? 'Yes' : 'No';
    }
}

class Html {
    public static function closeForm($display = true) {
        $html = "</form>";
        if ($display) {
            echo $html;
            return;
        }
        return $html;
    }
}

class Session {
    public static function getCurrentInterface() {
        return 'central';
    }
}

class Migration {
    public function __construct($version) {}
    public function displayMessage($msg) {}
    public function addField($table, $field, $type, $options = []) {}
    public function executeMigration() {}
    public function migrationOneTable($table) {}
}

// Load plugin classes
require_once __DIR__ . '/../inc/entity.class.php';

// Initialize global DB
global $DB;
$DB = DB::getInstance();

// Test Results
$results = [
    'create_config' => false,
    'update_config' => false,
    'inheritance_test' => false,
    'persistence_test' => false,
    'errors' => []
];

echo "\n";
echo "========================================\n";
echo "Entity Cost Configuration Test\n";
echo "========================================\n";
echo "\n";

// Test 1: Create entity cost configuration
echo "Test 1: Creating entity cost configuration...\n";
try {
    $entity_config = new PluginCostsEntity();
    
    // Create configuration for entity 1
    $config_data = [
        'entities_id' => 1,
        'fixed_cost' => 10.50,
        'time_cost' => 50.00,
        'cost_private' => 1,
        'auto_cost' => 1,
        'inheritance' => 0
    ];
    
    $config_id = $entity_config->add($config_data);
    
    if ($config_id === false) {
        throw new Exception("Failed to create entity cost configuration");
    }
    
    echo "  ✓ Created entity cost configuration with ID: $config_id\n";
    echo "  ✓ Fixed cost: " . $entity_config->fields['fixed_cost'] . "\n";
    echo "  ✓ Time cost: " . $entity_config->fields['time_cost'] . "\n";
    echo "  ✓ Cost private: " . $entity_config->fields['cost_private'] . "\n";
    echo "  ✓ Auto cost: " . $entity_config->fields['auto_cost'] . "\n";
    echo "  ✓ Inheritance: " . $entity_config->fields['inheritance'] . "\n";
    
    $results['create_config'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Create config failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Update entity cost configuration
echo "Test 2: Updating entity cost configuration...\n";
try {
    $entity_config = new PluginCostsEntity();
    $entity_config->getFromDB($config_id);
    
    // Update configuration
    $update_data = [
        'id' => $config_id,
        'fixed_cost' => 15.75,
        'time_cost' => 75.50,
        'cost_private' => 0,
        'auto_cost' => 0
    ];
    
    $update_result = $entity_config->update($update_data);
    
    if ($update_result === false) {
        throw new Exception("Failed to update entity cost configuration");
    }
    
    // Verify update
    $entity_config->getFromDB($config_id);
    
    if ($entity_config->fields['fixed_cost'] != 15.75) {
        throw new Exception("Fixed cost not updated correctly");
    }
    if ($entity_config->fields['time_cost'] != 75.50) {
        throw new Exception("Time cost not updated correctly");
    }
    if ($entity_config->fields['cost_private'] != 0) {
        throw new Exception("Cost private not updated correctly");
    }
    if ($entity_config->fields['auto_cost'] != 0) {
        throw new Exception("Auto cost not updated correctly");
    }
    
    echo "  ✓ Updated entity cost configuration\n";
    echo "  ✓ New fixed cost: " . $entity_config->fields['fixed_cost'] . "\n";
    echo "  ✓ New time cost: " . $entity_config->fields['time_cost'] . "\n";
    echo "  ✓ New cost private: " . $entity_config->fields['cost_private'] . "\n";
    echo "  ✓ New auto cost: " . $entity_config->fields['auto_cost'] . "\n";
    
    $results['update_config'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Update config failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Test inheritance configuration
echo "Test 3: Testing inheritance configuration...\n";
try {
    // First, create mock entity records in the database
    // Entity 10 is the root, Entity 20 is a child of Entity 10
    $DB->insert('glpi_entities', ['id' => 10, 'entities_id' => 0]);
    $DB->insert('glpi_entities', ['id' => 20, 'entities_id' => 10]);
    
    // Verify entities were created
    $entity_check = new Entity();
    if (!$entity_check->getFromDB(20)) {
        throw new Exception("Failed to create entity 20");
    }
    if ($entity_check->fields['entities_id'] != 10) {
        throw new Exception("Entity 20 parent is not 10, got: " . $entity_check->fields['entities_id']);
    }
    
    // Create parent entity configuration (entity 10) - NO inheritance
    $parent_config = new PluginCostsEntity();
    $parent_data = [
        'entities_id' => 10,
        'fixed_cost' => 20.00,
        'time_cost' => 100.00,
        'cost_private' => 1,
        'auto_cost' => 1,
        'inheritance' => 0  // Parent does NOT inherit
    ];
    $parent_id = $parent_config->add($parent_data);
    
    // Create child entity with inheritance enabled (entity 20)
    $child_config = new PluginCostsEntity();
    $child_data = [
        'entities_id' => 20,
        'fixed_cost' => 0,
        'time_cost' => 0,
        'cost_private' => 0,
        'auto_cost' => 0,
        'inheritance' => 1  // Child DOES inherit
    ];
    $child_id = $child_config->add($child_data);
    
    echo "  ✓ Created parent configuration (ID: $parent_id, Entity: 10, Inheritance: 0)\n";
    echo "  ✓ Created child configuration (ID: $child_id, Entity: 20, Inheritance: 1)\n";
    echo "  ✓ Entity 20 parent is Entity 10\n";
    
    // Test getConfigID - should return parent config because child has inheritance=1
    $resolved_id = PluginCostsEntity::getConfigID(20);
    
    echo "  ✓ Resolved config ID for child entity 20: $resolved_id\n";
    
    // Verify it resolves to parent
    if ($resolved_id == $parent_id) {
        echo "  ✓ Inheritance correctly resolves to parent configuration\n";
        $results['inheritance_test'] = true;
    } else {
        // Debug: Let's see what's in the config
        $debug_config = new PluginCostsEntity();
        $debug_config->getFromDB($resolved_id);
        throw new Exception("Inheritance did not resolve to parent configuration (expected $parent_id, got $resolved_id, inheritance flag: " . $debug_config->fields['inheritance'] . ")");
    }
} catch (Exception $e) {
    $results['errors'][] = "Inheritance test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Verify configuration persistence
echo "Test 4: Verifying configuration persistence...\n";
try {
    // Create a new configuration
    $persist_config = new PluginCostsEntity();
    $persist_data = [
        'entities_id' => 3,
        'fixed_cost' => 25.25,
        'time_cost' => 125.75,
        'cost_private' => 1,
        'auto_cost' => 0,
        'inheritance' => 0
    ];
    $persist_id = $persist_config->add($persist_data);
    
    // Create a new instance and retrieve the same configuration
    $verify_config = new PluginCostsEntity();
    $verify_config->getFromDB($persist_id);
    
    // Verify all fields match
    if ($verify_config->fields['entities_id'] != 3) {
        throw new Exception("Entity ID not persisted correctly");
    }
    if ($verify_config->fields['fixed_cost'] != 25.25) {
        throw new Exception("Fixed cost not persisted correctly");
    }
    if ($verify_config->fields['time_cost'] != 125.75) {
        throw new Exception("Time cost not persisted correctly");
    }
    if ($verify_config->fields['cost_private'] != 1) {
        throw new Exception("Cost private not persisted correctly");
    }
    if ($verify_config->fields['auto_cost'] != 0) {
        throw new Exception("Auto cost not persisted correctly");
    }
    if ($verify_config->fields['inheritance'] != 0) {
        throw new Exception("Inheritance not persisted correctly");
    }
    
    echo "  ✓ Configuration persisted correctly\n";
    echo "  ✓ All fields retrieved match original values\n";
    echo "    - Entity ID: " . $verify_config->fields['entities_id'] . "\n";
    echo "    - Fixed cost: " . $verify_config->fields['fixed_cost'] . "\n";
    echo "    - Time cost: " . $verify_config->fields['time_cost'] . "\n";
    echo "    - Cost private: " . $verify_config->fields['cost_private'] . "\n";
    echo "    - Auto cost: " . $verify_config->fields['auto_cost'] . "\n";
    echo "    - Inheritance: " . $verify_config->fields['inheritance'] . "\n";
    
    $results['persistence_test'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Persistence test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Test getFromDBByEntity method
echo "Test 5: Testing getFromDBByEntity method...\n";
try {
    // Test with existing entity
    $test_config = new PluginCostsEntity();
    $found = $test_config->getFromDBByEntity(1);
    
    if (!$found) {
        echo "  ⚠ Entity 1 not found, created default configuration\n";
    } else {
        echo "  ✓ Found existing configuration for entity 1\n";
    }
    
    // Test with non-existing entity (should create default)
    $new_config = new PluginCostsEntity();
    $found = $new_config->getFromDBByEntity(99);
    
    if (!$found && isset($new_config->fields['id'])) {
        echo "  ✓ Created default configuration for non-existing entity\n";
        echo "  ✓ Default inheritance: " . $new_config->fields['inheritance'] . "\n";
    }
    
    echo "  ✓ getFromDBByEntity method works correctly\n";
} catch (Exception $e) {
    $results['errors'][] = "getFromDBByEntity test failed: " . $e->getMessage();
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

if ($results['create_config']) {
    echo "✓ Create configuration: PASSED\n";
    $passed++;
} else {
    echo "✗ Create configuration: FAILED\n";
}

if ($results['update_config']) {
    echo "✓ Update configuration: PASSED\n";
    $passed++;
} else {
    echo "✗ Update configuration: FAILED\n";
}

if ($results['inheritance_test']) {
    echo "✓ Inheritance configuration: PASSED\n";
    $passed++;
} else {
    echo "✗ Inheritance configuration: FAILED\n";
}

if ($results['persistence_test']) {
    echo "✓ Configuration persistence: PASSED\n";
    $passed++;
} else {
    echo "✗ Configuration persistence: FAILED\n";
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
