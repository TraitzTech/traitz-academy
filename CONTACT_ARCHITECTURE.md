# Contact System Architecture & Integration Guide

## System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    USER SUBMITS FORM                        │
│              (resources/js/pages/Contact.vue)               │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ↓
        ┌────────────────────────────────┐
        │  Form Validation (Vue.js)      │
        │  - Name (required)             │
        │  - Email (required, valid)     │
        │  - Subject (required)          │
        │  - Message (10-5000 chars)     │
        └────────────────┬───────────────┘
                         │
                         ↓
        ┌────────────────────────────────────────────┐
        │  Submit to POST /contact                   │
        │  (PageController@submitContact)            │
        └────────────────┬──────────────────────────┘
                         │
                         ↓
        ┌──────────────────────────────────────┐
        │  Server-Side Validation              │
        │  (ContactFormRequest)                │
        │  Custom error messages               │
        └────────────────┬─────────────────────┘
                         │
                         ↓
        ┌──────────────────────────────────────┐
        │  Get Recipient Email                 │
        │  Option 1: Database                  │
        │  ├─ SiteSetting::get('contact_email')│
        │  Option 2: Fallback                  │
        │  └─ env('MAIL_FROM_ADDRESS')         │
        └────────────────┬─────────────────────┘
                         │
                         ↓
        ┌──────────────────────────────────────┐
        │  Send Email Notification             │
        │  (ContactFormSubmission)             │
        │  - via SMTP (Hostinger)              │
        │  - Professional format               │
        │  - Queued for background sending     │
        └────────────────┬─────────────────────┘
                         │
                         ↓
        ┌──────────────────────────────────────┐
        │  Return Success Response             │
        │  - Flash message to session          │
        │  - Redirect back to page             │
        └────────────────┬─────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────┐
│              USER SEES SUCCESS TOAST                        │
│         Form resets automatically                           │
└─────────────────────────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────┐
│          ADMIN RECEIVES EMAIL IN INBOX                      │
│     With sender details and full message content            │
└─────────────────────────────────────────────────────────────┘
```

## Component Interactions

### 1. Frontend Layer
```
Contact.vue (Vue 3 Component)
├── useForm() from Inertia.js
│   └── Handles form state and submission
├── usePage() from Inertia.js
│   └── Accesses $page.props.siteSettings
│       ├── contact_email
│       ├── contact_phone
│       └── contact_whatsapp
├── useToast() custom composable
│   └── Shows success/error notifications
└── Form submission
    └── POST /contact (with CSRF token)
```

### 2. Backend Layer
```
routes/web.php
└── POST /contact → PageController@submitContact

PageController::submitContact()
├── Validates with ContactFormRequest
├── Retrieves recipient email:
│   ├── SiteSetting::get('contact_email') - PRIMARY
│   └── config('mail.from.address') - FALLBACK
├── Creates notification
│   └── ContactFormSubmission
└── Sends email via SMTP
    └── Hostinger SMTP
```

### 3. Database Layer
```
site_settings table
├── contact_email
│   ├── Value: hello@traitzacademy.com
│   ├── Type: email
│   └── Group: contact
├── contact_phone
├── contact_whatsapp
└── contact_address
```

### 4. Middleware Layer
```
ShareSiteSettings Middleware
├── Runs on every request
├── Queries site_settings from cache
└── Shares with Inertia as $page.props.siteSettings
    └── Available in all Vue components
```

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                   ADMIN SETTINGS                            │
│              /admin/settings → Contact Tab                  │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Contact Email: [hello@traitzacademy.com]  [Save]  │   │
│  │  Phone:         [+234 XXX XXX XXXX]                │   │
│  │  WhatsApp:      [+234 XXX XXX XXXX]                │   │
│  │  Address:       [Physical address]                 │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────┬──────────────────────────────────────────┘
                  │ Saves to Database
                  ↓
    ┌─────────────────────────────────┐
    │  site_settings Table            │
    │  ├─ contact_email               │
    │  ├─ contact_phone               │
    │  ├─ contact_whatsapp            │
    │  └─ contact_address             │
    └─────────────┬───────────────────┘
                  │ Clears Cache
                  │ on Update
                  ↓
    ┌─────────────────────────────────┐
    │  Cache Layer                    │
    │  (1 hour TTL)                   │
    └─────────────┬───────────────────┘
                  │ Used by
                  ↓
    ┌─────────────────────────────────────┐
    │  ShareSiteSettings Middleware       │
    │  Caches query results               │
    └─────────────┬───────────────────────┘
                  │ Shared to
                  ↓
    ┌─────────────────────────────────────┐
    │  Vue Components                     │
    │  $page.props.siteSettings           │
    │  ├─ contact_email                   │
    │  ├─ contact_phone                   │
    │  ├─ contact_whatsapp                │
    │  └─ contact_address                 │
    └─────────────────────────────────────┘
```

## Configuration Hierarchy

```
Setting Resolution Order:
──────────────────────────

1. Database (SiteSetting::get('contact_email'))
   ├─ Managed via /admin/settings
   ├─ Editable without code changes
   └─ Cached for performance

2. Environment Variable Fallback
   └─ env('MAIL_FROM_ADDRESS')
      └─ Defined in .env file

ALWAYS PREFER: Database Setting
```

## Request/Response Flow

