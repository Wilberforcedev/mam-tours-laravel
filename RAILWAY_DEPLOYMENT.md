# 🚂 Railway Deployment Guide for MAM Tours

## ✅ Pre-Deployment Checklist (Already Done!)

I've created these files for you:
- ✅ `Procfile` - Tells Railway how to start your app
- ✅ `nixpacks.toml` - Build configuration
- ✅ `.railwayignore` - Files to exclude from deployment

## 🚀 Deployment Steps

### Step 1: Push Your Code to GitHub

If you haven't already, initialize Git and push to GitHub:

```bash
# Initialize git (if not already done)
git init

# Add all files
git add .

# Commit
git commit -m "Prepare for Railway deployment"

# Add your GitHub repository as remote
git remote add origin https://github.com/YOUR_USERNAME/mam-tours-laravel.git

# Push to GitHub
git push -u origin main
```

**Note**: If your default branch is `master`, use `master` instead of `main`.

### Step 2: Sign Up for Railway

1. Go to https://railway.app
2. Click **"Login"** or **"Start a New Project"**
3. Sign in with your **GitHub account**
4. Authorize Railway to access your repositories

### Step 3: Create New Project

1. Click **"New Project"**
2. Select **"Deploy from GitHub repo"**
3. Choose **"mam-tours-laravel"** (or your repository name)
4. Railway will automatically detect it's a Laravel app

### Step 4: Add MySQL Database

1. In your project dashboard, click **"New"**
2. Select **"Database"**
3. Choose **"Add MySQL"**
4. Railway automatically creates the database and connects it

### Step 5: Configure Environment Variables

Click on your web service → **"Variables"** tab → Add these variables:

#### Required Variables:

```env
APP_NAME=MAM Tours
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:KKmN0zpxMoDU1DkQa8ByHyZ/UD0b3+EoeKrJ3uRaktg=
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

# Database (Railway auto-fills these from MySQL service)
DB_CONNECTION=mysql
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Mail Configuration (use your settings)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@mamtours.com
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ADMIN_EMAIL=admin@mamtours.com

# Payment Configuration (add your keys later)
STRIPE_KEY=pk_test_your_key_here
STRIPE_SECRET=sk_test_your_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_here

# SMS Configuration (optional, add later)
TWILIO_ACCOUNT_SID=your_sid
TWILIO_AUTH_TOKEN=your_token
TWILIO_PHONE_NUMBER=your_number

# Security Settings
SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Activity Logging
ACTIVITY_LOG_ENABLED=true
```

**Important**: Railway automatically provides these variables when you add MySQL:
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`

Just reference them using `${{MYSQL_HOST}}` syntax.

### Step 6: Generate Public Domain

1. Go to your web service → **"Settings"**
2. Scroll to **"Networking"**
3. Click **"Generate Domain"**
4. Railway will give you a URL like: `https://mam-tours-production.up.railway.app`
5. Copy this URL and update `APP_URL` variable with it

### Step 7: Deploy!

Railway automatically deploys when you:
- Push to GitHub
- Change environment variables
- Click "Deploy" button

**First deployment takes 3-5 minutes.**

### Step 8: Run Database Migrations

After first deployment:

1. Go to your web service
2. Click **"Deployments"** tab
3. Click on the latest deployment
4. Click **"View Logs"**
5. Check if migrations ran successfully

If migrations didn't run, you can run them manually:
1. Click on your service → **"Settings"**
2. Under **"Deploy"**, find **"Custom Start Command"**
3. It should already have: `php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT`

### Step 9: Seed Your Database (Optional)

To add initial data:

1. In Railway dashboard, click your service
2. Go to **"Settings"** → **"Deploy"**
3. Temporarily change start command to:
   ```bash
   php artisan migrate:fresh --seed --force && php artisan serve --host=0.0.0.0 --port=$PORT
   ```
4. Click **"Deploy"** to redeploy
5. After seeding completes, change back to:
   ```bash
   php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
   ```

### Step 10: Test Your Application

