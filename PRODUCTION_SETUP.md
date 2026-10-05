Production Setup Checklist

Before you deploy MAM Tours to production, here's everything you need to have ready.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PRE-DEPLOYMENT CHECKLIST

1. Environment Configuration

□ Copy .env.production to .env on your server
□ Generate new APP_KEY: php artisan key:generate
□ Set APP_ENV=production
□ Set APP_DEBUG=false
□ Update APP_URL with your actual domain

2. Database Setup

□ Create production database
□ Update database credentials in .env
□ Run migrations: php artisan migrate --force
□ Seed initial data: php artisan db:seed (optional)
□ Create admin user: php artisan admin:create "Admin Name" admin@example.com

3. File Storage

Option A: AWS S3 (Recommended)
□ Create S3 bucket for uploads
□ Set up IAM user with S3 access
□ Add AWS credentials to .env
□ Set FILESYSTEM_DRIVER=s3

Option B: Local Storage
□ Create storage symlink: php artisan storage:link
□ Set proper permissions: chmod -R 755 storage
□ Set FILESYSTEM_DRIVER=public

4. Payment Integration

Stripe Setup
□ Sign up at https://stripe.com
□ Get live API keys (not test keys!)
□ Add STRIPE_KEY and STRIPE_SECRET to .env
□ Set up webhook endpoint: https://your-domain.com/webhook/stripe
□ Add STRIPE_WEBHOOK_SECRET to .env

Mobile Money Setup
□ Contact MTN Mobile Money for API access
□ Contact Airtel Money for API access
□ Add credentials to .env
□ Test transactions in sandbox first

5. Email Configuration

Recommended: Mailgun (Free tier: 5,000 emails/month)
□ Sign up at https://mailgun.com
□ Verify your domain
□ Get SMTP credentials
□ Update mail settings in .env:

  MAIL_MAILER=smtp
  MAIL_HOST=smtp.mailgun.org
  MAIL_PORT=587
  MAIL_USERNAME=your_mailgun_username
  MAIL_PASSWORD=your_mailgun_password
  MAIL_ENCRYPTION=tls
  MAIL_FROM_ADDRESS=noreply@mamtours.com

6. SMS Notifications

Recommended: Africa's Talking (Best for Uganda)
□ Sign up at https://africastalking.com
□ Get API key
□ Add to .env:

  AFRICAS_TALKING_USERNAME=your_username
  AFRICAS_TALKING_API_KEY=your_api_key

Alternative: Twilio
□ Sign up at https://twilio.com
□ Get Account SID and Auth Token
□ Add to .env

7. Security Hardening

□ Set SESSION_SECURE_COOKIE=true (requires HTTPS)
□ Update SANCTUM_STATEFUL_DOMAINS with your domain
□ Generate strong WEBHOOK_SECRET
□ Enable HTTPS/SSL certificate
□ Set up firewall rules
□ Create the admin account with a unique password through the hidden prompt

8. Performance Optimization

Redis Setup (Recommended)
□ Install Redis on server
□ Update .env:

  CACHE_DRIVER=redis
  SESSION_DRIVER=redis
  QUEUE_CONNECTION=redis

Without Redis (Basic)
□ Set CACHE_DRIVER=file
□ Set SESSION_DRIVER=file
□ Set QUEUE_CONNECTION=database

9. Queue Workers

Set up queue worker to process jobs:

  php artisan queue:work --tries=3

Or use Supervisor (recommended):

  sudo apt-get install supervisor

