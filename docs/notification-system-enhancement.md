# Notification System Enhancement

## Overview
The Command Uplink notification system has been enhanced with browser push notification preferences and pagination support. Users can now customize which notification types trigger browser push notifications while all notifications are always stored in their history.

## Features

### 1. User Push Notification Preferences
Users can control which types of events trigger browser push notifications:
- **Attack Push Notifications**: Browser push when their empire is attacked
- **Espionage Push Notifications**: Browser push when enemy spies target their empire
- **Alliance Push Notifications**: Browser push for alliance-related updates
- **System Push Notifications**: Browser push for game updates and announcements
- **Master Push Toggle**: Enable/disable all browser push notifications

**Important:** All notifications are ALWAYS created in the Command Uplink history regardless of preferences. User preferences only control browser push notifications, not whether notifications appear in the notification list.

### 2. Pagination
The notification inbox now supports pagination (20 items per page) to handle large notification histories efficiently.

### 3. Push Notification Logic
The JavaScript notification system checks both the master push toggle AND the specific notification type preference before triggering browser push notifications.

## Database Schema

### New Table: `user_notification_preferences`
```sql
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
    `user_id` INT UNSIGNED NOT NULL,
    `attack_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `spy_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `alliance_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `system_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `push_notifications_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_notif_prefs_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## API Endpoints

### Get User Preferences
**GET** `/notifications/preferences`

Returns the current user's notification preferences.

Response:
```json
{
  "attack_enabled": true,
  "spy_enabled": true,
  "alliance_enabled": true,
  "system_enabled": true,
  "push_notifications_enabled": false
}
```

### Update Preferences
**POST** `/settings/notifications`

Updates user notification preferences.

Parameters:
- `csrf_token` (required)
- `attack_enabled` (checkbox, value=1)
- `spy_enabled` (checkbox, value=1)
- `alliance_enabled` (checkbox, value=1)
- `system_enabled` (checkbox, value=1)
- `push_notifications_enabled` (checkbox, value=1)

### View Notifications (Paginated)
**GET** `/notifications?page={page_number}`

Returns paginated notifications for the current user.

Parameters:
- `page` (optional, default=1): Page number to display

## Architecture

### New Classes

1. **UserNotificationPreferences** (Entity)
   - Immutable DTO representing user notification preferences
   - Method: `isTypeEnabled(string $type): bool` - checks if a notification type is enabled

2. **UserNotificationPreferencesRepository** (Repository)
   - `getByUserId(int $userId): UserNotificationPreferences` - Gets preferences or returns defaults
   - `upsert(...)` - Creates or updates preferences
   - `createDefault(int $userId)` - Creates default preferences for new users

### Modified Classes

1. **NotificationRepository**
   - Added `getPaginated(int $userId, int $page, int $perPage): array`
   - Added `getTotalCount(int $userId): int`

2. **NotificationService**
   - Updated constructor to inject `UserNotificationPreferencesRepository`
   - Modified `sendNotification()` to check user preferences
   - Added `getPaginatedNotifications(int $userId, int $page, int $perPage): array`
   - Added `getPreferences(int $userId)`
   - Added `updatePreferences(...)`

3. **NotificationController**
   - Updated `index()` to use pagination
   - Added `getPreferences()` endpoint

4. **SettingsService**
   - Updated constructor to inject `NotificationService`
   - Modified `getSettingsData()` to include notification preferences
   - Added `updateNotificationPreferences(...)`

5. **SettingsController**
   - Added `handleNotifications()` method

6. **ContainerFactory**
   - Registered `UserNotificationPreferencesRepository`
   - Updated `NotificationService` definition to inject the new repository

## User Interface

### Settings Page
A new "Notification Preferences" card has been added to the settings page with:
- Checkboxes for each notification type
- Visual icons for each type
- Description text explaining each option
- "Save Preferences" button

### Notifications Page
- Pagination controls at the bottom (when there are multiple pages)
- "Previous" and "Next" buttons
- Current page indicator (e.g., "Page 1 of 5")

### JavaScript
The notification polling system (`notifications.js`) now:
- Fetches user preferences on initialization
- Only triggers push notifications if the user has enabled them
- Respects the `push_notifications_enabled` preference

## Usage Examples

### Sending a Notification (Always Created)
```php
$notificationService->sendNotification(
    userId: $defenderId,
    type: 'attack',
    title: 'Under Attack!',
    message: 'Your empire is under attack by ' . $attackerName,
    link: '/battle/report/' . $reportId
);
```

The notification is ALWAYS created in the database regardless of user preferences. User preferences only control whether a browser push notification is triggered, not whether the notification appears in the Command Uplink history.

### Getting Paginated Notifications
```php
$data = $notificationService->getPaginatedNotifications(
    userId: $userId,
    page: 2,
    perPage: 20
);

// Returns:
// [
//     'notifications' => [...],
//     'pagination' => [
//         'current_page' => 2,
//         'per_page' => 20,
//         'total_items' => 45,
//         'total_pages' => 3,
//         'has_previous' => true,
//         'has_next' => true
//     ]
// ]
```

### Updating Preferences
```php
$response = $settingsService->updateNotificationPreferences(
    userId: $userId,
    attackEnabled: true,
    spyEnabled: true,
    allianceEnabled: false,
    systemEnabled: true,
    pushEnabled: true
);

if ($response->isSuccess()) {
    // Preferences updated
}
```

## Migration

To apply the database schema changes, run the migration:

```bash
php database/migrations/20251221000000_create_user_notification_preferences.php
```

Or using Phinx:
```bash
composer phinx migrate
```

## Default Behavior

- All notification types are enabled by default
- Push notifications are disabled by default (requires explicit user action)
- When a user's preferences don't exist in the database, the repository returns default preferences (all enabled except push)

## Testing Considerations

1. **Preferences Persistence**: Verify that preference changes are saved to the database
2. **Notification Filtering**: Ensure notifications respect user preferences
3. **Pagination**: Test with various notification counts to verify pagination works correctly
4. **JavaScript Integration**: Verify that push notifications respect the user's preferences
5. **Default Preferences**: Test that new users get default preferences

## Future Enhancements

Potential improvements for future iterations:
- Email notifications
- Notification frequency settings (immediate vs. digest)
- More granular notification types (e.g., separate preferences for incoming vs. outgoing attacks)
- Notification archiving
- Bulk notification actions
