# ReviewMate — Operations Runbook

## Daily checks

- Check Laravel Cloud dashboard for job failures
- Check Stripe dashboard for failed payments
- Check error log for any 500s

---

## Common Operations

### Make a user admin

```bash
php artisan tinker
User::where('email', 'user@example.com')->first()->update(['is_admin' => true, 'role' => 'superadmin']);
```

### Force sync Google reviews for a business

```bash
php artisan tinker
$business = Business::find(1);
(new App\Jobs\SyncGoogleReviews($business))->handle(new App\Services\GoogleBusinessProfileService);
```

Or dispatch to queue:
```bash
php artisan tinker
App\Jobs\SyncGoogleReviews::dispatch(Business::find(1));
```

### Re-run follow-up job manually

```bash
php artisan tinker
App\Jobs\SendFollowUpRequests::dispatchSync();
```

### Send a weekly digest manually

```bash
php artisan tinker
App\Jobs\SendWeeklyDigests::dispatchSync();
```

### Clear all caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Flush failed jobs and retry

```bash
php artisan queue:failed           # list failed jobs
php artisan queue:retry all        # retry all failed jobs
php artisan queue:flush            # delete all failed jobs
```

---

## Stripe Operations

### Verify webhook signature

Check `STRIPE_WEBHOOK_SECRET` is set to the correct value from Stripe dashboard → Webhooks → signing secret.

### Refund a customer

Done via Stripe dashboard directly. ReviewMate does not handle refunds programmatically.

### Check subscription status

```bash
php artisan tinker
$user = User::where('email', 'customer@example.com')->first();
$user->subscription('default')->status;
$user->onFreePlan();
$user->subscribed();
```

### Grant free plan access temporarily

```bash
php artisan tinker
# Set is_admin to bypass plan checks (temporary)
User::where('email', 'customer@example.com')->first()->update(['is_admin' => true]);
```

---

## Integration Debugging

### Test ServiceM8 webhook

```bash
curl -X POST https://yourdomain.com/webhooks/servicem8/{uuid} \
  -H "Content-Type: application/json" \
  -d '{"job_uuid": "test-uuid", "customer_name": "Test Customer", "customer_email": "test@example.com"}'
```

### Test generic incoming webhook

```bash
curl -X POST https://yourdomain.com/webhooks/incoming/{token} \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Customer", "email": "test@example.com", "channel": "email"}'
```

### Regenerate a business webhook token

```bash
php artisan tinker
Business::find(1)->update(['webhook_token' => Str::random(40)]);
```

---

## Database Backups

Laravel Cloud provides automated daily backups for PostgreSQL. To export manually:

```bash
pg_dump $DB_URL > backup-$(date +%Y%m%d).sql
```

---

## Emergency: Disable all outgoing emails

Set in `.env` (requires redeploy or `php artisan config:clear`):

```env
MAIL_MAILER=log
```

This logs all emails to `storage/logs/laravel.log` instead of sending them.

---

## Emergency: Disable all outgoing SMS

```env
SMS_DRIVER=log
```

---

## Performance

- Google sync jobs: every 2 hours per connected business. If queue backs up, increase worker count.
- Cliniko/Halaxy polling: every 15 minutes. Consider increasing interval if API rate limits are hit.
- Database: SQLite in dev, PostgreSQL in prod. Add indexes if queries slow down.

---

## Logs

```bash
# Laravel Cloud
# View logs in the dashboard under Logs tab

# Local
php artisan pail
# Or: tail -f storage/logs/laravel.log
```
