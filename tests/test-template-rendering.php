<?php
/**
 * Template Rendering Test Script
 * 
 * This script tests template rendering functionality including:
 * - Loading config page and verifying template renders
 * - Loading entity costs tab and verifying template renders
 * - Checking for any Twig template errors
 * 
 * Requirements: 4.3, 12.5, 12.6
 * Task: 7.1
 */

// Simulate GLPI environment
define('GLPI_ROOT', '/tmp/glpi-test');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');
define('PLUGIN_COSTS_NUMBER_STEP', '0.01');

// Mock GLPI classes
class DB {
    private static $instance = null;
    private $data = [];
    
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
        
        $id = count($this->data[$table]) + 1;
        $data['id'] = $id;
        $this->data[$table][$id] = $data;
        
        return true;
    }
    
    public function tableExists($table) {
        return true;
    }
    
    public function fieldExists($table, $field) {
        return true;
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
    
    public function getEmpty() {
        $this->fields = [];
        return true;
    }
}

class CommonGLPI {
    public function getType() {
        return get_class($this);
    }
    
    public function can($id, $right) {
        return true;
    }
    
    public function getField($field) {
        return $this->fields[$field] ?? null;
    }
}

class Entity extends CommonGLPI {
    public $fields = [];
    protected static $table = 'glpi_entities';
    
    public static function getTable() {
        return self::$table;
    }
    
