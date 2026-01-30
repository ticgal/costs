<?php
/**
 * Ticket Cost Generation Test Script
 * 
 * This script tests ticket cost generation functionality including:
 * - Creating tickets in billable entities
 * - Verifying cost entries are generated
 * - Testing billable vs non-billable tickets
 * 
 * Requirements: 12.3
 * Task: 5.3
 */

// Simulate GLPI environment
define('GLPI_ROOT', '/tmp/glpi-test');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');

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
    public $input = [];
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
        $this->input = $input;
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
        $this->input = $input;
        $id = $input['id'];
        unset($input['id']);
        return $DB->update(static::getTable(), $input, ['id' => $id]);
    }
}

class Ticket extends CommonDBTM {
    protected static $table = 'glpi_tickets';
    
    public static function getType() {
        return 'Ticket';
    }
}

class Session {
    public static function getCurrentInterface() {
        return 'central';
    }
}

// Load plugin classes
require_once __DIR__ . '/../inc/entity.class.php';
require_once __DIR__ . '/../inc/ticket.class.php';

// Initialize global DB
global $DB;
$DB = DB::getInstance();

// Test Results
$results = [
    'billable_ticket' => false,
    'non_billable_ticket' => false,
    'auto_billable' => false,
    'manual_billable' => false,
    'errors' => []
];

echo "\n";
echo "========================================\n";
echo "Ticket Cost Generation Test\n";
echo "========================================\n";
echo "\n";

