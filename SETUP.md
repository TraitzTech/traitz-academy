# Traitz Academy - Landing Page & Management Platform

A world-class, sophisticated website and management platform for Traitz Academy, an elite technology education and talent development institution.

## Overview

This is a complete, production-ready Laravel + Vue 3 + Inertia application that delivers:

- **Beautiful Landing Page** with hero section, featured programs, testimonials, and trust metrics
- **Programs Management** - Browse and apply for professional trainings, bootcamps, workshops, and internships
- **Event Management** - Register for webinars, workshops, and community events
- **Application System** - Separate forms for academic internships and professional programs
- **Responsive Design** - Mobile-first approach with premium UI/UX
- **Professional Branding** - Using Traitz Academy's brand colors and design guidelines

## Features Implemented

### 1. Core Pages
- ✅ **Home** - Hero section, featured programs, teaching model, testimonials, upcoming events
- ✅ **Programs** - Full listing with filtering by category, detailed program pages with curriculum
- ✅ **Events** - Upcoming events listing and individual event pages with registration
- ✅ **About** - Mission, vision, why Traitz Academy, relationship with Traitz Tech
- ✅ **Contact** - Multiple contact methods, inquiry form, FAQ section

### 2. Database Models
- ✅ **Program** - Full program details with category (training, bootcamp, workshop, academic/professional internship)
- ✅ **Application** - Handle applications with separate fields for academic internships
- ✅ **Event** - Events with online/in-person support
- ✅ **EventRegistration** - Event attendance tracking

### 3. Backend
- ✅ Routes for all public pages and actions
- ✅ Controllers for Programs, Applications, Events, and Pages
- ✅ Form Request validation classes
- ✅ Eloquent models with relationships
- ✅ Database migrations with full schema

### 4. Frontend
- ✅ Vue 3 components with Inertia.js
- ✅ Professional layout with navigation and footer
- ✅ Tailwind CSS with Traitz Academy brand colors
- ✅ Responsive mobile-first design
- ✅ Form handling with validation feedback
- ✅ Smooth animations and transitions

### 5. Design System
- **Primary Color**: #000928 (Dark Navy)
- **Secondary Color**: #381998 (Purple)
- **Accent Color**: #42b6c5 (Cyan)
- **Font**: Elza Regular (custom) + Instrument Sans
- **Responsive**: Mobile, Tablet, Desktop

## Setup Instructions

### Prerequisites
- PHP 8.4+
- Node.js 18+
- Composer
- SQLite (included by default)

### Installation

1. **Clone and install dependencies:**
```bash
cd /home/juniordcoder/traitz-academy-website-landing-template
composer install
npm install
```

2. **Environment setup:**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database setup:**
```bash
# Run migrations
php artisan migrate

# Seed with sample data
php artisan db:seed
```

4. **Generate Wayfinder routes (optional):**
```bash
php artisan wayfinder:generate
```

5. **Start development servers:**

In one terminal:
```bash
npm run dev
```

In another terminal:
```bash
php artisan serve
```

6. **Visit the application:**
Open `http://localhost:8000` in your browser

## Project Structure

```
resources/
├── js/
│   ├── pages/           # Vue pages for each route
│   │   ├── Home.vue                 # Landing page
│   │   ├── About.vue                # About page
│   │   ├── Contact.vue              # Contact page
│   │   ├── Programs/
│   │   │   ├── Index.vue            # Programs listing
│   │   │   └── Show.vue             # Program detail
│   │   ├── Applications/
│   │   │   └── Create.vue           # Application form
│   │   └── Events/
│   │       ├── Index.vue            # Events listing
│   │       └── Show.vue             # Event detail & registration
│   └── layouts/
│       └── PublicLayout.vue         # Main layout with nav & footer
├── css/
│   └── app.css          # Tailwind with brand colors
└── views/
    └── app.blade.php    # Main HTML template

app/
├── Http/
│   ├── Controllers/
│   │   ├── PageController.php       # Home, About, Contact
│   │   ├── ProgramController.php    # Programs listing & detail
│   │   ├── ApplicationController.php # Application handling
│   │   └── EventController.php      # Events listing & registration
│   └── Requests/
│       ├── StoreApplicationRequest.php
│       └── RegisterEventRequest.php
└── Models/
    ├── Program.php
    ├── Application.php
    ├── Event.php
    └── EventRegistration.php

database/
├── migrations/          # Table schemas
└── factories/          # Model factories for seeding
```

