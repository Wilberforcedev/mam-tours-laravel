Render Deployment Guide for MAM Tours

Quick guide to deploy your car rental app on Render.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 1: PUSH TO GITHUB

Make sure your code is on GitHub:

  git add .
  git commit -m "Ready for Render deployment"
  git push origin main

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 2: CREATE RENDER ACCOUNT

1. Go to https://render.com
2. Sign up with your GitHub account
3. Authorize Render to access your repositories

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 3: CREATE MYSQL DATABASE

1. Click "New +" → "MySQL"
2. Name: mam-tours-db
3. Database: mam_tours
4. User: mam_tours_user
5. Region: Choose closest to you
6. Plan: Free
7. Click "Create Database"

Save these credentials (you'll need them):
- Internal Database URL
- External Database URL
- Host
- Port
- Database
- Username
- Password

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 4: CREATE WEB SERVICE

1. Click "New +" → "Web Service"
2. Connect your GitHub repository
3. Select "mam-tours-laravel" repository

Configuration:
- Name: mam-tours
- Region: Same as database
- Branch: main
- Root Directory: (leave empty)
- Environment: Docker
- Plan: Free

Build Settings:
- Build Command: bash build.sh
- Start Command: bash start.sh

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 5: ADD ENVIRONMENT VARIABLES

Click "Environment" tab and add these variables:

APP_NAME=MAM Tours
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:KKmN0zpxMoDU1DkQa8ByHyZ/UD0b3+EoeKrJ3uRaktg=
APP_URL=https://mam-tours.onrender.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=(paste from database credentials)
DB_PORT=(paste from database credentials)
DB_DATABASE=mam_tours
DB_USERNAME=(paste from database credentials)
DB_PASSWORD=(paste from database credentials)

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@mamtours.com
MAIL_FROM_NAME=MAM Tours
MAIL_ADMIN_EMAIL=admin@example.com

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 6: DEPLOY

1. Click "Create Web Service"
2. Render will start building and deploying
3. Wait 5-10 minutes for first deployment
4. Watch the logs for any errors

Your app will be live at: https://mam-tours.onrender.com

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 7: POST-DEPLOYMENT SETUP

Once deployed, create your admin account from the service shell. The command prompts for the password without displaying it.

Option A: Using Render Shell
1. Go to your web service dashboard
2. Click "Shell" tab
3. Run: php artisan admin:create "Your Name" admin@example.com

Option B: Using SSH (if available)
1. Connect via SSH
2. Navigate to your app directory
3. Run the admin creation command

Seed the cars:
  php artisan db:seed --class=CarsSeeder

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

IMPORTANT NOTES

Free Tier Limitations:
- App spins down after 15 minutes of inactivity
- Takes 30-60 seconds to wake up on first request
- 750 hours/month of runtime
- Database: 1GB storage, 97 connections

Performance Tips:
- App will be slow on first load (cold start)
- Subsequent requests will be fast
- Consider upgrading to paid plan for 24/7 uptime

Custom Domain:
- Go to Settings → Custom Domain
- Add your domain
- Update DNS records as instructed
- Update APP_URL environment variable

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

TROUBLESHOOTING

Build Failed:
- Check build logs in Render dashboard
- Verify composer.json and package.json are valid
- Make sure all dependencies are listed

App Not Loading:
- Check runtime logs
- Verify database connection
- Check APP_KEY is set
- Verify all environment variables

Database Connection Error:
- Double-check database credentials
- Make sure DB_HOST uses internal database URL
- Verify database is running

500 Error:
- Check logs: Click "Logs" in dashboard
- Look for PHP errors
- Verify .env variables are correct

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

UPDATING YOUR APP

After making changes:

1. Push to GitHub:
   git add .
   git commit -m "Update description"
   git push origin main

2. Render automatically detects the push and redeploys

3. Monitor deployment in Render dashboard

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ADDING PAYMENT PROCESSING

When you're ready to accept payments:

1. Get Stripe live keys from https://stripe.com
2. Add to Render environment variables:
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_...

3. Render will automatically redeploy

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

You're all set! Your MAM Tours app should now be live on Render.
