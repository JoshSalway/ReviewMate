# WorkOS Setup Guide

ReviewMate uses WorkOS AuthKit for authentication — passwordless email magic links + SSO.

## 1. Create a WorkOS account

Go to [https://workos.com](https://workos.com) and create a free account.

## 2. Create an application

In the WorkOS dashboard:
1. Click "Applications" → "Create application"
2. Name: `ReviewMate`
3. Type: `Web application`

## 3. Configure redirect URIs

In your application settings, add redirect URIs:
- Local: `http://localhost:8000/authenticate`
- Production: `https://yourdomain.com/authenticate`

## 4. Enable AuthKit

1. Go to Authentication → AuthKit
2. Enable "Email magic links" (passwordless)
3. Optionally enable Google SSO, Apple SSO, Microsoft SSO

## 5. Get credentials

From your application → API Keys:
- `WORKOS_CLIENT_ID` — starts with `client_`
- `WORKOS_API_KEY` — starts with `sk_`

## 6. Set environment variables

```env
WORKOS_CLIENT_ID=client_...
WORKOS_API_KEY=sk_...
WORKOS_REDIRECT_URL=https://yourdomain.com/authenticate
```

## 7. Configure webhook (optional but recommended)

WorkOS can send webhooks when users are created, updated, or deleted.

1. Go to Webhooks → Create endpoint
2. URL: `https://yourdomain.com/workos/webhook` (not yet implemented — add if needed)
3. Select events: `user.created`, `user.updated`

## 8. Make a user superadmin

After the first login via WorkOS, promote the user to superadmin via Tinker:

```bash
php artisan tinker
```

```php
User::where('email', 'your@email.com')->first()->update([
    'is_admin' => true,
    'role' => 'superadmin',
]);
```

## Notes

- WorkOS stores users in their system; ReviewMate stores only `workos_id`, `name`, `email`, `avatar` locally.
- The `laravel/workos` package handles the OAuth flow automatically.
- Session validation is handled by `ValidateSessionWithWorkOS` middleware on all authenticated routes.
- On logout, the WorkOS session is terminated via `WorkOS::logout()`.
