# Clean-room recon

## Sources

| Source | Use | Link |
| --- | --- | --- |
| Public job description supplied by the candidate, 2026-11-04 | Requirements, responsibilities, employer questions | Not published here |
| Loansmart public site | Product language, loan types, calculators, contact details | <https://loansmart.co.nz/> |
| Loansmart 1,000th-review page | Customer language, service claims | <https://loansmart.co.nz/our-1000th-review-yippee/> |
| Trustpilot | Public customer experience themes | <https://www.trustpilot.com/review/loansmart.co.nz> |

No proprietary code, internal documentation, customer records, brand assets or private APIs were accessed.

## Feature map

| Area | Observed or stated behaviour | Relevance |
| --- | --- | --- |
| Intake | Online application, loan purpose, amount, income, employment, consent | Core portal |
| Documents | Photo ID and bank statements requested during assessment | Out of scope for this build; boundary documented |
| Status | Applicants want to know what is happening and what comes next | Implemented as a timestamped timeline |
| Lenders | Multiple lender APIs and conditional approval | Represented by an integration architecture; no lender-specific credentials |
| CRM | Keap / Infusionsoft contact and status sync | Implemented |
| Websites | WordPress marketing surfaces and landing pages | Implemented as an intake plugin |
| Communications | SMS and AI calling | Out of scope; boundary documented |
| Analytics | GTM, server-side tagging, BigQuery | Implemented |
| Security | OWASP, access control, MFA, secrets, logging, backups, incident response | Implemented at application level; infrastructure controls documented as residual risk |
| Compliance | Privacy Act 2020, regulated financial advice | Consent, PII minimisation, audit trail; legal review remains external |

## User journey

1. Applicant lands on a WordPress page and opens the form.
2. Applicant enters identity, contact, loan and employment details and gives consent.
3. The site submits the application over OAuth 2.0 and receives a reference.
4. The applicant can see a timeline of events and the current status.
5. Operations review the application, request documents if needed, and record a decision.
6. Keap receives the contact and status; BigQuery receives the event stream; GTM receives consent-aware analytics.
7. A decision is communicated; the audit trail preserves who did what and when.

## Inferred data model

- **Applicant**: name, email, phone, income, employment.
- **Application**: reference, amount, purpose, status, consent, source.
- **Document**: type, status, received time (out of scope).
- **Lender**: name, product, criteria (out of scope).
- **Decision**: outcome, reason, actor, timestamp.
- **Event**: type, payload, occurred time, hash.
- **Audit**: actor, action, subject, metadata, hash.

## Limitations

- The public ad describes features; it is not a specification.
- No internal screens, workflows or data model were available.
- Review themes are a small public sample and are treated as directional, not statistically representative.
- The implementation is a reference, not a claim that the target uses this architecture.
