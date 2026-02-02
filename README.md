# EventConnect
A custom Drupal 10 module that allows users to register for events via custom forms, stores registrations in the database, and sends email notifications.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [URLs and Routes](#urls-and-routes)
- [Database Tables](#database-tables)
- [Configuration](#configuration)
- [Validation Logic](#validation-logic)
- [Email Notifications](#email-notifications)
- [Permissions](#permissions)
- [Technical Details](#technical-details)

## Features

- **Event Configuration Form**: Admin interface to create and manage events with registration periods
- **Event Registration Form**: Public form for users to register for events with AJAX-powered dropdowns
- **Admin Listing Page**: View all registrations with filtering and CSV export
- **Email Notifications**: Automated confirmation emails to users and admin notifications
- **Duplicate Prevention**: Prevents users from registering twice for the same event
- **Input Validation**: Comprehensive validation including special character restrictions

## Installation

### Prerequisites

- Drupal 10.x installed and configured
- PHP 8.1 or higher
- MySQL/MariaDB database

### Steps

1. **Copy the module files**
   
   Copy the `event_registration` folder to your Drupal installation:
   ```
   /web/modules/custom/event_registration/
   ```

2. **Import the database schema** (Optional - Drupal will create tables automatically)
   
   If you want to manually create the tables, import the SQL file:
   ```bash
   mysql -u username -p database_name < event_registration_schema.sql
   ```

3. **Enable the module**
   
   Via Drush:
   ```bash
   drush en event_registration -y
   ```
   
   Or via the Drupal admin interface:
   - Navigate to **Extend** (`/admin/modules`)
   - Search for "Event Registration"
   - Check the checkbox and click "Install"

4. **Clear cache**
   ```bash
   drush cr
   ```

5. **Configure permissions**
   - Navigate to **People > Permissions** (`/admin/people/permissions`)
   - Assign the following permissions as needed:
     - "Administer Event Registration" - for admins who manage events
     - "View Event Registrations" - for users who can view registration lists

6. **Configure email settings**
   - Navigate to **Configuration > Event Registration > Settings**
   - Enter the admin email address
   - Enable/disable admin notifications

## URLs and Routes

### Public Pages

| Page | URL | Description |
|------|-----|-------------|
| Event Registration Form | `/event-registration` | Public form for users to register for events |

### Admin Pages

| Page | URL | Description |
|------|-----|-------------|
| Settings | `/admin/config/event-registration/settings` | Configure admin email and notification settings |
| Event Configuration | `/admin/config/event-registration/event-config` | Create and manage events |
| View Registrations | `/admin/event-registration/registrations` | View all registrations with filters |
| Export CSV | `/admin/event-registration/export-csv` | Download registrations as CSV |

### AJAX Endpoints

| Endpoint | URL | Description |
|----------|-----|-------------|
| Get Event Dates | `/event-registration/ajax/get-event-dates` | Returns event dates for a category |
| Get Event Names | `/event-registration/ajax/get-event-names` | Returns events for category and date |
| Admin Get Event Names | `/admin/event-registration/ajax/get-event-names` | Returns events for a date (admin) |
| Filter Registrations | `/admin/event-registration/ajax/filter-registrations` | Returns filtered registrations |

## Database Tables

### event_registration_config

Stores event configuration details.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK) | Auto-increment primary key |
| `registration_start_date` | VARCHAR(20) | Date when registration opens (YYYY-MM-DD) |
| `registration_end_date` | VARCHAR(20) | Date when registration closes (YYYY-MM-DD) |
| `event_date` | VARCHAR(20) | Date of the event (YYYY-MM-DD) |
| `event_name` | VARCHAR(255) | Name of the event |
| `event_category` | VARCHAR(100) | Category (Online Workshop, Hackathon, Conference, One-day Workshop) |
| `created` | INT | Unix timestamp of creation |

### event_registration_submissions

Stores user registration submissions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK) | Auto-increment primary key |
| `full_name` | VARCHAR(255) | Registrant's full name |
| `email` | VARCHAR(255) | Registrant's email address |
| `college_name` | VARCHAR(255) | Registrant's college name |
| `department` | VARCHAR(255) | Registrant's department |
| `event_category` | VARCHAR(100) | Selected event category |
| `event_config_id` | INT (FK) | Foreign key to event_registration_config.id |
| `created` | INT | Unix timestamp of submission |

## Configuration

### Admin Email Settings

Located at `/admin/config/event-registration/settings`:

- **Admin Notification Email Address**: The email address where admin notifications will be sent
- **Enable Admin Notifications**: Toggle to enable/disable admin email notifications

Configuration is stored using Drupal's Config API in `event_registration.settings`.

### Event Configuration

Located at `/admin/config/event-registration/event-config`:

- **Registration Start Date**: When users can start registering
- **Registration End Date**: When registration closes
- **Event Date**: The actual date of the event
- **Event Name**: Name of the event
- **Event Category**: One of:
  - Online Workshop
  - Hackathon
  - Conference
  - One-day Workshop

## Validation Logic

### Text Field Validation

All text fields (Full Name, College Name, Department, Event Name) are validated to prevent special characters:

- **Full Name**: Only letters, spaces, and hyphens allowed (`/^[a-zA-Z\s\-]+$/`)
- **College Name, Department, Event Name**: Letters, numbers, spaces, and hyphens allowed (`/^[a-zA-Z0-9\s\-]+$/`)

### Email Validation

- Standard email format validation using `filter_var()` with `FILTER_VALIDATE_EMAIL`
- Ensures proper email structure (user@domain.tld)

### Date Validation

- Registration end date must be on or after start date
- Event date must be on or after registration start date

### Duplicate Registration Prevention

- Checks for existing registration with the same **Email + Event ID** combination
- Prevents users from registering multiple times for the same event
- Displays user-friendly error message if duplicate detected

### Registration Period Validation

- The registration form only shows events that are currently within their registration period
- Events are filtered based on:
  - `registration_start_date <= today`
  - `registration_end_date >= today`

## Email Notifications

### User Confirmation Email

Sent to the registrant upon successful registration. Contains:

- Personalized greeting with registrant's name
- Event details:
  - Event Name
  - Event Date
  - Category

### Admin Notification Email

Sent to the configured admin email (if enabled). Contains:

- Full registration details:
  - Name
  - Email
  - College
  - Department
  - Event Name
  - Event Date
  - Category

### Email Implementation

Emails are sent using Drupal's Mail API via `hook_mail()` in `event_registration.module`. The `EmailService` class handles the sending logic with proper dependency injection.

## Permissions

| Permission | Machine Name | Description |
|------------|--------------|-------------|
| Administer Event Registration | `administer event registration` | Full access to configure events and settings |
| View Event Registrations | `view event registrations` | Access to view registration list and export |

## Technical Details

### PSR-4 Autoloading

All PHP classes follow PSR-4 autoloading standards:

```
src/
├── Controller/
│   ├── AjaxController.php
│   └── RegistrationListController.php
├── Form/
│   ├── EventConfigForm.php
│   ├── EventRegistrationForm.php
│   └── EventRegistrationSettingsForm.php
└── Service/
    ├── EmailService.php
    ├── EventService.php
    └── RegistrationService.php
```

### Dependency Injection

All services use proper dependency injection via `ContainerInterface`:

- **EventService**: Database connection, Time service
- **RegistrationService**: Database connection, Time service
- **EmailService**: Mail manager, Config factory, Language manager, Logger factory

No use of `\Drupal::service()` in business logic - all dependencies are injected through constructors.

### Services

Services are defined in `event_registration.services.yml`:

- `event_registration.registration_service`: Handles registration CRUD operations
- `event_registration.event_service`: Handles event configuration operations
- `event_registration.email_service`: Handles email notifications

### AJAX Implementation

- Registration form uses Drupal's Form API AJAX callbacks
- Admin listing uses custom JavaScript with jQuery for dynamic filtering
- All AJAX responses return JSON data

## File Structure

```
event_registration/
├── config/
│   ├── install/
│   │   └── event_registration.settings.yml
│   └── schema/
│       └── event_registration.schema.yml
├── css/
│   └── admin-listing.css
├── js/
│   └── admin-listing.js
├── src/
│   ├── Controller/
│   │   ├── AjaxController.php
│   │   └── RegistrationListController.php
│   ├── Form/
│   │   ├── EventConfigForm.php
│   │   ├── EventRegistrationForm.php
│   │   └── EventRegistrationSettingsForm.php
│   └── Service/
│       ├── EmailService.php
│       ├── EventService.php
│       └── RegistrationService.php
├── event_registration.info.yml
├── event_registration.install
├── event_registration.libraries.yml
├── event_registration.links.menu.yml
├── event_registration.module
├── event_registration.permissions.yml
├── event_registration.routing.yml
└── event_registration.services.yml
```

## Troubleshooting

### Registration Form Not Showing Events

- Ensure events are configured with registration periods that include the current date
- Check that `registration_start_date <= today` and `registration_end_date >= today`

### Emails Not Sending

- Verify Drupal's mail system is configured correctly
- Check admin email is configured in Settings
- Ensure "Enable Admin Notifications" is checked
- Review Drupal watchdog logs for errors

### Permission Denied Errors

- Ensure user has appropriate permissions assigned
- Clear cache after changing permissions

## License

This module is licensed under GPL-2.0-or-later.

## Author

Aarushi Chaddha
