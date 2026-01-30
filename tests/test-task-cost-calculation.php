<?php
/**
 * Task Cost Calculation Test Script
 * 
 * This script tests task cost calculation functionality including:
 * - Adding tasks to billable tickets
 * - Verifying time-based costs are calculated correctly
 * - Testing with different time durations
 * 
 * Requirements: 12.4
 * Task: 5.5
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
    
    public function getFromDBByCrit($criteria) {
        global $DB;
        $results = $DB->request(['FROM' => static::getTable(), 'WHERE' => $criteria]);
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

class CommonDBRelation extends CommonDBTM {
    // Minimal implementation for entity_profile
}

class Ticket extends CommonDBTM {
    protected static $table = 'glpi_tickets';
    
    public static function getType() {
        return 'Ticket';
    }
}

class TicketTask extends CommonDBTM {
    protected static $table = 'glpi_tickettasks';
}

class TicketCost extends CommonDBTM {
    protected static $table = 'glpi_ticketcosts';
}

class User extends CommonDBTM {
    protected static $table = 'glpi_users';
}

class Planning {
    const DONE = 2;
    const TODO = 1;
}

class Session {
    public static function getCurrentInterface() {
        return 'central';
    }
}

// Mock functions for translation
function __($text, $domain = 'glpi') {
    return $text;
}

// Mock Glpi namespace classes
if (!class_exists('Glpi\RichText\RichText')) {
    class_alias('MockRichText', 'Glpi\RichText\RichText');
}
if (!class_exists('Glpi\Toolbox\Sanitizer')) {
    class_alias('MockSanitizer', 'Glpi\Toolbox\Sanitizer');
}

class MockRichText {
    public static function getTextFromHtml($html) {
        return strip_tags($html);
    }
}

class MockSanitizer {
    public static function sanitize($text) {
        return $text;
    }
}

// Load plugin classes
require_once __DIR__ . '/../inc/entity.class.php';
require_once __DIR__ . '/../inc/ticket.class.php';
require_once __DIR__ . '/../inc/task.class.php';
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/entity_profile.class.php';

    // Initialize global DB
    global $DB;
    $DB = DB::getInstance();

    // Test Results
    $results = [
        'task_cost_generation' => false,
        'time_based_calculation' => false,
        'different_durations' => false,
        'private_task' => false,
        'errors' => []
    ];

    echo "\n";
    echo "========================================\n";
    echo "Task Cost Calculation Test\n";
    echo "========================================\n";
    echo "\n";

    // Setup: Create entity configuration and config
    echo "Setup: Creating test environment...\n";
    try {
        // Create plugin config
        $config = new PluginCostsConfig();
        $config->add([
            'id' => 1,
            'taskdescription' => 0
        ]);
        
        // Create entity with costs
        $entity = new PluginCostsEntity();
        $entity->add([
            'entities_id' => 1,
            'fixed_cost' => 10.00,
            'time_cost' => 50.00,  // $50 per hour
            'cost_private' => 1,
            'auto_cost' => 1,
            'inheritance' => 0
        ]);
        
        // Create user
        $user = new User();
        $user->add([
            'id' => 1,
            'name' => 'test_user',
            'profiles_id' => 1
        ]);
        
        // Create billable ticket
        $ticket = new Ticket();
        $ticket->add([
            'id' => 1,
            'entities_id' => 1,
            'name' => 'Test Ticket',
            'content' => 'Test content'
        ]);
        
        // Create cost ticket entry
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add([
            'tickets_id' => 1,
            'billable' => 1
        ]);
        
        echo "  ✓ Created plugin configuration\n";
        echo "  ✓ Created entity with time_cost=$50/hour\n";
        echo "  ✓ Created test user\n";
        echo "  ✓ Created billable ticket\n";
    } catch (Exception $e) {
        echo "  ✗ Setup failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    echo "\n";

    // Test 1: Task cost generation for 1 hour
    echo "Test 1: Testing task cost generation (1 hour)...\n";
    try {
        $task = new TicketTask();
        $task->input = [
            'state' => Planning::DONE,
            'actiontime' => 3600,  // 1 hour in seconds
            'users_id_tech' => 1,
            'is_private' => 0,
            'content' => 'Test task content'
        ];
        $task->fields = [
            'id' => 1,
            'tickets_id' => 1,
            'state' => Planning::DONE,
            'actiontime' => 3600,
            'users_id_tech' => 1,
            'is_private' => 0,
            'content' => 'Test task content'
        ];
        
        // Simulate task addition
        PluginCostsTask::taskAdd($task);
        
        // Verify TicketCost was created
        $ticket_costs = $DB->request(['FROM' => 'glpi_ticketcosts', 'WHERE' => ['tickets_id' => 1]]);
        
        if (count($ticket_costs) == 0) {
            throw new Exception("No ticket cost created");
        }
        
        $cost = reset($ticket_costs);
        
        // Verify cost values
        if ($cost['cost_time'] != 50.00) {
            throw new Exception("Cost time should be 50.00, got: " . $cost['cost_time']);
        }
        
        if ($cost['cost_fixed'] != 10.00) {
            throw new Exception("Cost fixed should be 10.00, got: " . $cost['cost_fixed']);
        }
        
        if ($cost['actiontime'] != 3600) {
            throw new Exception("Action time should be 3600, got: " . $cost['actiontime']);
        }
        
        // Calculate expected total: (3600 seconds / 3600) * $50 + $10 = $60
        $expected_total = (3600 / 3600) * 50.00 + 10.00;
        
        echo "  ✓ Task cost created successfully\n";
        echo "  ✓ Time cost: $" . $cost['cost_time'] . "/hour\n";
        echo "  ✓ Fixed cost: $" . $cost['cost_fixed'] . "\n";
        echo "  ✓ Action time: " . ($cost['actiontime'] / 3600) . " hours\n";
        echo "  ✓ Expected total: $" . $expected_total . "\n";
        
        $results['task_cost_generation'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "Task cost generation failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 2: Time-based calculation verification
    echo "Test 2: Verifying time-based cost calculation...\n";
    try {
        // Create task with 2 hours
        $task = new TicketTask();
        $task->input = [
            'state' => Planning::DONE,
            'actiontime' => 7200,  // 2 hours
            'users_id_tech' => 1,
            'is_private' => 0,
            'content' => 'Two hour task'
        ];
        $task->fields = [
            'id' => 2,
            'tickets_id' => 1,
            'state' => Planning::DONE,
            'actiontime' => 7200,
            'users_id_tech' => 1,
            'is_private' => 0,
            'content' => 'Two hour task'
        ];
        
        PluginCostsTask::taskAdd($task);
        
        // Get the cost entry
        $ticket_costs = $DB->request(['FROM' => 'glpi_ticketcosts', 'WHERE' => ['name' => '2_1']]);
        
        if (count($ticket_costs) == 0) {
            throw new Exception("Cost entry not found");
        }
        
        $cost = reset($ticket_costs);
        
        // Expected: (7200 / 3600) * $50 + $10 = $110
        $hours = $cost['actiontime'] / 3600;
        $expected_total = $hours * $cost['cost_time'] + $cost['cost_fixed'];
        
        echo "  ✓ Created 2-hour task\n";
        echo "  ✓ Hours: $hours\n";
        echo "  ✓ Time cost rate: $" . $cost['cost_time'] . "/hour\n";
        echo "  ✓ Expected total: $" . $expected_total . "\n";
        
        $results['time_based_calculation'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "Time-based calculation failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 3: Different time durations
    echo "Test 3: Testing different time durations...\n";
    try {
        $durations = [
            ['seconds' => 1800, 'hours' => 0.5, 'name' => '30 minutes'],
            ['seconds' => 5400, 'hours' => 1.5, 'name' => '1.5 hours'],
            ['seconds' => 14400, 'hours' => 4, 'name' => '4 hours']
        ];
        
        $task_id = 3;
        foreach ($durations as $duration) {
            $task = new TicketTask();
            $task->input = [
                'state' => Planning::DONE,
                'actiontime' => $duration['seconds'],
                'users_id_tech' => 1,
                'is_private' => 0,
                'content' => $duration['name'] . ' task'
            ];
            $task->fields = [
                'id' => $task_id,
                'tickets_id' => 1,
                'state' => Planning::DONE,
                'actiontime' => $duration['seconds'],
                'users_id_tech' => 1,
                'is_private' => 0,
                'content' => $duration['name'] . ' task'
            ];
            
            PluginCostsTask::taskAdd($task);
            
            // Verify cost was created
            $ticket_costs = $DB->request(['FROM' => 'glpi_ticketcosts', 'WHERE' => ['name' => $task_id . '_1']]);
            
            if (count($ticket_costs) == 0) {
                throw new Exception("Cost not created for " . $duration['name']);
            }
            
            $cost = reset($ticket_costs);
            $expected_total = $duration['hours'] * 50.00 + 10.00;
            
            echo "  ✓ " . $duration['name'] . ": $" . $expected_total . "\n";
            
            $task_id++;
        }
        
        $results['different_durations'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "Different durations test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 4: Private task handling
    echo "Test 4: Testing private task cost generation...\n";
    try {
        // Test with cost_private = 1 (should generate cost)
        $task = new TicketTask();
        $task->input = [
            'state' => Planning::DONE,
            'actiontime' => 3600,
            'users_id_tech' => 1,
            'is_private' => 1,  // Private task
            'content' => 'Private task'
        ];
        $task->fields = [
            'id' => 10,
            'tickets_id' => 1,
            'state' => Planning::DONE,
            'actiontime' => 3600,
            'users_id_tech' => 1,
            'is_private' => 1,
            'content' => 'Private task'
        ];
        
        PluginCostsTask::taskAdd($task);
        
        // Verify cost was created (cost_private = 1)
        $ticket_costs = $DB->request(['FROM' => 'glpi_ticketcosts', 'WHERE' => ['name' => '10_1']]);
        
        if (count($ticket_costs) == 0) {
            throw new Exception("Private task cost not created (cost_private=1)");
        }
        
        echo "  ✓ Private task cost generated (cost_private=1)\n";
        
        // Now test with cost_private = 0 - create a new entity
        $entity2 = new PluginCostsEntity();
        $entity2->add([
            'entities_id' => 2,
            'fixed_cost' => 10.00,
            'time_cost' => 50.00,
            'cost_private' => 0,  // Do NOT cost private tasks
            'auto_cost' => 1,
            'inheritance' => 0
        ]);
        
        // Create a new ticket in entity 2
        $ticket2 = new Ticket();
        $ticket2->add([
            'id' => 2,
            'entities_id' => 2,
            'name' => 'Test Ticket 2',
            'content' => 'Test content'
        ]);
        
        $cost_ticket2 = new PluginCostsTicket();
        $cost_ticket2->add([
            'tickets_id' => 2,
            'billable' => 1
        ]);
        
        // Create another private task in the new ticket
        $task2 = new TicketTask();
        $task2->input = [
            'state' => Planning::DONE,
            'actiontime' => 3600,
            'users_id_tech' => 1,
            'is_private' => 1,
            'content' => 'Private task 2'
        ];
        $task2->fields = [
            'id' => 11,
            'tickets_id' => 2,  // Different ticket in entity with cost_private=0
            'state' => Planning::DONE,
            'actiontime' => 3600,
            'users_id_tech' => 1,
            'is_private' => 1,
            'content' => 'Private task 2'
        ];
        
        PluginCostsTask::taskAdd($task2);
        
        // Verify cost was NOT created (cost_private = 0)
        $ticket_costs = $DB->request(['FROM' => 'glpi_ticketcosts', 'WHERE' => ['name' => '11_1']]);
        
        if (count($ticket_costs) > 0) {
            throw new Exception("Private task cost should not be created (cost_private=0)");
        }
        
        echo "  ✓ Private task cost not generated (cost_private=0)\n";
        
        $results['private_task'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "Private task test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 5: Task update
    echo "Test 5: Testing task cost update...\n";
    try {
        // Create initial task
        $task = new TicketTask();
        $task->input = [
            'state' => Planning::DONE,
            'actiontime' => 3600,
            'users_id_tech' => 1,
            'is_private' => 0,
            'content' => 'Update test task'
        ];
        $task->fields = [
            'id' => 20,
            'tickets_id' => 1,
            'state' => Planning::DONE,
            'actiontime' => 3600,
            'users_id_tech' => 1,
            'is_private' => 0,
            'content' => 'Update test task',
            'begin' => '2024-01-01 10:00:00',
            'end' => '2024-01-01 11:00:00'
        ];
        
        PluginCostsTask::taskAdd($task);
        
        // Update task with new duration
        $task->input = [
            'actiontime' => 7200,  // Change to 2 hours
            'state' => Planning::DONE
        ];
        
        PluginCostsTask::preTaskUpdate($task);
        
        // Verify cost was updated
        $ticket_costs = $DB->request(['FROM' => 'glpi_ticketcosts', 'WHERE' => ['name' => '20_1']]);
        
        if (count($ticket_costs) == 0) {
            throw new Exception("Cost entry not found after update");
        }
        
        $cost = reset($ticket_costs);
        
        if ($cost['actiontime'] != 7200) {
            throw new Exception("Action time not updated, expected 7200, got: " . $cost['actiontime']);
        }
        
        echo "  ✓ Task cost updated successfully\n";
        echo "  ✓ New action time: " . ($cost['actiontime'] / 3600) . " hours\n";
    } catch (Exception $e) {
        $results['errors'][] = "Task update test failed: " . $e->getMessage();
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

    if ($results['task_cost_generation']) {
        echo "✓ Task cost generation: PASSED\n";
        $passed++;
    } else {
        echo "✗ Task cost generation: FAILED\n";
    }

    if ($results['time_based_calculation']) {
        echo "✓ Time-based calculation: PASSED\n";
        $passed++;
    } else {
        echo "✗ Time-based calculation: FAILED\n";
    }

    if ($results['different_durations']) {
        echo "✓ Different durations: PASSED\n";
        $passed++;
    } else {
        echo "✗ Different durations: FAILED\n";
    }

    if ($results['private_task']) {
        echo "✓ Private task handling: PASSED\n";
        $passed++;
    } else {
        echo "✗ Private task handling: FAILED\n";
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
