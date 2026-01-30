#!/bin/bash

# GLPI 11 Testing Environment Verification Script
# Verifies that the testing environment is properly configured and running

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

GLPI_URL="http://localhost:8081"
ERRORS=0
WARNINGS=0

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}GLPI 11 Environment Verification${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to check and report
check() {
    local description=$1
    local command=$2
    
    echo -n "Checking: $description... "
    
    if eval "$command" &> /dev/null; then
        echo -e "${GREEN}✓${NC}"
        return 0
    else
        echo -e "${RED}✗${NC}"
        ERRORS=$((ERRORS + 1))
        return 1
    fi
}

# Function for warnings
warn() {
    local description=$1
    local command=$2
    
    echo -n "Checking: $description... "
    
    if eval "$command" &> /dev/null; then
        echo -e "${GREEN}✓${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠${NC}"
        WARNINGS=$((WARNINGS + 1))
        return 1
    fi
}

echo -e "${YELLOW}1. Docker Environment${NC}"
check "Docker is installed" "command -v docker"
check "Docker Compose is installed" "command -v docker-compose"
check "Docker daemon is running" "docker info"
echo ""

echo -e "${YELLOW}2. Container Status${NC}"
check "GLPI container is running" "docker ps | grep -q glpi11-app"
check "MySQL container is running" "docker ps | grep -q glpi11-mysql"
echo ""

echo -e "${YELLOW}3. Service Health${NC}"
check "MySQL is responding" "docker exec glpi11-mysql mysqladmin ping -h localhost -u glpi_user -pglpi_pass"
check "GLPI web server is responding" "curl -s -o /dev/null -w '%{http_code}' $GLPI_URL | grep -q '200\|302'"
echo ""

echo -e "${YELLOW}4. Database Configuration${NC}"
check "Database exists" "docker exec glpi11-mysql mysql -u glpi_user -pglpi_pass -e 'USE glpi11_test;'"
check "Database is accessible" "docker exec glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test -e 'SELECT 1;'"
echo ""

echo -e "${YELLOW}5. Plugin Installation${NC}"
check "Plugin directory exists" "docker exec glpi11-app test -d /var/www/html/glpi/plugins/costs"
check "setup.php exists" "docker exec glpi11-app test -f /var/www/html/glpi/plugins/costs/setup.php"
check "hook.php exists" "docker exec glpi11-app test -f /var/www/html/glpi/plugins/costs/hook.php"
echo ""

echo -e "${YELLOW}6. Plugin Files${NC}"
check "inc/ directory exists" "docker exec glpi11-app test -d /var/www/html/glpi/plugins/costs/inc"
check "front/ directory exists" "docker exec glpi11-app test -d /var/www/html/glpi/plugins/costs/front"
check "templates/ directory exists" "docker exec glpi11-app test -d /var/www/html/glpi/plugins/costs/templates"
echo ""

echo -e "${YELLOW}7. GLPI Version${NC}"
if docker exec glpi11-app test -f /var/www/html/glpi/version.txt; then
    VERSION=$(docker exec glpi11-app cat /var/www/html/glpi/version.txt 2>/dev/null || echo "unknown")
    echo "GLPI Version: $VERSION"
    
    if [[ $VERSION == 11.* ]]; then
        echo -e "${GREEN}✓ GLPI 11.x detected${NC}"
    else
        echo -e "${RED}✗ Expected GLPI 11.x, found: $VERSION${NC}"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${YELLOW}⚠ Could not determine GLPI version${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

echo -e "${YELLOW}8. Plugin Version${NC}"
if docker exec glpi11-app test -f /var/www/html/glpi/plugins/costs/setup.php; then
    PLUGIN_VERSION=$(docker exec glpi11-app grep "PLUGIN_COSTS_VERSION" /var/www/html/glpi/plugins/costs/setup.php | grep -oP "'\K[^']+")
    MIN_GLPI=$(docker exec glpi11-app grep "PLUGIN_COSTS_MIN_GLPI" /var/www/html/glpi/plugins/costs/setup.php | grep -oP '"\K[^"]+')
    MAX_GLPI=$(docker exec glpi11-app grep "PLUGIN_COSTS_MAX_GLPI" /var/www/html/glpi/plugins/costs/setup.php | grep -oP '"\K[^"]+')
    
    echo "Plugin Version: $PLUGIN_VERSION"
    echo "Min GLPI: $MIN_GLPI"
    echo "Max GLPI: $MAX_GLPI"
    
    if [[ $PLUGIN_VERSION == "3.1.0" ]]; then
        echo -e "${GREEN}✓ Plugin version is 3.1.0${NC}"
    else
        echo -e "${RED}✗ Expected version 3.1.0, found: $PLUGIN_VERSION${NC}"
        ERRORS=$((ERRORS + 1))
    fi
    
    if [[ $MAX_GLPI == "12.0" ]]; then
        echo -e "${GREEN}✓ Max GLPI version supports 11.x${NC}"
    else
        echo -e "${RED}✗ Max GLPI should be 12.0, found: $MAX_GLPI${NC}"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${RED}✗ Could not read plugin version${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

echo -e "${YELLOW}9. Network Connectivity${NC}"
check "Can reach GLPI from host" "curl -s -o /dev/null -w '%{http_code}' $GLPI_URL | grep -q '200\|302'"
check "MySQL port is accessible" "nc -z localhost 3307"
echo ""

echo -e "${YELLOW}10. Optional: Plugin Database Tables${NC}"
warn "Plugin tables exist" "docker exec glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test -e 'SHOW TABLES LIKE \"glpi_plugin_costs%\";' | grep -q glpi_plugin_costs"
if [ $? -eq 0 ]; then
    echo -e "${BLUE}   Note: Plugin appears to be installed in database${NC}"
else
    echo -e "${BLUE}   Note: Plugin not yet installed (this is normal for fresh setup)${NC}"
fi
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Verification Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo ""
    echo "Your GLPI 11 testing environment is ready."
    echo ""
    echo "Next steps:"
    echo "  1. Access GLPI at: $GLPI_URL"
    echo "  2. Login with: glpi/glpi"
    echo "  3. Navigate to: Setup > Plugins"
    echo "  4. Install and enable the Costs plugin"
    echo ""
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ Checks passed with $WARNINGS warning(s)${NC}"
    echo ""
    echo "Your environment is mostly ready, but some optional checks failed."
    echo "You can proceed with testing, but review the warnings above."
    echo ""
    exit 0
else
    echo -e "${RED}✗ $ERRORS error(s) found${NC}"
    if [ $WARNINGS -gt 0 ]; then
        echo -e "${YELLOW}⚠ $WARNINGS warning(s) found${NC}"
    fi
    echo ""
    echo "Please fix the errors above before proceeding."
    echo ""
    echo "Common fixes:"
    echo "  - Start environment: docker-compose -f docker-compose.glpi11-test.yml up -d"
    echo "  - Check logs: docker-compose -f docker-compose.glpi11-test.yml logs -f"
    echo "  - Reset environment: docker-compose -f docker-compose.glpi11-test.yml down -v"
    echo ""
    exit 1
fi
