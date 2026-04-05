# Payment Module

## Entry Points
- Static checkout form: `belpay.html`
- Payment process pages:
  - `pages/payment/process.php`
  - `pages/payment/process_momo.php`
  - `pages/payment/success.php`
- Receipt views:
  - `receipt.html` (query-driven static receipt)
  - `pages/payment/receipt.php` (session + DB-aware receipt detail)

## Cameroon-Focused Metadata
The flow carries these fields end-to-end:
- `provider` (`mtn_momo`, `orange_money`, `express_union`, `yup`)
- `checkout_mode` (`ussd_push`, `qr`, `payment_link`)
- `integration_mode` (`aggregator`, `direct_api`)
- `settlement_profile` (`local_sme`, `pan_african`)
- `split_payment` (`on/off`)
- `reference`
- `deadline_at`
- `ipn_status`
- `momo_number`

## Flow
1. User starts in `belpay.html`.
2. Request is forwarded to `pages/payment/process.php`.
3. Mobile-money confirmation goes through `pages/payment/process_momo.php`.
4. Final receipt renders in `receipt.html` with verification context.
5. Transaction history links in buyer dashboard can open `pages/payment/receipt.php`.

## Verification Checklist
Receipts are designed to display:
- IPN status
- transaction reference
- reference deadline

Use this before fulfilment decisions.
