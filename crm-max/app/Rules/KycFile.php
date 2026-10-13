<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * A KYC upload must really be the image or PDF it claims to be.
 *
 * The mimes rule trusts the file's own signature as PHP's fileinfo reads it; this also
 * opens it: an image has to decode, a PDF has to start with its header. Size and type
 * limits stay in the rules list next to it (KYC only: 5 MiB, agreed with the frontend).
 */
class KycFile implements ValidationRule
{
    /** The rules every KYC file field uses, this one included. */
    public static function rules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120', new self];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $path = $value->getRealPath();
        $head = (string) @file_get_contents($path, false, null, 0, 5);

        $genuine = $head === '%PDF-'
            || (in_array(@getimagesize($path)[2] ?? null, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) && self::decodes($path));

        if (! $genuine) {
            $fail('The file could not be read as a JPG, PNG or PDF document.');

            return;
        }

        if ($head === '%PDF-' && self::pdfPages($path) > self::MAX_PDF_PAGES) {
            $fail('A KYC PDF can have at most '.self::MAX_PDF_PAGES.' pages.');
        }
    }

    /** NC-14's specified operational default; R-05 may change it. */
    public const MAX_PDF_PAGES = 10;

    /**
     * Pages in a PDF, as far as its uncompressed structure shows: the larger of the page
     * objects and the page tree's /Count. Compressed object streams can hide both, which
     * reads as 0 -- such a file is let through rather than refused on a guess.
     */
    private static function pdfPages(string $path): int
    {
        return self::pdfPagesIn((string) file_get_contents($path));
    }

    /** The same page count for a PDF already in memory (the CRM file reader's "Page N of M", NC-24.4). */
    public static function pdfPagesIn(string $content): int
    {
        $pages = preg_match_all('#/Type\s*/Page(?![a-zA-Z])#', $content);
        preg_match_all('#/Type\s*/Pages\b[^>]*?/Count\s+(\d+)|/Count\s+(\d+)[^>]*?/Type\s*/Pages\b#s', $content, $counts);
        $counted = array_map('intval', array_filter([...$counts[1], ...$counts[2]], 'strlen'));

        return max($pages, $counted ? max($counted) : 0);
    }

    /** getimagesize() only reads the header; actually decoding catches a forged one. */
    private static function decodes(string $path): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return true;
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));

        return $image !== false;
    }
}
