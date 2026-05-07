# skills.md

## Best Package Description

`dream-technologies/telebirr-laravel-plus` is the Laravel backend package for
Telebirr InApp Purchase. Use it inside an existing Laravel app to securely
apply Fabric Token, sign requests, create orders, receive notify callbacks, and
return `receiveCode` to a Flutter app.

Copy this file into an existing Laravel application to guide an AI coding
assistant while adding Telebirr backend support with
`dream-technologies/telebirr-laravel-plus`.

This file is only for adding Telebirr payments to an existing Laravel app.

## AI Must Ask First

Before changing code, ask the developer for these values:

- Target environment: `test` or `production`.
- Fabric App ID.
- Merchant App ID.
- Business Short Code.
- App Secret storage location.
- Private key file location.
- Public HTTPS notify URL for production.
- Existing order/payment model names.
- Existing checkout route or controller.
- Whether built-in package routes should be used or custom app routes are preferred.

Also confirm:

- Organization and product contract are approved in the Ethio Telecom developer portal.
- The private key is stored outside `public/`.
- The backend will persist local order/payment status.
- Flutter will only receive `receiveCode` and safe order fields.

## Developer Actions Before AI Starts

The developer must:

- Add Telebirr credentials to `.env`, not source code.
- Place the private key in a backend-only path.
- Decide the local order/payment status names.
- Decide whether to use package routes or call `TelebirrClient` from existing controllers.
- Provide the Flutter app team with the create-order URL.

## Goal

Add a secure Telebirr InApp backend to the current Laravel app.

The Laravel app must:

1. Keep Telebirr credentials on the backend.
2. Create Telebirr InApp orders.
3. Return `receiveCode` to Flutter.
4. Receive Telebirr `notify_url` callbacks.
5. Query Telebirr when final payment confirmation is needed.

## Install

```bash
composer require dream-technologies/telebirr-laravel-plus
php artisan vendor:publish --tag=telebirr-config
```

`vendor:publish` here only copies the Laravel config file into the existing app.

## Environment Variables

Add to the Laravel app `.env`:

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

Store the private key outside `public/`.

## Built-In Routes

The package registers:

```text
POST /api/telebirr/create-order
POST /api/telebirr/query-order
POST /api/telebirr/notify
```

Flutter should call:

```text
POST /api/telebirr/create-order
```

with:

```json
{
  "title": "Example order",
  "amount": "12.00"
}
```

## Existing System Integration

If the Laravel app already has orders, checkout, or ride/payment tables:

1. Create the local order first.
2. Call Telebirr create-order.
3. Save `merchantOrderId` with the local order.
4. Return `receiveCode` to Flutter.
5. On notify callback, update the local order status.
6. Use query-order when callback is delayed or payment state is unclear.

For custom checkout controllers, inject the client:

```php
use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;

public function checkout(TelebirrClient $telebirr)
{
    $order = $telebirr->createOrder(new CreateOrderData(
        title: 'Example order',
        amount: '12.00',
    ));

    return response()->json($order->toArray());
}
```

## Notify Callback

Listen for:

```php
DreamTechnologies\TelebirrLaravelPlus\Events\TelebirrNotificationReceived
```

Use that event to update app-specific order/payment records.

## Security Rules

- Do not expose App Secret or private key to Flutter.
- Do not commit `.env`.
- Do not commit private keys.
- Use HTTPS for production `TELEBIRR_NOTIFY_URL`.
- Use `TELEBIRR_VERIFY_SSL=true` in production.
- Do not trust Flutter callback as final payment confirmation.
- Confirm final payment through backend notify callback or query-order.

## Local Testing

Run Laravel:

```bash
php artisan serve --host=0.0.0.0 --port=8001
```

From Flutter on a real phone, use LAN IP, not `localhost`:

```text
http://192.168.x.x:8001/api/telebirr/create-order
```