Create supervisor config at /etc/supervisor/conf.d/mam-tours-worker.conf:

  [program:mam-tours-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3
  autostart=true
  autorestart=true
  user=www-data
  numprocs=2
  redirect_stderr=true
  stdout_logfile=/path/to/your/project/storage/logs/worker.log

Then:

  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start mam-tours-worker:*

10. Scheduled Tasks (Cron)

Add to crontab (crontab -e):

  * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

This enables:
- Automated booking reminders
- Database backups
- Cleanup tasks

11. Error Monitoring (Optional but Recommended)

Sentry Setup
□ Sign up at https://sentry.io (free tier available)
□ Create new project
□ Get DSN
□ Add to .env:

  SENTRY_LARAVEL_DSN=https://your_dsn@sentry.io/project_id

12. Backup Strategy

Automated Backups
□ Configure backup disk in .env
□ Test backup: php artisan backup:run
□ Set up automated backups via cron (already included in schedule)

Manual Backup

  Database backup:
  php artisan backup:run

  Or manual MySQL dump:
  mysqldump -u username -p database_name > backup.sql

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DEPLOYMENT PROCESS

First Time Deployment

1. Upload code to server

  git clone https://github.com/your-repo/mam-tours.git
  cd mam-tours

2. Install dependencies

  composer install --no-dev --optimize-autoloader
  npm ci --production
  npm run build

3. Configure environment

  cp .env.production .env
  (Edit .env with your settings)
  php artisan key:generate

4. Set up database

  php artisan migrate --force
  php artisan db:seed
  php artisan admin:create "Admin Name" admin@example.com

5. Set permissions

  chmod -R 755 storage bootstrap/cache
  php artisan storage:link

6. Optimize

  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

Subsequent Deployments

Just run the deployment script:

  chmod +x deploy.sh
  ./deploy.sh

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

POST-DEPLOYMENT TESTING

Critical Tests

□ Home page loads correctly
□ User registration works
□ User login works
□ Booking flow completes successfully
□ Payment processing works (test with small amount)
□ Email notifications send
□ SMS notifications send (if configured)
□ Admin panel accessible
□ File uploads work (ID/passport, condition reports)
□ Invoice generation works
□ All images load correctly

Security Tests

□ HTTPS is working
□ Admin panel requires authentication
□ CSRF protection is active
□ Rate limiting works on login
□ Unverified users can only pay cash
□ File upload validation works

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MONITORING

What to Monitor

1. Application Logs

  tail -f storage/logs/laravel.log

2. Queue Workers

  php artisan queue:work --verbose
  
  Or check supervisor status:
  sudo supervisorctl status

3. Database Performance
   - Monitor slow queries
   - Check connection pool

4. Disk Space
   - Uploaded files
   - Log files
   - Database size

Performance Metrics

- Page load times
- API response times
- Queue job processing times
- Database query times

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

TROUBLESHOOTING

Common Issues

500 Error
- Check storage/logs/laravel.log
- Verify .env configuration
- Check file permissions

Database Connection Failed
- Verify database credentials
- Check if MySQL is running
- Test connection: php artisan tinker then DB::connection()->getPdo();

Queue Jobs Not Processing
- Check if queue worker is running
- Verify Redis connection (if using Redis)
- Check worker logs

Emails Not Sending
- Test SMTP connection
- Check mail logs
- Verify firewall allows SMTP port

File Uploads Failing
- Check storage permissions
- Verify S3 credentials (if using S3)
- Check upload size limits in php.ini

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MAINTENANCE

Regular Tasks

Daily
- Monitor error logs
- Check queue status

Weekly
- Review booking analytics
- Check disk space
- Review failed jobs

Monthly
- Update dependencies: composer update
- Review and archive old logs
- Database optimization: php artisan db:optimize
- Test backup restoration

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SCALING CONSIDERATIONS

When you start getting more traffic:

1. Enable Redis for caching and sessions
2. Use queue workers for background jobs
3. Set up CDN for static assets
4. Database optimization - add indexes, optimize queries
5. Load balancing - multiple app servers
6. Database replication - read replicas

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SUPPORT CONTACTS

- Hosting Issues: Contact your hosting provider
- Payment Issues: Stripe support, Mobile Money providers
- Email Issues: Mailgun support
- SMS Issues: Africa's Talking support

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

EMERGENCY PROCEDURES

Site Down

1. Check server status
2. Review error logs
3. Put in maintenance mode: php artisan down
4. Fix issue
5. Bring back up: php artisan up

Database Corruption

1. Put site in maintenance mode
2. Restore from latest backup
3. Verify data integrity
4. Bring site back up

Security Breach

1. Immediately put site in maintenance mode
2. Change all passwords and API keys
3. Review access logs
4. Patch vulnerability
5. Restore from clean backup if needed
6. Notify affected users

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Ready to Deploy?

Once you've checked off everything above, you're ready to go live!

Remember: Test everything in a staging environment first if possible.
