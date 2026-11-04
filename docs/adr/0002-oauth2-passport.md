# ADR 0002: OAuth 2.0 with Laravel Passport

## Status

Accepted.

## Context

The WordPress intake is a confidential machine client. It needs a short-lived access token, scoped access and a standard token endpoint. Staff-facing endpoints need user tokens and scopes. The target stack also requires third-party OAuth integrations (Keap).

Options considered:

1. Laravel Sanctum personal access tokens.
2. A hand-rolled OAuth 2.0 server using `league/oauth2-server`.
3. Laravel Passport.

## Decision

Use Laravel Passport.

- Client credentials for the WordPress intake.
- Authorization code and refresh tokens available for future staff or partner apps.
- Scopes: `applications:write`, `applications:read`, `applications:manage`.
- Tokens expire after 12 hours; refresh tokens after 30 days.
- Machine routes use `EnsureClientIsResourceOwner`; human routes use `auth:api` plus `CheckToken`.

## Consequences

- Standard OAuth 2.0 behaviour, including hashed client secrets and key rotation via `passport:keys`.
- More database tables and key management than Sanctum.
- Tests must account for Passport's hashed client secret (`plainSecret` is available only at creation).
- A future move to an external identity provider can keep the same scope names and route middleware.
