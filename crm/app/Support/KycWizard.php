<?php

namespace App\Support;

use App\Models\KycSubmission;
use App\Rules\KycFile;
use App\Support\LeadMax\Geo;
use Illuminate\Validation\Rule;

/**
 * The KYC wizard's steps -- Personal, Identity, Address -- as one rule set, shared by the
 * CRM's Add KYC wizard and the client area's own KYC window, so the two never drift apart.
 */
class KycWizard
{
    public const STEPS = ['personal', 'identity', 'address'];

    /** Validation rules for one step. */
    public static function rules(string $step): array
    {
        $countries = array_keys(Geo::COUNTRIES);

        return match ($step) {
            'personal' => [
                'first_name' => ['required', 'string', 'max:100'],
                // NC-13's declared single-legal-name exception: no last name when declared.
                'single_name' => ['nullable', 'boolean'],
                'last_name' => ['nullable', 'required_unless:single_name,1', 'string', 'max:100'],
                'birthdate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'nationality' => ['required', Rule::in($countries)],
                'dial_code' => ['required', 'regex:/^\+\d{1,4}$/'],
                'phone' => ['required', 'regex:/^[\d\s\-().]{4,20}$/'],
            ],
            'identity' => [
                'id_type' => ['required', Rule::in(array_keys(KycSubmission::ID_TYPES))],
                'id_type_other' => ['nullable', 'required_if:id_type,other', 'string', 'min:2', 'max:100'],
                'id_front' => KycFile::rules(),
                'id_back' => KycFile::rules(),
                // Optional document details (NC-23.1); never required.
                'document_number' => ['nullable', 'string', 'max:50'],
                'document_expiry' => ['nullable', 'date_format:Y-m-d'],
                'issuing_country' => ['nullable', Rule::in($countries)],
            ],
            'address' => [
                'street' => ['required', 'string', 'max:200'],
                'city' => ['required', 'string', 'max:100'],
                'postcode' => ['nullable', 'string', 'max:20'],
                'country' => ['required', Rule::in($countries)],
                'document_type' => ['required', Rule::in(array_keys(KycSubmission::ADDRESS_TYPES))],
                'document_type_other' => ['nullable', 'required_if:document_type,others', 'string', 'min:2', 'max:100'],
                'address_front' => KycFile::rules(),
                'address_back' => KycFile::rules(),
            ],
        };
    }

    public static function messages(): array
    {
        return [
            'birthdate.before_or_equal' => 'The date of birth cannot be in the future.',
            'last_name.required_unless' => 'Enter the legal last name, or declare a single legal name.',
            'document_type_other.required_if' => 'Specify the document type.',
            'id_type_other.required_if' => 'Enter the name of the identity document.',
        ];
    }

    /** The validated fields of a step as they are saved into the draft (files are stored apart). */
    public static function fields(string $step, array $data): array
    {
        $fields = collect($data)->except(['id_front', 'id_back', 'address_front', 'address_back'])
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
        if ($step === 'personal') {
            $fields['single_name'] = (bool) ($fields['single_name'] ?? false);
            if ($fields['single_name']) {
                $fields['last_name'] = null;
            }
        }
        if ($step === 'address' && $fields['document_type'] !== 'others') {
            // A value typed before switching away from Others is not kept.
            $fields['document_type_other'] = null;
        }
        if ($step === 'identity' && ($fields['id_type'] ?? null) !== 'other') {
            $fields['id_type_other'] = null;
        }
        if ($step === 'identity' && filled($fields['issuing_country'] ?? null)) {
            $fields['issuing_country'] = Geo::find($fields['issuing_country'])['code'];
        }

        return $fields;
    }

    /** The catalogues the wizard's choices come from, for a client to build the same lists. */
    public static function options(): array
    {
        return [
            'countries' => collect(Geo::COUNTRIES)->map(fn ($row, $name) => ['name' => $name, 'code' => $row[0]])->values()->all(),
            // "Other" with a typed name is the CRM wizard's manual entry; the client area keeps its list.
            'id_types' => collect(KycSubmission::ID_TYPES)->except('other')->map(fn ($type, $value) => ['value' => $value, 'label' => $type['label'], 'needs_back' => $type['back']])->values()->all(),
            'address_document_types' => collect(KycSubmission::ADDRESS_TYPES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all(),
            'max_file_kb' => 5120,
        ];
    }
}
