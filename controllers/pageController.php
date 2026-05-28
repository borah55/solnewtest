<?php
/**
 * Page controller - faucet dashboard, affiliate area, contact form.
 *
 * Bug fixes vs. the original:
 *   - Every query that previously interpolated $_SESSION['user_id'] is
 *     now parameterised.
 *   - Hash claim flow no longer leaves an orphan claims_hashes row when
 *     the FaucetPay payout fails.
 *   - num_rows is checked with a strict integer comparison.
 *
 * @package Solnew
 */

if (!defined('FERNICO')) {
    http_response_code(403);
    exit('Forbidden');
}

class pageController extends AstridController
{
    /** @var Authentication */
    public $auth;

    public function __construct()
    {
        require_once FERNICO_PATH . '/models/Bootstrapper.php';
        parent::__construct();
        $this->auth = new Authentication();
    }

    public function dashboard($claimHash = '')
    {
        App::vomitLoginPageByRedirection($this->auth);

        global $fernico_db;
        $userId = (int) $_SESSION['user_id'];
        $reward = App::loadSiteVar('faucet_reward');

        $opt = [
            'pageName'    => 'Faucet',
            'captchaCode' => App::getCaptcha(),
            'winAmt'      => $reward,
        ];

        $opt['claims_registered'] = $this->fetchUserClaims($userId);

        // ------------------------------------------------------------
        // Stage 2: user has clicked through the shortlink, redeem the
        // hash for the actual payout.
        // ------------------------------------------------------------
        if ($claimHash !== '' && strlen($claimHash) === 64) {
            $stmt = $fernico_db->prepare(
                'SELECT id, win_amount FROM claims_hashes
                  WHERE user_id = ? AND hash = ? LIMIT 1'
            );
            $stmt->bind_param('is', $userId, $claimHash);
            $stmt->execute();
            $hashRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($hashRow) {
                $opt['responseMessage'] = $this->processClaim($userId, (int) $hashRow['id'], (float) $hashRow['win_amount']);
                $opt['claims_registered'] = $this->fetchUserClaims($userId);
            }
        }

        // ------------------------------------------------------------
        // Stage 1: user pressed the "Claim" button - validate captcha,
        // check rate limit, then redirect through the shortlink.
        // ------------------------------------------------------------
        if (Request::POST('claim')) {
            if (!fernico_verifyAntiCSRFToken(Request::POST('csrf_token'))) {
                $opt['responseMessage'] = 'Your session expired. Please reload and try again.';
            } elseif (!App::verifyCaptcha()) {
                $opt['responseMessage'] = 'The captcha test was not solved correctly.';
            } else {
                $opt['responseMessage'] = $this->beginClaim($userId, $reward);
            }
        }

        $this->renderTemplate('Dashboard.tpl', $opt);
    }

