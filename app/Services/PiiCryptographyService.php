<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

class PiiCryptographyService
{
    private ?Encrypter $encrypter = null;

    public function enabled(): bool
    {
        return (bool) config('pii.enabled', false);
    }

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        return (string) config('pii.prefix', 'pii:v1:').$this->encrypter()->encryptString($value);
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! $this->isEncrypted($value)) {
            // Transitional compatibility for pre-migration rows only.
            return $value;
        }

        $prefix = (string) config('pii.prefix', 'pii:v1:');

        return $this->encrypter()->decryptString(substr($value, strlen($prefix)));
    }

    public function isEncrypted(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, (string) config('pii.prefix', 'pii:v1:'));
    }

    public function blindIndex(?string $value, string $type = 'generic'): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $key = $this->decodeKey((string) config('pii.search_key'), 'PII_SEARCH_KEY');

        return hash_hmac('sha256', $this->normalize($value, $type), $key);
    }

    public function normalize(string $value, string $type = 'generic'): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return match ($type) {
            'email' => mb_strtolower($value, 'UTF-8'),
            'nic', 'pay_no', 'wop_number', 'service_file_no', 'professional_registration_no' => strtoupper(preg_replace('/\s+/u', '', $value) ?? $value),
            'phone' => preg_replace('/[^0-9+]/', '', $value) ?? $value,
            'name' => mb_strtolower($value, 'UTF-8'),
            default => mb_strtolower($value, 'UTF-8'),
        };
    }

    private function encrypter(): Encrypter
    {
        if ($this->encrypter) {
            return $this->encrypter;
        }

        $key = $this->decodeKey((string) config('pii.encryption_key'), 'PII_ENCRYPTION_KEY');
        $cipher = (string) config('pii.cipher', 'AES-256-CBC');

        if (! Encrypter::supported($key, $cipher)) {
            throw new RuntimeException("PII encryption key/cipher combination is not supported ({$cipher}).");
        }

        return $this->encrypter = new Encrypter($key, $cipher);
    }

    private function decodeKey(string $configured, string $name): string
    {
        if ($configured === '') {
            throw new RuntimeException("{$name} is not configured. Refusing to handle protected personnel data.");
        }

        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if ($decoded === false) {
                throw new RuntimeException("{$name} contains invalid base64 data.");
            }

            return $decoded;
        }

        return $configured;
    }
}
