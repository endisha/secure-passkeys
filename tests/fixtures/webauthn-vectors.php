<?php

/**
 * Deterministic WebAuthn fixtures generated for the test suite.
 * ES256 / P-256. Values are base64-encoded (except the PEM and plain fields).
 * Regenerate with tests/fixtures/gen_fixtures.php if the format ever changes.
 */

return array (
  'rp_id' => 'example.com',
  'origin' => 'https://example.com',
  'public_key_pem' => '-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE8hcAJMqWVDhlMpP8Du5NkQPtNI1T
lI/Avgq86pMdnhHorA6TnunSFq+3Dkzb4tW2WY0Q3oKW+UhDwkb112t6oQ==
-----END PUBLIC KEY-----
',
  'private_key_pem' => '-----BEGIN PRIVATE KEY-----
MIGHAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBG0wawIBAQQgpUY9lZMOQzWvhLUi
WKjzbPLAHNQEHibt7sk1EbdjWp6hRANCAATyFwAkypZUOGUyk/wO7k2RA+00jVOU
j8C+Crzqkx2eEeisDpOe6dIWr7cOTNvi1bZZjRDegpb5SEPCRvXXa3qh
-----END PRIVATE KEY-----
',
  'get_client_data_json' => 'eyJ0eXBlIjoid2ViYXV0aG4uZ2V0IiwiY2hhbGxlbmdlIjoibjhCblRNM3ZaUzd4Wmg4M2lUdVBEZnFmUDZ5Q0hud1VUZUM0TjNQTEx2MCIsIm9yaWdpbiI6Imh0dHBzOi8vZXhhbXBsZS5jb20ifQ==',
  'get_authenticator_data' => 'o3mm9u6vuaVeN4wRgDTidR5oL6ufLTCrE9ISVYbOGUcFAAAACg==',
  'get_signature' => 'MEQCIHC9IrmrLgkcRdqzD+dnPjY3TXZlBuLPIVVRJFoyOavxAiBMlmmlGhq1Q6/+h4riZx0yBreeTY1FNRtnW1PGgmvKcQ==',
  'get_challenge' => 'n8BnTM3vZS7xZh83iTuPDfqfP6yCHnwUTeC4N3PLLv0=',
  'get_prev_sign_count' => 5,
  'get_sign_count' => 10,
  'create_client_data_json' => 'eyJ0eXBlIjoid2ViYXV0aG4uY3JlYXRlIiwiY2hhbGxlbmdlIjoiNDdNVE9NRG1VaE44UWYyUjBvN3JlNTNpZDNmbU8zUWdqZEZlcHNNMEF2SSIsIm9yaWdpbiI6Imh0dHBzOi8vZXhhbXBsZS5jb20ifQ==',
  'create_attestation_object' => 'o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YViko3mm9u6vuaVeN4wRgDTidR5oL6ufLTCrE9ISVYbOGUdFAAAAAAAAAAAAAAAAAAAAAAAAAAAAIGXfyEdrf5ah6hsJelQxXzBhxQucZn/IvowyvARlpeBtpQECAyYgASFYIPIXACTKllQ4ZTKT/A7uTZED7TSNU5SPwL4KvOqTHZ4RIlgg6KwOk57p0havtw5M2+LVtlmNEN6ClvlIQ8JG9ddreqE=',
  'create_challenge' => '47MTOMDmUhN8Qf2R0o7re53id3fmO3QgjdFepsM0AvI=',
  'create_credential_id' => 'Zd/IR2t/lqHqGwl6VDFfMGHFC5xmf8i+jDK8BGWl4G0=',
);
