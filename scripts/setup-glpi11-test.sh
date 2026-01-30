#!/bin/bash

# GLPI 11 Testing Environment Setup Script
# This script automates the setup of a GLPI 11 testing environment using Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
COMPOSE_FILE="docker-compose.glpi11-test.yml"
GLPI_URL="http://localhost:8081"
WAIT_TIME=90

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}GLPI 11 Testing Environment Setup${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Error: Docker is not installed${NC}"
    echo "Please install Docker from: https://docs.docker.com/get-docker/"
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Error: Docker Compose is not installed${NC}"
    echo "Please install Docker Compose from: https://docs.docker.com/compose/install/"
    exit 1
fi

# Check if compose file exists
if [ ! -f "$COMPOSE_FILE" ]; then
    echo -e "${RED}Error: $COMPOSE_FILE not found${NC}"
    echo "Please run this script from the project root directory"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking for existing containers...${NC}"
if docker ps -a | grep -q "glpi11-"; then
    echo -e "${YELLOW}Found existing GLPI 11 containers${NC}"
    read -p "Do you want to remove them and start fresh? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Stopping and removing existing containers..."
        docker-compose -f "$COMPOSE_FILE" down -v
        echo -e "${GREEN}✓ Cleaned up existing containers${NC}"
    else
        echo "Keeping existing containers"
    fi
fi

echo ""
echo -e "${YELLOW}Step 2: Starting Docker containers...${NC}"
docker-compose -f "$COMPOSE_FILE" up -d

echo ""
echo -e "${YELLOW}Step 3: Waiting for services to be ready (${WAIT_TIME}s)...${NC}"
echo "This may take a while on first run as Docker images are downloaded..."

# Wait for MySQL to be ready
echo -n "Waiting for MySQL"
for i in {1..30}; do
    if docker exec glpi11-mysql mysqladmin ping -h localhost -u glpi_user -pglpi_pass &> /dev/null; then
        echo -e " ${GREEN}✓${NC}"
        break
    fi
    echo -n "."
    sleep 2
done

# Wait for GLPI to be ready
echo -n "Waiting for GLPI"
for i in {1..30}; do
    if curl -s -o /dev/null -w "%{http_code}" "$GLPI_URL" | grep -q "200\|302"; then
        echo -e " ${GREEN}✓${NC}"
        break
    fi
    echo -n "."
    sleep 3
done

echo ""
echo -e "${YELLOW}Step 4: Verifying installation...${NC}"

# Check container status
if docker ps | grep -q "glpi11-app"; then
    echo -e "${GREEN}✓ GLPI container is running${NC}"
else
    echo -e "${RED}✗ GLPI container is not running${NC}"
    exit 1
fi

if docker ps | grep -q "glpi11-mysql"; then
    echo -e "${GREEN}✓ MySQL container is running${NC}"
else
    echo -e "${RED}✗ MySQL container is not running${NC}"
    exit 1
fi

# Check if plugin directory is mounted
if docker exec glpi11-app test -d /var/www/html/glpi/plugins/costs; then
    echo -e "${GREEN}✓ Costs plugin is mounted${NC}"
else
    echo -e "${RED}✗ Costs plugin is not mounted${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "GLPI 11 is now running at: ${GREEN}${GLPI_URL}${NC}"
echo ""
echo "Default credentials:"
echo "  Admin:      glpi / glpi"
echo "  Technician: tech / tech"
echo ""
echo "Database connection:"
echo "  Host:     localhost:3307 (from host) or mysql:3306 (from container)"
echo "  Database: glpi11_test"
echo "  User:     glpi_user"
echo "  Password: glpi_pass"
echo ""
echo "Next steps:"
echo "  1. Open ${GLPI_URL} in your browser"
echo "  2. Complete GLPI installation wizard (if first run)"
echo "  3. Navigate to: Setup > Plugins"
echo "  4. Install and enable the 'Costs' plugin"
echo ""
echo "Useful commands:"
echo "  View logs:        docker-compose -f $COMPOSE_FILE logs -f"
echo "  Stop environment: docker-compose -f $COMPOSE_FILE stop"
echo "  Start again:      docker-compose -f $COMPOSE_FILE start"
echo "  Remove all:       docker-compose -f $COMPOSE_FILE down -v"
echo ""
echo "For detailed documentation, see:"
echo "  .context/docs/glpi-11-testing-environment.md"
echo ""
