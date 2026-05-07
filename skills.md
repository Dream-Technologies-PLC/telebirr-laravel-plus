# skills.md

Use this file as AI coding instructions when integrating or modifying
`dream-technologies/telebirr-laravel-plus`.

## Package Role

This Laravel package is the secure backend companion for
`telebirr_inapp_purchase_plus`.

It owns:

- Fabric Token requests
- RSA signing
- Create Order requests
- Query Order requests
- notify_url callback route
- Backend-only Telebirr credentials

Flutter must only receive `receiveCode` and safe order status fields.

## Install Flow

```bash
composer require dream-technologies/telebirr-laravel-plus
php artisan vendor:publish --tag=telebirr-config
```

Required `.env` values:

```env
TELEBIRR_ENV=test
TELEBIRR_FABRIC_APP_ID=your_fabric_app_id
TELEBIRR_APP_SECRET=your_app_secret
TELEBIRR_MERCHANT_APP_ID=your_merchant_app_id
TELEBIRR_SHORT_CODE=your_business_short_code
TELEBIRR_PRIVATE_KEY_PATH=/absolute/private/path/private_key.pem
TELEBIRR_NOTIFY_URL=https://yourdomain.com/api/telebirr/notify
TELEBIRR_VERIFY_SSL=true
```

## Routes

Built-in routes:

```text
POST /api/telebirr/create-order
POST /api/telebirr/query-order
POST /api/telebirr/notify
```

Create-order request:

```json
{
  "title": "Example order",
  "amount": "12.00"
}
```

Create-order response:

```json
{
  "success": true,
  "merchantOrderId": "ORDER_ID",
  "receiveCode": "TELEBIRR$BUYGOODS$YOUR_SHORT_CODE$12.00$PREPAY_ID$120m",
  "message": "success"
}
```

## Security Rules

- Never commit `.env` with real credentials.
- Never commit private keys.
- Never expose App Secret, private key, or Fabric Token to Flutter.
- Store private keys outside `public/`.
- Use `TELEBIRR_VERIFY_SSL=true` in production.
- Use HTTPS for `TELEBIRR_NOTIFY_URL` in production.
- Confirm final payment on the backend using `notify_url` and/or `queryOrder`.

## Coding Rules

- Keep payment request DTOs strongly typed.
- Keep response shape stable for Flutter:
  `success`, `merchantOrderId`, `receiveCode`, `code`, `message`, `raw`.
- Keep Telebirr API errors readable.
- Do not swallow Telebirr error codes such as `60200098`.
- Dispatch `TelebirrNotificationReceived` from notify callbacks.

## Testing Checklist

Before release:

```bash
composer validate --strict
find src config routes tests -name '*.php' -print0 | xargs -0 -n1 php -l
composer test
```

Scan for secrets:

```bash
rg "APP_SECRET|PRIVATE_KEY|MIIE|TELEBIRR_APP_SECRET|real_merchant"
```

## Packagist Release

Versions come from git tags:

```bash
git tag v0.1.1
git push origin main
git push origin v0.1.1
```

Packagist package:

```text
dream-technologies/telebirr-laravel-plus
```

