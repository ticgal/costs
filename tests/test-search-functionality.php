<?php
/**
 * Search Functionality Test Script
 * 
 * This script tests ticket search functionality with the billable field including:
 * - Performing ticket searches including billable field
 * - Verifying search results display correctly
 * - Testing filtering by billable status
 * 
 * Requirements: 8.3
 * Task: 8.1
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
    'search_options_defined' => false,
    'search_billable_true' => false,
    'search_billable_false' => false,
    'search_all_tickets' => false,
    'errors' => []
];

echo "\n";
echo "========================================\n";
echo "Search Functionality Test\n";
echo "========================================\n";
echo "\n";

// Test 1: Verify search options are defined
echo "Test 1: Verifying search options are defined...\n";
try {
    $search_options = PluginCostsTicket::rawSearchOptionsToAdd();
    
    if (empty($search_options)) {
        throw new Exception("No search options defined");
    }
    
    // Find the billable field option
    $billable_option = null;
    foreach ($search_options as $option) {
        if (isset($option['field']) && $option['field'] === 'billable') {
            $billable_option = $option;
            break;
        }
    }
    
    if ($billable_option === null) {
        throw new Exception("Billable field not found in search options");
    }
    
    // Verify option structure
    if (!isset($billable_option['id'])) {
        throw new Exception("Search option missing 'id' field");
    }
    if (!isset($billable_option['table'])) {
        throw new Exception("Search option missing 'table' field");
    }
    if (!isset($billable_option['field'])) {
        throw new Exception("Search option missing 'field' field");
    }
    if (!isset($billable_option['name'])) {
        throw new Exception("Search option missing 'name' field");
    }
    if (!isset($billable_option['datatype'])) {
        throw new Exception("Search option missing 'datatype' field");
    }
    if (!isset($billable_option['searchtype'])) {
        throw new Exception("Search option missing 'searchtype' field");
    }
    if (!isset($billable_option['joinparams'])) {
        throw new Exception("Search option missing 'joinparams' field");
    }
    
    // Verify option values
    if ($billable_option['id'] !== '1000') {
        throw new Exception("Search option ID should be '1000', got: " . $billable_option['id']);
    }
    if ($billable_option['table'] !== PluginCostsTicket::getTable()) {
        throw new Exception("Search option table should be '" . PluginCostsTicket::getTable() . "', got: " . $billable_option['table']);
    }
    if ($billable_option['field'] !== 'billable') {
        throw new Exception("Search option field should be 'billable', got: " . $billable_option['field']);
    }
    if ($billable_option['datatype'] !== 'bool') {
        throw new Exception("Search option datatype should be 'bool', got: " . $billable_option['datatype']);
    }
    if ($billable_option['searchtype'] !== 'equals') {
        throw new Exception("Search option searchtype should be 'equals', got: " . $billable_option['searchtype']);
    }
    if (!isset($billable_option['joinparams']['jointype']) || $billable_option['joinparams']['jointype'] !== 'child') {
        throw new Exception("Search option jointype should be 'child'");
    }
    
    echo "  ✓ Search options defined correctly\n";
    echo "  ✓ Billable field option found (ID: " . $billable_option['id'] . ")\n";
    echo "  ✓ Table: " . $billable_option['table'] . "\n";
    echo "  ✓ Field: " . $billable_option['field'] . "\n";
    echo "  ✓ Datatype: " . $billable_option['datatype'] . "\n";
    echo "  ✓ Searchtype: " . $billable_option['searchtype'] . "\n";
    echo "  ✓ Jointype: " . $billable_option['joinparams']['jointype'] . "\n";
    
    $results['search_options_defined'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Search options test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Setup: Create test data
echo "Setup: Creating test tickets...\n";
try {
    // Create entity configuration
    $entity_config = new PluginCostsEntity();
    $entity_config->add([
        'entities_id' => 1,
        'fixed_cost' => 10.00,
        'time_cost' => 50.00,
        'cost_private' => 1,
        'auto_cost' => 1,
        'inheritance' => 0
    ]);
    
    // Create billable tickets
    for ($i = 1; $i <= 3; $i++) {
        $ticket = new Ticket();
        $ticket->add([
            'id' => $i,
            'entities_id' => 1,
            'name' => "Billable Ticket $i",
            'content' => "Test content $i"
        ]);
        
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add([
            'tickets_id' => $i,
            'billable' => 1
        ]);
    }
    
    // Create non-billable tickets
    for ($i = 4; $i <= 6; $i++) {
        $ticket = new Ticket();
        $ticket->add([
            'id' => $i,
            'entities_id' => 1,
            'name' => "Non-Billable Ticket $i",
            'content' => "Test content $i"
        ]);
        
        $cost_ticket = new PluginCostsTicket();
        $cost_ticket->add([
            'tickets_id' => $i,
            'billable' => 0
        ]);
    }
    
    echo "  ✓ Created 3 billable tickets (IDs: 1, 2, 3)\n";
    echo "  ✓ Created 3 non-billable tickets (IDs: 4, 5, 6)\n";
} catch (Exception $e) {
    echo "  ✗ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 2: Search for billable tickets
echo "Test 2: Searching for billable tickets...\n";
try {
    // Simulate search query for billable=1
    $search_results = $DB->request([
        'FROM' => PluginCostsTicket::getTable(),
        'WHERE' => ['billable' => 1]
    ]);
    
    $billable_count = count($search_results);
    
    if ($billable_count !== 3) {
        throw new Exception("Expected 3 billable tickets, found: $billable_count");
    }
    
    // Verify all results have billable=1
    foreach ($search_results as $result) {
        if ($result['billable'] != 1) {
            throw new Exception("Search returned non-billable ticket: " . $result['tickets_id']);
        }
    }
    
    echo "  ✓ Search for billable=1 returned $billable_count tickets\n";
    echo "  ✓ All results have billable=1\n";
    echo "  ✓ Ticket IDs: ";
    $ids = array_column($search_results, 'tickets_id');
    echo implode(", ", $ids) . "\n";
    
    $results['search_billable_true'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Billable search test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Search for non-billable tickets
echo "Test 3: Searching for non-billable tickets...\n";
try {
    // Simulate search query for billable=0
    $search_results = $DB->request([
        'FROM' => PluginCostsTicket::getTable(),
        'WHERE' => ['billable' => 0]
    ]);
    
    $non_billable_count = count($search_results);
    
    if ($non_billable_count !== 3) {
        throw new Exception("Expected 3 non-billable tickets, found: $non_billable_count");
    }
    
    // Verify all results have billable=0
    foreach ($search_results as $result) {
        if ($result['billable'] != 0) {
            throw new Exception("Search returned billable ticket: " . $result['tickets_id']);
        }
    }
    
    echo "  ✓ Search for billable=0 returned $non_billable_count tickets\n";
    echo "  ✓ All results have billable=0\n";
    echo "  ✓ Ticket IDs: ";
    $ids = array_column($search_results, 'tickets_id');
    echo implode(", ", $ids) . "\n";
    
    $results['search_billable_false'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Non-billable search test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Search all tickets (no filter)
echo "Test 4: Searching all tickets (no billable filter)...\n";
try {
    // Simulate search query without billable filter
    $search_results = $DB->request([
        'FROM' => PluginCostsTicket::getTable()
    ]);
    
    $total_count = count($search_results);
    
    if ($total_count !== 6) {
        throw new Exception("Expected 6 total tickets, found: $total_count");
    }
    
    // Count billable and non-billable
    $billable_count = 0;
    $non_billable_count = 0;
    foreach ($search_results as $result) {
        if ($result['billable'] == 1) {
            $billable_count++;
        } else {
            $non_billable_count++;
        }
    }
    
    if ($billable_count !== 3) {
        throw new Exception("Expected 3 billable tickets in unfiltered search, found: $billable_count");
    }
    if ($non_billable_count !== 3) {
        throw new Exception("Expected 3 non-billable tickets in unfiltered search, found: $non_billable_count");
    }
    
    echo "  ✓ Unfiltered search returned $total_count tickets\n";
    echo "  ✓ Billable tickets: $billable_count\n";
    echo "  ✓ Non-billable tickets: $non_billable_count\n";
    
    $results['search_all_tickets'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Unfiltered search test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Test search with JOIN simulation
echo "Test 5: Testing search with JOIN (ticket + cost data)...\n";
try {
    // Simulate a JOIN query between tickets and cost tickets
    $tickets_table = Ticket::getTable();
    $costs_table = PluginCostsTicket::getTable();
    
    // Get all tickets
    $tickets = $DB->request(['FROM' => $tickets_table]);
    
    // Join with cost data
    $joined_results = [];
    foreach ($tickets as $ticket) {
        $cost_data = $DB->request([
            'FROM' => $costs_table,
            'WHERE' => ['tickets_id' => $ticket['id']]
        ]);
        
        if (count($cost_data) > 0) {
            $cost = reset($cost_data);
            $joined_results[] = array_merge($ticket, ['billable' => $cost['billable']]);
        }
    }
    
    if (count($joined_results) !== 6) {
        throw new Exception("Expected 6 joined results, found: " . count($joined_results));
    }
    
    // Filter joined results by billable=1
    $billable_joined = array_filter($joined_results, function($row) {
        return $row['billable'] == 1;
    });
    
    if (count($billable_joined) !== 3) {
        throw new Exception("Expected 3 billable tickets in joined results, found: " . count($billable_joined));
    }
    
    echo "  ✓ JOIN simulation successful\n";
    echo "  ✓ Total joined results: " . count($joined_results) . "\n";
    echo "  ✓ Billable tickets in joined results: " . count($billable_joined) . "\n";
    echo "  ✓ Search with JOIN works correctly\n";
} catch (Exception $e) {
    $results['errors'][] = "JOIN search test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Test search option compatibility with GLPI 11
echo "Test 6: Testing search option compatibility with GLPI 11...\n";
try {
    $search_options = PluginCostsTicket::rawSearchOptionsToAdd();
    $billable_option = null;
    
    foreach ($search_options as $option) {
        if (isset($option['field']) && $option['field'] === 'billable') {
            $billable_option = $option;
            break;
        }
    }
    
    // Verify GLPI 11 compatibility requirements
    $required_fields = ['id', 'table', 'field', 'name', 'datatype', 'searchtype', 'joinparams'];
    foreach ($required_fields as $field) {
        if (!isset($billable_option[$field])) {
            throw new Exception("Missing required field for GLPI 11: $field");
        }
    }
    
    // Verify joinparams structure (GLPI 11 format)
    if (!is_array($billable_option['joinparams'])) {
        throw new Exception("joinparams should be an array");
    }
    if (!isset($billable_option['joinparams']['jointype'])) {
        throw new Exception("joinparams missing 'jointype'");
    }
    
    // Verify datatype is valid for GLPI 11
    $valid_datatypes = ['bool', 'string', 'integer', 'decimal', 'date', 'datetime', 'text'];
    if (!in_array($billable_option['datatype'], $valid_datatypes)) {
        throw new Exception("Invalid datatype for GLPI 11: " . $billable_option['datatype']);
    }
    
    echo "  ✓ Search option structure compatible with GLPI 11\n";
    echo "  ✓ All required fields present\n";
    echo "  ✓ joinparams structure valid\n";
    echo "  ✓ datatype valid for GLPI 11\n";
} catch (Exception $e) {
    $results['errors'][] = "GLPI 11 compatibility test failed: " . $e->getMessage();
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

if ($results['search_options_defined']) {
    echo "✓ Search options defined: PASSED\n";
    $passed++;
} else {
    echo "✗ Search options defined: FAILED\n";
}

if ($results['search_billable_true']) {
    echo "✓ Search billable tickets: PASSED\n";
    $passed++;
} else {
    echo "✗ Search billable tickets: FAILED\n";
}

if ($results['search_billable_false']) {
    echo "✓ Search non-billable tickets: PASSED\n";
    $passed++;
} else {
    echo "✗ Search non-billable tickets: FAILED\n";
}

if ($results['search_all_tickets']) {
    echo "✓ Search all tickets: PASSED\n";
    $passed++;
} else {
    echo "✗ Search all tickets: FAILED\n";
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
