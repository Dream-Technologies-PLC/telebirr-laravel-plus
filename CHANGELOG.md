## 0.2.0

* Adds inline PEM/base64 private-key support while retaining protected key files.
* Uses explicit SHA-256 RSA-PSS salt length for request signing.
* Adds callback signature verification with PKCS#1 and RSA-PSS compatibility.
* Adds a typed, normalized `TelebirrNotification` with order/amount matching.
* Stops logging complete callback bodies and separates callback throttling from
  authenticated create/query route middleware.
* Normalizes Fabric Token variants, transport failures, business responses,
  prepay IDs, and missing receive codes.
* Prevents untrusted clients from overriding merchant order IDs, callback URLs,
  redirect URLs, or callback info unless each override is explicitly enabled.
* Prevents `extra` order fields from replacing server-owned identity, amount,
  callback, currency, and order fields.
* Requires callback verification and authenticated, rate-limited client routes
  by default; both remain explicitly configurable for existing applications.
* Adds create-order and callback-verification feature tests.
* Declares Guzzle explicitly because Laravel 10 treats the HTTP client transport
  as an optional dependency.

## 0.1.3

* Fixes CI by removing the empty feature test suite from PHPUnit configuration.

## 0.1.2

* Updates the root `skills.md` guide with integration prerequisites and required developer actions.

## 0.1.1

* Adds an integration-focused `skills.md` guide link for AI-assisted Laravel setup.

## 0.1.0

* Initial production-ready Laravel backend package for Telebirr InApp Purchase.
* Adds Fabric Token, create-order, query-order, notify routes, RSA signing, typed DTOs, and secure configuration.
* Aligns backend response format with `telebirr_inapp_purchase_plus` Flutter SDK.