// Setup: Create entity configurations
echo "Setup: Creating entity configurations...\n";
try {
    // Create entity with auto_cost enabled
    $auto_entity = new PluginCostsEntity();
    $auto_entity->add([
        'entities_id' => 1,
        'fixed_cost' => 10.00,
        'time_cost' => 50.00,
        'cost_private' => 1,
        'auto_cost' => 1,  // Auto billable
        'inheritance' => 0
    ]);
    
    // Create entity with auto_cost disabled
    $manual_entity = new PluginCostsEntity();
    $manual_entity->add([
        'entities_id' => 2,
        'fixed_cost' => 15.00,
        'time_cost' => 75.00,
        'cost_private' => 1,
        'auto_cost' => 0,  // Manual billable
        'inheritance' => 0
    ]);
    
    echo "  ✓ Created auto-billable entity (ID: 1)\n";
    echo "  ✓ Created manual-billable entity (ID: 2)\n";
} catch (Exception $e) {
    echo "  ✗ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 1: Create billable ticket in auto-billable entity
echo "Test 1: Creating billable ticket in auto-billable entity...\n";
try {
    $ticket = new Ticket();
    $ticket->input = [
        'entities_id' => 1,
        'name' => 'Test Ticket 1',
        'content' => 'Test content'
    ];
    $ticket->fields = ['id' => 1, 'entities_id' => 1];
    
    // Simulate ticket creation
    PluginCostsTicket::ticketAdd($ticket);
    
    // Verify cost ticket was created
    $cost_ticket = new PluginCostsTicket();
    $found = $cost_ticket->getFromDBByTicket(1);
    
    if (!isset($cost_ticket->fields['billable'])) {
        throw new Exception("Cost ticket not created");
    }
    
    if ($cost_ticket->fields['billable'] != 1) {
        throw new Exception("Ticket should be billable (auto_cost=1)");
    }
    
    echo "  ✓ Ticket created in auto-billable entity\n";
    echo "  ✓ Cost entry created with billable=1\n";
    echo "  ✓ Billable status: " . ($cost_ticket->fields['billable'] ? 'Yes' : 'No') . "\n";
    
    $results['billable_ticket'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Billable ticket test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Create non-billable ticket in manual entity
echo "Test 2: Creating non-billable ticket in manual entity...\n";
try {
    $ticket = new Ticket();
    $ticket->input = [
        'entities_id' => 2,
        'name' => 'Test Ticket 2',
        'content' => 'Test content'
    ];
    $ticket->fields = ['id' => 2, 'entities_id' => 2];
    
    // Simulate ticket creation
    PluginCostsTicket::ticketAdd($ticket);
    
    // Verify cost ticket was created
    $cost_ticket = new PluginCostsTicket();
    $found = $cost_ticket->getFromDBByTicket(2);
    
    if (!isset($cost_ticket->fields['billable'])) {
        throw new Exception("Cost ticket not created");
    }
    
    if ($cost_ticket->fields['billable'] != 0) {
        throw new Exception("Ticket should not be billable (auto_cost=0)");
    }
    
    echo "  ✓ Ticket created in manual entity\n";
    echo "  ✓ Cost entry created with billable=0\n";
    echo "  ✓ Billable status: " . ($cost_ticket->fields['billable'] ? 'Yes' : 'No') . "\n";
    
    $results['non_billable_ticket'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Non-billable ticket test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Test auto-billable behavior
echo "Test 3: Testing auto-billable behavior...\n";
try {
    // Create ticket in auto-billable entity
    $ticket = new Ticket();
    $ticket->input = [
        'entities_id' => 1,
        'name' => 'Auto Billable Test',
        'content' => 'Test content'
    ];
    $ticket->fields = ['id' => 3, 'entities_id' => 1];
    
    PluginCostsTicket::ticketAdd($ticket);
    
    // Check if ticket is billable
    $is_billable = PluginCostsTicket::isBillable(3);
    
    if (!$is_billable) {
        throw new Exception("Ticket should be auto-billable");
    }
    
    echo "  ✓ Ticket automatically marked as billable\n";
    echo "  ✓ isBillable() returns true\n";
    
    $results['auto_billable'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Auto-billable test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Test manual billable override
echo "Test 4: Testing manual billable override...\n";
try {
    // Create ticket with explicit billable flag
    $ticket = new Ticket();
    $ticket->input = [
        'entities_id' => 2,
        'name' => 'Manual Billable Test',
        'content' => 'Test content',
        'cost_billable' => 1  // Explicitly set to billable
    ];
    $ticket->fields = ['id' => 4, 'entities_id' => 2];
    
    PluginCostsTicket::ticketAdd($ticket);
    
    // Verify ticket is billable despite entity auto_cost=0
    $cost_ticket = new PluginCostsTicket();
    $cost_ticket->getFromDBByTicket(4);
    
    if ($cost_ticket->fields['billable'] != 1) {
        throw new Exception("Ticket should be billable (manual override)");
    }
    
    echo "  ✓ Ticket manually marked as billable\n";
    echo "  ✓ Manual override works correctly\n";
    
    $results['manual_billable'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Manual billable test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Test ticket update
echo "Test 5: Testing ticket billable status update...\n";
try {
    // Create a non-billable ticket
    $ticket = new Ticket();
    $ticket->input = [
        'entities_id' => 2,
        'name' => 'Update Test',
        'content' => 'Test content'
    ];
    $ticket->fields = ['id' => 5, 'entities_id' => 2];
    
    PluginCostsTicket::ticketAdd($ticket);
    
    // Verify it's not billable
    $cost_ticket = new PluginCostsTicket();
    $cost_ticket->getFromDBByTicket(5);
    
    if ($cost_ticket->fields['billable'] != 0) {
        throw new Exception("Initial ticket should not be billable");
    }
    
    echo "  ✓ Created non-billable ticket\n";
    
    // Update ticket to be billable
    $ticket->input = ['cost_billable' => 1];
    $ticket->fields = ['id' => 5];
    PluginCostsTicket::ticketUpdate($ticket);
    
    // Verify it's now billable
    $cost_ticket->getFromDBByTicket(5);
    
    if ($cost_ticket->fields['billable'] != 1) {
        throw new Exception("Updated ticket should be billable");
    }
    
    echo "  ✓ Updated ticket to billable\n";
    echo "  ✓ Billable status updated correctly\n";
} catch (Exception $e) {
    $results['errors'][] = "Ticket update test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Test getFromDBByTicket with non-existing ticket
echo "Test 6: Testing getFromDBByTicket with non-existing ticket...\n";
try {
    // Create a ticket but don't add cost entry
    $ticket = new Ticket();
    $ticket->fields = ['id' => 99, 'entities_id' => 1];
    $DB->insert('glpi_tickets', $ticket->fields);
    
    // Try to get cost ticket (should create default)
    $cost_ticket = new PluginCostsTicket();
    $found = $cost_ticket->getFromDBByTicket(99);
    
    if ($found) {
        throw new Exception("Should return false for non-existing cost ticket");
    }
    
    if (!isset($cost_ticket->fields['billable'])) {
        throw new Exception("Should create default cost ticket");
    }
    
    echo "  ✓ Non-existing cost ticket handled correctly\n";
    echo "  ✓ Default cost ticket created\n";
    echo "  ✓ Default billable status: " . ($cost_ticket->fields['billable'] ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    $results['errors'][] = "Non-existing ticket test failed: " . $e->getMessage();
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

if ($results['billable_ticket']) {
    echo "✓ Billable ticket creation: PASSED\n";
    $passed++;
} else {
    echo "✗ Billable ticket creation: FAILED\n";
}

if ($results['non_billable_ticket']) {
    echo "✓ Non-billable ticket creation: PASSED\n";
    $passed++;
} else {
    echo "✗ Non-billable ticket creation: FAILED\n";
}

if ($results['auto_billable']) {
    echo "✓ Auto-billable behavior: PASSED\n";
    $passed++;
} else {
    echo "✗ Auto-billable behavior: FAILED\n";
}

if ($results['manual_billable']) {
    echo "✓ Manual billable override: PASSED\n";
    $passed++;
} else {
    echo "✗ Manual billable override: FAILED\n";
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
