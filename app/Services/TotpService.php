<?php

declare(strict_types=1);

namespace App\Services;

final class TotpService
{
    public function verify(string $base32Secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->codeForSlice($base32Secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private function codeForSlice(string $base32Secret, int $slice): string
    {
        $secret = $this->base32Decode($base32Secret);
        $time = pack('N*', 0) . pack('N*', $slice);
        $hm = hash_hmac('sha1', $time, $secret, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hash = substr($hm, $offset, 4);
        $value = unpack('N', $hash)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $map = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bits = 0;
        $result = '';

        foreach (str_split($secret) as $char) {
            $buffer = ($buffer << 5) | $map[$char];
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $result .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $result;
    }
}
