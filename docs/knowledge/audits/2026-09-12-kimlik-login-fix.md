# Kimlik login production fix

## Report and cause

The user reached the Reklam callback after signing in through Kimlik, saw the signing-in message, then the generic failure. Production logged a 200 token response with an API landing-page message instead of an access token.

AuthController read WALLET_CLIENT_ID and WALLET_CLIENT_SECRET with env() at request time. With Laravel configuration cached, these values were absent from env() but present in services.wallet configuration. The outgoing request also omitted Accept: application/json. Kimlik's validation failure could therefore redirect to its landing response.

Read the local wallet repository's OAuthController and OAuthService to confirm the token endpoint, PKCE validation and response shape. Kimlik itself needed no code change.

## Fix and deployment

Backend commit 28298f4 reads the provider URL and credentials through config('services.wallet.*'), requests JSON, and sets connection/request timeouts. Profile sync uses the same cached configuration. Provider responses and exception messages are not logged with sensitive contents.

Applied the controller atomically to the active v4 API release after verifying that its original SHA-256 matched the committed source. Original controller retained at /home/ugn/reklam-backups/20260913-oauth-config/AuthController.php. New controller SHA-256: 1a78d4bb91c89df1697b145b226981a584166ecf2346f2630112a25736cf3a07. Frontend remains v8. No database, payment, credential or Kimlik application changes.

## Verification

- 22 backend tests passed, 129 assertions; formatter and syntax checks passed.
- Added regression tests for cached credentials, usable application session after successful exchange, returning identity, provider rejection, and protection against automatic linking by matching email.
- Production bootstrap confirmed credentials are present in cached configuration while runtime env values are absent.
- A deliberately invalid, non-user code sent from the production backend received Kimlik's expected 400 invalid_grant response. This establishes the corrected request reaches the OAuth handler, not the validation redirect.
- Live browser POST to Reklam's callback returned the expected 400 token-exchange error for the invalid code, confirming the updated web runtime. No user or session was created by these probes.
- The user's personal Kimlik sign-in was not repeated: no signed-in browser session was available to this agent. A new login attempt is required; old authorization codes are single-use/expire.
