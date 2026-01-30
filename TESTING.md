# GLPI 11 Testing Guide

Quick reference for testing the Costs plugin with GLPI 11.

## Quick Start

### Using Docker (Recommended)

```bash
# Run the automated setup script
./scripts/setup-glpi11-test.sh

# Access GLPI at http://localhost:8081
# Default credentials: glpi/glpi or tech/tech
```

### Manual Docker Setup

```bash
# Start the environment
docker-compose -f docker-compose.glpi11-test.yml up -d

# Wait for services to start (60-90 seconds)
sleep 90

# Access GLPI at http://localhost:8081
```

## Environment Management

```bash
# View logs
docker-compose -f docker-compose.glpi11-test.yml logs -f

# Stop environment (preserves data)
docker-compose -f docker-compose.glpi11-test.yml stop

# Start stopped environment
docker-compose -f docker-compose.glpi11-test.yml start

# Remove environment (deletes all data)
docker-compose -f docker-compose.glpi11-test.yml down -v
```

## Plugin Installation

1. Access GLPI web interface: http://localhost:8081
2. Login with admin credentials (glpi/glpi)
3. Navigate to: **Setup > Plugins**
4. Find "Costs" plugin in the list
5. Click **Install** button
6. Click **Enable** button

## Testing Checklist

### Basic Functionality

- [ ] Plugin installs without errors
- [ ] Plugin enables successfully
- [ ] Configuration page loads (Setup > General > Costs)
- [ ] Entity costs tab appears (Administration > Entities > Root Entity > Costs)
- [ ] Billable dropdown appears on ticket form
- [ ] Cost entries are generated for billable tickets
- [ ] Task costs are calculated correctly

### Database Verification

```bash
# Connect to database
docker exec -it glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test

# Check plugin tables
SHOW TABLES LIKE 'glpi_plugin_costs%';

# View cost entries
SELECT * FROM glpi_plugin_costs_tickets;
SELECT * FROM glpi_plugin_costs_tasks;
```

### Test Scenarios

#### Scenario 1: Configure Entity Costs

1. Navigate to: Administration > Entities > Root Entity > Costs tab
2. Set Fixed cost: 10.00
3. Set Time cost: 50.00
4. Enable "Billable by default"
5. Save configuration

#### Scenario 2: Create Billable Ticket

1. Navigate to: Assistance > Tickets > Create ticket
2. Fill in required fields
3. Set "Billable" to "Yes"
4. Save ticket
5. Verify cost entry in database

#### Scenario 3: Add Task with Time Cost

1. Open a billable ticket
2. Add a new task
3. Set duration to 1 hour
4. Save task
5. Verify time-based cost is calculated (should be 50.00)

## Troubleshooting

### Plugin Not Appearing

```bash
# Check plugin directory
docker exec glpi11-app ls -la /var/www/html/glpi/plugins/costs/

# Fix permissions if needed
docker exec glpi11-app chown -R www-data:www-data /var/www/html/glpi/plugins/costs
```

### Installation Errors

```bash
# Check GLPI logs
docker exec glpi11-app tail -100 /var/www/html/glpi/files/_log/php-errors.log
docker exec glpi11-app tail -100 /var/www/html/glpi/files/_log/sql-errors.log
```

### Database Connection Issues

```bash
# Test database connection
docker exec glpi11-mysql mysql -u glpi_user -pglpi_pass -e "SELECT 1;"

# Restart MySQL if needed
docker-compose -f docker-compose.glpi11-test.yml restart mysql
```

## Access Information

### Web Interface

- **URL**: http://localhost:8081
- **Admin**: glpi / glpi
- **Technician**: tech / tech

### Database

- **Host**: localhost:3307 (from host machine)
- **Host**: mysql:3306 (from within containers)
- **Database**: glpi11_test
- **User**: glpi_user
- **Password**: glpi_pass

### Container Names

- **GLPI**: glpi11-app
- **MySQL**: glpi11-mysql

## Documentation

For detailed setup instructions and advanced configuration, see:
- [GLPI 11 Testing Environment Setup](.context/docs/glpi-11-testing-environment.md)
- [Implementation Tasks](.kiro/specs/glpi-11-upgrade/tasks.md)
- [Design Document](.kiro/specs/glpi-11-upgrade/design.md)
- [Requirements](.kiro/specs/glpi-11-upgrade/requirements.md)

## CI/CD Integration

For automated testing in CI/CD pipelines, see the CI configuration examples in:
`.context/docs/glpi-11-testing-environment.md`

## Multi-Version Testing

To test against both GLPI 10 and GLPI 11:

```bash
# Create GLPI 10 environment (port 8080)
# Modify docker-compose.glpi11-test.yml and change:
# - GLPI_VERSION to "10.0.15"
# - Port to "8080:80"
# - Container names to glpi10-*

# Run both environments simultaneously
docker-compose -f docker-compose.glpi10-test.yml up -d
docker-compose -f docker-compose.glpi11-test.yml up -d
```

## Support

For issues or questions:
- Check logs: `docker-compose -f docker-compose.glpi11-test.yml logs -f`
- Review documentation: `.context/docs/glpi-11-testing-environment.md`
- Check GLPI documentation: https://glpi-project.org/documentation/