    /**
     * Pull this user's most recent 100 claims for display on the dashboard.
     */
    private function fetchUserClaims($userId)
    {
        global $fernico_db;
        $userId = (int) $userId;
        $stmt = $fernico_db->prepare(
            'SELECT id, amount_credited, time
               FROM claims_registered
              WHERE user_id = ?
              ORDER BY id DESC
              LIMIT 100'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * Step one: create a claim hash, store it, and bounce the user
     * through the configured shortlink.
     */
    private function beginClaim($userId, $rewardAmount)
    {
        global $fernico_db;

        $stmt = $fernico_db->prepare('SELECT last_claimed FROM users WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $lastClaimed = (int) ($row['last_claimed'] ?? 0);
        $diff = time() - $lastClaimed;
        $cooldown = (int) round((float) App::loadSiteVar('faucet_time_limit') * 60);

        if ($diff < $cooldown) {
            $remaining = (int) ceil(($cooldown - $diff) / 60);
            return "Please wait {$remaining} more minute(s) before claiming again.";
        }

        $claimHash = bin2hex(random_bytes(32));
        $linkClaim = fernico_getAbsURL() . 'page/dashboard/' . $claimHash;
        $shortLinkPref = (int) App::loadSiteVar('shortlink_preference');
        $finalUrl = $linkClaim;

        if ($shortLinkPref === 1) {
            $shortened = fernico_get(
                'https://ouo.io/api/' . App::loadSiteVar('ouo_api_key') . '?s=' . urlencode($linkClaim)
            );
            if ($shortened && filter_var($shortened, FILTER_VALIDATE_URL)) {
                $finalUrl = $shortened;
            } else {
                return 'The shortlink service is unavailable. Please try again later.';
            }
        } elseif ($shortLinkPref === 2) {
            $shortened = App::shortest($linkClaim);
            if ($shortened && filter_var($shortened, FILTER_VALIDATE_URL)) {
                $finalUrl = $shortened;
            } else {
                return 'The shortlink service is unavailable. Please try again later.';
            }
        }

        $now = time();
        $stmt = $fernico_db->prepare(
            'INSERT INTO claims_hashes (user_id, hash, win_amount, time)
                  VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('isdi', $userId, $claimHash, $rewardAmount, $now);
        $stmt->execute();
        $stmt->close();

        header('Location: ' . $finalUrl);
        fernico_destroy();
    }

    /**
     * Step two: settle the claim by calling FaucetPay, paying out the
     * referrer if any, and tearing down the redeemed hash.
     *
     * Returns a human-readable status string.
     */
    private function processClaim($userId, $hashId, $winAmount)
    {
        global $fernico_db;

        $now = time();

        $paymentResp = App::sendFaucetPay($userId, $winAmount);
        $paymentOk = is_array($paymentResp) && (int) ($paymentResp['status'] ?? 0) === 200;

        if (!$paymentOk) {
            $msg = is_array($paymentResp) ? ($paymentResp['message'] ?? 'unknown error') : 'unknown error';
            return 'We failed to process your claim. FaucetPay responded: ' . $msg;
        }

        // Record the payout.
        $userName = (string) $_SESSION['user_name'];
        $stmt = $fernico_db->prepare(
            'INSERT INTO claims_registered (user_id, user_name, time, amount_credited)
                  VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('isid', $userId, $userName, $now, $winAmount);
        $stmt->execute();
        $stmt->close();

        // Burn the hash so it can't be redeemed twice.
        $stmt = $fernico_db->prepare('DELETE FROM claims_hashes WHERE id = ?');
        $stmt->bind_param('i', $hashId);
        $stmt->execute();
        $stmt->close();

        // Pay the referrer, if any.
        $referralReward = 0.0;
        $stmt = $fernico_db->prepare('SELECT referral FROM users WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $referralRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $referrerId = (int) ($referralRow['referral'] ?? 0);

        if ($referrerId > 0) {
            $referralPercent = (float) App::loadSiteVar('referral_percentage');
            $referralReward = (float) bcmul((string) ($referralPercent / 100), (string) $winAmount, 8);

            $referralResp = App::sendFaucetPay($referrerId, $referralReward, true);
            $referralOk = is_array($referralResp) && (int) ($referralResp['status'] ?? 0) === 200;

            if ($referralOk) {
                $stmt = $fernico_db->prepare('SELECT user_name FROM users WHERE user_id = ? LIMIT 1');
                $stmt->bind_param('i', $referrerId);
                $stmt->execute();
                $referrerName = (string) ($stmt->get_result()->fetch_assoc()['user_name'] ?? '');
                $stmt->close();

                $stmt = $fernico_db->prepare(
                    'INSERT INTO referral_returns (user_name, referred_by, amount, time)
                          VALUES (?, ?, ?, ?)'
                );
                $stmt->bind_param('sidi', $referrerName, $userId, $referralReward, $now);
                $stmt->execute();
                $stmt->close();

                $stmt = $fernico_db->prepare(
                    'UPDATE users SET referral_income = referral_income + ?
                      WHERE user_id = ?'
                );
                $stmt->bind_param('di', $referralReward, $referrerId);
                $stmt->execute();
                $stmt->close();
            } else {
                // The referrer lookup didn't pay out; reset the locally-
                // attributed reward so the stats stay consistent.
                $referralReward = 0.0;
            }
        }

        // Bump the claimer's timestamps and counters.
        $stmt = $fernico_db->prepare(
            'UPDATE users
                SET last_claimed = ?,
                    claims_made  = claims_made + 1,
                    referred_income = referred_income + ?
              WHERE user_id = ?'
        );
        $stmt->bind_param('idi', $now, $referralReward, $userId);
        $stmt->execute();
        $stmt->close();

        // Site-wide stats.
        $totalCredited = $winAmount + $referralReward;
        $fernico_db->query("UPDATE config SET value = value + 1 WHERE parameter = 'stats_Claims_Made'");
        $stmt = $fernico_db->prepare(
            "UPDATE config SET value = value + ? WHERE parameter = 'stats_Amount_Claimed'"
        );
        $stmt->bind_param('d', $totalCredited);
        $stmt->execute();
        $stmt->close();

        return 'Your claim of ' . $winAmount . ' ' . App::loadSiteVar('coin_abbreviation')
             . ' has been sent to your FaucetPay account.';
    }

    public function affiliate__programme()
    {
        App::vomitLoginPageByRedirection($this->auth);
        global $fernico_db;

        $userId = (int) $_SESSION['user_id'];
        $stmt = $fernico_db->prepare(
            'SELECT referral_income FROM users WHERE user_id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc() ?: ['referral_income' => '0.00000000'];
        $stmt->close();

        $this->renderTemplate('Affiliate-Programme.tpl', [
            'pageName' => 'Affiliate Programme',
            'u'        => $u,
        ]);
    }

    public function referred__users()
    {
        App::vomitLoginPageByRedirection($this->auth);
        global $fernico_db;

        $userId = (int) $_SESSION['user_id'];
        $records = 50;

        $stmt = $fernico_db->prepare(
            'SELECT COUNT(user_id) AS id FROM users WHERE referral = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $numRows = (int) $stmt->get_result()->fetch_assoc()['id'];
        $stmt->close();

        [$reqPage, $totalPages, $offset] = $this->paginate($numRows, $records);

        $stmt = $fernico_db->prepare(
            'SELECT user_name, registration_datetime, referred_income
               FROM users WHERE referral = ?
              ORDER BY user_id DESC LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('iii', $userId, $records, $offset);
        $stmt->execute();
        $items = $stmt->get_result();
        $stmt->close();

        $this->renderTemplate('Referred-Users.tpl', [
            'pageName'    => 'Referred Users',
            'items'       => $items,
            'req_page'    => $reqPage,
            'total_pages' => $totalPages,
        ]);
    }

    public function referral__claims()
    {
        App::vomitLoginPageByRedirection($this->auth);
        global $fernico_db;

        $userId = (int) $_SESSION['user_id'];
        $records = 100;

        $stmt = $fernico_db->prepare(
            'SELECT COUNT(id) AS id FROM referral_returns WHERE referred_by = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $numRows = (int) $stmt->get_result()->fetch_assoc()['id'];
        $stmt->close();

        [$reqPage, $totalPages, $offset] = $this->paginate($numRows, $records);

        $stmt = $fernico_db->prepare(
            'SELECT user_name, amount, time
               FROM referral_returns WHERE referred_by = ?
              ORDER BY id DESC LIMIT ? OFFSET ?'
        );
        $stmt->bind_param('iii', $userId, $records, $offset);
        $stmt->execute();
        $items = $stmt->get_result();
        $stmt->close();

        $this->renderTemplate('Referral-Claims.tpl', [
            'pageName'    => 'Referral Claims',
            'items'       => $items,
            'req_page'    => $reqPage,
            'total_pages' => $totalPages,
        ]);
    }

    public function contact()
    {
        $opt = ['pageName' => 'Contact'];

        if (Request::POST('contactForm') !== null) {
            if (!fernico_verifyAntiCSRFToken(Request::POST('csrf_token'))) {
                $opt['responseMessage'] = 'Your session expired. Please reload and try again.';
            } else {
                $opt['responseMessage'] = App::contactFormSubmit();
            }
        }

        $this->renderTemplate('Contact.tpl', $opt);
    }

    /**
     * Compute a clamped page index from the ?offset= query string.
     *
     * @return array{0:int,1:int,2:int} [requested page, total pages, offset]
     */
    private function paginate($numRows, $perPage)
    {
        $totalPages = max(1, (int) ceil($numRows / $perPage));
        $reqPage = (int) Request::GET('offset');
        if ($reqPage < 1) {
            $reqPage = 1;
        }
        if ($reqPage > $totalPages) {
            $reqPage = $totalPages;
        }
        return [$reqPage, $totalPages, ($reqPage - 1) * $perPage];
    }
}
