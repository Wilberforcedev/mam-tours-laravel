# 🚗 MAM Tours - Car Rental Made Simple

Hey there! Welcome to MAM Tours, a car rental platform built to make booking vehicles as smooth as possible. Whether you're renting out cars or managing bookings, we've got you covered.

## What's Inside?

This isn't just another booking system. Here's what makes it tick:

- **Easy Sign-Up & Login**: Get customers registered and verified with ID upload (just like Jumia does it)
- **Smart Booking**: Date/time overlap checks, expiring holds, configurable rental limits, and transparent quotations
- **Fleet Operations**: Maintenance blocks, service/accident history, and pickup/return mileage and fuel records
- **Flexible Payments**: Configurable Stripe card payments and IntaSend mobile-money push for Ugandan MTN/Airtel; both remain disabled until provider credentials and staging tests are complete
- **Admin Control**: Manage everything from one dashboard - bookings, cars, customers, verifications, reviews
- **Stay Connected**: Notification hooks and scheduled in-app fleet reminders; verify production email/SMS delivery separately
- **Customer Reviews**: Let your customers share their experience
- **Professional Invoices**: Generate clean PDF invoices automatically

## What You'll Need

Before diving in, make sure you have:
- PHP 8.0+ (Composer requires PHP ^8.0)
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
STRIPE_ENABLED=false
STRIPE_CURRENCY=UGX
INTASEND_ENABLED=false
INTASEND_SECRET_KEY=
INTASEND_WEBHOOK_CHALLENGE=
INTASEND_BASE_URL=https://api.intasend.com
RENTAL_CURRENCY=UGX
```

**4. Get Your URL**
- Go to Settings → Networking → Generate Domain
- Boom! You're live at something like `https://mam-tours.railway.app`

Need more details? Check out `RAILWAY_DEPLOYMENT.md` for the full walkthrough.

## Setting Things Up

**Payments**

Stripe card payments are implemented but disabled unless `STRIPE_ENABLED=true`, `STRIPE_CURRENCY`, API keys and the signed webhook secret are configured. Verify that your Stripe account supports the selected currency before enabling live payments.

Ugandan MTN/Airtel mobile-money push is implemented through IntaSend and remains disabled unless `INTASEND_ENABLED=true`, `INTASEND_SECRET_KEY`, and `INTASEND_WEBHOOK_CHALLENGE` are configured. Set the same long random challenge in the IntaSend dashboard and your hosting secrets. Configure these callback URLs in the provider dashboards:
- Stripe: `POST /api/webhooks/stripe` using `STRIPE_WEBHOOK_SECRET`
- IntaSend: `POST /api/webhooks/intasend` using the configured challenge

Only provider-confirmed, amount-matched and currency-matched payments are recorded as completed. Do not treat provider initiation as payment confirmation. Stripe refunds are provider-processed; mobile-money/cash refunds must be transferred through the approved workflow and recorded by finance.

Before production, run migrations in staging, back up the database and storage, test provider callbacks and receipts, and confirm that KYC files are stored on the private disk. Do not expose the private disk through a public storage symlink.

You'll want to get your payment providers sorted:
- **Stripe**: API keys and signed webhook secret from your Stripe dashboard
- **IntaSend**: live account activation for Uganda mobile-money push, secret key, and webhook challenge
- Pop the credentials into hosting secrets, not source control

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

Run the tests and PHP lint before deploying. The current automated suite is not a substitute for staging tests of booking concurrency, private documents, and real provider callbacks:

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