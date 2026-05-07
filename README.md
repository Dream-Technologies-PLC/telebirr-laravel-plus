# telebirr-laravel-plus

Production-ready Laravel backend package for Telebirr InApp Purchase in Ethiopia.

It is built to pair with the Flutter package:

- [telebirr_inapp_purchase_plus on pub.dev](https://pub.dev/packages/telebirr_inapp_purchase_plus)
- [Flutter GitHub repository](https://github.com/Dream-Technologies-PLC/telebirr_inapp_purchase_plus)

Use this Laravel package on your backend. Use the Flutter package only to open the native Telebirr SDK with the `receiveCode` returned from Laravel.

## What This Package Does

- Applies Fabric Token on the backend.
- Signs Telebirr requests with your private key.
- Creates InApp orders.
- Returns `receiveCode` for Flutter.
- Provides query-order support.
- Provides notify callback route.
- Keeps secrets away from Flutter.
- Supports testbed and production environments.

## What Flutter Does

Flutter calls your Laravel endpoint:

```text
POST /api/telebirr/create-order
```

Laravel returns:

```json
{
  "success": true,
  "merchantOrderId": "1778141110976",
  "receiveCode": "TELEBIRR$BUYGOODS$YOUR_SHORT_CODE$12.00$024demoPrepayId$120m",
  "code": "0",
  "message": "success"
}
```

Flutter then starts payment:

```dart
await Telebirr.initialize(
  appId: 'YOUR_MERCHANT_APP_ID',
  shortCode: 'YOUR_SHORT_CODE',
  returnScheme: 'yourappscheme',
  environment: TelebirrEnvironment.test,
);

final result = await Telebirr.pay(receiveCode: receiveCodeFromLaravel);
```

## Install

```bash
composer require dream-technologies/telebirr-laravel-plus
```

Publish config:

```bash
php artisan vendor:publish --tag=telebirr-config
```

Add environment variables:

```env
TELEBIRR_ENV=test
TELEBIRR_FABRIC_APP_ID=your_fabric_app_id
TELEBIRR_APP_SECRET=your_app_secret
TELEBIRR_MERCHANT_APP_ID=your_merchant_app_id
TELEBIRR_SHORT_CODE=your_business_short_code
TELEBIRR_PRIVATE_KEY_PATH=/absolute/path/to/storage/app/private/telebirr/private_key.pem
TELEBIRR_NOTIFY_URL=https://yourdomain.com/api/telebirr/notify
TELEBIRR_VERIFY_SSL=true
```

Put your private key in a private backend-only path, for example:

```text
storage/app/private/telebirr/private_key.pem
```

Never place the private key in `public/`.

## Routes

Routes are registered automatically:

```text
POST /api/telebirr/create-order
POST /api/telebirr/query-order
POST /api/telebirr/notify
```

Change the route prefix:

```env
TELEBIRR_ROUTE_PREFIX=api/payments/telebirr
```

Disable built-in routes if you want custom controllers:

```env
TELEBIRR_ROUTES_ENABLED=false
```

## Create Order

Request:

```bash
curl -X POST https://yourdomain.com/api/telebirr/create-order \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Peace Ride order",
    "amount": "12.00"
  }'
```

Response:

```json
{
  "success": true,
  "merchantOrderId": "1778141110976",
  "receiveCode": "TELEBIRR$BUYGOODS$YOUR_SHORT_CODE$12.00$024demoPrepayId$120m",
  "code": "0",
  "message": "success",
  "raw": {}
}
```

## Use In Your Own Controller

```php
use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;

Route::post('/checkout/telebirr', function (TelebirrClient $telebirr) {
    $order = $telebirr->createOrder(new CreateOrderData(
        title: 'Peace Ride order',
        amount: '12.00',
    ));

    return response()->json($order->toArray());
});
```

## Query Order

```bash
curl -X POST https://yourdomain.com/api/telebirr/query-order \
  -H "Content-Type: application/json" \
  -d '{
    "merchantOrderId": "1778141110976"
  }'
```

Use query-order when the Flutter callback is delayed, the user closes the app, or `notify_url` is delayed.

## Handle Notify Callback

The built-in notify route logs the callback and dispatches:

```php
DreamTechnologies\TelebirrLaravelPlus\Events\TelebirrNotificationReceived
```

Listen to the event in your app to update your own order records:

```php
use DreamTechnologies\TelebirrLaravelPlus\Events\TelebirrNotificationReceived;
use Illuminate\Support\Facades\Event;

Event::listen(TelebirrNotificationReceived::class, function ($event) {
    // Store $event->payload, then confirm final status with queryOrder.
});
```

For final payment confirmation, prefer backend `notify_url` plus `queryOrder`.

## Security Rules

Keep these on Laravel only:

- App Secret
- Private key
- RSA signing
- Fabric Token
- createOrder
- queryOrder
- notify_url verification

Flutter should receive only `receiveCode`, `merchantOrderId`, and safe UI status fields.

## Testbed And Production

Testbed:

```env
TELEBIRR_ENV=test
```

Production:

```env
TELEBIRR_ENV=production
TELEBIRR_VERIFY_SSL=true
TELEBIRR_NOTIFY_URL=https://yourdomain.com/api/telebirr/notify
```

If your organization or contract is not approved, Telebirr may return:

```text
60200098: Product is not subscribed or the contract status is not allowed to do this operation.
```

Check your account at [developer.ethiotelecom.et](https://developer.ethiotelecom.et/) and make sure organization members and product contracts are approved.

## Local Device Testing

Run Laravel:

```bash
php artisan serve --host=0.0.0.0 --port=8001
```

Use your computer LAN IP in Flutter, not `localhost`:

```text
http://192.168.x.x:8001/api/telebirr/create-order
```

## Contact Us

Need support with Telebirr integration, websites, mobile apps, or custom
software? Contact Dream Technologies PLC:
[dreamtech.et/contact](https://dreamtech.et/contact)
