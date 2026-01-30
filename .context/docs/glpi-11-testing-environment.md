# GLPI 11 Testing Environment Setup

## Overview

This document provides instructions for setting up a GLPI 11.0.x testing environment to validate the Costs plugin compatibility upgrade from version 3.0.6 to 3.1.0.

## Prerequisites

- Docker and Docker Compose (recommended) OR
- PHP 8.1+ with required extensions
- MySQL 8.0+ or MariaDB 10.5+
- Web server (Apache or Nginx)
- Composer (for dependency management)

## Option 1: Docker-Based Setup (Recommended)

### Step 1: Create Docker Compose Configuration

Create a `docker-compose.glpi11-test.yml` file in the project root:

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: glpi11-mysql
    environment:
      MYSQL_ROOT_PASSWORD: glpi_root_pass
      MYSQL_DATABASE: glpi11_test
      MYSQL_USER: glpi_user
      MYSQL_PASSWORD: glpi_pass
    volumes:
      - glpi11-mysql-data:/var/lib/mysql
    ports:
      - "3307:3306"
    command: --default-authentication-plugin=mysql_native_password

  glpi:
    image: diouxx/glpi:latest
    container_name: glpi11-app
    environment:
      GLPI_VERSION: "11.0.0"
      MYSQL_HOST: mysql
      MYSQL_DATABASE: glpi11_test
      MYSQL_USER: glpi_user
      MYSQL_PASSWORD: glpi_pass
    ports:
      - "8081:80"
    volumes:
      - glpi11-files:/var/www/html/glpi/files
      - glpi11-plugins:/var/www/html/glpi/plugins
      - ./:/var/www/html/glpi/plugins/costs:ro
    depends_on:
      - mysql

volumes:
  glpi11-mysql-data:
  glpi11-files:
  glpi11-plugins:
```

### Step 2: Start the Environment

```bash
# Start the containers
docker-compose -f docker-compose.glpi11-test.yml up -d

# Wait for GLPI to initialize (30-60 seconds)
sleep 60

# Check container status
docker-compose -f docker-compose.glpi11-test.yml ps
```

### Step 3: Access GLPI

1. Open browser to `http://localhost:8081`
2. Complete GLPI installation wizard:
   - Database host: `mysql`
   - Database name: `glpi11_test`
   - Database user: `glpi_user`
   - Database password: `glpi_pass`
3. Default credentials: `glpi/glpi` (admin) or `tech/tech` (technician)

### Step 4: Install Costs Plugin

```bash
# Copy plugin to GLPI plugins directory (if not already mounted)
docker exec glpi11-app cp -r /var/www/html/glpi/plugins/costs /var/www/html/glpi/plugins/

# Set proper permissions
docker exec glpi11-app chown -R www-data:www-data /var/www/html/glpi/plugins/costs

# Access GLPI web interface
# Navigate to: Setup > Plugins
# Find "Costs" plugin and click "Install" then "Enable"
```

## Option 2: Manual Installation

### Step 1: Install GLPI 11.0.x

```bash
# Download GLPI 11.0.0
cd /tmp
wget https://github.com/glpi-project/glpi/releases/download/11.0.0/glpi-11.0.0.tgz

# Extract to web server directory
sudo tar -xzf glpi-11.0.0.tgz -C /var/www/html/

# Set permissions
sudo chown -R www-data:www-data /var/www/html/glpi
sudo chmod -R 755 /var/www/html/glpi
```

### Step 2: Configure Database

```bash
# Create database
mysql -u root -p << EOF
CREATE DATABASE glpi11_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'glpi_user'@'localhost' IDENTIFIED BY 'glpi_pass';
GRANT ALL PRIVILEGES ON glpi11_test.* TO 'glpi_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Step 3: Configure Web Server

For Apache, create `/etc/apache2/sites-available/glpi11-test.conf`:

```apache
<VirtualHost *:8081>
    ServerName localhost
    DocumentRoot /var/www/html/glpi

    <Directory /var/www/html/glpi>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/glpi11-error.log
    CustomLog ${APACHE_LOG_DIR}/glpi11-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite glpi11-test
