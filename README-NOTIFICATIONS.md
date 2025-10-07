# Customer Notification System - Setup Guide

This pawnshop system has a comprehensive notification system for customers and staff.

## Email Notifications (Already Working!)

### Automatic Emails Sent:

1. **Loan Reminder** (3 days before due date)
   - Sent daily at 9:00 AM
   - Reminds customers their loan is expiring soon

2. **Loan Overdue** (when loan becomes overdue)
   - Sent daily at 1:00 AM for all overdue loans
   - Includes interest charges applied

3. **Payment Received** (when customer makes a payment)
   - Sent immediately when payment is created
   - Includes payment receipt link

### Email Configuration

Update your `.env` file with your email provider settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Casa de Empeño"
```

For Gmail, you need to:
1. Enable 2-Factor Authentication
2. Create an "App Password" in your Google Account settings
3. Use the App Password as MAIL_PASSWORD

## 📱 SMS Notifications (Optional - Requires Setup)

To enable SMS notifications via Twilio:

### 1. Install Twilio SDK

```bash
composer require twilio/sdk
```

### 2. Add Twilio Credentials to `.env`

```env
TWILIO_SID=your_account_sid
TWILIO_TOKEN=your_auth_token
TWILIO_SMS_FROM=+1234567890
```

### 3. Add to `config/services.php`

```php
'twilio' => [
    'sid' => env('TWILIO_SID'),
    'token' => env('TWILIO_TOKEN'),
    'sms_from' => env('TWILIO_SMS_FROM'),
],
```

### 4. Uncomment SMS channel in notifications

In `app/Notifications/LoanOverdueNotification.php`, uncomment:

```php
$channels[] = \App\Channels\SmsChannel::class;
```

### 5. Update `app/Channels/SmsChannel.php`

Uncomment the Twilio implementation code in the `send()` method.

## WhatsApp Notifications (Optional - Requires Setup)

To enable WhatsApp notifications via Twilio WhatsApp Business:

### 1. Set up Twilio WhatsApp (same as SMS above)

### 2. Add WhatsApp number to `.env`

```env
TWILIO_WHATSAPP_FROM=+1234567890
```

### 3. Add to `config/services.php`

```php
'twilio' => [
    // ... existing config
    'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
],
```

### 4. Uncomment WhatsApp channel in notifications

```php
$channels[] = \App\Channels\WhatsAppChannel::class;
```

### 5. Update `app/Channels/WhatsAppChannel.php`

Uncomment the Twilio implementation code.

## Admin/Staff Notifications

Staff members (Admin and Gerente roles) receive:
- Database notifications (bell icon in admin panel)
- Shows when loans become overdue
- Updated every 30 seconds automatically

## Scheduled Tasks

To run scheduled tasks, add this to your cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or for Windows, use Task Scheduler to run:

```bash
php artisan schedule:run
```

Every minute.

### Manual Testing Commands

```bash
# Test loan reminder emails
php artisan loans:send-reminders

# Test overdue interest calculation and emails
php artisan loans:calculate-overdue-interest

# View scheduled tasks
php artisan schedule:list
```

## Notification Status

| Type | Email | SMS | WhatsApp | Database |
|------|-------|-----|----------|----------|
| Loan Reminder |  | 🔧 | 🔧 |  |
| Loan Overdue |  | 🔧 | 🔧 |  (Staff) |
| Payment Received |  | 🔧 | 🔧 |  |

 = Active | 🔧 = Needs API configuration |  = Not implemented

## Testing Notifications

To test email sending without setting up a full SMTP server, use:

### Option 1: Mailtrap (Development)

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
```

### Option 2: Log Driver (Just logs to file)

```env
MAIL_MAILER=log
```

Emails will be written to `storage/logs/laravel.log`

##  Customizing Notifications

Edit notification content in:
- `app/Notifications/LoanReminderNotification.php`
- `app/Notifications/LoanOverdueNotification.php`
- `app/Notifications/PaymentReceivedNotification.php`

Each notification has methods:
- `toMail()` - Email content
- `toSms()` - SMS text (140 chars max recommended)
- `toWhatsApp()` - WhatsApp message (Markdown supported)
- `toArray()` - Database notification data
