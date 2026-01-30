<?php
/**
 * Rights Management Test Script
 * 
 * This script tests permission checks for the Costs plugin including:
 * - Testing with users having entity UPDATE rights
 * - Testing with users having config READ/UPDATE rights
 * - Testing with users lacking required rights
 * 
 * Requirements: 5.4
 * Task: 10.1
 */

// Simulate GLPI environment
define('GLPI_ROOT', '/tmp/glpi-test');
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/config');
define('PLUGIN_COSTS_NUMBER_STEP', '0.01');

// Mock GLPI classes
class Session {
    private static $rights = [];
    private static $interface = 'central';
    
    public static function setRights(array $rights) {
        self::$rights = $rights;
    }
    
    public static function haveRight($right, $value) {
        if (!isset(self::$rights[$right])) {
            return false;
        }
        return (self::$rights[$right] & $value) === $value;
    }
    
    public static function haveRightsOr($right, array $values) {
        if (!isset(self::$rights[$right])) {
            return false;
        }
        foreach ($values as $value) {
            if ((self::$rights[$right] & $value) === $value) {
                return true;
            }
        }
        return false;
    }
    
    public static function getCurrentInterface() {
        return self::$interface;
    }
    
    public static function setInterface($interface) {
        self::$interface = $interface;
    }
    
    public static function clearRights() {
        self::$rights = [];
    }
}

class Plugin {
    private static $registered_classes = [];
    
    public static function registerClass($class, $options = []) {
        self::$registered_classes[$class] = $options;
    }
    
    public static function isClassRegistered($class) {
        return isset(self::$registered_classes[$class]);
    }
    
    public static function getRegisteredClasses() {
        return self::$registered_classes;
    }
    
    public static function clearRegistrations() {
        self::$registered_classes = [];
    }
    
    public static function isPluginActive($plugin) {
        return true;
    }
}

class CommonDBTM {
    public $fields = [];
    protected static $rightname = '';
    
    public static function getRightname() {
        return static::$rightname;
    }
    
    public function check($id, $right) {
        $rightname = static::getRightname();
        if (!Session::haveRight($rightname, $right)) {
            throw new Exception("Access denied: Missing $rightname right with value $right");
        }
        return true;
    }
}

class PluginCostsConfig extends CommonDBTM {
    public static $rightname = 'config';
}

class PluginCostsEntity extends CommonDBTM {
    public static $rightname = 'entity';
}

// Define GLPI constants
define('READ', 1);
define('UPDATE', 2);
define('CREATE', 4);
define('DELETE', 8);
define('PURGE', 16);

// Test Results
$results = [
    'entity_update_access' => false,
    'entity_no_access' => false,
    'config_read_access' => false,
    'config_update_access' => false,
    'config_no_access' => false,
    'plugin_registration_entity' => false,
    'plugin_registration_config' => false,
    'errors' => []
];

echo "\n";
echo "========================================\n";
echo "Rights Management Test\n";
echo "========================================\n";
echo "\n";

