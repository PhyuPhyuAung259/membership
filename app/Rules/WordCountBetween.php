<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WordCountBetween implements ValidationRule
{
    public function __construct(
        private readonly int $min,
        private readonly int $max,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // An empty field is "not provided" here, not "zero words" — pair
        // this rule with 'nullable' to make the field optional; blank input
        // from a Livewire string property (never truly null) must still
        // pass through rather than fail the minimum.
        if (trim((string) $value) === '') {
            return;
        }

        $words = str_word_count(strip_tags((string) $value));

        if ($words < $this->min || $words > $this->max) {
            $fail("The :attribute must be between {$this->min} and {$this->max} words (currently {$words}).");
        }
    }
}
