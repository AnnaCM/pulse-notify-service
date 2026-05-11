# Pulse Notify Service

A notification system built with Laravel that supports email, SMS, and push notifications. It handles single and batch processing, queue-based delivery, rate limiting, retries, and system health checks.

The project is fully containerised using Docker for easy and consistent local setup.

---

## Table of Contents

- [Features](#features)  
- [Architecture & Design Decisions](#architecture--design-decisions)  
- [Technology Stack](#technology-stack)  
- [Installation](#installation)  
- [Configuration](#configuration)  
- [Running the Project](#running-the-project)  
- [Testing](#testing)  
- [API Documentation](#api-documentation)  
- [Error Handling](#error-handling)

---

## Features

- Send notifications via email, SMS, and push
- Batch notification processing
- Queue-based asynchronous processing
- Priority queues (high, normal, low)
- Rate limiting per channel
- Retry handling for failed deliveries
- System health checks (DB, Redis, queue)
- Metrics endpoint for monitoring system state
- Cursor-based pagination for large datasets
- Idempotency support for safe retries and duplicate prevention

---

## Architecture & Design Decisions

### Layered Structure

- **Controllers**  
  Handle HTTP requests and responses. Delegate business logic to use cases.

- **Requests**  
  Validate incoming payloads and query parameters before reaching the business layer.

- **DTOs (Data Transfer Objects)**  
  Encapsulate validated input data and pass it from controllers to use cases in a structured way.

- **Use Cases**  
  Contain business logic and application rules. Keep controllers thin and focused.

- **Jobs**  
  Handle asynchronous processing using queues (e.g. sending notifications).

- **Services**  
  Manage external integrations and shared logic (e.g. notification providers, rate limiting).

- **Models**  
  Represent database entities and handle persistence.

- **Resources**  
  Format API responses into a consistent JSON structure.

- **Enums**  
  Define allowed values (status, priority, channel) to ensure consistency across the system.

- **Custom Exceptions**  
  Represent domain-specific errors and map them to appropriate HTTP responses.

### Key Design Choices

- Separation of concerns for maintainability and testability
- Queue-based processing to avoid blocking requests
- Rate limiting per channel to protect external providers
- Idempotency keys to avoid duplicate processing
- Cursor pagination for efficient large dataset

---

## Technology Stack

- PHP 8.4.20
- Laravel 13.7.0
- MySQL 8.0.46
- Redis 7.4.8
- Docker & Docker Compose
- PHPUnit

---

## Installation

### 1. Clone the repository

```bash
git clone git@github.com:AnnaCM/pulse-notify-service.git
cd pulse-notify-service
```

### 2. Start Docker

```bash
docker-compose up -d --build
```

This will start:

- Laravel application (PHP-FPM)
- MySQL database
- Redis
- Queue worker

### 3. Install dependencies (if required)

```bash
docker-compose exec app composer install
```

## Configuration

### 1. Create environment file

```bash
cp .env.example .env
```

### 2. Configure Webhook

This project uses webhook.site as a mock external provider.

Steps:

1. Go to [https://webhook.site/](https://webhook.site/)
2. Copy your unique URL
3. Paste it into your .env file:
```bash
WEBHOOK_URL=https://webhook.site/your-unique-url
```


### 3. Configure Mock Response

In webhook.site, configure a custom response with:

- Status code: `202`
- Response body:

```json
{
  "messageId": "uuid-here",
  "status": "accepted",
  "timestamp": "ISO8601"
}
```

This allows inspection of outgoing notification requests while simulating a successful external provider response.

## Running the Project

### 1. Generate application key

```bash
docker-compose exec app php artisan key:generate
```

### 2. Run migrations

```bash
docker-compose exec app php artisan migrate
```

Reset database:
```bash
docker-compose exec app php artisan migrate:fresh
```

### 3. Start the application

```bash
docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000
```

API available at:
```bash
http://localhost:8000
```

## Testing

Run all tests:
```bash
php artisan test
```

### Testing approach

- Feature tests for API endpoints
- Unit tests for use cases and services
- Mocking Redis, queues, and external services
- Database factories for notifications
- Error scenario coverage (DB down, Redis down, invalid payloads)
- Logging and time-based assertions

## API Documentation

This project includes an OpenAPI specification describing all endpoints, request/response formats, validation rules, and error responses.
The file is located at: [docs/openapi.yaml](docs/openapi.yaml).

### Notifications
- **POST `/api/notifications`** → Create a notification  
- **GET `/api/notifications`** → List notifications (filters + pagination)  
- **GET `/api/notifications/{id}`** → Get notification by ID  
- **POST `/api/notifications/{id}/cancel`** → Cancel notification  

### Batch Notifications
- **POST `/api/notifications/batch`** → Create batch  
- **GET `/api/notifications/batch/{batchId}`** → Get batch notifications 
- **POST `/api/notifications/batch/{batchId}/cancel`** → Cancel batch  

### System
- **GET `/api/metrics`** → System metrics  
- **GET `/api/health-check`** → System health (DB, Redis, Queue)

## Error Handling

The API uses standard HTTP status codes to indicate success or failure.

### 400 Bad Request

Returned when a request is valid but violates business rules.
Example:
- Cancelling a notification that has already been processed

### 404 Not Found

Returned when a resource does not exist.
Example:
- Notification not found
- Batch not found

### 422 Unprocessable Entity

Returned when request validation fails.
Example:
- Missing required fields
- Invalid `channel` or `priority` values
- Invalid query parameters

### Error Response Format

All error responses follow a consistent JSON structure:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message"
    ]
  }
}
```
- `message`: Human-readable error description
- `errors`: Optional field-level validation errors

## Notes

This project was designed with:

- Clean architecture principles
- Testability in mind
- Real-world queue processing patterns
- Extensibility for additional providers
- Production-ready logging and monitoring structure

## License

This project is licensed under the terms of the [MIT license](LICENSE.md).
