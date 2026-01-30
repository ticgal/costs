# GLPI 11 Testing - Quick Reference Card

## One-Line Setup

```bash
./scripts/setup-glpi11-test.sh
```

## Essential Commands

```bash
# Start environment
docker-compose -f docker-compose.glpi11-test.yml up -d

# Stop environment (keep data)
docker-compose -f docker-compose.glpi11-test.yml stop

# Remove environment (delete data)
docker-compose -f docker-compose.glpi11-test.yml down -v

# View logs
docker-compose -f docker-compose.glpi11-test.yml logs -f

# Verify setup
./scripts/verify-glpi11-setup.sh
```

## Access Points

| Service | URL/Connection | Credentials |
|---------|---------------|-------------|
| GLPI Web | http://localhost:8081 | glpi/glpi or tech/tech |
| MySQL (host) | localhost:3307 | glpi_user/glpi_pass |
| MySQL (container) | mysql:3306 | glpi_user/glpi_pass |
| Database | glpi11_test | - |

## Container Names

- **GLPI**: `glpi11-app`
- **MySQL**: `glpi11-mysql`

## Quick Tests

### Check Plugin Status
```bash
docker exec glpi11-app ls -la /var/www/html/glpi/plugins/costs/
```

### Check Database Tables
```bash
docker exec -it glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test \
  -e "SHOW TABLES LIKE 'glpi_plugin_costs%';"
```

### View Plugin Version
```bash
docker exec glpi11-app grep PLUGIN_COSTS_VERSION \
  /var/www/html/glpi/plugins/costs/setup.php
```

### Check GLPI Logs
```bash
docker exec glpi11-app tail -50 /var/www/html/glpi/files/_log/php-errors.log
```

## Plugin Installation Steps

1. Open http://localhost:8081
2. Login: glpi/glpi
3. Setup > Plugins
4. Find "Costs" → Install → Enable

## Test Workflow

1. **Configure Entity Costs**
   - Administration > Entities > Root Entity > Costs tab
   - Set: Fixed cost = 10.00, Time cost = 50.00

2. **Create Billable Ticket**
   - Assistance > Tickets > Create ticket
   - Set: Billable = Yes

3. **Add Task**
   - Open ticket > Add task
   - Set: Duration = 1 hour
   - Expected cost: 50.00

4. **Verify in Database**
   ```bash
   docker exec -it glpi11-mysql mysql -u glpi_user -pglpi_pass glpi11_test \
     -e "SELECT * FROM glpi_plugin_costs_tickets; SELECT * FROM glpi_plugin_costs_tasks;"
   ```

## Troubleshooting

### Plugin Not Visible
```bash
docker exec glpi11-app chown -R www-data:www-data /var/www/html/glpi/plugins/costs
```

### Container Won't Start
```bash
docker-compose -f docker-compose.glpi11-test.yml down -v
docker-compose -f docker-compose.glpi11-test.yml up -d
```

### Database Connection Failed
```bash
docker-compose -f docker-compose.glpi11-test.yml restart mysql
sleep 10
docker-compose -f docker-compose.glpi11-test.yml restart glpi
```

## Documentation Links

- Full Setup Guide: `.context/docs/glpi-11-testing-environment.md`
- Testing Guide: `TESTING.md`
- Requirements: `.kiro/specs/glpi-11-upgrade/requirements.md`
- Design: `.kiro/specs/glpi-11-upgrade/design.md`
- Tasks: `.kiro/specs/glpi-11-upgrade/tasks.md`

## Version Information

- **Plugin Version**: 3.1.0
- **Min GLPI**: 10.0
- **Max GLPI**: 12.0 (supports all 11.x)
- **Target GLPI**: 11.0.x

## Next Steps After Setup

1. ✓ Environment running
2. → Install plugin via web UI
3. → Run Task 3: Test plugin installation
4. → Run Tasks 4-11: Functional tests
5. → Run Task 13: Backward compatibility tests
