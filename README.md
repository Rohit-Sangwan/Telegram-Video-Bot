# 🎬 Clean Telegram Video Bot

A clean, production-ready Telegram bot for serving videos with auto-deletion functionality.

## 📁 Files Structure

```
clean/
├── config.php          # Bot configuration
├── TelegramBot.php      # Telegram API wrapper
├── VideoManager.php     # Video management
├── webhook.php          # Webhook handler
├── cron.php            # Auto-deletion cron job
├── setup.php           # Initial setup
├── admin.php           # Admin panel
└── data/
    ├── file_ids.json   # Video file IDs (391 videos)
    ├── progress.json   # User progress
    ├── logs.json       # System logs
    └── deletion_queue.json # Deletion queue
```

## 🚀 Installation

1. **Upload Files**
   - Upload all files to your server
   - Ensure proper directory structure

2. **Set Permissions**
   ```bash
   chmod 755 *.php
   chmod 777 data/
   chmod 777 data/*
   ```

3. **Configure**
   - Edit `config.php` with your bot token
   - Set webhook URL to your server

4. **Setup**
   - Run `php setup.php` to initialize webhook
   - Or visit `yourserver.com/setup.php` in browser

## 🎯 Features

- **391 Videos Ready**: Pre-loaded with working file IDs
- **Auto-Deletion**: Videos deleted after 30 minutes
- **User Progress**: Tracks individual user progress
- **Random Videos**: Users can get random videos
- **Clean Interface**: Simple, professional design
- **Admin Panel**: Monitor bot performance
- **Secure**: Clean code with proper error handling

## 📋 Bot Commands

- `/start` - Welcome message and status
- `/next` - Get next video in sequence
- `/random` - Get random video
- `/stats` - View progress
- `/reset` - Reset progress
- `/help` - Show help

## 🔧 Admin Panel

Access: `yourserver.com/admin.php`
- Username: `admin`
- Password: `Haryanvi@123`

## ⚙️ Cron Job

Add to crontab for auto-deletion:
```bash
*/5 * * * * php /path/to/your/bot/cron.php
```

## 🛡️ Security

- Clean code with no test files
- Proper error handling
- Secure authentication
- Rate limiting built-in

## 📊 Statistics

- Total videos: 391
- Auto-deletion: 30 minutes
- User progress tracking
- Activity logging

## 🎉 Ready to Deploy!

This is a production-ready version with all unnecessary files removed. Simply upload and configure!
