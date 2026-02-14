# 🚗 MAM Tours - Car Rental Management System

A modern Laravel-based car rental management system with integrated payment processing, automated notifications, and admin dashboard.

## 🚀 Features

- **User Management**: Registration, login, profile management, KYC verification
- **Booking System**: Real-time car availability, booking management, pricing calculation
- **Payment Integration**: Stripe (credit/debit cards) and Mobile Money (MTN/Airtel)
- **Admin Dashboard**: Manage bookings, cars, users, KYC verifications, and reviews
- **Automated Notifications**: Email/SMS reminders for payments, pickups, returns
- **Review System**: Customer reviews and ratings
- **Invoice Generation**: Professional PDF invoices

## 📋 Requirements

- PHP 7.3 or higher
- MySQL 5.7 or higher
- Composer
- Node.js & NPM

## 🛠️ Local Development Setup

### 1. Clone and Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build frontend assets
npm run build
```

### 2. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mam_tours
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Seed Database (Optional)

```bash
php artisan db:seed
```

### 5. Start Development Server

```bash
php artisan serve
```

Visit: http://127.0.0.1:8000

## 🚂 Railway Deployment

### Quick Deploy

1. **Push to GitHub**
   ```bash
   git add .
   git commit -m "Deploy to Railway"
   git push origin main
   ```

2. **Deploy on Railway**
   - Go to https://railway.app
   - Sign in with GitHub
   - Click "New Project" → "Deploy from GitHub repo"
   - Select your repository
   - Add MySQL database: "New" → "Database" → "MySQL"

3. **Configure Environment Variables**
   
   Add these in Railway dashboard (Variables tab):
   ```env
   APP_NAME=MAM Tours
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:KKmN0zpxMoDU1DkQa8ByHyZ/UD0b3+EoeKrJ3uRaktg=
   APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
   
   DB_CONNECTION=mysql
   DB_HOST=${{MYSQL_HOST}}
   DB_PORT=${{MYSQL_PORT}}
   DB_DATABASE=${{MYSQL_DATABASE}}
   DB_USERNAME=${{MYSQL_USER}}
   DB_PASSWORD=${{MYSQL_PASSWORD}}
   
   SESSION_DRIVER=file
   CACHE_DRIVER=file
   
   # Add your payment keys
   STRIPE_KEY=your_stripe_key
   STRIPE_SECRET=your_stripe_secret
   ```

4. **Generate Domain**
   - Go to Settings → Networking → Generate Domain
   - Your app will be live at: `https://your-app.railway.app`

For detailed deployment instructions, see `RAILWAY_DEPLOYMENT.md`

## 🔧 Configuration

### Payment Setup

1. **Stripe**: Get API keys from https://stripe.com
2. **Mobile Money**: Configure provider credentials
3. Update `.env` with your keys

### Email/SMS Setup

1. **Email**: Configure SMTP settings (Mailgun, SendGrid, etc.)
2. **SMS**: Set up Twilio or Africa's Talking
3. Update `.env` with credentials

### Scheduled Tasks

For automated notifications, add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📁 Project Structure

```
mam-tours-laravel/
├── app/
│   ├── Console/Commands/      # Automated tasks
│   ├── Http/Controllers/      # Application controllers
│   ├── Models/                # Database models
│   └── Services/              # Business logic (Payment, Notifications)
├── database/
│   ├── migrations/            # Database schema
│   └── seeders/               # Sample data
├── public/                    # Public assets
├── resources/
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript & Vue components
│   └── views/                 # Blade templates
├── routes/
│   └── web.php               # Application routes
└── tests/                    # Automated tests
```

## 🎯 Key Routes

- `/` - Home page
- `/register` - User registration
- `/login` - User login
- `/bookings` - Make a booking
- `/dashboard` - User dashboard
- `/admin` - Admin dashboard
- `/admin/kyc` - KYC verification
- `/payments/{booking}` - Payment page

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 📝 Default Admin Account

After seeding:
- **Email**: admin@mamtours.com
- **Password**: password

**⚠️ Change this in production!**

## 🔐 Security Features

- CSRF protection
- XSS prevention
- SQL injection protection
- Rate limiting
- Secure password hashing
- Two-factor authentication ready
- Activity logging

## 📞 Support

For issues or questions:
1. Check `RAILWAY_DEPLOYMENT.md` for deployment help
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check Railway logs in dashboard

## 📄 License

This project is proprietary software for MAM Tours.

## 🙏 Credits

Built with:
- Laravel 8
- Vue.js 3
- Tailwind CSS
- Stripe API
- MySQL

---

**MAM Tours** - Modern Car Rental Management System