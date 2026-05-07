# Contributing

Thanks for helping improve `telebirr-laravel-plus`.

Before opening a pull request:

1. Do not commit merchant credentials, private keys, app secrets, or real payment data.
2. Run `composer validate`.
3. Run `composer analyse`.
4. Add tests for behavior changes where possible.
5. Keep Flutter-facing response fields stable: `success`, `merchantOrderId`, `receiveCode`, `code`, `message`, `raw`.
