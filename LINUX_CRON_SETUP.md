# 🐧 Linux Cron Job Setup for Clean Bot

## Quick Setup Commands

### 1. Upload Files to Server
Upload these files to `/home/fastme/htdocs/fastme.cloud/bot/`:
- `cron.php` (main cron job)
- `cron_linux_setup.sh` (setup script)

### 2. Set Up Cron Jobs

#### Option A: Automatic Setup (Recommended)
```bash
# Make setup script executable
chmod +x /home/fastme/htdocs/fastme.cloud/bot/cron_linux_setup.sh

# Run setup script
/home/fastme/htdocs/fastme.cloud/bot/cron_linux_setup.sh
```

#### Option B: Manual Setup
```bash
# Edit crontab
crontab -e

# Add these lines:
*/30 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php >> /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log 2>&1
0 9 * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php >> /home/fastme/htdocs/fastme.cloud/bot/logs/cron_daily.log 2>&1
```

## Cron Job Schedule Explained

### Your Original Command:
```bash
* * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php
```
**This runs EVERY MINUTE** - might be too frequent!

### Recommended Schedule:
```bash
# Every 30 minutes (recommended)
*/30 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php

# Every 15 minutes (if you have high traffic)
*/15 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php

# Every hour (for light usage)
0 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php
```

## Cron Schedule Format
```
* * * * * command
│ │ │ │ │
│ │ │ │ └── Day of week (0-7, 0 or 7 = Sunday)
│ │ │ └──── Month (1-12)
│ │ └────── Day of month (1-31)
│ └──────── Hour (0-23)
└────────── Minute (0-59)
```

## Common Schedules

| Schedule | Cron Expression | Description |
|----------|----------------|-------------|
| Every minute | `* * * * *` | Too frequent, not recommended |
| Every 5 minutes | `*/5 * * * *` | Good for testing |
| Every 15 minutes | `*/15 * * * *` | Good for busy bots |
| Every 30 minutes | `*/30 * * * *` | **Recommended** |
| Every hour | `0 * * * *` | Good for light usage |
| Daily at 9 AM | `0 9 * * *` | Good for daily reports |
| Weekly on Sunday | `0 2 * * 0` | Good for weekly cleanup |

## Setup Steps

### 1. Connect to Your Server
```bash
ssh username@fastme.cloud
```

### 2. Navigate to Bot Directory
```bash
cd /home/fastme/htdocs/fastme.cloud/bot
```

### 3. Test Cron Job Manually
```bash
/usr/bin/php8.4 cron.php
```

### 4. Set Up Cron Job
```bash
# Edit crontab
crontab -e

# Add your preferred schedule:
*/30 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php >> /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log 2>&1
```

### 5. Verify Cron Job
```bash
# List current cron jobs
crontab -l

# Check if cron service is running
systemctl status cron
```

## File Permissions
```bash
# Set correct permissions
chmod 755 /home/fastme/htdocs/fastme.cloud/bot/cron.php
chmod 777 /home/fastme/htdocs/fastme.cloud/bot/data
chmod 777 /home/fastme/htdocs/fastme.cloud/bot/backups
chmod 777 /home/fastme/htdocs/fastme.cloud/bot/logs
```

## Monitoring

### Check Logs
```bash
# View recent cron execution logs
tail -f /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log

# View bot activity logs
tail -f /home/fastme/htdocs/fastme.cloud/bot/data/logs.json
```

### Check Cron Job Status
```bash
# View cron jobs
crontab -l

# Check system cron log
tail -f /var/log/cron
```

## Troubleshooting

### Common Issues

**Issue: Cron job not running**
```bash
# Check if cron service is running
systemctl status cron

# Start cron service if needed
sudo systemctl start cron
```

**Issue: Permission denied**
```bash
# Fix file permissions
chmod 755 /home/fastme/htdocs/fastme.cloud/bot/cron.php
```

**Issue: PHP not found**
```bash
# Check PHP path
which php8.4
# Or try: which php

# Update cron job with correct path
```

**Issue: Files not found**
```bash
# Check if files exist
ls -la /home/fastme/htdocs/fastme.cloud/bot/

# Check file permissions
ls -la /home/fastme/htdocs/fastme.cloud/bot/cron.php
```

## Security Notes

### Log File Management
```bash
# Rotate logs to prevent disk space issues
*/30 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php >> /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log 2>&1 && tail -n 1000 /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log > /tmp/cron_temp && mv /tmp/cron_temp /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log
```

### Email Notifications
```bash
# Add email to cron for error notifications
MAILTO="your-email@example.com"
*/30 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php
```

## Quick Commands

```bash
# Install cron job (every 30 minutes)
echo "*/30 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php >> /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log 2>&1" | crontab -

# Remove all cron jobs
crontab -r

# Edit cron jobs
crontab -e

# List cron jobs
crontab -l

# Test cron job manually
/usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php
```

---

## 🚀 Recommended Final Setup

```bash
# 1. Set up main cron job (every 30 minutes)
echo "*/30 * * * * /usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php >> /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log 2>&1" | crontab -

# 2. Test it works
/usr/bin/php8.4 /home/fastme/htdocs/fastme.cloud/bot/cron.php

# 3. Check logs
tail -f /home/fastme/htdocs/fastme.cloud/bot/logs/cron.log
```

Your bot will now automatically clean up old data, process deletion queues, and maintain optimal performance! 🎉