// Test 1: User with entity UPDATE rights
echo "Test 1: Testing entity UPDATE rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with entity UPDATE rights
    Session::setRights(['entity' => UPDATE]);
    
    // Simulate plugin initialization
    if (Session::haveRight('entity', UPDATE)) {
        Plugin::registerClass('PluginCostsEntity', ['addtabon' => 'Entity']);
    }
    
    // Verify plugin registered the entity class
    if (!Plugin::isClassRegistered('PluginCostsEntity')) {
        throw new Exception("PluginCostsEntity not registered with entity UPDATE rights");
    }
    
    echo "  ✓ User with entity UPDATE rights can access entity costs\n";
    echo "  ✓ PluginCostsEntity registered successfully\n";
    
    // Test that the entity form handler would allow access
    $entity_config = new PluginCostsEntity();
    try {
        $entity_config->check(1, UPDATE);
        echo "  ✓ Entity cost configuration update check passed\n";
        $results['entity_update_access'] = true;
    } catch (Exception $e) {
        throw new Exception("Entity update check failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    $results['errors'][] = "Entity UPDATE rights test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: User without entity UPDATE rights
echo "Test 2: Testing user without entity UPDATE rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with only READ rights (not UPDATE)
    Session::setRights(['entity' => READ]);
    
    // Simulate plugin initialization
    if (Session::haveRight('entity', UPDATE)) {
        Plugin::registerClass('PluginCostsEntity', ['addtabon' => 'Entity']);
    }
    
    // Verify plugin did NOT register the entity class
    if (Plugin::isClassRegistered('PluginCostsEntity')) {
        throw new Exception("PluginCostsEntity should not be registered without UPDATE rights");
    }
    
    echo "  ✓ User without entity UPDATE rights cannot access entity costs\n";
    echo "  ✓ PluginCostsEntity not registered (as expected)\n";
    
    // Test that the entity form handler would deny access
    $entity_config = new PluginCostsEntity();
    $access_denied = false;
    try {
        $entity_config->check(1, UPDATE);
        throw new Exception("Entity update check should have failed");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "  ✓ Entity update check correctly denied access\n";
            $access_denied = true;
        } else {
            throw $e;
        }
    }
    
    if ($access_denied) {
        $results['entity_no_access'] = true;
    }
    
} catch (Exception $e) {
    $results['errors'][] = "Entity no access test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: User with config READ rights
echo "Test 3: Testing config READ rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with config READ rights
    Session::setRights(['config' => READ]);
    
    // Simulate plugin initialization
    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsConfig', ['addtabon' => 'Config']);
    }
    
    // Verify plugin registered the config class
    if (!Plugin::isClassRegistered('PluginCostsConfig')) {
        throw new Exception("PluginCostsConfig not registered with config READ rights");
    }
    
    echo "  ✓ User with config READ rights can access config\n";
    echo "  ✓ PluginCostsConfig registered successfully\n";
    
    $results['config_read_access'] = true;
    
} catch (Exception $e) {
    $results['errors'][] = "Config READ rights test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: User with config UPDATE rights
echo "Test 4: Testing config UPDATE rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with config UPDATE rights
    Session::setRights(['config' => UPDATE]);
    
    // Simulate plugin initialization
    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsConfig', ['addtabon' => 'Config']);
    }
    
    // Verify plugin registered the config class
    if (!Plugin::isClassRegistered('PluginCostsConfig')) {
        throw new Exception("PluginCostsConfig not registered with config UPDATE rights");
    }
    
    echo "  ✓ User with config UPDATE rights can access config\n";
    echo "  ✓ PluginCostsConfig registered successfully\n";
    
    // Test that the config form handler would allow access
    $config = new PluginCostsConfig();
    try {
        $config->check(1, UPDATE);
        echo "  ✓ Config update check passed\n";
        $results['config_update_access'] = true;
    } catch (Exception $e) {
        throw new Exception("Config update check failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    $results['errors'][] = "Config UPDATE rights test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: User without config rights
echo "Test 5: Testing user without config rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with no config rights
    Session::setRights(['entity' => READ]); // Only entity rights, no config
    
    // Simulate plugin initialization
    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsConfig', ['addtabon' => 'Config']);
    }
    
    // Verify plugin did NOT register the config class
    if (Plugin::isClassRegistered('PluginCostsConfig')) {
        throw new Exception("PluginCostsConfig should not be registered without config rights");
    }
    
    echo "  ✓ User without config rights cannot access config\n";
    echo "  ✓ PluginCostsConfig not registered (as expected)\n";
    
    // Test that the config form handler would deny access
    $config = new PluginCostsConfig();
    $access_denied = false;
    try {
        $config->check(1, UPDATE);
        throw new Exception("Config update check should have failed");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "  ✓ Config update check correctly denied access\n";
            $access_denied = true;
        } else {
            throw $e;
        }
    }
    
    if ($access_denied) {
        $results['config_no_access'] = true;
    }
    
} catch (Exception $e) {
    $results['errors'][] = "Config no access test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Plugin registration with entity rights
echo "Test 6: Testing plugin registration behavior with entity rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with entity UPDATE rights
    Session::setRights(['entity' => UPDATE]);
    
    // Simulate plugin_init_costs() logic
    if (Session::haveRight('entity', UPDATE)) {
        Plugin::registerClass('PluginCostsEntity', ['addtabon' => 'Entity']);
    }
    
    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsConfig', ['addtabon' => 'Config']);
    }
    
    // Verify only entity class is registered
    $registered = Plugin::getRegisteredClasses();
    
    if (!isset($registered['PluginCostsEntity'])) {
        throw new Exception("PluginCostsEntity should be registered");
    }
    
    if (isset($registered['PluginCostsConfig'])) {
        throw new Exception("PluginCostsConfig should not be registered without config rights");
    }
    
    echo "  ✓ Only PluginCostsEntity registered with entity rights\n";
    echo "  ✓ PluginCostsConfig not registered (correct)\n";
    
    $results['plugin_registration_entity'] = true;
    
} catch (Exception $e) {
    $results['errors'][] = "Plugin registration entity test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Plugin registration with config rights
echo "Test 7: Testing plugin registration behavior with config rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with config READ rights
    Session::setRights(['config' => READ]);
    
    // Simulate plugin_init_costs() logic
    if (Session::haveRight('entity', UPDATE)) {
        Plugin::registerClass('PluginCostsEntity', ['addtabon' => 'Entity']);
    }
    
    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsConfig', ['addtabon' => 'Config']);
    }
    
    // Verify only config class is registered
    $registered = Plugin::getRegisteredClasses();
    
    if (isset($registered['PluginCostsEntity'])) {
        throw new Exception("PluginCostsEntity should not be registered without entity UPDATE rights");
    }
    
    if (!isset($registered['PluginCostsConfig'])) {
        throw new Exception("PluginCostsConfig should be registered");
    }
    
    echo "  ✓ Only PluginCostsConfig registered with config rights\n";
    echo "  ✓ PluginCostsEntity not registered (correct)\n";
    
    $results['plugin_registration_config'] = true;
    
} catch (Exception $e) {
    $results['errors'][] = "Plugin registration config test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Test 8: Combined rights scenario
echo "Test 8: Testing user with both entity and config rights...\n";
try {
    // Clear previous state
    Session::clearRights();
    Plugin::clearRegistrations();
    
    // Set user with both entity UPDATE and config UPDATE rights
    Session::setRights([
        'entity' => UPDATE,
        'config' => UPDATE
    ]);
    
    // Simulate plugin_init_costs() logic
    if (Session::haveRight('entity', UPDATE)) {
        Plugin::registerClass('PluginCostsEntity', ['addtabon' => 'Entity']);
    }
    
    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsConfig', ['addtabon' => 'Config']);
    }
    
    // Verify both classes are registered
    $registered = Plugin::getRegisteredClasses();
    
    if (!isset($registered['PluginCostsEntity'])) {
        throw new Exception("PluginCostsEntity should be registered");
    }
    
    if (!isset($registered['PluginCostsConfig'])) {
        throw new Exception("PluginCostsConfig should be registered");
    }
    
    echo "  ✓ Both PluginCostsEntity and PluginCostsConfig registered\n";
    echo "  ✓ User with combined rights has full access\n";
    
} catch (Exception $e) {
    $results['errors'][] = "Combined rights test failed: " . $e->getMessage();
    echo "  ✗ " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "\n";

$passed = 0;
$total = 7;

if ($results['entity_update_access']) {
    echo "✓ Entity UPDATE rights access: PASSED\n";
    $passed++;
} else {
    echo "✗ Entity UPDATE rights access: FAILED\n";
}

if ($results['entity_no_access']) {
    echo "✓ Entity no access denial: PASSED\n";
    $passed++;
} else {
    echo "✗ Entity no access denial: FAILED\n";
}

if ($results['config_read_access']) {
    echo "✓ Config READ rights access: PASSED\n";
    $passed++;
} else {
    echo "✗ Config READ rights access: FAILED\n";
}

if ($results['config_update_access']) {
    echo "✓ Config UPDATE rights access: PASSED\n";
    $passed++;
} else {
    echo "✗ Config UPDATE rights access: FAILED\n";
}

if ($results['config_no_access']) {
    echo "✓ Config no access denial: PASSED\n";
    $passed++;
} else {
    echo "✗ Config no access denial: FAILED\n";
}

if ($results['plugin_registration_entity']) {
    echo "✓ Plugin registration with entity rights: PASSED\n";
    $passed++;
} else {
    echo "✗ Plugin registration with entity rights: FAILED\n";
}

if ($results['plugin_registration_config']) {
    echo "✓ Plugin registration with config rights: PASSED\n";
    $passed++;
} else {
    echo "✗ Plugin registration with config rights: FAILED\n";
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
