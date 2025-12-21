# Notification System Architecture

## Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────┐              ┌─────────────────────────┐ │
│  │  Notifications   │              │  Settings Page          │ │
│  │  Page            │              │                         │ │
│  │  (/notifications)│              │  - Profile              │ │
│  │                  │              │  - Email                │ │
│  │  - List view     │              │  - Password             │ │
│  │  - Pagination    │◄────────────►│  - Security Questions   │ │
│  │  - Mark read     │              │  - Notification Prefs ✨│ │
│  │  - Push button   │              │                         │ │
│  └──────────────────┘              └─────────────────────────┘ │
│           │                                    │                │
│           │                                    │                │
└───────────┼────────────────────────────────────┼────────────────┘
            │                                    │
            │ GET/POST                           │ POST
            ▼                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                        CONTROLLERS                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────┐      ┌──────────────────────────┐│
│  │ NotificationController   │      │ SettingsController       ││
│  │                          │      │                          ││
│  │ - index() ✨ pagination  │      │ - handleNotifications() ✨││
│  │ - check()                │      │                          ││
│  │ - getPreferences() ✨    │      │                          ││
│  │ - handleMarkRead()       │      │                          ││
│  │ - handleMarkAllRead()    │      │                          ││
│  └──────────────────────────┘      └──────────────────────────┘│
│           │                                    │                │
└───────────┼────────────────────────────────────┼────────────────┘
            │                                    │
            │ Service calls                      │ Service calls
            ▼                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                         SERVICES                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────┐  ┌────────────────────┐  │
│  │ NotificationService              │  │ SettingsService    │  │
│  │                                  │  │                    │  │
│  │ - sendNotification() ✨          │◄─┤ - getSettingsData()│  │
│  │   (checks preferences)           │  │ - updateNotif...() │  │
│  │ - getPaginatedNotifications() ✨ │  └────────────────────┘  │
│  │ - getPreferences() ✨            │                          │
│  │ - updatePreferences() ✨         │                          │
│  │ - getPollingData()               │                          │
│  │ - markAsRead()                   │                          │
│  │ - markAllRead()                  │                          │
│  └──────────────────────────────────┘                          │
│           │                                                     │
└───────────┼─────────────────────────────────────────────────────┘
            │
            │ Repository calls
            ▼
┌─────────────────────────────────────────────────────────────────┐
│                       REPOSITORIES                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌────────────────────────┐  ┌──────────────────────────────┐  │
│  │ NotificationRepository │  │ UserNotificationPreferences  │  │
│  │                        │  │ Repository ✨                │  │
│  │ - create()             │  │                              │  │
│  │ - getUnreadCount()     │  │ - getByUserId()              │  │
│  │ - getRecent()          │  │ - upsert()                   │  │
│  │ - getPaginated() ✨    │  │ - createDefault()            │  │
│  │ - getTotalCount() ✨   │  │                              │  │
│  │ - markAsRead()         │  │                              │  │
│  │ - markAllRead()        │  │                              │  │
│  └────────────────────────┘  └──────────────────────────────┘  │
│           │                             │                       │
└───────────┼─────────────────────────────┼───────────────────────┘
            │                             │
            │ SQL                         │ SQL
            ▼                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                         DATABASE                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌────────────────────────────────┐  ┌──────────────────────┐  │
│  │ notifications                  │  │ user_notification_   │  │
│  │                                │  │ preferences ✨       │  │
│  │ - id                           │  │                      │  │
│  │ - user_id                      │  │ - user_id (PK)       │  │
│  │ - type                         │  │ - attack_enabled     │  │
│  │ - title                        │  │ - spy_enabled        │  │
│  │ - message                      │  │ - alliance_enabled   │  │
│  │ - link                         │  │ - system_enabled     │  │
│  │ - is_read                      │  │ - push_enabled       │  │
│  │ - created_at                   │  │ - created_at         │  │
│  └────────────────────────────────┘  │ - updated_at         │  │
│                                       └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘

✨ = New or Enhanced Feature
```

## Data Flow Examples

### 1. Sending a Notification (with Preference Check)

```
Game Event (Attack) 
    ↓
AttackService calls NotificationService.sendNotification()
    ↓
NotificationService fetches UserNotificationPreferences
    ↓
Check if 'attack' type is enabled
    ↓
    ├─→ YES: Create notification in database
    │        ↓
    │        Return notification ID
    │
    └─→ NO:  Return 0 (notification not created)
```

### 2. Viewing Notifications with Pagination

```
User visits /notifications?page=2
    ↓
NotificationController.index()
    ↓
NotificationService.getPaginatedNotifications(userId, page=2, perPage=20)
    ↓
NotificationRepository.getPaginated() [LIMIT 20 OFFSET 20]
    +
NotificationRepository.getTotalCount()
    ↓
Calculate pagination metadata
    ↓
Return notifications + pagination info
    ↓
Render view with pagination controls
```

### 3. Push Notification Flow

```
JavaScript NotificationSystem.init()
    ↓
Load user preferences via /notifications/preferences
    ↓
Start polling /notifications/check every 15s
    ↓
New notification detected
    ↓
Check preferences.push_notifications_enabled
    ↓
    ├─→ TRUE:  Trigger browser notification
    │          ↓
    │          Display push notification
    │
    └─→ FALSE: Only update badge, no push
               ↓
               Update navbar badge count
```

### 4. Updating Preferences

```
User modifies checkboxes in Settings
    ↓
Submit form to /settings/notifications
    ↓
SettingsController.handleNotifications()
    ↓
Validate CSRF token
    ↓
SettingsService.updateNotificationPreferences()
    ↓
NotificationService.updatePreferences()
    ↓
UserNotificationPreferencesRepository.upsert()
    ↓
    INSERT ... ON DUPLICATE KEY UPDATE
    ↓
Success message, redirect to /settings
```

## Key Design Decisions

1. **Preference Filtering at Service Layer**
   - Notifications are filtered BEFORE database insertion
   - Saves database space and improves performance
   - Ensures consistency across all notification sources

2. **Graceful Defaults**
   - If no preferences exist, return defaults (all enabled except push)
   - No errors for new users
   - Lazy initialization

3. **Pagination**
   - 20 items per page balances UX and performance
   - Uses OFFSET/LIMIT for simplicity
   - Could be optimized with cursor-based pagination if needed

4. **Push Notification Independence**
   - Push preference is separate from notification types
   - Users can receive notifications in-app but not as push
   - Browser permission required in addition to user preference

5. **Autowiring in DI Container**
   - SettingsService uses autowiring (not explicitly defined)
   - Dependencies automatically resolved by PHP-DI
   - Reduces boilerplate configuration
