# Agent Desk API Documentation

## Authentication

All API endpoints require authentication using Laravel Sanctum tokens.

### Creating an API Token

```bash
# In tinker
$token = User::first()->createToken('api-token')->plainTextToken;
// Copy the token
```

### Including Token in Requests

**Header:**
```
Authorization: Bearer {token}
```

**Options:**
- cURL: `curl -H "Authorization: Bearer {token}" ...`
- JavaScript: `fetch(url, { headers: { 'Authorization': 'Bearer ' + token } })`

## Base URL

```
http://localhost:8000/api
```

## Endpoints

### Agents

#### List All Agents
```
GET /api/agents
```

**Query Parameters:**
- `page` (default: 1)
- `per_page` (default: 15)
- `search` (optional)
- `template_id` (optional)
- `is_active` (optional, boolean)

**Response:**
```json
{
  "data": [...],
  "current_page": 1,
  "per_page": 15,
  "total": 2
}
```

#### Get Single Agent
```
GET /api/agents/{id}
```

**Response:**
```json
{
  "id": "...",
  "user_id": "...",
  "template_id": "...",
  "name": "...",
  "description": "...",
  "agent_config": {...},
  "env_variables": {...},
  "config_template": {...},
  "is_active": true,
  "created_at": "...",
  "updated_at": "...",
  "user": {...},
  "template": {...},
  "executions": [...]
}
```

#### Create Agent
```
POST /api/agents
```

**Request Body:**
```json
{
  "template_id": "...",
  "name": "My Agent",
  "description": "Agent description",
  "agent_config": {
    "model": "gpt-4",
    "temperature": 0.7
  },
  "env_variables": {
    "OPENAI_API_KEY": "..."
  },
  "config_template": {
    "workspace_path": "/tmp"
  },
  "is_active": true
}
```

**Response:** 201 Created

#### Update Agent
```
PATCH /api/agents/{id}
```

#### Delete Agent
```
DELETE /api/agents/{id}
```

#### Get Available Templates
```
GET /api/agents/templates
```

**Response:**
```json
[
  {
    "id": "...",
    "name": "Template Name",
    "description": "...",
    ...
  }
]
```

#### Run Agent
```
POST /api/agents/{id}/run
```

**Request Body:**
```json
{
  "input": {
    "prompt": "Execute task",
    "context": {}
  },
  "context": {}
}
```

**Response:** 202 Accepted
```json
{
  "message": "Execution started",
  "execution": {
    "id": "...",
    "status": "pending",
    "progress": 0,
    ...
  }
}
```

### Agent Templates

#### List All Templates
```
GET /api/templates
```

**Query Parameters:**
- `page` (default: 1)
- `per_page` (default: 15)
- `search` (optional)
- `category` (optional)

#### Get Single Template
```
GET /api/templates/{id}
```

#### Create Template
```
POST /api/templates
```

**Request Body:**
```json
{
  "name": "My Template",
  "description": "...",
  "system_prompt": "AGENT.md content",
  "user_context": "USER.md content",
  "config_defaults": {...},
  "env_defaults": {...},
  "tool_definitions": [...]
}
```

#### Update Template
```
PATCH /api/templates/{id}
```

#### Delete Template
```
DELETE /api/templates/{id}
```

#### Import Template
```
POST /api/templates/import
```

**Request Body:**
```json
{
  "file": "path/to/template.json"
}
```

#### Export Template
```
GET /api/templates/{id}/export
```

### Executions

#### List All Executions
```
GET /api/executions
```

**Query Parameters:**
- `page` (default: 1)
- `per_page` (default: 15)
- `agent_id` (optional)
- `user_id` (optional)
- `status` (optional)
- `started_after` (optional, ISO date)
- `started_before` (optional, ISO date)

#### Get Single Execution
```
GET /api/executions/{id}
```

#### Get Execution Logs
```
GET /api/executions/{id}/logs
```

**Response:** File download (execution.log)

#### Get Execution Results
```
GET /api/executions/{id}/results
```

**Response:**
```json
{
  "status": "success",
  "output": "..."
}
```

#### Cancel Execution
```
POST /api/executions/{id}/cancel
```

**Response:**
```json
{
  "message": "Execution cancelled successfully",
  "execution": {...}
}
```

#### Restart Execution
```
POST /api/executions/{id}/restart
```

**Response:**
```json
{
  "message": "Execution restarted successfully",
  "execution": {...}
}
```

### Webhooks

#### Receive Webhook from Pod
```
POST /api/webhooks/pods
```

**Request Body:**
```json
{
  "execution_id": "...",
  "status": "running|completed|failed|cancelled",
  "progress": 0-100,
  "logs": "Log content",
  "result": {...},
  "artifacts": [
    {
      "name": "file.py",
      "type": "code",
      "language": "python",
      "content": "..."
    }
  ],
  "error": "...",
  "started_at": "2024-01-01T00:00:00Z"
}
```

**Response:**
```json
{
  "message": "Webhook processed successfully"
}
```

## Webhook Payload

Pods should send status updates to the webhook endpoint:
- `running` - Execution is in progress
- `completed` - Execution finished successfully
- `failed` - Execution failed
- `cancelled` - Execution was cancelled

### Artifact Types

```json
{
  "type": "code",
  "language": "python",
  "content": "def example(): ..."
}
```

## Error Responses

All endpoints return appropriate HTTP status codes:

- `200 OK` - Successful GET
- `201 Created` - Successful POST/PUT
- `202 Accepted` - Task started (run/restart)
- `204 No Content` - Successful DELETE
- `400 Bad Request` - Validation error
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `500 Internal Server Error` - Server error

### Error Response Format

```json
{
  "error": "Error message",
  "details": {
    "field": ["Error message"]
  }
}
```
