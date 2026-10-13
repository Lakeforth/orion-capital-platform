@php
    $compact = $compact ?? false;
    $hoverActions = $hoverActions ?? false;
@endphp

<div class="grid">
    @if(company_settings('mini_wallet','no') == 'yes')
        <Balance
            wallet_url="{{ route('admin.user.wallet', $item->id) }}"
            edit_url="{{ route('admin.users.edit', $item->id) }}"
            balance="{{ format_money($item->estimatedBalance()) }}"
            credit="{{ format_money($item->estimatedCreditBalance()) }}"
            custom_wallet="{{ company_settings('custom_wallet') }}"
            total_balance="{{ format_money($item->totalPortfolioBalance()) }}"
            id="{{ $item->id }}"
            is_admin="{{ is_admin() }}"
            :compact='@js($compact)'
            :hover-actions='@js($hoverActions)'
        />
    @else
        <Balance
            wallet_url="{{ route('admin.user.wallet', $item->id) }}"
            edit_url="{{ route('admin.users.edit', $item->id) }}"
            balance="{{ format_money($item->estimatedBalance()) }}"
            custom_wallet="Credit"
            credit="{{ format_money($item->estimatedCreditBalance()) }}"
            bonus="{{ format_money($item->estimatedBonusBalance()) }}"
            total_balance="{{ format_money($item->totalPortfolioBalance()) }}"
            id="{{ $item->id }}"
            is_admin="{{ is_admin() }}"
            :compact='@js($compact)'
            :hover-actions='@js($hoverActions)'
        />
    @endif


</div>
