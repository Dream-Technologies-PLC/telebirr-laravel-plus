# Production Checklist

1. Create and approve your organization at `https://developer.ethiotelecom.et/`.
2. Confirm your organization member status is approved.
3. Confirm the Telebirr InApp product contract is active.
4. Store `TELEBIRR_APP_SECRET` only in server environment variables.
5. Store the private key outside public web folders.
6. Set `TELEBIRR_ENV=production`.
7. Set `TELEBIRR_VERIFY_SSL=true`.
8. Use an HTTPS `TELEBIRR_NOTIFY_URL`.
9. Persist `merchantOrderId` and order status in your database before returning `receiveCode`.
10. Confirm final payment on the backend through `notify_url` or `queryOrder`.

If Telebirr returns:

```text
60200098: Product is not subscribed or the contract status is not allowed to do this operation.
```

check organization approval, product subscription, contract status, Merchant App ID, and Short Code.
