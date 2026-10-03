<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\PiiCryptographyService;
use Illuminate\Database\Eloquent\Builder;

trait EncryptsPersonnelData
{
    /**
     * Models using this trait define:
     * protected array $encryptedPii = ['field' => 'normalizer', ...];
     * protected array $searchablePii = ['field', ...];
     */
    public function setAttribute($key, $value)
    {
        if ($this->isPiiField((string) $key) && app(PiiCryptographyService::class)->enabled()) {
            $service = app(PiiCryptographyService::class);
            $plain = $value === null ? null : (string) $value;
            $this->attributes[$key] = $service->encrypt($plain);

            if ($this->isSearchablePii((string) $key)) {
                $this->attributes[$key.'_hmac'] = $service->blindIndex($plain, $this->piiNormalizer((string) $key));
            }

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttributeValue($key)
    {
        if ($this->isPiiField((string) $key)) {
            $raw = $this->getAttributeFromArray($key);
            if ($raw === null) {
                return null;
            }

            return app(PiiCryptographyService::class)->decrypt((string) $raw);
        }

        return parent::getAttributeValue($key);
    }

    public function scopeWherePiiEquals(Builder $query, string $field, ?string $value): Builder
    {
        if (! $this->isSearchablePii($field)) {
            throw new \InvalidArgumentException("{$field} is not configured for exact PII search.");
        }

        if ($value === null || trim($value) === '') {
            return $query->whereRaw('1 = 0');
        }

        $service = app(PiiCryptographyService::class);
        if (! $service->enabled()) {
            return $query->where($field, $value);
        }

        $hmac = $service->blindIndex($value, $this->piiNormalizer($field));

        // HMAC path is the normal path. The exact plaintext OR is transitional
        // compatibility for rows not yet processed by pii:encrypt-existing.
        return $query->where(function (Builder $inner) use ($field, $value, $hmac, $service) {
            $inner->where($field.'_hmac', $hmac)
                ->orWhere(function (Builder $legacy) use ($field, $value, $service) {
                    $legacy->whereNull($field.'_hmac')->where($field, $value);
                });
        });
    }

    public function piiIsEncrypted(string $field): bool
    {
        return app(PiiCryptographyService::class)->isEncrypted(
            isset($this->attributes[$field]) ? (string) $this->attributes[$field] : null
        );
    }

    protected function isPiiField(string $field): bool
    {
        return property_exists($this, 'encryptedPii') && array_key_exists($field, $this->encryptedPii);
    }

    protected function isSearchablePii(string $field): bool
    {
        return property_exists($this, 'searchablePii') && in_array($field, $this->searchablePii, true);
    }

    protected function piiNormalizer(string $field): string
    {
        return $this->encryptedPii[$field] ?? 'generic';
    }
}
