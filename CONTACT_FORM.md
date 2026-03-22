# Contact Form Implementation Guide

## Overview

The contact form is now fully functional with:
- **Dynamic contact information** (email, phone, WhatsApp) pulled from site settings
- **Form validation** on both client and server side
- **Email notifications** sent to a configurable email address
- **Real-time feedback** with toast notifications
- **Fully tested** with comprehensive test suite

## How It Works

### 1. Contact Form Submission Flow

1. User fills out the contact form on `/contact`
2. Form data is validated on the client side (Vue.js)
3. Form submits to `POST /contact` endpoint
4. Server validates the data using `ContactFormRequest`
5. Email notification is sent to the configured email
6. User receives success confirmation

### 2. Email Configuration

#### Option A: Database Setting (Recommended)
The email recipient is stored in the database as a site setting:
- **Key:** `contact_email`
- **Default:** `hello@traitzacademy.com`
- **Editable:** Yes, via Admin Settings page (`/admin/settings`)

To change the email that receives contact form submissions:
1. Go to `/admin/settings`
2. Click the "Contact" tab
3. Update "Contact Email" field
4. Save

#### Option B: Environment Variable Fallback
If no database setting is found, the system falls back to:
```env
MAIL_FROM_ADDRESS=noreply@example.com
```

This is in your `.env` file.

### 3. Email Notification Details

The email sent to the admin includes:
- **Sender's name, email, and subject**
- **Full message content**
- **Reply button** for quick responses
- **Formatted nicely** for readability
- **Automatically queued** for background sending (if queue is configured)

### 4. Dynamic Contact Information Display

The contact page displays information from site settings:

```vue
<!-- Email -->
<a :href="`mailto:${$page.props.siteSettings.contact_email}`">
  {{ $page.props.siteSettings.contact_email }}
</a>

<!-- Phone -->
<a :href="`tel:${$page.props.siteSettings.contact_phone}`">
  {{ $page.props.siteSettings.contact_phone }}
</a>

<!-- WhatsApp -->
<a :href="getWhatsAppLink()">
  {{ $page.props.siteSettings.contact_whatsapp }}
</a>
```

All values are pulled from the database and can be updated in the admin settings without code changes.

## Files Changed/Created

### Backend
- **PageController** (`app/Http/Controllers/PageController.php`)
  - Added `submitContact()` method to handle form submissions
  
- **ContactFormRequest** (`app/Http/Requests/ContactFormRequest.php`)
  - Form validation with custom error messages
  - Validates: name, email, subject, message
  
- **ContactFormSubmission** (`app/Notifications/ContactFormSubmission.php`)
  - Email notification that gets sent to admin
  - Includes formatted message and sender details

### Frontend
- **Contact.vue** (`resources/js/pages/Contact.vue`)
  - Reactive form using Inertia `useForm`
  - Dynamic display of contact information
  - Form submission with error handling
  - Success/error toast notifications
  - WhatsApp link generation

### Routes
- `POST /contact` → `PageController@submitContact`

### Tests
- **ContactFormTest** (`tests/Feature/ContactFormTest.php`)
  - 6 comprehensive tests
  - Validates form submission, validation, and settings

## Form Fields

| Field | Type | Validation | Notes |
|-------|------|-----------|-------|
| Name | Text | Required, max 255 chars | Visitor's full name |
| Email | Email | Required, valid email | Contact email address |
| Subject | Text | Required, max 255 chars | Inquiry subject |
| Message | Textarea | Required, min 10, max 5000 chars | Detailed message |

## Admin Settings for Contact Page

Go to `/admin/settings` → **Contact** tab to manage:

- **Contact Email** - Where form submissions are sent
- **Contact Phone** - Displayed on contact page
- **WhatsApp Number** - WhatsApp link (with country code, e.g., +234123456789)
- **Physical Address** - Mailing address

## Email Configuration (`.env`)

Ensure your `.env` is properly configured for email sending:

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com  # or your mail service
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Traitz Academy"
```

## Testing

Run the contact form tests:

```bash
php artisan test tests/Feature/ContactFormTest.php
```

All tests should pass:
- ✓ Contact page loads
- ✓ Valid form submission works
- ✓ Validation catches errors
- ✓ Required fields enforced
- ✓ Message length validated
- ✓ Settings shared with page

## Security Considerations

1. **Rate Limiting** - Consider adding rate limiting to prevent spam:
   ```php
   Route::post('/contact', [PageController::class, 'submitContact'])
       ->middleware('throttle:3,1'); // 3 requests per minute
   ```

2. **CSRF Protection** - Automatically handled by Inertia/Laravel

3. **Email Validation** - Form requests validate email format

4. **Message Sanitization** - Messages are HTML-escaped in emails

## Customization

### Change recipient email
Via admin panel: `/admin/settings` → Contact tab

### Change email template
Edit: `app/Notifications/ContactFormSubmission.php`
- Customize the `toMail()` method
- Change subject, greeting, content, etc.

### Add spam protection
Install Honeypot or reCAPTCHA package and add to form

### Add file attachments
Modify the form to accept files and update notification class

## Troubleshooting

### Emails not sending
1. Check `.env` mail configuration
2. Test with: `php artisan tinker` → `Mail::to('test@email.com')->send(new TestMail());`
3. Check Laravel logs: `storage/logs/`

### Form not submitting
1. Check browser console for errors
2. Verify route exists: `php artisan route:list | grep contact`
3. Check network tab to see response details

### Settings not showing
1. Run seeder: `php artisan db:seed --class=SiteSettingSeeder`
2. Check database: `SELECT * FROM site_settings WHERE group='contact';`
3. Clear cache: `php artisan cache:clear`

## Features Added

✅ Fully functional contact form
✅ Server-side validation
✅ Email notifications to admin
✅ Dynamic contact information
✅ Toast notifications for user feedback
✅ Form error display
✅ Loading state during submission
✅ Database-driven configuration
✅ Comprehensive test coverage
✅ Professional email formatting

## Next Steps

1. Configure your mail service in `.env`
2. Update contact email in admin settings
3. Test the form by submitting a test message
4. Customize the email template if needed
5. Add rate limiting if needed for spam prevention
