# PHP Logs to Grafana POC

This is a proof of concept demonstrating a complete logging pipeline:
1. PHP application generates logs in a Docker container
2. Grafana Alloy collects logs via Docker log driver
3. Logs are sent to Loki for storage
4. Grafana dashboard visualizes the logs

## Architecture

```
PHP App (Container) → Docker Logs → Grafana Alloy → Loki → Grafana Dashboard
```

## Components

- **PHP App**: Simple PHP application that generates various types of logs (INFO, WARNING, ERROR, DEBUG)
- **Grafana Alloy**: Collects logs from Docker containers and forwards them to Loki
- **Loki**: Log aggregation system for storing and indexing logs
- **Grafana**: Visualization platform with pre-configured dashboard

## Quick Start

1. Start all services:
```bash
docker-compose up -d
```

2. Wait for all services to be ready (about 30-60 seconds)

3. Access the services:
   - **Grafana**: http://localhost:3000 (admin/admin)
   - **Alloy UI**: http://localhost:12345
   - **Loki**: http://localhost:3100

4. View logs in Grafana:
   - Go to http://localhost:3000
   - Login with admin/admin
   - Navigate to "PHP Application Logs Dashboard"

## Dashboard Features

The pre-configured dashboard includes:
- **Log Levels Distribution**: Pie chart showing distribution of log levels
- **Log Rate by Level**: Time series showing log rates over time
- **PHP Application Logs**: Real-time log viewer with all logs
- **Top Users by Activity**: Table showing most active users
- **Error Logs Only**: Filtered view showing only error logs

## Log Format

The PHP application generates structured logs with:
- Timestamp
- Log level (INFO, WARNING, ERROR, DEBUG)
- Message
- Context data (user, action, IP, etc.)

Example log entry:
```
[2024-01-15 10:30:45] INFO: User activity: login {"user":"alice","action":"login","page":"/home","ip":"192.168.1.100","user_agent":"Mozilla/5.0 (compatible; TestBot/1.0)"}
```

## Stopping the Stack

```bash
docker-compose down
```

To remove all data:
```bash
docker-compose down -v
```

## Troubleshooting

1. **No logs appearing**: Wait a few minutes for the PHP app to generate logs and for Alloy to collect them
2. **Grafana dashboard not loading**: Ensure Loki is running and accessible at http://loki:3100
3. **Alloy not collecting logs**: Check Alloy logs with `docker logs alloy`

## File Structure

```
.
├── docker-compose.yml              # Main orchestration file
├── php-app/
│   ├── Dockerfile                  # PHP app container
│   └── app.php                     # Log generating application
├── alloy/
│   └── config.alloy               # Alloy configuration
├── loki/
│   └── loki-config.yml            # Loki configuration
└── grafana/
    └── provisioning/
        ├── datasources/
        │   └── loki.yml           # Loki datasource config
        └── dashboards/
            ├── dashboard.yml       # Dashboard provider config
            └── php-logs-dashboard.json  # Pre-built dashboard
