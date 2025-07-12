🚀 DEPLOYMENT INSTRUCTIONS
=========================

## 📦 What's Included

This clean package contains ONLY the essential files:

✅ config.php - Configuration
✅ TelegramBot.php - API wrapper  
✅ VideoManager.php - Video management
✅ webhook.php - Webhook handler
✅ cron.php - Auto-deletion
✅ setup.php - Initial setup
✅ admin.php - Admin panel
✅ README.md - Documentation
✅ data/file_ids.json - 391 working video IDs

## 🗑️ Removed Files

❌ All test files (test_*.php)
❌ All Python scripts (*.py)
❌ All debug files
❌ All development files
❌ All unnecessary files

## 📋 Deployment Steps

1. **Upload to Server**
   - Upload entire 'clean' folder contents to your server
   - Maintain directory structure

2. **Set Permissions**
   ```bash
   chmod 755 *.php
   chmod 777 data/
   chmod 777 data/*
   ```

3. **Test Server**
   - Visit: yourserver.com/setup.php
   - Should show "Setup complete!"

4. **Configure Cron Job**
   ```bash
   */5 * * * * php /path/to/your/bot/cron.php
   ```

5. **Access Admin Panel**
   - Visit: yourserver.com/admin.php
   - Login: admin / Haryanvi@123

## ✅ Verification

- Bot responds to @PornFlixBot
- /start command works
- /next sends videos with clean caption
- Videos auto-delete after 30 minutes
- Admin panel shows statistics

## 🎯 Bot Features

- 391 videos ready to serve
- Clean caption format
- Auto-deletion after 30 minutes
- User progress tracking
- Random video selection
- Comprehensive admin panel
- Activity logging

## 🔧 Configuration

Edit config.php to change:
- Bot token
- Webhook URL
- Auto-deletion time
- Admin credentials

## 📊 Statistics

The bot tracks:
- Total videos served
- User progress
- System activity
- Deletion queue

## 🎉 Ready for Production!

This is a clean, production-ready version with:
- No test files
- No debug code
- No unnecessary features
- Optimized performance
- Secure code
- Professional appearance

Upload and enjoy your video bot!
