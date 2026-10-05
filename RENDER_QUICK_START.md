Render Quick Start

Your app is now configured for Render! Here's what to do:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 1: COMMIT AND PUSH

  git add .
  git commit -m "Configure for Render deployment"
  git push origin main

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 2: GO TO RENDER

1. Visit https://render.com
2. Sign up/Login with GitHub
3. Click "New +" → "Web Service"
4. Connect your repository

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 3: CONFIGURE SERVICE

Name: mam-tours
Environment: Docker
Region: Choose closest to you
Branch: main
Plan: Free

Build Command: bash build.sh
Start Command: bash start.sh

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 4: ADD DATABASE

Before creating the web service:

1. Click "New +" → "MySQL"
2. Name: mam-tours-db
3. Plan: Free
4. Click "Create Database"
5. Save the credentials shown

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 5: ADD ENVIRONMENT VARIABLES

In your web service, add these (use your database credentials):

APP_NAME=MAM Tours
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:KKmN0zpxMoDU1DkQa8ByHyZ/UD0b3+EoeKrJ3uRaktg=
APP_URL=https://your-app-name.onrender.com

DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=mam_tours
DB_USERNAME=your-database-username
DB_PASSWORD=your-database-password

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_FROM_ADDRESS=noreply@mamtours.com
MAIL_ADMIN_EMAIL=admin@example.com

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 6: DEPLOY

Click "Create Web Service" and wait for deployment (5-10 minutes)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

STEP 7: CREATE ADMIN ACCOUNT

After deployment, use Render Shell:

1. Go to your service dashboard
2. Click "Shell" tab
3. Run: php artisan admin:create "Your Name" admin@example.com
The command prompts for the password without displaying it.

4. Run: php artisan db:seed --class=CarsSeeder

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DONE!

Your app will be live at: https://your-app-name.onrender.com

For detailed instructions, see RENDER_DEPLOYMENT.md

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

IMPORTANT: Free Tier Notes

- App sleeps after 15 minutes of inactivity
- Takes 30-60 seconds to wake up
- Perfect for testing and demos
- Upgrade to paid plan for 24/7 uptime
