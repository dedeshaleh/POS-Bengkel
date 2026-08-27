# Deployment Guide — Bengkel Berkah POS & Inventory

Panduan deployment production untuk aplikasi BengkelBerkah.

## 1. Server Requirements

### Software
- **OS:** Ubuntu 22.04 LTS / Debian 12 / Windows Server 2019+
- **PHP:** 8.3+ dengan extension:
  - `pdo_pgsql`, `pgsql`
  - `fileinfo`
  - `zip` (untuk PhpSpreadsheet import/export)
  - `mbstring`, `openssl`, `curl`, `gd` (untuk barcode/QR generation)
- **PostgreSQL:** 14+
- **Web Server:** Nginx (recommended) atau Apache
- **Node.js:** 18+ (untuk build asset Vite, hanya saat deploy)
- **Composer:** 2.x

### Hardware (minimum untuk workshop kecil-menengah)
- **RAM:** 2 GB (4 GB recommended)
- **Disk:** 20 GB (untuk DB, log, dan backup)
- **CPU:** 2 core

## 2. PostgreSQL Setup

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE bengkel_berkah;
CREATE USER bengkelberkah WITH ENCRYPTED PASSWORD 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE bengkel_berkah TO bengkelberkah;
ALTER DATABASE bengkel_berkah OWNER TO bengkelberkah;
```

Pastikan `pg_hba.conf` mengizinkan koneksi dari web server (localhost):

```
host    bengkel_berkah    bengkelberkah    127.0.0.1/32    md5
```

## 3. Application Deployment

### Clone & install

```bash
cd /var/www
git clone https://github.com/dedeshaleh/POS-Bengkel.git bengkelberkah
cd bengkelberkah
composer install --no-dev --optimize-autoloader
```

### Environment configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.bengkelberkah.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bengkel_berkah
DB_USERNAME=bengkelberkah
DB_PASSWORD=CHANGE_ME_STRONG_PASSWORD

CACHE_STORE=redis          # atau file jika tidak ada Redis
SESSION_DRIVER=redis       # atau database
QUEUE_CONNECTION=database
```

### Migrate & seed

```bash
php artisan migrate --force
php artisan db:seed --force
```

### Build frontend assets

```bash
npm install
npm run build
```

### Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Set permissions

```bash
sudo chown -R www-data:www-data /var/www/bengkelberkah
sudo chmod -R 775 storage bootstrap/cache
```

## 4. Nginx Configuration

```nginx
server {
    listen 80;
    server_name pos.bengkelberkah.test;
    root /var/www/bengkelberkah/public;
    index index.php index.html;

    # Redirect to HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pos.bengkelberkah.test;
    root /var/www/bengkelberkah/public;
    index index.php index.html;

    ssl_certificate     /etc/ssl/certs/bengkelberkah.crt;
    ssl_certificate_key /etc/ssl/private/bengkelberkah.key;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
    location ~ ^/(composer|artisan|package\.json|package-lock\.json|webpack\.mix\.js|vite\.config\.js) {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Client upload limit (for Excel imports)
    client_max_body_size 10M;
}
```

## 5. Queue Worker

Aplikasi menggunakan `QUEUE_CONNECTION=database` untuk background jobs (price import, dll).

### Supervisor config

`/etc/supervisor/conf.d/bengkelberkah-worker.conf`:

```ini
[program:bengkelberkah-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bengkelberkah/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/bengkelberkah/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bengkelberkah-worker:*
```

## 6. Scheduler (Cron)

Tambah ke crontab user `www-data`:

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/bengkelberkah && php artisan schedule:run >> /dev/null 2>&1
```

## 7. Cache (Optional — Redis)

Jika menggunakan Redis:

```bash
sudo apt install redis-server
sudo systemctl enable redis-server
```

Update `.env`:

```ini
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 8. Storage & Backup

### Storage link

```bash
php artisan storage:link
```

### Database backup (daily)

`/etc/cron.daily/backup-bengkelberkah.sh`:

```bash
#!/bin/bash
BACKUP_DIR=/var/backups/bengkelberkah
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
PGPASSWORD=CHANGE_ME pg_dump -U bengkelberkah -h 127.0.0.1 bengkel_berkah | gzip > $BACKUP_DIR/db_$DATE.sql.gz
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
```

```bash
sudo chmod +x /etc/cron.daily/backup-bengkelberkah.sh
```

## 9. Logging & Monitoring

### Laravel logs

- Lokasi: `storage/logs/laravel.log`
- Rotation: konfigurasi di `config/logging.php` (default daily)

### Monitor

- **Uptime:** Uptime Kuma / Better Stack
- **Error tracking:** Sentry (opsional — `SENTRY_LARAVEL_DSN` di `.env`)
- **Server metrics:** Netdata / Prometheus + Grafana

## 10. Production Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] DB password kuat (bukan `root`)
- [ ] HTTPS aktif (Let's Encrypt / SSL cert)
- [ ] `php artisan config:cache` + `route:cache` + `view:cache`
- [ ] Queue worker running via Supervisor
- [ ] Scheduler cron aktif
- [ ] Backup database harian
- [ ] `storage/` dan `bootstrap/cache` writable
- [ ] `.env` tidak di-commit (sudah di `.gitignore`)
- [ ] Firewall: hanya expose 80/443, block 5432 (PostgreSQL)
- [ ] Update OS & PHP secara berkala

## 11. Update Deployment

Saat ada update dari repository:

```bash
cd /var/www/bengkelberkah
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm install && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart bengkelberkah-worker:*
```
