# Pawnshop Management System - Implementation Guide

## Completed Items

### 1. Packages Installed
-  bezhansalleh/filament-shield (v3.9) - for role/permission management UI
-  barryvdh/laravel-dompdf (v3.1) - for PDF generation
-  pxlrbt/filament-excel (already installed) - for Excel exports
-  spatie/laravel-activitylog (already migrated) - for activity tracking
-  spatie/laravel-permission (already installed) - for role/permission system

### 2. Database Migrations Created
-  `2025_10_02_064004_create_loan_renewals_table.php` - tracks loan renewals
-  `2025_10_02_064011_create_interest_charges_table.php` - tracks interest charges
-  `2025_10_02_064013_add_interest_rate_overdue_to_loans_table.php` - adds overdue interest rate field

### 3. Models Created/Updated
-  `app/Models/LoanRenewal.php` - new model for loan renewals
-  `app/Models/InterestCharge.php` - new model for interest charges
-  `app/Models/Loan.php` - updated with LogsActivity trait and new relationships

## Remaining Implementation Steps

### STEP 1: Add LogsActivity to Other Models

Update the following models to include activity logging:

**app/Models/Customer.php:**
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

// Add to class:
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['first_name', 'last_name', 'email', 'phone', 'is_active'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

**app/Models/Item.php:**
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

// Add to class:
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'status', 'appraised_value', 'sale_price'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

**app/Models/Payment.php:**
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

// Add to class:
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['payment_number', 'loan_id', 'amount', 'status'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

**app/Models/Sale.php:**
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

// Add to class:
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['sale_number', 'item_id', 'customer_id', 'final_price', 'status'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

### STEP 2: Run Database Migrations

```bash
php artisan migrate
```

### STEP 3: Install and Configure Shield

```bash
php artisan vendor:publish --tag="filament-shield-config"
php artisan shield:install
```

### STEP 4: Create Role Seeder

Create `database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin = Role::create(['name' => 'Admin']);
        $manager = Role::create(['name' => 'Manager']);
        $cashier = Role::create(['name' => 'Cashier']);

        // Admin gets all permissions (handled by Shield)
        
        // Manager permissions
        $managerPermissions = [
            'view_customer', 'view_any_customer',
            'view_item', 'view_any_item', 'create_item', 'update_item',
            'view_loan', 'view_any_loan', 'update_loan',
            'view_payment', 'view_any_payment',
            'view_sale', 'view_any_sale',
        ];
        
        // Cashier permissions
        $cashierPermissions = [
            'view_customer', 'view_any_customer', 'create_customer',
            'view_loan', 'create_loan',
            'view_payment', 'create_payment',
            'view_sale', 'create_sale',
        ];
    }
}
```

Run the seeder:
```bash
php artisan db:seed --class=RoleSeeder
```

### STEP 5: Create PDF Service Classes

The following files need to be created in `app/Services/PDF/`:

1. **LoanContractPDF.php** - generates loan contract PDF
2. **PaymentReceiptPDF.php** - generates payment receipt PDF
3. **SaleInvoicePDF.php** - generates sale invoice PDF

### STEP 6: Create Scheduled Command for Interest Calculation

```bash
php artisan make:command CalculateOverdueInterest
```

Then implement the command to:
- Find loans past due_date with status 'active' or 'overdue'
- Calculate daily interest based on interest_rate_overdue
- Create InterestCharge records
- Update loan balance_remaining

### STEP 7: Create Filament Resources

1. **ActivityLogResource** - for viewing activity logs
2. **ReportsPage** - custom page for generating reports

### STEP 8: Update LoanResource

Add the improved renewal action with:
- Track renewal history in loan_renewals table
- Create renewal record with processed_by = auth()->id()
- Update loan due_date and add interest

### STEP 9: Add PDF Print Actions

Add "Imprimir" actions to:
- LoanResource (contract)
- PaymentResource (receipt)
- SaleResource (invoice)

### STEP 10: Create Business Configuration

Create `config/business.php`:

```php
<?php

return [
    'name' => env('BUSINESS_NAME', 'Casa de Empeño'),
    'address' => env('BUSINESS_ADDRESS', ''),
    'phone' => env('BUSINESS_PHONE', ''),
    'email' => env('BUSINESS_EMAIL', ''),
    'tax_id' => env('BUSINESS_TAX_ID', ''),
    'logo_path' => env('BUSINESS_LOGO_PATH', 'images/logo.png'),
    
    'pdf' => [
        'header_color' => '#1e40af',
        'footer_text' => 'Gracias por su preferencia',
    ],
];
```

### STEP 11: Configure AdminPanelProvider

Update `app/Providers/Filament/AdminPanelProvider.php` to include Shield plugin.

## Testing Checklist

- [ ] Run migrations without errors
- [ ] Create test users with different roles
- [ ] Test loan renewal functionality
- [ ] Test automatic interest calculation command
- [ ] Generate PDF documents (loan, payment, sale)
- [ ] Test activity log tracking
- [ ] Generate reports (Excel/PDF)
- [ ] Test role-based permissions

## Notes

- All enum values in database remain in English (active, pending, paid, etc.)
- All UI labels are in Spanish as required
- Business configuration can be set via .env file
- Activity logs track who changed what and when
- Interest charges are automatically calculated daily via scheduled command

