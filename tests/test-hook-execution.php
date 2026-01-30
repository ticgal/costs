<?php
/**
 * Hook Execution Test Script
 * 
 * This script tests all plugin hooks on GLPI 11 including:
 * - POST_ITEM_FORM hook on ticket form
 * - PRE_ITEM_UPDATE hook on ticket update
 * - ITEM_ADD hooks for tickets and tasks
 * - ITEM_PURGE hook for task deletion
 * 
 * Requirements: 3.7
 * Task: 9.1
 */

// Simulate GLPI environment
define('GLPI_ROOT', '/tmp/glpi-test');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');
define('GLPI_VERSION', '11.0.0');

// Mock GLPI constants
define('READ', 1);
define('UPDATE', 2);

// Mock Planning constants
class Planning {
    const TODO = 1;
    const INFO = 2;
    const DONE = 3;
}

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
    
    public function delete($table, $where) {
        if (!isset($this->data[$table])) {
            return false;
        }
        
        foreach ($this->data[$table] as $id => $row) {
            $match = true;
            foreach ($where as $field => $value) {
                if (!isset($row[$field]) || $row[$field] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                unset($this->data[$table][$id]);
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
    
    public function delete($input, $force = 0) {
        global $DB;
        return $DB->delete(static::getTable(), ['id' => $input['id']]);
    }
    
    public function deleteByCriteria($criteria) {
        global $DB;
        return $DB->delete(static::getTable(), $criteria);
    }
    
    public function canUpdate() {
        return true;
    }
    
    public static function getType() {
        return static::class;
    }
    
    public static function getTypeStatic() {
        return static::class;
    }
}

class CommonDBRelation extends CommonDBTM {
    // Empty class for inheritance
}

class Ticket extends CommonDBTM {
    protected static $table = 'glpi_tickets';
    
    public static function getType() {
        return 'Ticket';
    }
    
    public static function getTypeName($nb = 0) {
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

class Entity extends CommonDBTM {
    protected static $table = 'glpi_entities';
}

class Session {
    public static function getCurrentInterface() {
        return 'central';
    }
    
    public static function haveRight($right, $value) {
        return true;
    }
    
    public static function haveRightsOr($right, $values) {
        return true;
    }
}

class Plugin {
    public static function registerClass($class, $options = []) {
        return true;
    }
}

// Mock translation function
function __($text, $domain = 'glpi') {
    return $text;
}

// Mock TemplateRenderer and other namespaced classes
if (!class_exists('Glpi\Application\View\TemplateRenderer')) {
    eval('
    namespace Glpi\Application\View {
        class TemplateRenderer {
            private static $instance = null;
            
            public static function getInstance() {
                if (self::$instance === null) {
                    self::$instance = new self();
                }
                return self::$instance;
            }
            
            public function display($template, $options = []) {
                echo "<!-- Template: $template rendered -->\n";
            }
        }
    }
    ');
}

if (!class_exists('Glpi\RichText\RichText')) {
    eval('
    namespace Glpi\RichText {
        class RichText {
            public static function getTextFromHtml($html) {
                return strip_tags($html);
            }
        }
    }
    ');
}

if (!class_exists('Glpi\Toolbox\Sanitizer')) {
    eval('
    namespace Glpi\Toolbox {
        class Sanitizer {
            public static function sanitize($text) {
                return $text;
            }
        }
    }
    ');
}

// Load plugin classes
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/entity.class.php';
require_once __DIR__ . '/../inc/entity_profile.class.php';
require_once __DIR__ . '/../inc/ticket.class.php';
require_once __DIR__ . '/../inc/task.class.php';

// Initialize global DB
global $DB;
$DB = DB::getInstance();

    // Test Results
    $results = [
        'post_item_form' => false,
        'pre_item_update_ticket' => false,
        'pre_item_update_task' => false,
        'item_add_ticket' => false,
        'item_add_task' => false,
        'item_purge_task' => false,
        'errors' => []
    ];

    echo "\n";
    echo "========================================\n";
    echo "Hook Execution Test\n";
    echo "========================================\n";
    echo "\n";

    // Setup: Create necessary data
    echo "Setup: Creating test data...\n";
    try {
        // Create entity in database first
        $DB->insert('glpi_entities', ['id' => 1, 'name' => 'Root Entity']);
        
        // Create entity configuration with all required fields
        $entity = new PluginCostsEntity();
        $entity_id = $entity->add([
            'entities_id' => 1,
            'fixed_cost' => 10.00,
            'time_cost' => 50.00,
            'cost_private' => 1,
            'auto_cost' => 1,
            'inheritance' => 0
        ]);
        
        // Verify entity was created
        if (!$entity_id) {
            throw new Exception("Failed to create entity configuration");
        }
        
        // Create config
        $config = new PluginCostsConfig();
        $config->add([
            'id' => 1,
            'credit' => 1,
            'taskdescription' => 1
        ]);
        
        // Create user
        $user = new User();
        $user->add([
            'id' => 1,
            'name' => 'test_user',
            'profiles_id' => 1
        ]);
        
        echo "  ✓ Created entity configuration\n";
        echo "  ✓ Created plugin config\n";
        echo "  ✓ Created test user\n";
    } catch (Exception $e) {
        echo "  ✗ Setup failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    echo "\n";

    // Test 1: POST_ITEM_FORM hook
    echo "Test 1: Testing POST_ITEM_FORM hook on ticket form...\n";
    try {
        // Create a ticket
        $ticket = new Ticket();
        $ticket->input = ['entities_id' => 1];
        $ticket->fields = ['id' => 0, 'entities_id' => 1];
        
        // Simulate hook call
        ob_start();
        PluginCostsTicket::postItemForm(['item' => $ticket]);
        $output = ob_get_clean();
        
        // Verify template was rendered
        if (strpos($output, 'Template:') === false) {
            throw new Exception("Template not rendered");
        }
        
        echo "  ✓ Hook executed without errors\n";
        echo "  ✓ Template rendered for new ticket\n";
        
        // Test with existing ticket
        $ticket->fields = ['id' => 1, 'entities_id' => 1];
        $DB->insert('glpi_tickets', $ticket->fields);
        
        // Create cost ticket
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add(['tickets_id' => 1, 'billable' => 1]);
        
        ob_start();
        PluginCostsTicket::postItemForm(['item' => $ticket]);
        $output = ob_get_clean();
        
        if (strpos($output, 'Template:') === false) {
            throw new Exception("Template not rendered for existing ticket");
        }
        
        echo "  ✓ Template rendered for existing ticket\n";
        
        $results['post_item_form'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "POST_ITEM_FORM hook test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 2: PRE_ITEM_UPDATE hook for Ticket
    echo "Test 2: Testing PRE_ITEM_UPDATE hook on ticket update...\n";
    try {
        // Create a ticket with cost entry
        $ticket = new Ticket();
        $ticket->fields = ['id' => 2, 'entities_id' => 1];
        $DB->insert('glpi_tickets', $ticket->fields);
        
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add(['tickets_id' => 2, 'billable' => 0]);
        
        // Simulate ticket update with billable change
        $ticket->input = ['cost_billable' => 1];
        PluginCostsTicket::ticketUpdate($ticket);
        
        // Verify cost ticket was updated
        $cost_ticket->getFromDBByTicket(2);
        
        if ($cost_ticket->fields['billable'] != 1) {
            throw new Exception("Cost ticket not updated");
        }
        
        echo "  ✓ Hook executed without errors\n";
        echo "  ✓ Billable status updated correctly\n";
        
        $results['pre_item_update_ticket'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "PRE_ITEM_UPDATE (Ticket) hook test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 3: PRE_ITEM_UPDATE hook for TicketTask
    echo "Test 3: Testing PRE_ITEM_UPDATE hook on task update...\n";
    try {
        // Create a billable ticket
        $ticket = new Ticket();
        $ticket->fields = ['id' => 3, 'entities_id' => 1];
        $DB->insert('glpi_tickets', $ticket->fields);
        
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add(['tickets_id' => 3, 'billable' => 1]);
        
        // Create a task
        $task = new TicketTask();
        $task->fields = [
            'id' => 1,
            'tickets_id' => 3,
            'users_id_tech' => 1,
            'actiontime' => 3600,
            'is_private' => 0,
            'content' => 'Test task content',
            'state' => Planning::TODO
        ];
        $DB->insert('glpi_tickettasks', $task->fields);
        
        // Simulate task update to DONE state
        $task->input = [
            'state' => Planning::DONE,
            'actiontime' => 7200
        ];
        
        PluginCostsTask::preTaskUpdate($task);
        
        // Verify cost was created
        $task_cost = new PluginCostsTask();
        $found = $task_cost->getFromDBByCrit(['tasks_id' => 1]);
        
        if (!$found) {
            throw new Exception("Task cost not created");
        }
        
        echo "  ✓ Hook executed without errors\n";
        echo "  ✓ Task cost created on state change to DONE\n";
        
        $results['pre_item_update_task'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "PRE_ITEM_UPDATE (Task) hook test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 4: ITEM_ADD hook for Ticket
    echo "Test 4: Testing ITEM_ADD hook on ticket creation...\n";
    try {
        // Create a new ticket
        $ticket = new Ticket();
        $ticket->input = [
            'entities_id' => 1,
            'name' => 'Test Ticket',
            'content' => 'Test content'
        ];
        $ticket->fields = ['id' => 4, 'entities_id' => 1];
        
        // Simulate ticket add hook
        PluginCostsTicket::ticketAdd($ticket);
        
        // Verify cost ticket was created
        $cost_ticket = new PluginCostsTicket();
        $found = $cost_ticket->getFromDBByTicket(4);
        
        if (!isset($cost_ticket->fields['billable'])) {
            throw new Exception("Cost ticket not created");
        }
        
        echo "  ✓ Hook executed without errors\n";
        echo "  ✓ Cost ticket created automatically\n";
        echo "  ✓ Billable status: " . ($cost_ticket->fields['billable'] ? 'Yes' : 'No') . "\n";
        
        $results['item_add_ticket'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "ITEM_ADD (Ticket) hook test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 5: ITEM_ADD hook for TicketTask
    echo "Test 5: Testing ITEM_ADD hook on task creation...\n";
    try {
        // Create a billable ticket
        $ticket = new Ticket();
        $ticket->fields = ['id' => 5, 'entities_id' => 1];
        $DB->insert('glpi_tickets', $ticket->fields);
        
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add(['tickets_id' => 5, 'billable' => 1]);
        
        // Create a task with DONE state
        $task = new TicketTask();
        $task->input = [
            'state' => Planning::DONE
        ];
        $task->fields = [
            'id' => 2,
            'tickets_id' => 5,
            'users_id_tech' => 1,
            'actiontime' => 3600,
            'is_private' => 0,
            'content' => 'Test task content',
            'state' => Planning::DONE
        ];
        
        // Simulate task add hook
        PluginCostsTask::taskAdd($task);
        
        // Verify cost was created
        $task_cost = new PluginCostsTask();
        $found = $task_cost->getFromDBByCrit(['tasks_id' => 2]);
        
        if (!$found) {
            throw new Exception("Task cost not created");
        }
        
        echo "  ✓ Hook executed without errors\n";
        echo "  ✓ Task cost created for DONE task\n";
        
        $results['item_add_task'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "ITEM_ADD (Task) hook test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 6: ITEM_PURGE hook for TicketTask
    echo "Test 6: Testing ITEM_PURGE hook on task deletion...\n";
    try {
        // Create a billable ticket
        $ticket = new Ticket();
        $ticket->fields = ['id' => 6, 'entities_id' => 1];
        $DB->insert('glpi_tickets', $ticket->fields);
        
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add(['tickets_id' => 6, 'billable' => 1]);
        
        // Create a task with cost
        $task = new TicketTask();
        $task->fields = [
            'id' => 3,
            'tickets_id' => 6,
            'users_id_tech' => 1,
            'actiontime' => 3600,
            'is_private' => 0,
            'content' => 'Test task content',
            'state' => Planning::DONE
        ];
        $DB->insert('glpi_tickettasks', $task->fields);
        
        // Create ticket cost
        $ticket_cost = new TicketCost();
        $cost_id = $ticket_cost->add([
            'id' => 1,
            'tickets_id' => 6,
            'name' => '3_1',
            'comment' => 'Test cost',
            'actiontime' => 3600,
            'cost_time' => 50.00,
            'cost_fixed' => 10.00,
            'entities_id' => 1
        ]);
        
        // Create task cost link
        $task_cost = new PluginCostsTask();
        $task_cost->add(['tasks_id' => 3, 'costs_id' => 1]);
        
        // Verify task cost exists
        $found = $task_cost->getFromDBByCrit(['tasks_id' => 3]);
        if (!$found) {
            throw new Exception("Task cost not created for test");
        }
        
        echo "  ✓ Test data created\n";
        
        // Simulate task purge hook
        PluginCostsTask::taskPurge($task);
        
        // Verify task cost was deleted
        $task_cost_check = new PluginCostsTask();
        $found = $task_cost_check->getFromDBByCrit(['tasks_id' => 3]);
        
        if ($found) {
            throw new Exception("Task cost not deleted");
        }
        
        echo "  ✓ Hook executed without errors\n";
        echo "  ✓ Task cost deleted correctly\n";
        
        $results['item_purge_task'] = true;
    } catch (Exception $e) {
        $results['errors'][] = "ITEM_PURGE (Task) hook test failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Test 7: Hook registration verification
    echo "Test 7: Verifying hook registration in setup.php...\n";
    try {
        $setup_content = file_get_contents(__DIR__ . '/../setup.php');
        
        // Check for Hooks namespace
        if (strpos($setup_content, 'use Glpi\Plugin\Hooks;') === false) {
            throw new Exception("Hooks namespace not imported");
        }
        echo "  ✓ Hooks namespace imported\n";
        
        // Check for CSRF_COMPLIANT
        if (strpos($setup_content, "Hooks::CSRF_COMPLIANT") === false) {
            throw new Exception("CSRF_COMPLIANT hook not registered");
        }
        echo "  ✓ CSRF_COMPLIANT hook registered\n";
        
        // Check for POST_ITEM_FORM
        if (strpos($setup_content, "Hooks::POST_ITEM_FORM") === false) {
            throw new Exception("POST_ITEM_FORM hook not registered");
        }
        echo "  ✓ POST_ITEM_FORM hook registered\n";
        
        // Check for PRE_ITEM_UPDATE
        if (strpos($setup_content, "Hooks::PRE_ITEM_UPDATE") === false) {
            throw new Exception("PRE_ITEM_UPDATE hook not registered");
        }
        echo "  ✓ PRE_ITEM_UPDATE hook registered\n";
        
        // Check for ITEM_ADD
        if (strpos($setup_content, "Hooks::ITEM_ADD") === false) {
            throw new Exception("ITEM_ADD hook not registered");
        }
        echo "  ✓ ITEM_ADD hook registered\n";
        
        // Check for ITEM_PURGE
        if (strpos($setup_content, "Hooks::ITEM_PURGE") === false) {
            throw new Exception("ITEM_PURGE hook not registered");
        }
        echo "  ✓ ITEM_PURGE hook registered\n";
        
    } catch (Exception $e) {
        $results['errors'][] = "Hook registration verification failed: " . $e->getMessage();
        echo "  ✗ " . $e->getMessage() . "\n";
    }
    echo "\n";

    // Summary
    echo "========================================\n";
    echo "Test Summary\n";
    echo "========================================\n";
    echo "\n";

    $passed = 0;
    $total = 6;

    if ($results['post_item_form']) {
        echo "✓ POST_ITEM_FORM hook: PASSED\n";
        $passed++;
    } else {
        echo "✗ POST_ITEM_FORM hook: FAILED\n";
    }

    if ($results['pre_item_update_ticket']) {
        echo "✓ PRE_ITEM_UPDATE (Ticket) hook: PASSED\n";
        $passed++;
    } else {
        echo "✗ PRE_ITEM_UPDATE (Ticket) hook: FAILED\n";
    }

    if ($results['pre_item_update_task']) {
        echo "✓ PRE_ITEM_UPDATE (Task) hook: PASSED\n";
        $passed++;
    } else {
        echo "✗ PRE_ITEM_UPDATE (Task) hook: FAILED\n";
    }

    if ($results['item_add_ticket']) {
        echo "✓ ITEM_ADD (Ticket) hook: PASSED\n";
        $passed++;
    } else {
        echo "✗ ITEM_ADD (Ticket) hook: FAILED\n";
    }

    if ($results['item_add_task']) {
        echo "✓ ITEM_ADD (Task) hook: PASSED\n";
        $passed++;
    } else {
        echo "✗ ITEM_ADD (Task) hook: FAILED\n";
    }

    if ($results['item_purge_task']) {
        echo "✓ ITEM_PURGE (Task) hook: PASSED\n";
        $passed++;
    } else {
        echo "✗ ITEM_PURGE (Task) hook: FAILED\n";
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

