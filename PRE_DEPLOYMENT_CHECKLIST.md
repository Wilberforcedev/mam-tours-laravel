Pre-Deployment Checklist

Quick checklist before you deploy to production.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CODE READY?

☑ All test files removed
☑ All guide/documentation files cleaned up
☑ README humanized and updated
☑ Deployment scripts ready
□ All features tested locally
□ No console.log() or dd() left in code
□ Error handling in place

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CONFIGURATION FILES

☑ .env.production template created
☑ deploy.sh script ready
☑ Procfile configured for Railway
☑ nixpacks.toml configured
☑ .railwayignore set up
□ .env configured with production values

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DATABASE

□ Production database created
□ Database credentials ready
□ Migrations tested
□ Seeder ready (optional)
□ Admin user creation command tested

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

THIRD-PARTY SERVICES

Payment (Required)
□ Stripe account created
□ Live API keys obtained (not test keys!)
□ Webhook endpoint configured
□ Test transaction completed

Email (Required)
□ Email service chosen (Mailgun, SendGrid, etc.)
□ SMTP credentials obtained
□ Sender domain verified
□ Test email sent successfully

SMS (Optional but Recommended)
□ SMS provider chosen (Africa's Talking or Twilio)
□ API credentials obtained
□ Test SMS sent successfully

File Storage
□ Storage solution chosen (S3 or local)
□ If S3: Bucket created and credentials ready
□ If local: Storage permissions configured

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SECURITY

□ APP_DEBUG=false in production
□ APP_ENV=production set
□ Strong APP_KEY generated
□ HTTPS/SSL certificate ready
□ SESSION_SECURE_COOKIE=true (requires HTTPS)
□ No default admin account is seeded
□ Webhook secrets generated
□ CORS settings configured

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PERFORMANCE

□ Redis available (recommended) or fallback to file/database
□ Queue worker strategy decided
□ Caching strategy decided
□ CDN considered for static assets (optional)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MONITORING (Optional but Recommended)

□ Error tracking service (Sentry) configured
□ Log monitoring plan in place
□ Backup strategy decided
□ Uptime monitoring considered

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

RAILWAY SPECIFIC

□ GitHub repository ready
□ Railway account created
□ MySQL database will be added on Railway
□ Environment variables list prepared
□ Domain name ready (optional)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

POST-DEPLOYMENT PLAN

□ Test checklist prepared (see PRODUCTION_SETUP.md)
□ Admin user creation command ready
□ Car seeding plan ready
□ Customer communication plan (if migrating)
□ Rollback plan in case of issues

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

QUICK RAILWAY DEPLOYMENT

Once everything above is checked:

1. Push to GitHub

  git add .
  git commit -m "Ready for production"
  git push origin main

2. Deploy on Railway

  - Go to https://railway.app
  - New Project → Deploy from GitHub
  - Select repository
  - Add MySQL database
  - Configure environment variables
  - Generate domain

3. After deployment

  Create admin user (via the service shell; enter its password at the hidden prompt):
  php artisan admin:create "Admin Name" admin@example.com
  
  Seed cars (optional):
  php artisan db:seed --class=CarsSeeder

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

NEED HELP?

- Deployment Guide: See RAILWAY_DEPLOYMENT.md
- Production Setup: See PRODUCTION_SETUP.md
- README: See README.md

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Ready to deploy? Make sure everything is checked off above!
