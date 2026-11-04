# Replica recon (adapted)

The replica workflow normally clones an app from public screens and flows. This build adapts it to a public job brief and public product pages. No proprietary code, copy or assets were used.

## Inputs

- Public job description supplied on 2026-11-04.
- <https://loansmart.co.nz/>
- <https://www.trustpilot.com/review/loansmart.co.nz>

## Surfaces

1. Marketing landing page.
2. Loan application form.
3. Document upload (photo ID, bank statements) — out of scope.
4. Status / next-step view — implemented as an API timeline.
5. Operations review — API state machine, no UI.
6. CRM record — Keap contact sync.
7. Analytics — GTM and BigQuery.

## Flows

- Applicant submits an application.
- System validates and acknowledges with a reference.
- Operations screen, request documents, and decide.
- CRM and analytics receive the events.
- Applicant sees the status timeline.

## Data model

See `docs/research/recon.md`.

## Out of scope

- Proprietary UI, copy, logos and assets.
- Bank-statement retrieval, SMS, AI calling.
- Credit decisioning and lender-specific API contracts.
- Operations UI.

## Parity note

This is not a pixel clone. Parity is measured against the engineering brief, not the target's visual design. See `docs/evidence-matrix.md`.
