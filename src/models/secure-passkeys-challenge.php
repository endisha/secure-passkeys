<?php

namespace Secure_Passkeys\Models;

use DateInterval;
use DateTime;
use Exception;
use Secure_Passkeys\Core\Secure_Passkeys_Model;
use Secure_Passkeys\Utils\Secure_Passkeys_Helper;

defined('ABSPATH') || exit;

class Secure_Passkeys_Challenge extends Secure_Passkeys_Model
{
    protected $table = 'secure_passkeys_challenges';

    public function generate_challenge(string $challenge_type, ?int $userId = null, ?string $ip_address = null): string
    {
        if (is_null($ip_address)) {
            $ip_address = Secure_Passkeys_Helper::get_ip_address();
        }

        $fingerprint = Secure_Passkeys_Helper::generate_fingerprint();

        if ($challenge_type === 'registration') {
            $minutes = intval(Secure_Passkeys_Helper::get_option('login_timeout', 5));
        } else {
            $minutes = intval(Secure_Passkeys_Helper::get_option('registration_timeout', 5));
        }

        $date = (new DateTime())->add(new DateInterval('PT' . $minutes . 'M'));
        $expire_date = $date->format('Y-m-d H:i:s');

        $counter = 0;
        do {
            $challenge = base64_encode(random_bytes(32));
            $counter++;

            if (!$this->is_challenge_exists($challenge)) {
                $this->insert([
                    'user_id' => $userId,
                    'challenge_type' => $challenge_type,
                    'challenge' => $challenge,
                    'fingerprint' => $fingerprint,
                    'expired_at' => $expire_date,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ]);
                break;
            }
        } while ($counter < 10);

        return $challenge;
    }

    public function verify_challenge_or_throw_exception(string $challenge, string $challenge_type, ?string $ip_address = null): void
    {
        $fingerprint = Secure_Passkeys_Helper::generate_fingerprint();

        if (!is_null($ip_address)) {
            $record = $this->get_by_challenge_ip_address_not_used($challenge, $ip_address);
        } else {
            $record = $this->get_by_challenge_not_used($challenge);
        }

        if (is_null($record)) {
            throw new Exception('Invalid challenge');
        }

        if ($record->challenge_type !== $challenge_type) {
            throw new Exception('Invalid challenge type');
        }

        if ($record->fingerprint !== $fingerprint) {
            throw new Exception('Invalid fingerprint');
        }

        if ($record->expired_at < (new DateTime())->format('Y-m-d H:i:s')) {
            throw new Exception('Expired challenge');
        }
    }

    public function mark_as_used_challenge(string $challenge): void
    {
        $this->update(['challenge' => $challenge], ['used_at' => (new DateTime())->format('Y-m-d H:i:s')]);
    }

    public function is_challenge_exists(?string $challenge)
    {
        $record = $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `challenge` = %s
            ", $challenge)
        );

        return !is_null($record);
    }

    public function get_by_challenge_not_used(?string $challenge)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `challenge` = %s AND `used_at` IS NULL
            ", $challenge)
        );
    }

    public function get_by_challenge_ip_address_not_used(?string $challenge, ?string $ip_address)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `challenge` = %s AND `ip_address` = %s AND `used_at` IS NULL
            ", $challenge, $ip_address)
        );
    }

    public function get_count()
    {
        return (int) $this->db->get_var(
            $this->db->prepare("
                SELECT COUNT(*) as `count`
                FROM {$this->table}
            ")
        );
    }
}
