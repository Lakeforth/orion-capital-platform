<?php

namespace App\Services;

/**
 * Minimal RFC-6238 TOTP implementation, hand-rolled to avoid pulling a
 * composer package onto the shared box. Compatible with Google
 * Authenticator / Authy (SHA1, 6 digits, 30s period).
 */
class TwoFactorService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    /** Generate a random base32 secret (default 20 bytes -> 32 chars). */
    public function generateSecret(int $bytes = 20): string
    {
        $random = random_bytes($bytes);
        $secret = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($random) as $char) {
            $buffer = ($buffer << 8) | ord($char);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $secret .= self::BASE32_ALPHABET[($buffer >> $bitsLeft) & 31];
            }
        }
        if ($bitsLeft > 0) {
            $secret .= self::BASE32_ALPHABET[($buffer << (5 - $bitsLeft)) & 31];
        }

        return $secret;
    }

    /** Build the otpauth:// URI a QR code encodes. */
    public function otpauthUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer.':'.$account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    /** Verify a user-entered code, tolerating +-1 time step for clock drift. */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $key = $this->base32Decode($secret);
        if ($key === '') {
            return false;
        }

        $counter = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generateCode($key, $counter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /** Generate a batch of formatted single-use recovery codes. */
    public function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->recoveryChunk(4).'-'.$this->recoveryChunk(4);
        }

        return $codes;
    }

    private function recoveryChunk(int $len): string
    {
        $alphabet = 'ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }

    private function generateCode(string $key, int $counter): string
    {
        $binCounter = pack('N*', 0, $counter);
        $hash = hash_hmac('sha1', $binCounter, $key, true);
        $offset = ord($hash[19]) & 0xF;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));
        if ($secret === '') {
            return '';
        }

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        foreach (str_split($secret) as $char) {
            $val = strpos(self::BASE32_ALPHABET, $char);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
