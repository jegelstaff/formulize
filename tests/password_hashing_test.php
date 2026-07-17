<?php
/**
 * Standalone logic tests for the A4 password-KDF migration (bcrypt + pepper).
 *
 * These are NOT PHPUnit / Playwright — deliberately a single dependency-free
 * script so it can be run anywhere PHP is available, including inside the
 * container:  php tests/password_hashing_test.php
 *
 * It exercises the real icms_core_Password class (hashPassword / verifyPassword /
 * passwordNeedsUpgrade / applyPepper via those) covering: bcrypt+cost pinning,
 * correct/wrong verification, the site pepper actually participating, bcrypt's
 * 72-byte truncation being neutralised by the HMAC pre-step, cost-bump rehash,
 * and backward-compatible verification + upgrade-flagging of legacy SHA256 hashes.
 *
 * What it does NOT cover: the live login round-trip (fetch-then-verify in
 * icms_member_Handler::loginUser() and the transparent rehash-on-login write).
 * That still needs a real login against a running instance.
 *
 * Usage:
 *   php tests/password_hashing_test.php [path/to/Password.php]
 * The optional argument lets you point at an edited copy (handy when the running
 * container is mounted from a different checkout); it defaults to this repo's copy.
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. Refuse to run over the web: shipping this in the repo
// must never expose an unauthenticated, CPU-expensive (bcrypt) endpoint or
// disclose password-hashing internals on a live site. A web request just 404s.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// The pepper is normally XOOPS_DB_SALT from the trust folder. Define a fixed test
// value BEFORE the class is loaded (the class captures it into a property default).
if (!defined('XOOPS_DB_SALT')) {
    define('XOOPS_DB_SALT', 's3cr3t-test-pepper-from-trust-folder');
}

// The legacy verification branch calls encryptPass() -> priv_encryptPass(), which
// reads the global site encryption type. enc_type 1 == the default salted-SHA256.
$GLOBALS['icmsConfigUser'] = array('enc_type' => 1);

$passwordClassFile = isset($argv[1])
    ? $argv[1]
    : (__DIR__ . '/../libraries/icms/core/Password.php');

if (!is_file($passwordClassFile)) {
    fwrite(STDERR, "Cannot find Password.php at: $passwordClassFile\n");
    exit(2);
}
require_once $passwordClassFile;

// ---- tiny assertion harness -------------------------------------------------
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
    $ok = ($got === $want);
    $GLOBALS[$ok ? '__pass' : '__fail']++;
    printf("  [%s] %-52s got=%s want=%s\n",
        $ok ? 'PASS' : 'FAIL',
        $label,
        var_export($got, true),
        var_export($want, true));
}

$p = new icms_core_Password();
$plain = 'Corr3ct-Horse-Battery!';

echo "A4 password hashing — logic tests\n";
echo "pepper (XOOPS_DB_SALT) = " . XOOPS_DB_SALT . "\n\n";

// 1. New hashes are bcrypt at the pinned cost.
$hash = $p->hashPassword($plain);
$info = password_get_info($hash);
check('new hash algo is bcrypt',      $info['algoName'], 'bcrypt');
check('new hash cost is BCRYPT_COST', $info['options']['cost'], icms_core_Password::BCRYPT_COST);

// 2. Correct password verifies; wrong password does not.
check('verify correct password', $p->verifyPassword($plain, $hash), true);
check('reject wrong password',   $p->verifyPassword('not-the-password', $hash), false);

// 3. A freshly minted hash does not need upgrading.
check('fresh hash needs no upgrade', $p->passwordNeedsUpgrade($hash), false);

// 4. The pepper genuinely participates: a bcrypt hash built with a DIFFERENT
//    pepper key must not verify. (Proves a DB-only leak without the pepper is
//    useless to an attacker.)
$foreignPepperHash = password_hash(
    hash_hmac('sha256', $plain, 'a-different-pepper'),
    PASSWORD_BCRYPT, array('cost' => icms_core_Password::BCRYPT_COST));
check('hash under different pepper is rejected', $p->verifyPassword($plain, $foreignPepperHash), false);

// 5. bcrypt's 72-byte input limit is neutralised by the HMAC pre-step: two
//    passwords sharing their first 72 bytes must NOT be interchangeable.
$prefix72 = str_repeat('a', 72);
$longHash = $p->hashPassword($prefix72 . 'DIFFERENT-TAIL');
check('80-char password verifies against itself', $p->verifyPassword($prefix72 . 'DIFFERENT-TAIL', $longHash), true);
check('72-byte prefix does NOT collide',          $p->verifyPassword($prefix72, $longHash), false);

// 6. Cost bump: a hash written at a lower cost still verifies AND is flagged for
//    upgrade-on-login (simulates raising BCRYPT_COST later).
$lowCost = max(4, icms_core_Password::BCRYPT_COST - 2);
$oldCostHash = password_hash(
    hash_hmac('sha256', $plain, XOOPS_DB_SALT),
    PASSWORD_BCRYPT, array('cost' => $lowCost));
check('lower-cost hash still verifies',       $p->verifyPassword($plain, $oldCostHash), true);
check('lower-cost hash flagged for upgrade',  $p->passwordNeedsUpgrade($oldCostHash), true);

// 7. Legacy compatibility: a stored value in the old salted-SHA256 shape
//    (enc_type 1: sha256(salt . md5(pass) . pepper)) still verifies, is NOT
//    double-peppered, and is flagged for transparent upgrade on next login.
$legacySalt = 'perUserSalt-xyz';
$legacyHash = hash('sha256', $legacySalt . md5($plain) . XOOPS_DB_SALT);
check('legacy SHA256 hash verifies',         $p->verifyPassword($plain, $legacyHash, $legacySalt), true);
check('legacy SHA256 wrong pw rejected',     $p->verifyPassword('wrong', $legacyHash, $legacySalt), false);
check('legacy SHA256 flagged for upgrade',   $p->passwordNeedsUpgrade($legacyHash), true);

// 8. Degenerate stored values must fail closed, not error.
check('empty stored hash rejected',   $p->verifyPassword($plain, '', ''), false);
check('garbage stored hash rejected', $p->verifyPassword($plain, 'not-a-hash', 'x'), false);

// 9. The timing-equaliser returns false and does not throw.
check('wasteTimeVerifying returns false', $p->wasteTimeVerifying($plain), false);

echo "\n";
printf("%d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