```
CLIENT REQUEST (Form Submission)
│
├─ POST /contact
├─ Content-Type: application/x-www-form-urlencoded
├─ CSRF Token: auto-included by Inertia
└─ Body:
   {
     "name": "John Doe",
     "email": "john@example.com",
     "subject": "Inquiry",
     "message": "Message content..."
   }

                    │
                    ↓

SERVER PROCESSING
│
├─ Route: routes/web.php
├─ Controller: PageController@submitContact
├─ Middleware: ShareSiteSettings, CSRF
├─ Request Validation: ContactFormRequest
│
├─ Validation Rules:
│  ├─ name: required|string|max:255
│  ├─ email: required|email|max:255
│  ├─ subject: required|string|max:255
│  └─ message: required|string|min:10|max:5000
│
├─ On Success:
│  ├─ Get recipient: SiteSetting::get('contact_email')
│  ├─ Create notification: ContactFormSubmission
│  ├─ Send email via SMTP
│  └─ Flash session: "success"
│
└─ On Error:
   └─ Return with validation errors

                    │
                    ↓

CLIENT RESPONSE
│
├─ Status: 302 (redirect back)
├─ Flash: success message
├─ Reset form
└─ Show toast notification
```

## File Dependencies

```
Contact.vue
├── @/layouts/PublicLayout.vue
│   ├── Footer shows dynamic contact info
│   └── Shares site settings via middleware
├── @inertiajs/vue3
│   ├── useForm
│   ├── usePage
│   └── route()
└── @/composables/useToast
    └── Toast notifications

PageController.php
├── ContactFormRequest (validation)
├── SettingHelper (get recipient email)
├── ContactFormSubmission (notification)
├── AnonymousNotifiable (email routing)
└── SiteSetting model

routes/web.php
├── GET /contact → PageController@contact
└── POST /contact → PageController@submitContact

Middleware
├── ShareSiteSettings (auto-shares settings)
├── CSRF (protection)
└── HandleInertiaRequests

Database
└── site_settings table
    └── contact_* settings
```

## Email Flow

```
┌─────────────────────────────────────┐
│   ContactFormSubmission Notification│
│   (app/Notifications)               │
└────────────────┬────────────────────┘
                 │
                 ↓ via() → ['mail']
                 │
        ┌────────────────────────┐
        │  Email Notification    │
        │  - Subject             │
        │  - Greeting            │
        │  - Sender info         │
        │  - Message body        │
        │  - Reply button        │
        └────────────┬───────────┘
                     │
                     ↓ toMail()
                     │
        ┌────────────────────────────────┐
        │  Laravel Mail System            │
        │  (Mailable)                     │
        └────────────┬───────────────────┘
                     │
                     ↓
        ┌────────────────────────────────┐
        │  Queue (if enabled)             │
        │  ShouldQueue trait              │
        └────────────┬───────────────────┘
                     │
                     ↓
        ┌────────────────────────────────┐
        │  SMTP Transport                 │
        │  - Host: smtp.hostinger.com     │
        │  - Port: 465 (SSL)              │
        │  - Auth: notify@traitz.tech     │
        └────────────┬───────────────────┘
                     │
                     ↓
        ┌────────────────────────────────┐
        │  Email Delivered               │
        │  To: contact_email (from DB)   │
        └────────────────────────────────┘
```

## Cache Strategy

```
Setting Retrieval
│
├─ First Call
│  ├─ Query: site_settings table
│  ├─ Store in cache (key: site_setting_contact_email)
│  ├─ TTL: 3600 seconds (1 hour)
│  └─ Return value
│
└─ Subsequent Calls (within 1 hour)
   ├─ Check cache first (FAST)
   └─ Return cached value

Cache Invalidation
│
├─ When setting saved via /admin/settings
│  └─ SiteSetting::clearCache() called
│      └─ All settings cache cleared
│
├─ Manual clear
│  └─ php artisan cache:forget site_setting_*
│
└─ Automatic expiry after 1 hour
   └─ Next request re-queries database
```

## Security Measures

```
✓ CSRF Protection
  └─ Token auto-included in Inertia forms

✓ Input Validation
  └─ ContactFormRequest with custom rules

✓ Email Validation
  └─ 'email' rule ensures valid format

✓ Message Sanitization
  └─ HTML escaped in email template

✓ Rate Limiting (Optional)
  └─ Can add throttle middleware if needed

✓ HTTPS
  └─ All communication encrypted

✓ Database
  └─ Parameterized queries (Eloquent)
```

## Testing Coverage

```
ContactFormTest.php
│
├─ Test 1: Page loads successfully
│  └─ Verifies GET /contact returns 200
│
├─ Test 2: Form submission works
│  └─ Valid data → Success response
│
├─ Test 3: Validation errors caught
│  └─ Invalid email → Error response
│
├─ Test 4: Required fields enforced
│  └─ Empty fields → Validation errors
│
├─ Test 5: Message length validated
│  └─ Short message → Validation error
│
└─ Test 6: Settings shared with page
   └─ $page.props.siteSettings available
```

---

This architecture ensures:
- ✅ Dynamic configuration
- ✅ High performance (caching)
- ✅ Security (validation, CSRF)
- ✅ Maintainability (clean separation)
- ✅ Testability (comprehensive tests)
- ✅ Scalability (queue support)
