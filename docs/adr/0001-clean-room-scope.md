# ADR 0001: Clean-room scope

## Status

Accepted.

## Context

The brief describes a senior full-stack lead for a New Zealand personal-loan brokerage. The target product includes a customer portal, lender API integrations, Keap CRM, bank-statement retrieval, SMS, AI calling, WordPress sites, GTM and BigQuery.

We do not have access to the target's source code, internal systems, customer data or brand assets, and we must not imply that we do.

## Decision

Build an independent, clean-room reference implementation from public information only:

- the public job description supplied by the candidate on 2026-11-04;
- public pages on `loansmart.co.nz`;
- public Trustpilot reviews;
- general lending, OAuth 2.0, Keap, BigQuery and GTM documentation.

Do not copy code, copy, branding, logos, customer data or proprietary workflows. Use a neutral product name (`Loanflow`) and original UI copy.

## Consequences

- The repository demonstrates engineering capability, not a clone of the target product.
- Some target features (bank-statement retrieval, SMS, AI calling, credit decisioning) are documented as out of scope.
- Every feature included is implemented, tested and independently defensible.
