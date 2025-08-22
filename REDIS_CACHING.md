# Redis Caching for Events

This document provides information about the Redis caching implementation for the Events tag and how to troubleshoot common issues.

## Overview

The Events tag has been optimized to work with Redis caching in production. The following changes have been made:

1. Removed cache tags which aren't supported by Redis
2. Implemented Redis-specific cache invalidation
3. Added better debugging and logging
4. Created custom commands for cache management
5. Optimized cache key generation

## Commands

### Clear Events Cache

```bash
php artisan events:clear-cache
```

This command will:
- Clear all Redis keys related to events
- Clear the application cache
- Clear the static cache

Use this command when you need to completely refresh the events cache.

### Check Future Events

```bash
php artisan events:check-future
```

This command will:
- List all future events in the database
- Show the current time being used for comparison
- Display detailed information about each future event

Use this command to verify that there are future events in the database and that the dates are being interpreted correctly.

## Troubleshooting

### No Events Showing in Production

If events are showing locally but not in production, try the following steps:

1. **Clear the Redis cache**:
   ```bash
   php artisan events:clear-cache
   ```

2. **Check if there are future events**:
   ```bash
   php artisan events:check-future
   ```

3. **Check the logs for errors**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Verify Redis is working**:
   ```bash
   redis-cli ping
   ```

5. **Check Redis keys**:
   ```bash
   redis-cli keys "monsun_events_*"
   ```

### Common Issues

1. **Time Zone Issues**: The server time zone might be different from your local time zone, causing events to be filtered incorrectly. The code now uses a consistent time zone.

2. **Redis Connection Issues**: Make sure Redis is running and accessible.

3. **Cache Invalidation**: The cache might not be properly invalidated when events are updated. The new invalidator should handle this correctly.

4. **Static Caching**: The static cache might be serving old content. Try clearing the static cache.

## Implementation Details

### Cache Keys

Cache keys are now structured as follows:

- `events.{type}.{collection}.{hash}` - For event lists
- `events.grouped.{format}.{hash}` - For grouped event results

### Redis Cache Invalidation

The `EventsInvalidator` class now uses Redis-specific methods to clear cache keys:

1. It uses the Redis `SCAN` command to find keys matching a pattern
2. It deletes the found keys using the Redis `DEL` command
3. It also clears the application cache

### Debug Mode

The Events tag has a debug mode that can be enabled by setting `protected $debug = true;` in the `Events` class. When debug mode is enabled:

- All cache operations are logged
- Cache is bypassed to ensure fresh data
- Detailed information is logged about the events being processed

## Deployment

When deploying changes to production:

1. Upload the updated files
2. Clear the Redis cache:
   ```bash
   php artisan events:clear-cache
   ```
3. Check if future events are available:
   ```bash
   php artisan events:check-future
   ```
4. Monitor the logs for any errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Redis Configuration

The Redis configuration in `config/cache.php` has been optimized for better performance:

```php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'lock_connection' => 'default',
    'persistent' => true,
    'read_timeout' => 60,
    'retry_interval' => 100,
    'timeout' => 5.0,
],
```

This configuration:
- Uses persistent connections for better performance
- Sets appropriate timeouts and retry intervals
- Uses the default Redis connection
