# 🚗 MAM Tours - Car Rental Made Simple

Hey there! Welcome to MAM Tours, a car rental platform built to make booking vehicles as smooth as possible. Whether you're renting out cars or managing bookings, we've got you covered.

## What's Inside?

This isn't just another booking system. Here's what makes it tick:

- **Easy Sign-Up & Login**: Get customers registered and verified with ID upload (just like Jumia does it)
- **Smart Booking**: Real-time availability, instant pricing, and a booking flow that actually works
- **Flexible Payments**: Stripe card processing is supported; mobile money initiation is currently simulated and needs a real provider integration before production use
- **Admin Control**: Manage everything from one dashboard - bookings, cars, customers, verifications, reviews
- **Stay Connected**: Automatic email and SMS notifications keep everyone in the loop
- **Customer Reviews**: Let your customers share their experience
- **Professional Invoices**: Generate clean PDF invoices automatically

## What You'll Need

Before diving in, make sure you have:
- PHP 7.3+ (the engine that runs everything)
- MySQL 5.7+ (where all your data lives)
- Composer (PHP's package manager)
- Node.js & NPM (for the frontend stuff)

## Getting Started Locally

Want to run this on your machine? Here's how:

**Step 1: Install Everything**
```bash
composer install    # Gets all PHP packages
npm install        # Gets all JavaScript packages
npm run build      # Builds the frontend
```

**Step 2: Set Up Your Environment**
```bash
cp .env.example .env           # Create your config file
php artisan key:generate       # Generate security key
```

Now open `.env` and add your database details:
```env
DB_DATABASE=mam_tours
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Step 3: Set Up the Database**
```bash
php artisan migrate    # Creates all the tables
php artisan db:seed    # Adds sample data (optional but helpful)
```

**Step 4: Fire It Up**
```bash
php artisan serve
```

Open your browser and go to http://127.0.0.1:8000 - you're live!

## Ready to Go Live?

Deploying to Railway is pretty straightforward. Here's the quick version:

**1. Push Your Code to GitHub**
```bash
git add .
git commit -m "Ready for production"
git push origin main
```

**2. Set Up on Railway**
- Head to https://railway.app and sign in with GitHub
- Click "New Project" → "Deploy from GitHub repo"
- Pick this repository
- Add a MySQL database: "New" → "Database" → "MySQL"

**3. Add Your Settings**

In the Railway dashboard, go to Variables and add:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

# Database (Railway fills these automatically)
DB_HOST=${{MYSQL_HOST}}
DB_DATABASE=${{MYSQL_DATABASE}}
DB_USERNAME=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}

# Your payment keys
STRIPE_KEY=your_actual_stripe_key
STRIPE_SECRET=your_actual_stripe_secret
```

**4. Get Your URL**
- Go to Settings → Networking → Generate Domain
- Boom! You're live at something like `https://mam-tours.railway.app`

Need more details? Check out `RAILWAY_DEPLOYMENT.md` for the full walkthrough.

## Setting Things Up

**Payments**

Stripe can process card payments when valid Stripe keys and webhook settings are configured. Mobile money initiation is currently simulated; do not treat it as a live MTN or Airtel integration.

The mobile money callback endpoint is `POST /webhooks/mobile-money`. It accepts callbacks only when `WEBHOOK_SECRET` is configured, and requires `X-Webhook-Signature` to be the lowercase hexadecimal HMAC-SHA256 of the exact raw request body. Configure the same long random secret on the trusted callback sender. Callbacks without a valid signature are rejected, and repeated successful callbacks do not send duplicate payment notifications.

You'll want to get your payment providers sorted:
- **Stripe**: Grab your API keys from https://stripe.com (handles card payments)
- **Mobile Money**: Set up MTN and Airtel Money credentials
- Pop everything into your `.env` file

**Emails & SMS**

To keep customers updated:
- **Email**: Use Mailgun, SendGrid, or any SMTP service
- **SMS**: Sign up for Twilio or Africa's Talking
- Add the credentials to `.env`

**Automated Reminders**

Want to send automatic payment and pickup reminders? Add this to your server's crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and checks if any notifications need to go out.

## How It's Organized

Here's where everything lives:

```
app/
├── Console/Commands/      # Automated tasks (reminders, backups)
├── Http/Controllers/      # Handles all the requests
├── Models/                # Your data structures
└── Services/              # Payment processing, notifications, etc.

database/
├── migrations/            # Database structure
└── seeders/               # Sample data for testing

resources/
├── css/                   # Styles
├── js/                    # JavaScript & Vue components
└── views/                 # All the pages (Blade templates)

public/                    # Images, compiled assets
routes/web.php            # Where URLs map to controllers
tests/                    # Automated tests
```

## Important Pages

- `/` - Home page with featured cars
- `/register` - Sign up new customers
- `/login` - Customer login
- `/bookings` - Book a car
- `/profile` - Upload ID/passport, manage account
- `/dashboard` - Customer's booking history
- `/admin` - Admin control panel
- `/admin/kyc` - Verify customer IDs
- `/payments/{booking}` - Complete payment

## Testing

Want to make sure everything works? Run the tests:

```bash
php artisan test                        # Run everything
php artisan test --testsuite=Feature    # Just the feature tests
php artisan test --coverage             # See what's covered
```

## First Time Setup

The database seeder does not create admin or demo-user accounts. Create an administrator from a trusted application shell; the password is entered through a hidden prompt:

```bash
php artisan admin:create "MAM Tours Admin" admin@example.com
```

If this installation previously ran an older seeder, change or remove any demo accounts that were created at that time.

## Security

We take security seriously. The app includes:
- CSRF protection on all forms
- XSS and SQL injection prevention
- Rate limiting to prevent abuse
- Secure password hashing (bcrypt)
- Activity logging for admin actions
- Ready for two-factor authentication

Plus, unverified users can only pay with cash until they upload their ID - just like Jumia does it.

## Need Help?

Running into issues? Here's what to check:

1. **Deployment problems?** → See `RAILWAY_DEPLOYMENT.md`
2. **App errors?** → Check `storage/logs/laravel.log`
3. **Production issues?** → View logs in your Railway dashboard

## Built With

This project runs on some solid tech:
- **Laravel 8** - The PHP framework that powers everything
- **Vue.js 3** - For interactive frontend components
- **Tailwind CSS** - Clean, modern styling
- **Stripe API** - Card payment processing
- **MySQL** - Reliable data storage

---

Made with ☕ for MAM Tours