sudo systemctl reload apache2
```

### Step 4: Complete GLPI Installation

1. Access `http://localhost:8081` in browser
2. Follow installation wizard
3. Configure database connection
4. Create admin account

### Step 5: Install Costs Plugin

```bash
# Create symlink or copy plugin
sudo ln -s $(pwd) /var/www/html/glpi/plugins/costs
# OR
sudo cp -r . /var/www/html/glpi/plugins/costs

# Set permissions
sudo chown -R www-data:www-data /var/www/html/glpi/plugins/costs
```

## Verification Steps

### 1. Verify GLPI Version

```bash
# Via CLI
docker exec glpi11-app php /var/www/html/glpi/bin/console glpi:system:check_requirements

# Via Web UI
# Navigate to: Setup > General > System Information
# Verify "GLPI version" shows 11.0.x
```

### 2. Verify Plugin Installation

```bash
# Check plugin is recognized
docker exec glpi11-app ls -la /var/www/html/glpi/plugins/costs

# Via Web UI
# Navigate to: Setup > Plugins
# Verify "Costs" plugin appears in the list
# Check version shows 3.1.0
```

### 3. Verify Database Tables

```bash
# Connect to database
docker exec -it glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test

# Check plugin tables exist
SHOW TABLES LIKE 'glpi_plugin_costs%';

# Expected output:
# glpi_plugin_costs_configs
# glpi_plugin_costs_entities
# glpi_plugin_costs_entities_profiles
# glpi_plugin_costs_tasks
# glpi_plugin_costs_tickets
```

### 4. Verify Plugin Functionality

1. **Configuration Page**:
   - Navigate to: Setup > General > Costs
   - Verify page loads without errors

2. **Entity Configuration**:
   - Navigate to: Administration > Entities > Root Entity > Costs tab
   - Verify tab appears and loads

3. **Ticket Creation**:
   - Create a new ticket
   - Verify "Billable" dropdown appears on ticket form

## Testing Workflow

### Basic Functional Test

```bash
# 1. Enable plugin
# Via Web UI: Setup > Plugins > Costs > Enable

# 2. Configure entity costs
# Via Web UI: Administration > Entities > Root Entity > Costs tab
# Set: Fixed cost = 10.00, Time cost = 50.00/hour

# 3. Create billable ticket
# Via Web UI: Assistance > Tickets > Create ticket
# Set: Billable = Yes
# Save ticket

# 4. Add task to ticket
# Add task with 1 hour duration
# Verify cost entry is created

# 5. Check database
docker exec -it glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test -e \
  "SELECT * FROM glpi_plugin_costs_tickets; SELECT * FROM glpi_plugin_costs_tasks;"
```

### Automated Test Execution

```bash
# Run PHPStan analysis (if configured)
docker exec glpi11-app php /var/www/html/glpi/plugins/costs/tools/phpstan.sh

# Run unit tests (when implemented)
# docker exec glpi11-app vendor/bin/phpunit tests/

# Run property-based tests (when implemented)
# docker exec glpi11-app vendor/bin/phpunit tests/PropertyTests/
```

## Environment Management

### Start Environment

```bash
docker-compose -f docker-compose.glpi11-test.yml up -d
```

### Stop Environment

```bash
docker-compose -f docker-compose.glpi11-test.yml stop
```

### Reset Environment

```bash
# Stop and remove containers
docker-compose -f docker-compose.glpi11-test.yml down

# Remove volumes (WARNING: deletes all data)
docker-compose -f docker-compose.glpi11-test.yml down -v

# Restart fresh
docker-compose -f docker-compose.glpi11-test.yml up -d
```

### View Logs

```bash
# GLPI application logs
docker-compose -f docker-compose.glpi11-test.yml logs -f glpi

# MySQL logs
docker-compose -f docker-compose.glpi11-test.yml logs -f mysql

# PHP error logs
docker exec glpi11-app tail -f /var/log/apache2/error.log
```

## Troubleshooting

### Plugin Not Appearing