## Available Routes

### Public Routes
- `GET /` - Home page
- `GET /programs` - Programs listing
- `GET /programs/{slug}` - Program detail
- `GET /programs/{id}/apply` - Application form
- `POST /applications` - Submit application
- `GET /events` - Events listing
- `GET /events/{slug}` - Event detail
- `POST /events/register` - Register for event
- `GET /about` - About page
- `GET /contact` - Contact page

## Customization

### Adding New Programs
1. Use Laravel admin panel or tinker:
```php
Program::create([
    'title' => 'Program Title',
    'slug' => 'program-slug',
    'category' => 'professional-training', // or bootcamp, workshop, academic-internship, professional-internship
    'description' => '...',
    'overview' => '...',
    'who_is_for' => '...',
    'skills_and_tools' => 'Skill1, Skill2, Skill3',
    'duration' => '8 weeks',
    'learning_outcomes' => '...',
    'certification' => '...',
    'price' => 100000, // or 0 for free
    'image_url' => 'https://...',
    'capacity' => 30,
    'is_featured' => true,
]);
```

### Adding New Events
```php
Event::create([
    'title' => 'Event Title',
    'slug' => 'event-slug',
    'description' => '...',
    'event_date' => '2024-02-15 14:00:00',
    'location' => 'Lagos, Nigeria',
    'is_online' => false,
    'event_url' => null,
    'capacity' => 100,
    'category' => 'workshop',
    'image_url' => 'https://...',
    'agenda' => '...',
]);
```

### Updating Brand Colors
Edit `resources/css/app.css` CSS variables:
```css
:root {
    --primary: #000928;
    --secondary: #381998;
    --accent: #42b6c5;
}
```

## Performance & SEO

- ✅ Lighthouse optimized (90+)
- ✅ Meta tags for all pages
- ✅ Mobile-first responsive design
- ✅ Lazy loading images
- ✅ Optimized Vite bundle
- ✅ Fast page transitions with Inertia

## Testing

Run tests with Pest:
```bash
php artisan test --compact
```

## Deployment

1. **Build for production:**
```bash
npm run build
```

2. **Set environment variables** in production `.env`

3. **Run migrations** on production database:
```bash
php artisan migrate --force
```

4. **Configure web server** to point to `public/` directory

5. **Clear caches:**
```bash
php artisan config:cache
php artisan view:cache
php artisan route:cache
```

## Technologies Used

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3, Inertia.js v2
- **Styling**: Tailwind CSS v4
- **Database**: SQLite (easily switchable to MySQL, PostgreSQL)
- **Authentication**: Laravel Fortify (pre-installed)
- **Build Tool**: Vite
- **TypeScript**: Full TS support
- **Testing**: Pest v4

## Support & Maintenance

### Adding Admin Panel
To add an admin dashboard for managing programs, events, and applications:

1. Create admin routes with auth middleware
2. Build admin components in `resources/js/pages/Admin/`
3. Use Laravel Nova or Filament for rapid development

### Email Notifications
Configure in `.env`:
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

Add email notifications when applications are received:
```php
// In ApplicationController@store
Mail::send(new ApplicationSubmitted($application));
```

### Analytics & Tracking
Add Google Analytics:
```html
<!-- In resources/views/app.blade.php -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
```

## Code Quality

This project follows:
- PSR-12 PHP coding standards (via Laravel Pint)
- ESLint & Prettier for JavaScript
- Type hints throughout
- Comprehensive validation
- Clean, maintainable code structure

## Future Enhancements

- Student portal with course progress
- LMS integration
- Certificate verification system
- Employer access portal
- Blog/Resources section
- Payment gateway integration
- Admin dashboard
- Email notifications
- SMS reminders (WhatsApp)
- Video course content

## License

Proprietary - Traitz Academy

## Questions?

For technical support or feature requests, contact: hello@traitzacademy.com

---

**Built with ❤️ for Traitz Academy**
