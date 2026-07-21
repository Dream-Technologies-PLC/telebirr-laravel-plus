# Production Checklist

1. Create and approve your organization at `https://developer.ethiotelecom.et/`.
2. Confirm your organization member status is approved.
3. Confirm the Telebirr InApp product contract is active.
4. Store `TELEBIRR_APP_SECRET` only in server environment variables.
5. Store the private key outside public web folders.
6. Set `TELEBIRR_ENV=production`.
7. Set `TELEBIRR_VERIFY_SSL=true`.
8. Use an HTTPS `TELEBIRR_NOTIFY_URL`.
9. Configure Telebirr's callback public key; callback signatures are required by default.
10. Protect create/query routes with authentication and rate-limit the public notify route.
11. Persist `merchantOrderId`, amount, currency, and order status before returning `receiveCode`.
12. Match callback order ID, amount, and currency against that server-owned record.
13. Confirm final payment with `queryOrder` before fulfillment or wallet credit.
14. Make the final database update idempotent so duplicate callbacks cannot credit twice.

If Telebirr returns:

```text
60200098: Product is not subscribed or the contract status is not allowed to do this operation.
```

check organization approval, product subscription, contract status, Merchant App ID, and Short Code.
