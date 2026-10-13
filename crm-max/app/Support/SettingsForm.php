<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The data a settings page's form is opened with, and which of its fields a save changed.
 *
 * Settings pages used to open their form with every setting the tenant has and post all of them
 * back. Splade posts multipart data, so a tenant with many settings lost the fields past PHP's
 * max_input_vars; a save re-ran other pages' validation, so one unrelated stored value could
 * block it; and it put the page's old values back over anything changed since it was opened.
 * A form now carries only its own fields plus a fingerprint of each, and a save applies only
 * the fields whose value differs from what the page showed.
 */
class SettingsForm
{
    /** The posted field holding the fingerprints, as one JSON object. */
    public const LOADED_KEY = '_settings_loaded';

    /** The page's own fields from $settings, plus the fingerprints of what it shows. */
    public static function data(array $settings, string $view, array $extraFields = []): array
    {
        $data = array_intersect_key($settings, array_flip(array_merge(self::fields($view), $extraFields)));
        $data[self::LOADED_KEY] = json_encode(array_map(fn ($value) => self::fingerprint($value), $data));

        return $data;
    }

    /** The settings a page's form edits: its fields' names and the form.* values its template reads. */
    public static function fields(string $view): array
    {
        $template = (string) file_get_contents(view()->getFinder()->find($view));
        preg_match_all('/\bname="([A-Za-z0-9_]+)(?:\[\])?"/', $template, $names);
        preg_match_all('/\bform\.([A-Za-z0-9_]+)/', $template, $reads);

        return array_values(array_unique(array_merge($names[1], $reads[1])));
    }

    /**
     * Whether the request carries $key with a value other than the page showed. A page opened
     * before fingerprints existed posts none, and then every field it posts counts as changed.
     */
    public static function changed(Request $request, string $key): bool
    {
        if (! $request->exists($key)) {
            return false;
        }

        $loaded = $request->input(self::LOADED_KEY);
        $loaded = is_string($loaded) ? json_decode($loaded, true) : $loaded;

        return ! is_array($loaded) || ($loaded[$key] ?? null) !== self::fingerprint($request->input($key));
    }

    /** Only the entries of $values the page changed. */
    public static function onlyChanged(Request $request, array $values): array
    {
        return array_filter($values, fn ($value, $key) => self::changed($request, (string) $key), ARRAY_FILTER_USE_BOTH);
    }

    /** Compares values as the form sends them back: true/"1", 5/"5" and null/"" count as the same. */
    public static function fingerprint(mixed $value): string
    {
        $normalize = function ($value) use (&$normalize) {
            return match (true) {
                is_array($value) => array_map($normalize, $value),
                is_bool($value) => $value ? '1' : '0',
                default => (string) ($value ?? ''),
            };
        };

        return md5((string) json_encode($normalize($value)));
    }
}
