# 🎬 Clean Telegram Video Bot

A clean, production-ready Telegram bot for serving videos with auto-deletion functionality.

## 📁 Files Structure

```
clean/
├── config.php          # Bot configuration
├── TelegramBot.php      # Core bot functionality
├── VideoManager.php     # Video management & progress
├── webhook.php          # Webhook handler
├── cron.php            # Auto-deletion cron job
├── setup.php           # Initial setup
├── RateLimiter.php     # API rate limiting
├── support.php         # Support system
├── support_handler.php # Support request handler
├── admin/              # Admin panel directory
├── backups/            # Backup files directory
└── data/
    ├── file_ids.json   # Video file IDs (391 videos)
    ├── progress.json   # User progress tracking
    ├── logs.json       # System logs
    └── deletion_queue.json # Auto-deletion queue
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
- **Auto-Deletion**: Messages deleted after 1 minute, Videos after 15 minutes
- **User Progress**: Tracks individual user progress through video sequence
- **Sequential Videos**: Users get videos in order with progress tracking
- **Privacy Protection**: Library size and position information hidden from users
- **Video Protection**: Protected content prevents forwarding and downloading
- **Rate Limiting**: API protection to prevent spam
- **Support System**: Built-in user support functionality
- **Clean Interface**: Simple, professional design with no clutter
- **Admin Tools**: Monitor bot performance and manage users
- **Backup System**: Automatic data backups for safety
- **Secure**: Clean production code with proper error handling

## 📋 Bot Commands

- `/start` - Welcome message and bot status
- `/next` - Get next video in sequence
- `/stats` - View your progress statistics
- `/reset` - Reset your progress to start over
- `/help` - Show help information
- `/support` - Contact support for assistance

## 🔧 Admin Panel

Access: `yourserver.com/admin/`
- Secure admin interface
- Monitor bot performance
- View user statistics
- Manage bot settings
- Check system logs

## ⚙️ Cron Job Setup

**For Linux/Unix servers:**
```bash
# Edit crontab
crontab -e

# Add this line (runs every 5 minutes)
*/5 * * * * /usr/bin/php /path/to/your/bot/cron.php >/dev/null 2>&1
```

**For Windows servers:**
- Use Task Scheduler to run `php cron.php` every 5 minutes
- Or use Windows Subsystem for Linux (WSL)

## 🛡️ Security Features

- **Clean Production Code**: All test files and debug code removed
- **Privacy Protection**: Library size and user position information hidden
- **Video Protection**: Protected content prevents unauthorized downloads
- **Rate Limiting**: Built-in API protection against spam
- **Error Handling**: Comprehensive error logging and handling
- **Data Validation**: Input sanitization and validation
- **Secure File Access**: Protected data directory structure
- **Backup System**: Automatic data backups for recovery

## 📊 Statistics

- **Total Videos**: 391 ready-to-serve videos
- **Auto-Deletion**: Messages: 1 minute, Videos: 15 minutes
- **User Progress**: Individual tracking per user (position hidden for privacy)
- **Sequential Delivery**: Ordered video progression
- **Video Protection**: Protected content with download prevention
- **Rate Limiting**: API protection enabled
- **Support System**: Built-in user assistance
- **Activity Logging**: Comprehensive system logs

## 🎉 Ready to Deploy!

This is a **production-ready** version with:
- ✅ All unnecessary files removed
- ✅ Clean, optimized code structure  
- ✅ Sequential video delivery system
- ✅ Auto-deletion functionality (1 min messages, 15 min videos)
- ✅ Privacy protection (library size and position hidden)
- ✅ Video protection (prevents forwarding and downloading)
- ✅ Rate limiting and security features
- ✅ Support system for users
- ✅ Comprehensive logging and monitoring

Simply upload to your server, configure your bot token, and you're ready to go!

## 🚀 Quick Start

1. **Upload** all files to your server
2. **Edit** `config.php` with your bot token
3. **Set** proper file permissions
4. **Run** `setup.php` to initialize
5. **Add** cron job for auto-deletion
6. **Test** your bot - it's ready! 🎬
