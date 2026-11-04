# ADR 0003: Outbox and hash-chained events

## Status

Accepted.

## Context

A loan submission must be fast even when Keap, BigQuery or GTM are slow or unavailable. Regulated decisions also need an audit trail that can detect tampering.

Options considered:

1. Call every integration synchronously inside the request.
2. Write only application rows and rely on database audit features.
3. Write domain events in the same transaction, dispatch a job after commit, and hash-chain the events and audit records.

## Decision

Use option 3.

- `loan_events` stores the domain timeline with `previous_hash` and `hash`.
- `audit_logs` stores actor/action records with the same chain pattern.
- `ProcessLoanApplication` runs after commit and marks each destination independently.
- `loanflow:outbox` re-dispatches pending work every minute.
- Failed deliveries are logged and audited; they do not fail the applicant request.

## Consequences

- The applicant path stays fast and resilient.
- Integration failures are recoverable and observable.
- The hash chain is tamper-evident, not tamper-proof; production should anchor the head hash externally.
- The database queue is sufficient for this reference build; production should use Redis/SQS and a dead-letter queue.
