# ECS Deployment Guide for PHP Logs to Grafana POC

## Key Differences: Docker Compose vs ECS

### 1. **Log Collection Method**

#### Docker Compose (Current POC)
```alloy
// Direct Docker socket access
loki.source.docker "docker_logs" {
  host       = "unix:///var/run/docker.sock"
  targets    = discovery.docker.containers.targets
  forward_to = [loki.process.parse_logs.receiver]
}
```

#### ECS Deployment
```alloy
// Option A: CloudWatch Logs (Recommended)
loki.source.cloudwatch_logs "ecs_logs" {
  region     = "us-east-1"
  log_groups = [
    {
      log_group_name = "/ecs/php-app"
      log_stream_name_prefix = "ecs/"
    }
  ]
  forward_to = [loki.process.parse_ecs_logs.receiver]
}

// Option B: ECS Service Discovery
discovery.ecs "ecs_tasks" {
  region = "us-east-1"
  port   = 80
}
```

### 2. **Authentication & Permissions**

#### Docker Compose
- No authentication needed (local environment)
- Direct socket access

#### ECS Deployment
- **AWS IAM Role/Credentials required**:
  ```json
  {
    "Version": "2012-10-17",
    "Statement": [
      {
        "Effect": "Allow",
        "Action": [
          "ecs:ListTasks",
          "ecs:DescribeTasks",
          "ecs:DescribeServices",
          "ecs:ListServices",
          "logs:DescribeLogGroups",
          "logs:DescribeLogStreams",
          "logs:GetLogEvents"
        ],
        "Resource": "*"
      }
    ]
  }
  ```

### 3. **Network Configuration**

#### Docker Compose
```yaml
networks:
  logging-network:
    driver: bridge
```

#### ECS
- **VPC Configuration**: Alloy needs network access to:
  - AWS APIs (ECS, CloudWatch)
  - Loki endpoint (could be Grafana Cloud or self-hosted)
- **Security Groups**: Allow outbound HTTPS (443) and Loki port
- **Service Discovery**: Use AWS Service Discovery for internal communication

### 4. **Environment Variables**

#### ECS Task Definition Example
```json
{
  "family": "alloy-task",
  "taskRoleArn": "arn:aws:iam::ACCOUNT:role/AlloyTaskRole",
  "executionRoleArn": "arn:aws:iam::ACCOUNT:role/ecsTaskExecutionRole",
  "containerDefinitions": [
    {
      "name": "alloy",
      "image": "grafana/alloy:latest",
      "environment": [
        {
          "name": "AWS_REGION",
          "value": "us-east-1"
        },
        {
          "name": "LOKI_ENDPOINT",
          "value": "https://your-loki-instance.com/loki/api/v1/push"
        }
      ],
      "secrets": [
        {
          "name": "GRAFANA_CLOUD_API_KEY",
          "valueFrom": "arn:aws:secretsmanager:us-east-1:ACCOUNT:secret:grafana-api-key"
        }
      ]
    }
  ]
}
```

### 5. **PHP App Configuration**

#### ECS Task Definition for PHP App
```json
{
  "family": "php-app-task",
  "containerDefinitions": [
    {
      "name": "php-app",
      "image": "your-account.dkr.ecr.us-east-1.amazonaws.com/php-app:latest",
      "logConfiguration": {
        "logDriver": "awslogs",
        "options": {
          "awslogs-group": "/ecs/php-app",
          "awslogs-region": "us-east-1",
          "awslogs-stream-prefix": "ecs"
        }
      }
    }
  ]
}
```

## Deployment Options

### Option 1: CloudWatch Logs (Recommended)
**Pros:**
- Native AWS integration
- Automatic log collection
- No direct container access needed
- Built-in retention policies

**Cons:**
- Additional AWS costs
- Slight delay in log delivery

### Option 2: Sidecar Pattern
**Pros:**
- Real-time log collection
- Direct container access
- Lower latency

**Cons:**
- More complex configuration
- Requires shared volumes
- Higher resource usage

### Option 3: Grafana Cloud
**Pros:**
- Fully managed
- No infrastructure to maintain
- Built-in alerting and dashboards

**Cons:**
- Ongoing costs
- Data egress charges

## Migration Steps

1. **Containerize and Push to ECR**
   ```bash
   # Build and push PHP app
   docker build -t php-app ./php-app
   docker tag php-app:latest ACCOUNT.dkr.ecr.REGION.amazonaws.com/php-app:latest
   docker push ACCOUNT.dkr.ecr.REGION.amazonaws.com/php-app:latest
   ```

2. **Create IAM Roles**
   - Task execution role for ECR access
   - Task role for AWS API access

3. **Set up CloudWatch Log Groups**
   ```bash
   aws logs create-log-group --log-group-name /ecs/php-app
   aws logs create-log-group --log-group-name /ecs/alloy
   ```

4. **Deploy ECS Services**
   - Create task definitions
   - Create ECS services
   - Configure load balancers if needed

5. **Configure Alloy**
   - Use the ECS-specific configuration
   - Set up environment variables
   - Configure remote Loki endpoint

## Cost Considerations

- **CloudWatch Logs**: ~$0.50 per GB ingested + $0.03 per GB stored
- **ECS Tasks**: Based on CPU/memory allocation
- **Data Transfer**: Egress charges for sending to external Loki
- **Grafana Cloud**: Variable pricing based on usage

## Monitoring & Troubleshooting

- Use CloudWatch Container Insights for ECS monitoring
- Check Alloy logs in CloudWatch
- Monitor ECS service health
- Set up alerts for failed log delivery
