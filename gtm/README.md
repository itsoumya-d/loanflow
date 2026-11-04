# Google Tag Manager

## Architecture

```text
Web GTM container
  └─ Loanflow Event tag (gtm/templates/loanflow_event.tpl)
       └─ POST → GTM server container URL
            └─ server-side client/tag adds HMAC signature
                 └─ POST /api/v1/events/gtm
                      └─ Laravel validates, records the event, writes to the hash chain
```

The browser never receives the HMAC secret. The server container is the signing boundary.

## Event contract

| Event | When | Parameters |
| --- | --- | --- |
| `loan_application_viewed` | Form rendered | `step=form` |
| `loan_application_started` | First field interaction | `step=form` |
| `loan_application_submitted` | API accepted | `step=complete`, `application_reference` |
| `loan_application_validation_failed` | Client or server validation failed | `step=client` or `step=server` |
| `loan_application_approved` | Decision recorded | `application_reference` |
| `loan_application_declined` | Decision recorded | `application_reference` |

Rules:

- Never send name, email, phone, income or free-text notes.
- Only scalar values.
- Keys must match `[a-z0-9_]{1,40}`.
- The server caps parameters at 40 keys and strings at 500 characters.

## Import the template

1. In GTM, open **Templates → New → Import**.
2. Upload `gtm/templates/loanflow_event.tpl`.
3. Create a tag using the template.
4. Set the server container URL.
5. Add a trigger for the dataLayer event you want to forward.
6. Grant the `send_http_request` permission for the exact endpoint.

## Consent

`gtm/datalayer.mjs` defaults analytics consent to `denied` and returns `null` unless `analytics_storage` is `granted`. The WordPress plugin localises consent defaults to denied and does not push PII.

## Tests

```bash
npm run test:gtm
```

The test suite covers consent gating, event allow-listing, parameter validation, dataLayer pushes, and structural validation of the custom template.