1. Visit your Railway domain (e.g., `https://mam-tours-production.up.railway.app`)
2. Test key features:
   - ✅ Home page loads
   - ✅ Registration works
   - ✅ Login works
   - ✅ Booking system works
   - ✅ Admin panel accessible

---

## 🔧 Post-Deployment Configuration

### 1. Configure Payment Keys

Once you have real Stripe keys:
1. Go to Variables tab
2. Update `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
3. Railway auto-redeploys

### 2. Set Up Email

For production emails:
1. Sign up for Mailgun (free: 5000 emails/month)
2. Get SMTP credentials
3. Update mail variables in Railway
4. Test email notifications

### 3. Set Up SMS (Optional)

For SMS notifications:
1. Sign up for Twilio or Africa's Talking
2. Get API credentials
3. Update SMS variables in Railway
4. Test SMS notifications

### 4. Configure Custom Domain (Optional)

To use your own domain:
1. Go to service → **"Settings"** → **"Networking"**
2. Click **"Custom Domain"**
3. Add your domain (e.g., `mamtours.com`)
4. Update DNS records as instructed
5. Railway provides free SSL automatically

---

## 📊 Monitoring Your Application

### View Logs:
1. Click your service
2. Go to **"Deployments"** tab
3. Click on any deployment
4. View real-time logs

### Check Metrics:
1. Click your service
2. Go to **"Metrics"** tab
3. See CPU, Memory, Network usage

### Database Access:
1. Click on MySQL service
2. Go to **"Data"** tab
3. View/edit database directly
4. Or use **"Connect"** to get connection string for external tools

---

## 🔄 Updating Your Application

After making changes locally:

```bash
# Commit changes
git add .
git commit -m "Your update message"

# Push to GitHub
git push origin main
```

Railway automatically detects the push and redeploys! ✨

---

## 💰 Free Tier Limits

Railway free tier includes:
- **$5 credit per month**
- **500 hours of usage**
- **100GB bandwidth**
- **Unlimited projects**

This is usually enough for:
- Development/testing
- Small production apps
- 100-500 active users

### When to Upgrade:

Upgrade to paid tier ($5/month) when:
- You exceed free credits
- Need 24/7 uptime (no sleep)
- Have more than 500 users
- Need better performance

---

## 🆘 Troubleshooting

### Issue: "Application Error" or 500 Error

**Solution**:
1. Check logs in Railway dashboard
2. Common causes:
   - Missing environment variables
   - Database connection issues
   - Missing `APP_KEY`
3. Verify all variables are set correctly

### Issue: Database Connection Failed

**Solution**:
1. Make sure MySQL service is running
2. Check database variables are using `${{MYSQL_*}}` syntax
3. Verify `DB_CONNECTION=mysql` is set

### Issue: Assets Not Loading (CSS/JS)

**Solution**:
1. Make sure `npm run build` ran successfully
2. Check build logs for errors
3. Verify `public` folder is not in `.gitignore`

### Issue: Migrations Not Running

**Solution**:
1. Check start command includes `php artisan migrate --force`
2. View deployment logs for migration errors
3. Manually run migrations if needed

### Issue: App Sleeps After Inactivity

**Solution**:
- This is normal on free tier
- App wakes up on first request (takes 10-30 seconds)
- Upgrade to paid tier for 24/7 uptime
- Or use a service like UptimeRobot to ping your app every 5 minutes

---

## ✅ Success Checklist

After deployment, verify:

- [ ] Application loads at Railway URL
- [ ] Database is connected and working
- [ ] User registration works
- [ ] Login/logout works
- [ ] Booking system functional
- [ ] Admin panel accessible
- [ ] Images and assets load correctly
- [ ] Forms submit successfully
- [ ] Email notifications configured (if needed)
- [ ] Payment system configured (if needed)

---

## 🎉 You're Live!

Once deployed, your MAM Tours application will be accessible at:
`https://your-app-name.up.railway.app`

Share this URL with users to start testing!

---

## 📞 Need Help?

If you encounter any issues:
1. Check Railway logs first
2. Review this guide
3. Check Railway documentation: https://docs.railway.app
4. Ask me for help!

**Your application is ready for Railway deployment!** 🚀