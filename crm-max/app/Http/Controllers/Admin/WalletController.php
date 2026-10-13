<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Tables\AllWalletTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ProtoneMedia\Splade\Facades\Toast;

class WalletController extends Controller
{
    public function index()
    {
        return view('admin.wallets.all', [
            'data' => new AllWalletTable,
        ]);
    }

    public function create(Request $request)
    {
        [$walletCryptoOptions, $walletCryptoNetworkOptions] = $this->walletFormOptions();

        return view('admin.wallets.create', [
            'default' => [
                'user_id' => $request->get('user_id'),
                'type' => 'wallet',
            ],
            'walletCryptoOptions' => $walletCryptoOptions,
            'walletCryptoNetworkOptions' => $walletCryptoNetworkOptions,
        ]);
    }

    public function edit($id)
    {
        $wallet = Wallet::findOrFail($id);

        if (! $wallet->type) {
            $wallet->type = 'wallet';
        }

        [$walletCryptoOptions, $walletCryptoNetworkOptions] = $this->walletFormOptions($wallet);

        if ($wallet->type === 'wallet') {
            $wallet->crypto = $this->normalizeWalletCryptoForForm($wallet);
        }

        return view('admin.wallets.edit', [
            'default' => $wallet,
            'walletCryptoOptions' => $walletCryptoOptions,
            'walletCryptoNetworkOptions' => $walletCryptoNetworkOptions,
        ]);
    }

    public function destroy($id)
    {
        $wallet = Wallet::findOrFail($id);
        $wallet->delete();

        Toast::title('Wallet deleted successfully');

        return redirect()->back();
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (! ($data['user_id'] ?? null)) {
            $data['is_general'] = true;
        }

        Wallet::create($data);

        Toast::title('Wallet created successfully');

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateData($request, $id);
        $wallet = Wallet::findOrFail($id);
        $wallet->update($data);

        Toast::title('Wallet updated successfully');

        return redirect()->back();
    }

    public function toggle($id, $field): RedirectResponse
    {
        $item = Wallet::findOrFail($id);
        $item->{$field} = ! $item->{$field};
        $item->save();

        Toast::title('Wallet updated successfully');

        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $type = (string) ($request->input('type') ?: 'wallet');
        $existingWallet = $id ? Wallet::findOrFail($id) : null;
        $walletCryptoDefinitions = $this->walletCryptoDefinitions($existingWallet);

        $rules = [
            'user_id' => 'nullable',
            'crypto' => $type === 'wallet'
                ? ['required', Rule::in(array_keys($walletCryptoDefinitions))]
                : ['required', 'string'],
            'crypto_network' => ['required', 'string'],
            'address' => ['required', 'string'],
            'status' => 'nullable',
            'barcode' => 'nullable',
            'type' => 'nullable|in:wallet,link',
            'is_general' => 'nullable',
            'is_active' => 'nullable',
        ];

        $data = $request->validate($rules);

        if (empty($data['type'])) {
            $data['type'] = 'wallet';
        }

        if ($data['type'] === 'wallet') {
            $definition = $walletCryptoDefinitions[$data['crypto']] ?? null;

            if (! $definition) {
                throw ValidationException::withMessages([
                    'crypto' => 'Selected crypto is not enabled for this company.',
                ]);
            }

            $matchedNetwork = collect($definition['networks'] ?? [])
                ->first(fn (string $network) => strcasecmp($network, (string) $data['crypto_network']) === 0);

            if (! $matchedNetwork) {
                throw ValidationException::withMessages([
                    'crypto_network' => 'Selected network is not valid for the chosen crypto.',
                ]);
            }

            $data['crypto'] = $definition['code'];
            $data['crypto_network'] = $matchedNetwork;
        }

        return $data;
    }

    private function walletFormOptions(?Wallet $wallet = null): array
    {
        $definitions = $this->walletCryptoDefinitions($wallet);

        $walletCryptoOptions = collect($definitions)
            ->mapWithKeys(fn (array $definition, string $code) => [
                $code => sprintf('%s (%s)', $definition['name'], $code),
            ])
            ->all();

        $walletCryptoNetworkOptions = collect($definitions)
            ->mapWithKeys(fn (array $definition, string $code) => [
                $code => collect($definition['networks'] ?? [])
                    ->values()
                    ->map(fn (string $network) => [
                        'value' => $network,
                        'label' => $network,
                    ])
                    ->all(),
            ])
            ->all();

        return [$walletCryptoOptions, $walletCryptoNetworkOptions];
    }

    private function walletCryptoDefinitions(?Wallet $wallet = null): array
    {
        $configuredWallets = collect(config('crypto_wallets', []));

        $selectedIds = collect(Arr::wrap(company_settings('crypto_wallet_ids', [])))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => trim((string) $id))
            ->values();

        $definitions = $selectedIds
            ->map(fn (string $id) => $configuredWallets->get($id))
            ->filter()
            ->groupBy(fn (array $walletConfig) => strtoupper((string) ($walletConfig['code'] ?? '')))
            ->map(function (Collection $group, string $code) {
                $first = $group->first();

                return [
                    'code' => $code,
                    'name' => (string) ($first['name'] ?? $code),
                    'networks' => $group
                        ->flatMap(fn (array $walletConfig) => Arr::wrap($walletConfig['networks'] ?? []))
                        ->filter(fn ($network) => filled($network))
                        ->map(fn ($network) => trim((string) $network))
                        ->unique(fn (string $network) => strtolower($network))
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $definition) => filled($definition['code']))
            ->all();

        if ($wallet && $wallet->type === 'wallet' && filled($wallet->crypto)) {
            $normalizedCode = $this->normalizeWalletCryptoForForm($wallet);

            if (! isset($definitions[$normalizedCode])) {
                $definitions[$normalizedCode] = [
                    'code' => $normalizedCode,
                    'name' => $wallet->crypto,
                    'networks' => collect([$wallet->crypto_network])
                        ->filter(fn ($network) => filled($network))
                        ->values()
                        ->all(),
                ];
            } elseif (
                filled($wallet->crypto_network)
                && ! collect($definitions[$normalizedCode]['networks'])->contains(
                    fn (string $network) => strcasecmp($network, $wallet->crypto_network) === 0
                )
            ) {
                $definitions[$normalizedCode]['networks'][] = $wallet->crypto_network;
            }
        }

        return $definitions;
    }

    private function normalizeWalletCryptoForForm(Wallet $wallet): string
    {
        $walletCrypto = trim((string) $wallet->crypto);

        $match = collect(config('crypto_wallets', []))->first(function (array $walletConfig) use ($walletCrypto) {
            return in_array(strtolower($walletCrypto), [
                strtolower((string) ($walletConfig['code'] ?? '')),
                strtolower((string) ($walletConfig['name'] ?? '')),
                strtolower((string) ($walletConfig['id'] ?? '')),
            ], true);
        });

        return strtoupper((string) ($match['code'] ?? $walletCrypto));
    }
}
