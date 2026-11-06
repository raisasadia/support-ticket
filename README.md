# SupportDesk

SupportDesk is a Laravel-based support ticket and SLA management system. It provides separate workflows for administrators, support agents, and customers, including ticket conversations, assignment, attachments, SLA tracking, CSV import, and an operational dashboard.

## Features

- Customer ticket creation, editing, replies, and attachments
- Administrator and agent ticket queues
- Roles: Administrator, Support Agent, and Customer
- Server-side ticket visibility and action authorization
- Manual assignment, automatic assignment, and agent claiming
- Least-open-ticket automatic assignment strategy
- Database transaction and row-lock protection for concurrent assignment
- Ticket statuses: Open, In Progress, Waiting for Customer, Resolved, and Closed
- Internal notes hidden from customers
- Protected attachment downloads with file validation
- Configurable priority-based SLA rules and business hours
- SLA approaching/breached escalation command
- Queued email notifications
- Client-side ticket filtering and pagination-free dashboard-style queue
- CSV import with queued processing, duplicate detection, invalid-row reporting, and progress status
- Administrator dashboard with ticket KPIs, priority distribution, and agent workload
- Responsive Helpdesk landing page and authenticated sidebar layout

## Requirements

- PHP 8.3+
- Composer 2+
- MariaDB 10.11+ or MySQL 8+
- Node.js and npm
- XAMPP users should enable the PHP `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, and `zip` extensions.

## Local installation

From the project directory:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
```

For Linux/macOS, use:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

## Database setup

Create an empty database, for example:

```sql
CREATE DATABASE support_ticket_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Configure `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=support_ticket_db
DB_USERNAME=root
DB_PASSWORD=
```

The complete MariaDB schema is available in [database/schema.sql](database/schema.sql). Import it with Adminer/phpMyAdmin or:

```powershell
Get-Content database\schema.sql | mysql -u root -p support_ticket_db
```

```bash
mysql -u root -p support_ticket_db < database/schema.sql
```

The SQL file creates the application tables, Laravel cache/queue/session tables, indexes, foreign keys, default roles, and default SLA rules. It is the recommended setup path for this assessment because the ticket module schema is maintained as SQL rather than additional application migrations.

Default SLA rules:

| Priority | Resolution time | Business days | Business hours |
|---|---:|---|---|
| Low | 48 hours | Sunday–Thursday | 09:00–18:00 |
| Medium | 24 hours | Sunday–Thursday | 09:00–18:00 |
| High | 8 hours | Sunday–Thursday | 09:00–18:00 |
| Critical | 2 hours | Sunday–Thursday | 09:00–18:00 |

To count every day, including Friday and Saturday, update the rules:

```sql
UPDATE sla_rules
SET business_days = '0,1,2,3,4,5,6';
```

Carbon day values are Sunday `0` through Saturday `6`.

## Environment and mail configuration

The local `.env.example` uses:

```dotenv
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
MAIL_MAILER=log
```

`MAIL_MAILER=log` writes notification messages to `storage/logs/laravel.log`. Configure SMTP in `.env` when real email delivery is required.

Create the storage link only if public storage files are added later:

```bash
php artisan storage:link
```

Ticket attachments remain on the private local disk and are served through an authorized download controller.

## Create an administrator

Register a user through `/register`, then promote the account:

```bash
php artisan users:make-admin admin@example.com
```

The command also activates the account. The SQL schema includes the `admin`, `agent`, and `customer` roles.

## Running the application

Start the Laravel development server:

```bash
php artisan serve
```

Start the frontend watcher during UI development:

```bash
npm run dev
```

Open `http://127.0.0.1:8000`.

## Queue and scheduler

CSV imports and ticket email notifications are queued. Run a worker continuously:

```bash
php artisan queue:work
```

The database queue requires the `jobs`, `job_batches`, and `failed_jobs` tables included in [database/schema.sql](database/schema.sql).

SLA escalation runs hourly through the Laravel scheduler:

```bash
php artisan schedule:work
```

The scheduled command is:

```bash
php artisan tickets:escalate
```

It marks approaching/breached active tickets, creates an activity entry, and queues a notification to the assigned agent or ticket creator.

For production, run `queue:work` under a process manager and invoke `php artisan schedule:run` every minute through the operating system scheduler.

## CSV import

Administrators and agents can upload CSV files from the Tickets page. Required columns:

```text
subject,description,priority
```

Optional columns:

```text
customer_email,category,status,assigned_agent_email,created_at
```

Accepted priority values are `low`, `medium`, `high`, and `critical` in any letter case. Accepted statuses include `Open`, `In Progress`, `Waiting for Customer`, `Resolved`, and `Closed`.

The import is streamed by a queued job. It reports progress, invalid rows, duplicate rows, and failures in the Tickets UI. Keep `php artisan queue:work` running while importing.

## Testing

Run the complete test suite:

```bash
php artisan test
```

Or:

```bash
composer test
```

The current suite covers Laravel authentication/profile behavior. Ticket-specific tests should be expanded for assignment, SLA boundaries, authorization, imports, and escalation before production deployment.

## Architecture and important decisions

### Application structure

Ticket-specific code is organized under `App\Modules\TicketManagement`:

- `Controllers`: HTTP request orchestration and authorization checks
- `Models`: ticket, reply, activity, attachment, and SLA relationships
- `Services`: SLA deadline calculation and assignment strategy
- `Jobs`: long-running CSV processing
- `Commands`: scheduled SLA escalation and administrator promotion
- `Notifications`: queued ticket event email notifications

User administration is organized under `App\Modules\UserManagement`.

### Authorization

Authorization is enforced on the server. Customers are limited to their own tickets and cannot view internal notes. Agents can work their assigned/unassigned queue, while administrators have full operational and user-management access. The active-account middleware logs deactivated users out.

### Assignment

New tickets use automatic assignment to the active agent with the fewest open tickets. Manual assignment, administrator auto-assignment, and agent claiming are also available. Assignment updates use transactions, `lockForUpdate`, and a conditional update to prevent two concurrent agents from claiming the same ticket.

### SLA calculation

SLA rules are stored in `sla_rules`, not hardcoded in controllers. `SlaDeadlineCalculator` consumes the configured resolution minutes, business days, and business hours, skipping non-working periods when calculating `due_at`.

### Import processing

CSV files are stored privately and processed by `ImportTicketsJob` using a stream rather than loading the entire file into memory. Each valid row is inserted in a transaction. Duplicate and invalid rows are reported without stopping valid rows from importing.

### Filtering

The ticket list loads only records permitted by the server-side `visibleTo` scope. Search and operational filters are then applied in the browser with JavaScript for immediate interaction without repeated filter requests.

### Database and performance

The schema includes indexes for ticket status/priority, assignment/status, customer/date, due dates, replies, activities, and foreign keys. Ticket detail pages eager-load related creator, agent, replies, activities, and attachments to avoid common N+1 queries.

## Useful commands

```bash
php artisan route:list
php artisan migrate:status
php artisan queue:failed
php artisan queue:retry all
php artisan tickets:escalate
php artisan users:make-admin admin@example.com
php artisan view:cache
npm run build
```

## Security notes

- Do not commit `.env` or credentials.
- Use a non-default `APP_KEY`.
- Configure a real mail provider only through environment variables.
- Keep ticket attachments on a private disk.
- Use HTTPS and a process manager for production queue workers.
- Review upload limits and allowed MIME types before exposing the application publicly.
