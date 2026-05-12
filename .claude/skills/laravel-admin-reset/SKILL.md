---
name: laravel-admin-reset
description: |
  Reset or create a Laravel admin user with a specified email and password in any Laravel project.
  Use this skill whenever the user asks to reset admin credentials, create an admin account, set up an admin user with specific credentials, or bootstrap admin access for a Laravel application. This is especially useful when the user provides specific email/password combinations like "admin@82cf.com / test123" or similar credential resets.
  Make sure to use this skill for any request involving resetting, creating, or configuring admin user credentials in a Laravel backend, regardless of how the request is phrased — whether the user says "reset password", "create admin", "set up admin account", or "bootstrap admin credentials".
compatibility: "Laravel 10+, PHP 8.1+, Laravel Sanctum (optional)"
---

# Laravel Admin Reset Skill

This skill provides a reliable, repeatable way to reset or create an admin user in a Laravel project. It works with the standard Laravel `User` model and Sanctum authentication, and can be adapted to custom user models.

## Core Approach

There are **two ways** to reset/create an admin user:

1. **Via Laravel Artisan Tinker** — most reliable, works in any environment with tinker enabled
2. **Via Database Query** — when tinker is not available, direct DB manipulation

The skill prioritizes Tinker as it's the cleanest and most "Laravel-native" approach.

---

## Step 1: Locate the User Model and Determine Auth Configuration

Before touching the database, inspect the Laravel project to understand:

1. **Find the User model** — typically `app/Models/User.php`
2. **Check if `is_admin` or similar admin flag exists** — look at the model's fillable fields, casts, and any admin-related scopes
3. **Check auth configuration** — read `config/auth.php` to understand guards and providers
4. **Check for Sanctum** — if `Laravel\Sanctum\HasApiTokens` is present on the User model, the project uses Sanctum for API auth

```bash
# Look for the User model
find /var/www/laravel13x/app/Models -name "*.php" | xargs grep -l "class User"
cat /var/www/laravel13x/app/Models/User.php

# Check if is_admin or similar field exists in the model
grep -n "is_admin\|admin\|role" /var/www/laravel13x/app/Models/User.php

# Check auth config
cat /var/www/laravel13x/config/auth.php
```

---

## Step 2: Find the Default Guard and Provider

In `config/auth.php`, the default guard is typically `web` with an `eloquent` user provider. If the project uses Sanctum (API tokens), the guard configuration will indicate `sanctum` or a custom guard.

```php
// Example auth.php guard setup
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'sanctum', 'provider' => 'users'],
],
```

Note whether the project uses:
- **Sanctum** for API token auth (`createToken()` method on User model)
- **Session-based auth** (standard web login)
- **Custom auth** (e.g., phone + email dual login)

---

## Step 3: Reset the Admin User via Tinker

The most reliable method is using Laravel's built-in password hashing via Tinker. This avoids manual hash mismatches.

### If the project uses `is_admin` flag on the User model:

```bash
cd /path/to/laravel/project

php artisan tinker --execute="
use App\Models\User;
use Illuminate\Support\Facades\Hash;

\$user = User::where('email', 'admin@82cf.com')->first();

if (\$user) {
    // Update existing user
    \$user->password = Hash::make('test123');
    \$user->is_admin = true;
    \$user->save();
    echo 'Updated existing user: ' . \$user->email;
} else {
    // Create new user
    User::create([
        'name' => 'Admin',
        'email' => 'admin@82cf.com',
        'password' => Hash::make('test123'),
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);
    echo 'Created new admin user: admin@82cf.com';
}
"
```

### If the project uses a separate admin model (e.g., Admin model with `admins` table):

```bash
cd /path/to/laravel/project

php artisan tinker --execute="
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

\$admin = Admin::where('email', 'admin@82cf.com')->first();

if (\$admin) {
    \$admin->password = Hash::make('test123');
    \$admin->save();
    echo 'Updated admin: ' . \$admin->email;
} else {
    Admin::create([
        'name' => 'Admin',
        'email' => 'admin@82cf.com',
        'password' => Hash::make('test123'),
    ]);
    echo 'Created new admin: admin@82cf.com';
}
"
```

### If the project uses a roles/permissions system (SpatiePermission, etc.):

Adjust the creation query to assign the appropriate admin role after creating/updating the user.

---

## Step 4: Verify the Reset

After resetting, verify the user exists and can authenticate:

```bash
# Check the user in tinker
php artisan tinker --execute="
use App\Models\User;
\$user = User::where('email', 'admin@82cf.com')->first();
if (\$user) {
    echo 'User found: ' . \$user->email . PHP_EOL;
    echo 'is_admin: ' . (\$user->is_admin ?? 'N/A') . PHP_EOL;
    echo 'password set: ' . (\$user->password ? 'YES' : 'NO') . PHP_EOL;
} else {
    echo 'User NOT found';
}
"
```

---

## Step 5: (Optional) Test Authentication

If the project has a login endpoint, verify the credentials work:

```bash
# Using curl to test login endpoint
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@82cf.com","password":"test123"}'
```

Or if using Sanctum's token approach:

```bash
php artisan tinker --execute="
use App\Models\User;
\$user = User::where('email', 'admin@82cf.com')->first();
if (\$user && Hash::check('test123', \$user->password)) {
    echo 'Password verification PASSED';
} else {
    echo 'Password verification FAILED';
}
"
```

---

## Handling Edge Cases

### Case: `is_admin` field doesn't exist

If the User model doesn't have an `is_admin` field, check if the project uses a different pattern:
- Role-based (Spatie Permission): assign `admin` role
- Multi-tenancy: check tenant_id on user
- Custom admin model: may need a separate `Admin` model

### Case: User model uses `phone` as primary identifier

Some Laravel projects use phone instead of email for authentication. In that case, check `AuthController` for the login logic and adjust the query accordingly.

### Case: Hash mismatch

If authentication fails even after reset, ensure `config/hashing.php` uses `bcrypt` (Laravel default). If the project uses a different hasher (e.g., `argon2`), adjust the hash method accordingly.

### Case: User has `email_verified_at = null`

Some projects require email verification before login. Set `email_verified_at = now()` to bypass this during admin reset.

---

## Summary

1. Find and inspect the User model
2. Identify the admin flag field (`is_admin`, role, or separate Admin model)
3. Use `php artisan tinker` with `Hash::make()` to reset/create the user
4. Verify with a lookup query
5. Optionally test authentication

This approach is repeatable and works across most Laravel projects with standard auth setups.