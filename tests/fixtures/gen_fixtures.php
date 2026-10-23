<?php
/**
 * One-off generator for deterministic WebAuthn test fixtures.
 * Produces an ES256 (P-256) key pair and builds a self-consistent
 * assertion (webauthn.get) and attestation (webauthn.create, fmt=none)
 * that the vendored Web_Authn library must accept.
 *
 * Output: a PHP file of base64-encoded vectors, written to the path given
 * as argv[1]. Run from the project root with:
 *
 *     php tests/fixtures/gen_fixtures.php tests/fixtures/webauthn-vectors.php
 *
 * The keys are regenerated each run, so only re-run this when the fixture
 * format itself changes; the committed vectors are the regression anchor.
 */

$out = $argv[1] ?? 'php://stdout';

$rpId   = 'example.com';
$origin = 'https://example.com';

/* ---- key pair (deterministic is impossible for EC keygen; we embed the
        generated private key so the fixture is fully reproducible) ---- */
$pkey = openssl_pkey_new([
    'private_key_type' => OPENSSL_KEYTYPE_EC,
    'curve_name'       => 'prime256v1',
]);
openssl_pkey_export($pkey, $privPem);
$details = openssl_pkey_get_details($pkey);
$pubPem  = $details['key'];
$x = $details['ec']['x'];              // 32 bytes
$y = $details['ec']['y'];              // 32 bytes

function b64u(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

/* ---------------- assertion (webauthn.get) ---------------- */
$challenge = random_bytes(32);
$clientDataGet = json_encode([
    'type'      => 'webauthn.get',
    'challenge' => b64u($challenge),
    'origin'    => $origin,
], JSON_UNESCAPED_SLASHES);

$rpIdHash = hash('sha256', $rpId, true);
$flags    = chr(0x05);                           // UP (0x01) | UV (0x04)
$signCount = 10;
$authDataGet = $rpIdHash . $flags . pack('N', $signCount);

$signedData = $authDataGet . hash('sha256', $clientDataGet, true);
openssl_sign($signedData, $sig, $pkey, OPENSSL_ALGO_SHA256);   // DER ECDSA

/* ---------------- attestation (webauthn.create, fmt=none) ---------------- */
$challengeCreate = random_bytes(32);
$clientDataCreate = json_encode([
    'type'      => 'webauthn.create',
    'challenge' => b64u($challengeCreate),
    'origin'    => $origin,
], JSON_UNESCAPED_SLASHES);

// COSE_Key (EC2 / ES256 / P-256): {1:2, 3:-7, -1:1, -2:x, -3:y}
function cbor_uint(int $n): string
{
    if ($n < 24) return chr($n);
    if ($n < 256) return chr(0x18) . chr($n);
    return chr(0x19) . pack('n', $n);
}
function cbor_nint(int $n): string            // negative int, n is the magnitude-1 value
{
    $v = -1 - $n;                              // encode -1-value
    $u = -1 - $v;
    return ($u < 24) ? chr(0x20 + $u) : chr(0x38) . chr($u);
}
function cbor_bstr(string $b): string
{
    $len = strlen($b);
    $head = ($len < 24) ? chr(0x40 + $len) : chr(0x58) . chr($len);
    return $head . $b;
}
// map with 5 pairs
$cose  = chr(0xA5);
$cose .= cbor_uint(1) . cbor_uint(2);          // kty: EC2
$cose .= cbor_uint(3) . cbor_nint(6);          // alg: -7  (ES256)  -> -1-6 = -7
$cose .= cbor_nint(0) . cbor_uint(1);          // crv (-1): P-256   -> -1-0 = -1
$cose .= cbor_nint(1) . cbor_bstr($x);         // x   (-2)
$cose .= cbor_nint(2) . cbor_bstr($y);         // y   (-3)

$aaguid   = str_repeat("\x00", 16);
$credId   = random_bytes(32);
$credIdLen = pack('n', strlen($credId));
$attFlags = chr(0x45);                          // UP | UV | AT (0x40)
$authDataCreate = $rpIdHash . $attFlags . pack('N', 0)
    . $aaguid . $credIdLen . $credId . $cose;

// attestationObject = CBOR map {"fmt":"none","attStmt":{},"authData":bytes}
function cbor_tstr(string $s): string
{
    $len = strlen($s);
    return chr(0x60 + $len) . $s;               // assumes < 24
}
$attObj  = chr(0xA3);
$attObj .= cbor_tstr('fmt') . cbor_tstr('none');
$attObj .= cbor_tstr('attStmt') . chr(0xA0);    // empty map
$attObj .= cbor_tstr('authData') . cbor_bstr($authDataCreate);

/* ---------------- emit ---------------- */
$vectors = [
    'rp_id'  => $rpId,
    'origin' => $origin,
    'public_key_pem' => $pubPem,
    // Throwaway P-256 private key, embedded so negative tests can re-sign
    // authenticator-data variants (e.g. with the UV/UP flags cleared).
    'private_key_pem' => $privPem,
    // assertion
    'get_client_data_json' => base64_encode($clientDataGet),
    'get_authenticator_data' => base64_encode($authDataGet),
    'get_signature' => base64_encode($sig),
    'get_challenge' => base64_encode($challenge),
    'get_prev_sign_count' => 5,
    'get_sign_count' => $signCount,
    // attestation
    'create_client_data_json' => base64_encode($clientDataCreate),
    'create_attestation_object' => base64_encode($attObj),
    'create_challenge' => base64_encode($challengeCreate),
    'create_credential_id' => base64_encode($credId),
];

$php = "<?php\n\n/**\n * Deterministic WebAuthn fixtures generated for the test suite.\n"
     . " * ES256 / P-256. Values are base64-encoded (except the PEM and plain fields).\n"
     . " * Regenerate with tests/fixtures/gen_fixtures.php if the format ever changes.\n */\n\n"
     . "return " . var_export($vectors, true) . ";\n";

file_put_contents($out, $php);
fwrite(STDERR, "wrote fixtures to $out\n");
