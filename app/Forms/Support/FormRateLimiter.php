<?php

namespace App\Forms\Support;

use App\Forms\Types\FormType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class FormRateLimiter
{
    public static function ensureAllowed(FormType $type, Request $request): void
    {
        [$maxAttempts, $decayMinutes] = $type->rateLimit();
        $key = self::keyFor($type, $request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'form' => trans('forms.errors.rate_limited'),
            ]);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
    }

    public static function clear(FormType $type, Request $request): void
    {
        RateLimiter::clear(self::keyFor($type, $request));
    }

    private static function keyFor(FormType $type, Request $request): string
    {
        return 'forms:'.$type->key().':'.$request->ip();
    }
}
