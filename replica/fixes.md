# Entrepreneur fixes

See `docs/research/entrepreneur.md` for the sample, quotes and links.

## What they hate

1. Having to chase for status — 2 reviews, 1 source (thin).
2. A promise that does not match the process — 2 reviews, 1 source (thin).
3. A broker who does not turn up or reply — 2 reviews, 1 source (thin).

## What is missing

1. A timestamped status view.
2. A named next step and expected response time.
3. A document checklist.
4. Proactive updates.

## What is unsolved

The applicant carries the coordination burden. A lender that proactively explains status and next steps would feel different even at the same rate.

## Fix plan

- **Shipped:** timeline endpoint with hash-chained events.
- **Next:** `next_step` and `expected_response_at` on the application resource.
- **Next:** document checklist with per-document status.
- **Next:** email/SMS notification adapter behind the existing outbox.
- **Next:** decision reason codes exposed to the applicant.

## Angle

> For New Zealand borrowers who hate chasing a loan application, Loanflow shows a timestamped status timeline and the next step, instead of leaving them to call.

Evidence: status transparency, 2 reviews, 1 source. **Thin**; validate before commercial use.

## Guardrails

No invented reviews, no fake counts, no reviewer words used as marketing testimonials.