```bash
# Check plugin directory
docker exec glpi11-app ls -la /var/www/html/glpi/plugins/

# Check permissions
docker exec glpi11-app ls -la /var/www/html/glpi/plugins/costs/

# Fix permissions
docker exec glpi11-app chown -R www-data:www-data /var/www/html/glpi/plugins/costs
```

### Database Connection Issues

```bash
# Test database connection
docker exec glpi11-mysql mysql -u glpi_user -pglpi_pass -e "SELECT 1;"

# Check GLPI database config
docker exec glpi11-app cat /var/www/html/glpi/config/config_db.php
```

### Installation Errors

```bash
# Check GLPI logs
docker exec glpi11-app tail -100 /var/www/html/glpi/files/_log/php-errors.log
docker exec glpi11-app tail -100 /var/www/html/glpi/files/_log/sql-errors.log

# Check Apache error log
docker exec glpi11-app tail -100 /var/log/apache2/error.log
```

### Version Mismatch

If GLPI version is not 11.0.x:

```bash
# Check actual version
docker exec glpi11-app cat /var/www/html/glpi/version.txt

# Update docker-compose.yml to specify exact version
# Rebuild containers
docker-compose -f docker-compose.glpi11-test.yml down
docker-compose -f docker-compose.glpi11-test.yml up -d --build
```

## Multi-Version Testing

To test against multiple GLPI versions, create separate compose files:

```bash
# GLPI 10.0.x
docker-compose -f docker-compose.glpi10-test.yml up -d

# GLPI 11.0.x
docker-compose -f docker-compose.glpi11-test.yml up -d

# Use different ports for each version
# GLPI 10: http://localhost:8080
# GLPI 11: http://localhost:8081
```

## CI/CD Integration

For automated testing in CI/CD pipelines:

```yaml
# .github/workflows/glpi11-test.yml
name: GLPI 11 Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: glpi_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup GLPI 11
        run: |
          wget https://github.com/glpi-project/glpi/releases/download/11.0.0/glpi-11.0.0.tgz
          tar -xzf glpi-11.0.0.tgz
          
      - name: Install Plugin
        run: |
          cp -r . glpi/plugins/costs
          
      - name: Run Tests
        run: |
          # Add test commands here
          echo "Tests would run here"
```

## Reference Information

### GLPI 11 System Requirements

- PHP 8.1 or 8.2
- MySQL 8.0+ or MariaDB 10.5+
- Required PHP extensions:
  - curl, fileinfo, gd, intl, json, mbstring, mysqli, session, zlib, simplexml, xml, cli, domxml, iconv, ldap, openssl, xmlrpc, APCu

### Useful GLPI CLI Commands

```bash
# Check system requirements
php bin/console glpi:system:check_requirements

# Database status
php bin/console glpi:database:check

# Install plugin via CLI
php bin/console glpi:plugin:install costs

# Enable plugin via CLI
php bin/console glpi:plugin:activate costs
```

### Database Connection Details

- **Host**: localhost (or mysql for Docker)
- **Port**: 3306 (or 3307 for Docker host)
- **Database**: glpi11_test
- **User**: glpi_user
- **Password**: glpi_pass

## Next Steps

After environment setup:

1. Proceed to Task 3: Test plugin installation on GLPI 11
2. Run functional tests (Tasks 4-11)
3. Test backward compatibility with GLPI 10 (Task 13)
4. Document any issues or compatibility concerns

## Maintenance

### Updating GLPI Version

```bash
# Stop containers
docker-compose -f docker-compose.glpi11-test.yml stop

# Update GLPI_VERSION in docker-compose.yml
# Example: GLPI_VERSION: "11.0.1"

# Restart with new version
docker-compose -f docker-compose.glpi11-test.yml up -d --force-recreate
```

### Backup Test Data

```bash
# Backup database
docker exec glpi11-mysql mysqldump -u glpi_user -pglpi_pass glpi11_test > glpi11_backup.sql

# Restore database
docker exec -i glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test < glpi11_backup.sql
```

---

**Document Version**: 1.0  
**Last Updated**: 2026-01-30  
**Related Requirements**: 12.1  
**Related Tasks**: Task 2 (Setup), Tasks 3-15 (Testing)
