# Entrepreneur review analysis

Method: [replica-entrepreneur](https://github.com/Jakeschincariol/replica-skill) was used as the review-mining workflow. Public Trustpilot reviews were read; every quote below is verbatim and linked. Reviewers are not used as testimonials.

## Sample

- Source: <https://www.trustpilot.com/review/loansmart.co.nz> (pages 1–2, read 2026-11-04).
- Trustpilot displayed 35–37 reviews, TrustScore 4.4–4.5, 91–93% 5-star and 7–9% 1-star. These figures change over time.
- This is a small, single-source sample. Themes with one review are marked **thin**.

## What users complain about

### 1. Status transparency and being chased — 2 reviews, 1 source (thin)

> "Wow the lack of communication… very unprofessional."

— Leilani Vini, 18 Nov 2025, 1-star, <https://www.trustpilot.com/review/loansmart.co.nz>

> "I was told 3-5 business days to process the request and I firmly believe that if I hadn't been calling to check up on the process I wouldn't be informed that it was rejected."

— Harvad McLean, 11 Feb 2025, 1-star, <https://www.trustpilot.com/review/loansmart.co.nz>

### 2. Document handoff friction — 1 review, 1 source (thin)

> "when I completed the photo id and bank statements … it took 2 hours to get in contact with him"

— Leilani Vini, 18 Nov 2025, 1-star, <https://www.trustpilot.com/review/loansmart.co.nz>

### 3. Promise vs process — 2 reviews, 1 source (thin)

> "what is posted on you website is completely different once the experience starts"

— Harvad McLean, 11 Feb 2025, 1-star, <https://www.trustpilot.com/review/loansmart.co.nz>

### 4. Broker responsiveness — 2 reviews, 1 source (thin)

> "He booked a meeting last Friday, 14 June 24 and didn’t turn up."

— "Loan Smart: Worst customer service ever", June 2024, 1-star, <https://www.trustpilot.com/review/loansmart.co.nz?page=2>

> "I asked what happened to him to not to turn up to the meeting, and not reply to my messages and not answer my calls. No reply."

— same review

## What is missing

- A clear, always-available status view.
- A named next step and an expected response time.
- A document checklist that says what is needed, why, and what happens next.
- Proactive updates when a decision is made or delayed.

## What is unsolved

The service appears to depend on the applicant doing the chasing. That is a positioning gap: a lender that proactively explains status and next steps would feel materially different, even with the same rates.

## Fix plan

| Priority | Fix | Size | Evidence | Implemented here |
| --- | --- | --- | --- | --- |
| 1 | Timestamped application timeline | S | 2 reviews, status transparency | Yes: `GET /api/v1/loan-applications/{reference}/timeline` |
| 2 | Explicit `next_step` and expected response time in the API response | S | 2 reviews, promise vs process | No; next iteration |
| 3 | Document checklist with status per document | M | 1 review, document handoff | No; out of scope |
| 4 | Proactive email/SMS on status change | M | 2 reviews, chasing | Queue is ready; no provider adapter |
| 5 | Decision reason codes shown to the applicant | S | 1 review, rejection not explained | Partial: transition reason is stored in the event |
| 6 | Broker response-time SLA and escalation | S | 2 reviews, responsiveness | No; operational process |

## Positioning angle

> For New Zealand borrowers who hate chasing a loan application, Loanflow shows a timestamped status timeline and the next step, instead of leaving them to call.

Evidence: status transparency theme, 2 reviews, 1 source. **Thin** — validate with at least 20 reviews across two sources before using this commercially.

## Guardrails

- No review was invented, paraphrased into a fake quote, or used as a testimonial.
- No reviewer personal data beyond the public display name is reproduced.
- The sample is small and single-source; findings are directional.