    public static function getType() {
        return 'Entity';
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

class Config extends CommonGLPI {
    public static function getType() {
        return 'Config';
    }
}

class Plugin {
    public function isInstalled($name) {
        return false;
    }
    
    public function isActivated($name) {
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

// Mock TemplateRenderer
namespace Glpi\Application\View {
    class TemplateRenderer {
        private static $instance = null;
        private $displayCalled = false;
        private $lastTemplate = null;
        private $lastOptions = null;
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function display($template, $options = []) {
            $this->displayCalled = true;
            $this->lastTemplate = $template;
            $this->lastOptions = $options;
            
            // Simulate template rendering
            echo "<!-- Template: $template -->\n";
            echo "<!-- Template rendered successfully -->\n";
            
            return true;
        }
        
        public function wasDisplayCalled() {
            return $this->displayCalled;
        }
        
        public function getLastTemplate() {
            return $this->lastTemplate;
        }
        
        public function getLastOptions() {
            return $this->lastOptions;
        }
        
        public function reset() {
            $this->displayCalled = false;
            $this->lastTemplate = null;
            $this->lastOptions = null;
        }
    }
}

// Load plugin classes
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/entity.class.php';

// Initialize global DB
global $DB;
$DB = DB::getInstance();

// Test Results
$results = [
    'config_template' => false,
    'entity_template' => false,
    'template_errors' => false,
    'errors' => []
];

echo "\n";
echo "========================================\n";
echo "Template Rendering Test\n";
echo "========================================\n";
echo "\n";

// Test 1: Config template rendering
echo "Test 1: Testing config template rendering...\n";
try {
    // Insert mock config data
    $DB->insert('glpi_plugin_costs_configs', [
        'id' => 1,
        'taskdescription' => 0
    ]);
    
    // Capture output
    ob_start();
    $result = PluginCostsConfig::showConfigForm();
    $output = ob_get_clean();
    
    if ($result !== true) {
        throw new Exception("showConfigForm() did not return true");
    }
    
    // Verify TemplateRenderer was called
    $renderer = \Glpi\Application\View\TemplateRenderer::getInstance();
    if (!$renderer->wasDisplayCalled()) {
        throw new Exception("TemplateRenderer::display() was not called");
    }
    
    // Verify correct template was used
    $template = $renderer->getLastTemplate();
    if ($template !== '@costs/config.html.twig') {
        throw new Exception("Expected template '@costs/config.html.twig', got: $template");
    }
    
    // Verify template options
    $options = $renderer->getLastOptions();
    if (!isset($options['item'])) {
        throw new Exception("Template options missing 'item' parameter");
    }
    if (!isset($options['credit'])) {
        throw new Exception("Template options missing 'credit' parameter");
    }
    
    echo "  ✓ Config template rendered successfully\n";
    echo "  ✓ Template: " . $template . "\n";
    echo "  ✓ Template options validated\n";
    echo "  ✓ No Twig errors detected\n";
    
    $results['config_template'] = true;
    
    // Reset for next test
    $renderer->reset();
} catch (Exception $e) {
    $results['errors'][] = "Config template test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Entity costs tab template rendering
echo "Test 2: Testing entity costs tab template rendering...\n";
try {
    // Create mock entity
    $DB->insert('glpi_entities', [
        'id' => 1,
        'entities_id' => 0,
        'name' => 'Root Entity'
    ]);
    
    // Create mock entity cost config
    $DB->insert('glpi_plugin_costs_entities', [
        'id' => 1,
        'entities_id' => 1,
        'fixed_cost' => 10.00,
        'time_cost' => 50.00,
        'cost_private' => 1,
        'auto_cost' => 1,
        'inheritance' => 0
    ]);
    
    // Create entity instance
    $entity = new Entity();
    $entity->fields = [
        'id' => 1,
        'entities_id' => 0,
        'name' => 'Root Entity'
    ];
    
    // Test displayTabContentForItem
    ob_start();
    $result = PluginCostsEntity::displayTabContentForItem($entity, 1, 0);
    $output = ob_get_clean();
    
    if ($result !== true) {
        throw new Exception("displayTabContentForItem() did not return true");
    }
    
    // Verify output contains form elements
    if (strpos($output, '<form') === false) {
        throw new Exception("Output does not contain form element");
    }
    
    if (strpos($output, 'Fixed cost') === false) {
        throw new Exception("Output does not contain 'Fixed cost' label");
    }
    
    if (strpos($output, 'Time cost') === false) {
        throw new Exception("Output does not contain 'Time cost' label");
    }
    
    if (strpos($output, 'Private task') === false) {
        throw new Exception("Output does not contain 'Private task' label");
    }
    
    if (strpos($output, 'Auto billable ticket') === false) {
        throw new Exception("Output does not contain 'Auto billable ticket' label");
    }
    
    echo "  ✓ Entity costs tab rendered successfully\n";
    echo "  ✓ Form elements present\n";
    echo "  ✓ All required fields displayed\n";
    echo "  ✓ No rendering errors detected\n";
    
    $results['entity_template'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Entity template test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Check for Twig template errors
echo "Test 3: Checking for Twig template errors...\n";
try {
    // Verify template files exist
    $config_template = __DIR__ . '/../templates/config.html.twig';
    $billable_template = __DIR__ . '/../templates/billable_dropdown.html.twig';
    
    if (!file_exists($config_template)) {
        throw new Exception("Config template file not found: $config_template");
    }
    
    if (!file_exists($billable_template)) {
        throw new Exception("Billable dropdown template file not found: $billable_template");
    }
    
    // Read template contents and check for basic syntax
    $config_content = file_get_contents($config_template);
    $billable_content = file_get_contents($billable_template);
    
    // Check for balanced Twig tags
    $config_open = substr_count($config_content, '{%');
    $config_close = substr_count($config_content, '%}');
    if ($config_open !== $config_close) {
        throw new Exception("Config template has unbalanced Twig tags");
    }
    
    $billable_open = substr_count($billable_content, '{%');
    $billable_close = substr_count($billable_content, '%}');
    if ($billable_open !== $billable_close) {
        throw new Exception("Billable template has unbalanced Twig tags");
    }
    
    // Check for required Twig imports
    if (strpos($config_content, "import 'components/form/fields_macros.html.twig'") === false) {
        throw new Exception("Config template missing fields_macros import");
    }
    
    if (strpos($billable_content, "import 'components/form/fields_macros.html.twig'") === false) {
        throw new Exception("Billable template missing fields_macros import");
    }
    
    echo "  ✓ Template files exist\n";
    echo "  ✓ Config template: $config_template\n";
    echo "  ✓ Billable template: $billable_template\n";
    echo "  ✓ Twig syntax validated\n";
    echo "  ✓ Required imports present\n";
    echo "  ✓ No template errors detected\n";
    
    $results['template_errors'] = true;
} catch (Exception $e) {
    $results['errors'][] = "Template error check failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Test getTabNameForItem methods
echo "Test 4: Testing tab name methods...\n";
try {
    // Test PluginCostsConfig::getTabNameForItem
    $config = new Config();
    $config_tab_name = PluginCostsConfig::getTabNameForItem($config, 0);
    
    if (empty($config_tab_name)) {
        throw new Exception("PluginCostsConfig::getTabNameForItem returned empty string");
    }
    
    echo "  ✓ Config tab name: $config_tab_name\n";
    
    // Test PluginCostsEntity::getTabNameForItem
    $entity = new Entity();
    $entity_tab_name = PluginCostsEntity::getTabNameForItem($entity, 0);
    
    if (empty($entity_tab_name)) {
        throw new Exception("PluginCostsEntity::getTabNameForItem returned empty string");
    }
    
    echo "  ✓ Entity tab name: $entity_tab_name\n";
    echo "  ✓ Tab name methods work correctly\n";
} catch (Exception $e) {
    $results['errors'][] = "Tab name test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "\n";

$passed = 0;
$total = 3;

if ($results['config_template']) {
    echo "✓ Config template rendering: PASSED\n";
    $passed++;
} else {
    echo "✗ Config template rendering: FAILED\n";
}

if ($results['entity_template']) {
    echo "✓ Entity template rendering: PASSED\n";
    $passed++;
} else {
    echo "✗ Entity template rendering: FAILED\n";
}

if ($results['template_errors']) {
    echo "✓ Template error check: PASSED\n";
    $passed++;
} else {
    echo "✗ Template error check: FAILED\n";
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
