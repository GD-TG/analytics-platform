# Deployment Guide - Analytics Platform

## Environment Setup

### Prerequisites
- PHP 8.1+ with Laravel 10
- MySQL 8.0+
- Redis (for caching & queue)
- Node.js 18+ & npm
- Composer

### Step 1: Backend Setup

```bash
# Clone repository
git clone <repo-url>
cd analytics-platform

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key (if not already set)
php artisan key:generate

# Set up database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=analytics_platform
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:cache
php artisan view:cache
```

### Step 2: Queue Setup (Redis)

```bash
# In .env, set:
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=cookie

# Start queue worker (keep running in background or use supervisor)
php artisan queue:work redis --tries=3 --timeout=60

# Or with supervisor (recommended for production):
# Create /etc/supervisor/conf.d/analytics-queue.conf
# [program:analytics-queue]
# process_name=%(program_name)s_%(process_num)02d
# command=php /path/to/analytics-platform/artisan queue:work redis --tries=3 --timeout=60
# autostart=true
# autorestart=true
# numprocs=2
# redirect_stderr=true
# stdout_logfile=/var/log/analytics-queue.log
```

### Step 3: Scheduler Setup

```bash
# Add to cron (/etc/crontab or via crontab -e):
* * * * * cd /path/to/analytics-platform && php artisan schedule:run >> /dev/null 2>&1

# Or with supervisor for continuous scheduling
```

### Step 4: Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Build for production
npm run build

# Or run development server (for testing)
npm run dev
```

### Step 5: Web Server Configuration

#### Nginx Example
```nginx
server {
    listen 80;
    server_name analytics.example.com;

    root /path/to/analytics-platform/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### Apache Example
```apache
<VirtualHost *:80>
    ServerName analytics.example.com
    DocumentRoot /path/to/analytics-platform/public

    <Directory /path/to/analytics-platform/public>
        AllowOverride All
        Require all granted

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [L]
        </IfModule>
    </Directory>
</VirtualHost>
```

## Environment Variables

Create/update `.env` file:

```env
APP_NAME="Analytics Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://analytics.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=analytics_platform
DB_USERNAME=analytics_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Yandex OAuth
YANDEX_CLIENT_ID=your_client_id
YANDEX_CLIENT_SECRET=your_client_secret
YANDEX_REDIRECT_URI=https://analytics.example.com/api/auth/yandex/callback

# HuggingFace AI
HUGGINGFACE_API_KEY=your_hf_api_key
HUGGINGFACE_API_URL=https://api-inference.huggingface.co/models

# Email
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@analytics.example.com
```

## Security Best Practices

1. **APP_KEY** - Securely generated and stored
   - Never commit `.env` to repository
   - Use secrets management (AWS Secrets Manager, etc.)

2. **Encrypted Password Storage**
   - Passwords are hashed for authentication (`Hash::make`)
   - Reversible copy stored encrypted via `Crypt::encryptString` (AES-256)
   - Only used when explicitly needed for integrations
   - Ensure `APP_KEY` rotation policy

3. **API Rate Limiting**
   - Integrated Redis-based leaky-bucket rate limiter
   - Configured in middleware

4. **CORS**
   - Configure trusted domains in `config/cors.php`

5. **Database Backups**
   ```bash
   # Daily backup script
   mysqldump -u DB_USER -p DB_NAME > /backups/analytics_$(date +%Y%m%d).sql
   ```

## Monitoring & Health Checks

### Log Files
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/queue.log
```

### Queue Status
```bash
# Check queue length
php artisan queue:failed
php artisan queue:retry all
```

### Cache Status
```bash
# Redis connection test
php artisan tinker
# In tinker:
Cache::put('test', 'value', 60);
Cache::get('test');
```

## API Endpoints Summary

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - Email/password login
- `GET /api/auth/yandex/url` - Get Yandex OAuth URL
- `POST /api/auth/yandex/callback` - Yandex OAuth callback
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Current user info

### Projects (Protected)
- `GET /api/projects` - List projects
- `POST /api/projects` - Create project
- `GET /api/projects/{id}` - Get project
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project

### Counters (Protected)
- `GET /api/projects/{projectId}/counters` - List counters
- `POST /api/projects/{projectId}/counters` - Add counter
- `DELETE /api/projects/{projectId}/counters/{counterId}` - Remove counter

### Direct Accounts (Protected)
- `GET /api/projects/{projectId}/direct-accounts` - List accounts
- `POST /api/projects/{projectId}/direct-accounts` - Add account
- `DELETE /api/projects/{projectId}/direct-accounts/{accountId}` - Remove account

### Goals (Protected)
- `GET /api/projects/{projectId}/goals` - List goals
- `POST /api/projects/{projectId}/goals` - Create goal
- `PUT /api/projects/{projectId}/goals/{goalId}` - Update goal
- `DELETE /api/projects/{projectId}/goals/{goalId}` - Delete goal

### Sync & Report (Protected)
- `POST /api/projects/{projectId}/sync` - Trigger sync job
- `GET /api/projects/{projectId}/sync/status` - Check sync status
- `GET /api/projects/{projectId}/report` - Get 3-month report

### AI Analytics (Protected)
- `GET /api/projects/{projectId}/ai/pulse` - Business pulse analysis
- `GET /api/projects/{projectId}/ai/sources-pie` - Traffic sources pie
- `POST /api/projects/{projectId}/ai/compare` - Compare metrics
- `GET /api/projects/{projectId}/ai/thermometer` - Project health status
- `GET /api/projects/{projectId}/ai/heatmap` - Activity heatmap

### Settings (Protected)
- `GET /api/settings` - User OAuth settings
- `POST /api/settings/yandex-metrika` - Save Metrika credentials
- `POST /api/settings/yandex-direct` - Save Direct credentials
- `POST /api/settings/test/yandex-metrika` - Test Metrika connection
- `POST /api/settings/test/yandex-direct` - Test Direct connection

## Troubleshooting

### Database Migration Issues
```bash
php artisan migrate:rollback
php artisan migrate
```

### Queue Not Processing
```bash
# Check if Redis is running
redis-cli ping

# Restart queue worker
pkill -f 'php artisan queue:work'
php artisan queue:work redis
```

### CORS Issues
Check `config/cors.php` and ensure frontend domain is whitelisted.

### Vite Frontend Build
```bash
cd frontend
npm run build
# Output in frontend/dist/
```

## Production Checklist

- [ ] `APP_DEBUG=false` in .env
- [ ] Database backups configured
- [ ] Queue worker running (supervisor)
- [ ] Cron job for scheduler active
- [ ] SSL certificate configured
- [ ] Rate limiting enabled
- [ ] Log rotation configured
- [ ] Monitoring alerts set up
- [ ] Redis persistence enabled
- [ ] .env not committed to repository
