# Abiy Tsom - Great Lent Tracker

A comprehensive web application for Ethiopian Orthodox Tewahedo Church members to track their spiritual journey during the 55-day Great Lent (Abiy Tsom).

## Features

### For Church Members
- **Mobile-First Design** - Optimized for smartphone use
- **Daily Content Feed** - Bible readings, Mezmur, Sinksar, and spiritual books
- **Weekly Themes** - Track the 8 weeks of Great Lent (Zewerede through Hosanna)
- **Daily Checklist** - Track prayer, fasting, and spiritual activities
- **Progress Tracking** - Visual graphs showing completion rates and areas to improve
- **Passcode Lock** - Optional PIN protection for privacy
- **Multi-Language** - English and Amharic support
- **Dark/Light Theme** - Automatic theme switching
- **Local Storage** - Works offline, syncs to server when online

### For Admins
- **Season Management** - Configure yearly lent dates
- **Weekly Theme Editor** - Manage the 8 weekly themes
- **Daily Content Manager** - Feed daily Bible readings, Mezmur, Sinksar, books, and reflections
- **Activity Builder** - Create custom checklist activities
- **Translation Manager** - Manage English/Amharic translations
- **Dashboard** - Overview of published content and member engagement

## Tech Stack

- **Backend:** Laravel 11 (PHP 8.2+)
- **Frontend:** Blade Templates + Tailwind CSS v4 + Alpine.js
- **Database:** MySQL 8
- **Hosting:** Shared cPanel hosting (XAMPP local development)

## Installation

### Prerequisites
- PHP 8.2+
- MySQL 8
- Composer
- Node.js 18+ & npm

### Local Development (XAMPP)

1. **Clone the repository:**
   ```bash
   cd C:\xampp\htdocs
   git clone <your-repo-url> Abiy_Tsom
   cd Abiy_Tsom
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install Node dependencies:**
   ```bash
   npm install
   ```

4. **Create database:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database: `abiy_tsom`
   - Or via MySQL CLI:
     ```bash
     C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE abiy_tsom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     ```

5. **Configure environment:**
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```

6. **Update `.env` file:**
   ```env
   APP_NAME="Abiy Tsom"
   APP_URL=http://localhost/Abiy_Tsom/public
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=abiy_tsom
   DB_USERNAME=root
   DB_PASSWORD=
   ```

7. **Run migrations:**
   ```bash
   php artisan migrate
   ```

8. **Seed the database (creates admin user):**
   ```bash
   php artisan db:seed
   ```
   
   **Default Admin Credentials:**
   - Email: `admin@abiy-tsom.com`
   - Password: `password`

9. **Build frontend assets:**
   ```bash
   npm run build
   ```
   
   For development with hot reload:
   ```bash
   npm run dev
   ```

10. **Access the application:**
    - Member Site: `http://localhost/Abiy_Tsom/public/`
    - Admin Login: `http://localhost/Abiy_Tsom/public/admin/login`

### Production Deployment (cPanel Shared Hosting)

1. **Setup Git on cPanel:**
   - Go to cPanel → Git Version Control
   - Clone your repository to a subdomain folder
   - Set up deployment key if using private repo

2. **Create subdomain:**
   - cPanel → Domains → Create Subdomain
   - Example: `tsom.yourchurch.org`
   - Point document root to: `/public_html/Abiy_Tsom/public`

3. **Create MySQL database:**
   - cPanel → MySQL Databases
   - Create database: `youruser_abiy_tsom`
   - Create user and grant all privileges

4. **Upload files via Git or FTP:**
   ```bash
   git clone <your-repo> Abiy_Tsom
   cd Abiy_Tsom
   ```

5. **Install dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   ```

6. **Configure `.env`:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   Update database credentials:
   ```env
   DB_DATABASE=youruser_abiy_tsom
   DB_USERNAME=youruser_dbuser
   DB_PASSWORD=your_secure_password
   ```

7. **Run migrations:**
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```

8. **Set permissions:**
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

9. **Access your site:**
   - `https://tsom.yourchurch.org`

## Usage Guide

### Admin Workflow

1. **Login** at `/admin/login`
2. **Create a Season** (e.g., 2026):
   - Set start date (e.g., Feb 16, 2026)
   - Set end date / Easter (e.g., Apr 12, 2026)
   - Mark as "Active"
3. **Create 8 Weekly Themes** (Zewerede through Hosanna)
4. **Create Activities** (e.g., "Pray 7 times", "Fast until 3 PM")
5. **Add Daily Content** for all 55 days:
   - Bible reading reference
   - Mezmur (YouTube link)
   - Sinksar
   - Spiritual book
   - Daily reflection
   - Mark as "Published"
6. **Manage Translations** (optional Amharic translations)

### Member Workflow

1. **Visit** the homepage
2. **Enter baptism name** to register
3. **Receive a unique token** (stored in browser)
4. **View today's content:**
   - Read Bible passage
   - Listen to Mezmur
   - Read Sinksar
   - Check off daily activities
5. **Track progress** via graphs
6. **Enable passcode** for privacy (optional)
7. **Switch language** (EN ↔ AM)
8. **Toggle theme** (Light ↔ Dark)

## Database Schema

### Core Tables
- `users` - Admin users
- `members` - Church members (token-based auth)
- `lent_seasons` - Yearly lent configurations
- `weekly_themes` - 8 weekly themes per season
- `daily_contents` - 55 days of curated content
- `activities` - Checklist activities
- `member_checklists` - Daily check-ins per member
- `translations` - Admin-managed i18n strings

## Brand Colors

- **Primary Blue:** `#0a6286` - Headers, primary buttons
- **Primary Gold:** `#e2ca18` - Accents, highlights, secondary buttons

## File Structure

```
Abiy_Tsom/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin CRUD controllers
│   │   │   └── Member/         # Member-facing controllers
│   │   └── Middleware/         # Custom middleware
│   └── Models/                 # Eloquent models
├── database/
│   ├── migrations/             # Database schema
│   └── seeders/                # Initial data
├── resources/
│   ├── css/app.css             # Tailwind + brand colors
│   ├── js/app.js               # Alpine.js initialization
│   ├── lang/                   # Translations (en, am)
│   └── views/
│       ├── layouts/            # Base layouts (admin, member)
│       ├── admin/              # Admin views
│       └── member/             # Member views
├── routes/web.php              # All routes
└── public/                     # Web root (point subdomain here)
```

## API Endpoints (AJAX)

- `POST /api/member/checklist/toggle` - Toggle checklist item
- `POST /api/member/settings` - Update member preferences
- `GET /api/member/progress/data` - Fetch progress chart data

## Security Features

- CSRF protection on all forms
- Hashed passwords (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection (Blade escaping)
- Rate limiting on login
- Optional member passcode lock

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### "500 Internal Server Error"
- Check `.env` file exists and has valid DB credentials
- Verify `storage/` and `bootstrap/cache/` are writable
- Check Apache error logs in cPanel

### "Mix file not found"
- Run `npm run build` to compile assets
- Ensure `public/build/` folder exists

### Database connection failed
- Verify MySQL service is running (XAMPP Control Panel)
- Check DB credentials in `.env`
- Ensure database exists

### Styles not loading
- Clear browser cache
- Run `npm run build` again
- Check `public/build/manifest.json` exists

## Contributing

This is a church-specific project. For feature requests or bug reports, contact the admin.

## License

Proprietary - © 2026 Ethiopian Orthodox Tewahedo Church

## Support

For technical support:
- Email: admin@abiy-tsom.com
- Church contact: [Your church contact info]

---

**May your Great Lent journey be blessed! ✨**
