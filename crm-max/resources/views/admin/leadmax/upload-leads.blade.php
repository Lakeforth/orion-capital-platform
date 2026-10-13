{{-- Upload Leads (Figma 2078:14630). --}}
@php
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/upload/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    $info = fn ($title) => '<span class="lm-up-info" title="'.e($title).'"><img src="'.asset('images/leadmax/upload/field-info-circle.svg').'" width="12.8333" height="12.8333" alt=""><img src="'.asset('images/leadmax/upload/field-info-line.svg').'" width="1.1725" height="5.83333" alt=""></span>';
    $change = fn ($value, bool $goodWhenUp = true) => $value === null ? '' : (round($value, 1) == 0 ? '<b class="is-flat">0%</b>' : '<b class="'.(($value >= 0) === $goodWhenUp ? 'is-up' : 'is-down').'">'.($value >= 0 ? '↑' : '↓').' '.abs($value).'%</b>');
    $statusBadge = fn (string $status) => '<span class="lm-up-status is-'.$status.'">'.e(['queued' => 'Queued', 'processing' => 'Processing', 'completed' => 'Completed', 'partial' => 'Partial', 'failed' => 'Failed'][$status] ?? ucfirst($status)).'</span>';
    $tabs = ['upload' => 'Upload Leads', 'history' => 'Upload History', 'mapping' => 'Field Mapping', 'settings' => 'Import Settings'];
    $processing = $uploads->getCollection()->whereIn('status', ['queued', 'processing'])->isNotEmpty() || ($details && in_array($details->status, ['queued', 'processing'], true));
    $fieldLabels = ['first_name' => 'First Name', 'last_name' => 'Last Name', 'email' => 'Email', 'phone' => 'Phone', 'country' => 'Country', 'source' => 'Source', 'campaign' => 'Campaign', 'affiliate' => 'Affiliate', 'notes' => 'Notes'];
@endphp
<x-leadmax-layout :central-url="$centralUrl" :active-lead-max-page="$activeLeadMaxPage">
<div class="lm-up" @if($processing) data-lm-refresh="5000" @endif>
    <header class="lm-up-top">
        <div>
            <h1>Upload Leads <span class="lm-up-info is-lg" title="Import leads from CSV or Excel files">{!! $icon('info-circle', 14.6667) !!}{!! $icon('info-line', 1.34, 6.66667) !!}</span></h1>
            <p>Import your leads in bulk and assign them to your team automatically.</p>
        </div>
        <div class="lm-up-top-right">
            <nav class="lm-up-crumbs"><a href="{{ route('admin.leadmax.dashboard') }}">Dashboard</a><span>/</span><span>CRM</span><span>/</span><b>Upload Leads</b></nav>
            <a class="lm-up-template-btn" href="{{ route('admin.leadmax.upload-leads.template') }}">{!! $icon('download', 14) !!}<span>View Template</span></a>
        </div>
    </header>
    @if(session('success'))<p class="lm-detail-flash" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="lm-detail-flash lm-detail-flash-warning" role="alert">{{ $errors->first() }}</p>@endif

    <section class="lm-up-kpis" aria-label="Upload summary">
        <div class="lm-up-kpi lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.upload-leads', ['tab' => 'history']) }}" aria-label="Total Uploaded (This Month)"></a><span class="lm-up-kpi-icon is-blue">{!! $icon('kpi-users', 20) !!}</span><div><small>Total Uploaded (This Month)</small><strong>{{ number_format($kpi['total']) }} {!! $change($kpi['totalChange']) !!}</strong><em>vs last month: {{ number_format($kpi['totalPrevious']) }}</em></div></div>
        <div class="lm-up-kpi lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.upload-leads', ['tab' => 'history']) }}" aria-label="Successfully Imported"></a><span class="lm-up-kpi-icon is-green">{!! $icon('kpi-check', 20) !!}</span><div><small>Successfully Imported</small><strong>{{ number_format($kpi['imported']) }} {!! $change($kpi['importedChange']) !!}</strong><em>{{ $kpi['successRate'] !== null ? $kpi['successRate'].'% success rate' : 'No uploads this month' }}</em></div></div>
        <div class="lm-up-kpi lm-card-link"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.upload-leads', ['tab' => 'history']) }}" aria-label="Failed / Rejected"></a><span class="lm-up-kpi-icon is-red">{!! $icon('kpi-x', 20) !!}</span><div><small>Failed / Rejected</small><strong>{{ number_format($kpi['failed']) }} {!! $change($kpi['failedChange'], false) !!}</strong><em>{{ $kpi['successRate'] !== null ? round(100 - $kpi['successRate'], 1).'% failed' : '—' }}</em></div></div>
        <div class="lm-up-kpi lm-card-link is-last"><a class="lm-card-main lm-card-cover" href="{{ route('admin.leadmax.upload-leads', ['tab' => 'history']) }}" aria-label="Last Upload"></a><span class="lm-up-kpi-icon is-navy">{!! $icon('kpi-clock', 20) !!}</span><div><small>Last Upload</small><strong class="is-small">{{ $lastUpload?->created_at->format('M d, Y H:i') ?? 'No uploads yet' }}</strong><em>{{ $lastUpload ? 'By '.($lastUpload->uploader?->name ?? 'System') : '' }}</em></div></div>
    </section>

    <nav class="lm-up-tabs" aria-label="Upload sections">
        @foreach($tabs as $key => $label)<a href="{{ route('admin.leadmax.upload-leads', $key === 'upload' ? [] : ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach
    </nav>

    @if($tab === 'upload' || $tab === 'settings')
        @php $isSettings = $tab === 'settings'; @endphp
        <form id="lm-up-form" class="lm-up-grid {{ $isSettings ? 'is-settings' : '' }}" method="POST" action="{{ $isSettings ? route('admin.leadmax.upload-leads.settings') : route('admin.leadmax.upload-leads.store') }}" enctype="multipart/form-data" @unless($isSettings) data-lm-upload-form @endunless>
            @csrf
            @if($isSettings)<input type="hidden" name="section" value="defaults">@endif
            @unless($isSettings)
            <section class="lm-up-card lm-up-drop-card">
                <label class="lm-up-dropzone" data-lm-dropzone>
                    <input type="file" name="file" accept=".csv,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="sr-only" data-lm-upload-input>
                    <span class="lm-up-cloud">{!! $icon('upload-cloud', 32) !!}</span>
                    <strong>Drag &amp; drop your file here</strong>
                    <span class="lm-up-browse">or <u>click to browse</u></span>
                    <span class="lm-up-formats">Supported formats: CSV, XLS, XLSX</span>
                    <span class="lm-up-limits">Max file size: 10MB | Maximum rows: 50,000</span>
                    <span class="lm-up-choose">{!! $icon('file', 16) !!}<span data-lm-upload-label>Choose File</span></span>
                </label>

                {{-- Additive: upload still works exactly as before. The Drive button retargets
                     this same form, so the Import Options below travel with it unchanged. --}}
                @if($driveConnected)
                    <div class="lm-up-drive" data-lm-drive-scope>
                        <span class="lm-up-drive-or">or</span>
                        <input type="hidden" name="file_id" value="">
                        <input type="hidden" name="file_name" value="">
                        <button type="button" class="lm-up-drive-btn"
                                data-lm-drive-pick="file"
                                data-lm-drive-mimes="text/csv,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.google-apps.spreadsheet"
                                data-lm-drive-form="lm-up-form"
                                data-lm-drive-action="{{ route('admin.leadmax.upload-leads.from-drive') }}"
                                data-lm-drive-submit="true">
                            Import from Google Drive
                        </button>
                        <p class="lm-up-drive-error" data-lm-drive-error hidden role="alert"></p>
                    </div>
                @endif
            </section>
            @endunless
            <section class="lm-up-card lm-up-options">
                <h2>{{ $isSettings ? 'Default Import Options' : 'Import Options' }}</h2>
                <div class="lm-up-fields">
                    <label class="lm-up-field"><span>Assign Leads To</span><span class="lm-up-select-row"><span class="lm-up-select"><select name="assign" @disabled(! is_admin())><option value="auto" @selected($defaults['assign'] === 'auto')>Auto (Use Lead Splitter Rules)</option><option value="none" @selected($defaults['assign'] === 'none')>Leave unassigned</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($defaults['assign'] === $agent->id)>{{ $agent->name }}</option>@endforeach</select>{!! $icon('select-chevron', 18) !!}{!! $icon('select-chevron-sm', 14) !!}</span>{!! $info(is_admin() ? 'Auto shares new leads between active agents.' : 'Leads you import are assigned to you.') !!}</span></label>
                    <label class="lm-up-field"><span>Default Country</span><span class="lm-up-select-row"><span class="lm-up-select"><select name="country"><option value="auto">Auto-detect from data</option>@foreach($countries as $country)<option @selected($defaults['country'] === $country)>{{ $country }}</option>@endforeach</select>{!! $icon('select-chevron', 18) !!}{!! $icon('select-chevron-sm', 14) !!}</span>{!! $info('Used when a row has no country.') !!}</span></label>
                    <label class="lm-up-field"><span>Default Source</span><span class="lm-up-select-row"><span class="lm-up-select"><select name="source" class="{{ $defaults['source'] ? '' : 'is-placeholder' }}"><option value="">Select source</option>@foreach($sources as $source)<option @selected($defaults['source'] === $source)>{{ $source }}</option>@endforeach</select>{!! $icon('select-chevron', 18) !!}{!! $icon('select-chevron-sm', 14) !!}</span>{!! $info('Used when a row has no source.') !!}</span></label>
                    <label class="lm-up-field"><span>Default Campaign</span><span class="lm-up-select-row"><span class="lm-up-select"><select name="campaign" class="{{ $defaults['campaign'] ? '' : 'is-placeholder' }}"><option value="">Select campaign</option>@foreach($campaigns as $campaign)<option @selected($defaults['campaign'] === $campaign)>{{ $campaign }}</option>@endforeach</select>{!! $icon('select-chevron', 18) !!}{!! $icon('select-chevron-sm', 14) !!}</span>{!! $info('Used when a row has no campaign.') !!}</span></label>
                </div>
                <div class="lm-up-toggles">
                    @foreach(['update_existing' => ['Update existing leads', 'Rows whose email already belongs to a lead update that lead instead of failing.'], 'welcome_email' => ['Send welcome email', 'Emails each new lead the welcome message.'], 'follow_up' => ['Add to follow-up sequence', 'Creates a follow-up call reminder for tomorrow on each new lead.']] as $name => [$label, $hint])
                        <label class="lm-up-toggle"><span>{{ $label }}</span><span class="lm-up-toggle-ctrl"><input type="checkbox" name="{{ $name }}" value="1" @checked($defaults[$name]) class="sr-only"><i aria-hidden="true"></i>{!! $info($hint) !!}</span></label>
                    @endforeach
                </div>
                @if($isSettings)<button type="submit" class="lm-up-save">Save defaults</button>@endif
            </section>
            <section class="lm-up-card lm-up-required">
                <div>
                    <div class="lm-up-required-head"><h2>Required Columns</h2><span title="Your file must include these column headers">{!! $icon('panel-info', 14) !!}</span></div>
                    <ul class="lm-up-required-list">@foreach(['First Name', 'Last Name', 'Email', 'Phone'] as $column)<li>{!! $icon('check-required', 16) !!}<span>{{ $column }}</span><small>Required</small></li>@endforeach</ul>
                    <div class="lm-up-optional"><h3>Additional Columns (Optional)</h3><ul>@foreach(['Country', 'Source', 'Campaign', 'Affiliate', 'Notes'] as $column)<li>{!! $icon('check-optional', 14) !!}<span>{{ $column }}</span></li>@endforeach</ul></div>
                </div>
                <a class="lm-up-download" href="{{ route('admin.leadmax.upload-leads.template') }}">{!! $icon('download-template', 16) !!}<span>Download Template</span></a>
            </section>
        </form>
    @endif

    @if($tab === 'upload' || $tab === 'history')
        <section class="lm-up-table-card">
            <header><h2>{{ $tab === 'history' ? 'Upload History' : 'Recent Uploads' }}</h2><span class="lm-up-count">{{ $uploads->total() }}</span></header>
            <div class="lm-up-table">
                <div class="lm-up-thead"><span>#</span><span>File Name</span><span>Total Rows</span><span>Imported</span><span>Failed</span><span>Success Rate</span><span>Uploaded By</span><span>Date &amp; Time</span><span>Status</span><span>Action</span></div>
                @forelse($uploads as $index => $upload)
                    <div class="lm-up-row">
                        <span class="is-muted">{{ $uploads->firstItem() + $index }}</span>
                        <span class="is-ellipsis" title="{{ $upload->file_name }}">{{ $upload->file_name }}</span>
                        <span class="is-soft">{{ number_format($upload->total_rows) }}</span>
                        <span>{{ number_format($upload->imported + $upload->updated) }}</span>
                        <span class="is-muted">{{ number_format($upload->failed) }}</span>
                        <span>{{ $upload->successRate() !== null ? number_format($upload->successRate(), 1).'%' : '—' }}</span>
                        <span class="is-soft is-ellipsis">{{ $upload->uploader?->name ?? 'System' }}</span>
                        <span class="is-muted">{{ $upload->created_at->format('Y-m-d H:i') }}</span>
                        <span>{!! $statusBadge($upload->status) !!}</span>
                        <span class="lm-up-actions">
                            <a href="{{ request()->fullUrlWithQuery(['import' => $upload->id]) }}" title="View details" aria-label="View details for {{ $upload->file_name }}">{!! $icon('eye', 16) !!}</a>
                            <details class="lm-up-more"><summary title="More actions" aria-label="More actions">{!! $icon('more', 16) !!}</summary>
                                <div>
                                    <a href="{{ request()->fullUrlWithQuery(['import' => $upload->id]) }}">View details</a>
                                    @if($upload->failed > 0 && ! empty($upload->errors))<a href="{{ route('admin.leadmax.upload-leads.errors', $upload->id) }}">Download error report</a>@endif
                                    @if(is_admin() && ! in_array($upload->status, ['queued', 'processing'], true))<form method="POST" action="{{ route('admin.leadmax.upload-leads.destroy', $upload->id) }}">@csrf @method('DELETE')<button type="submit" data-lm-confirm-submit="Delete the record for {{ $upload->file_name }}? Imported leads are kept.">Delete record</button></form>@endif
                                </div>
                            </details>
                        </span>
                    </div>
                @empty
                    <div class="lm-up-empty">No uploads yet. Choose a CSV or Excel file above to import leads.</div>
                @endforelse
            </div>
            @php
                $last = max(1, $uploads->lastPage());
                $first = max(1, min($uploads->currentPage() - 1, $last - 2));
                $pages = range($first, min($last, $first + 2));
            @endphp
            <footer class="lm-up-foot">
                <span>Showing {{ $uploads->firstItem() ?? 0 }} to {{ $uploads->lastItem() ?? 0 }} of {{ number_format($uploads->total()) }} entries</span>
                <div class="lm-up-pager">
                    <form method="GET" action="{{ route('admin.leadmax.upload-leads') }}" class="lm-up-perpage">
                        @if($tab !== 'upload')<input type="hidden" name="tab" value="{{ $tab }}">@endif
                        <select name="per_page" aria-label="Items per page" data-lm-autosubmit>@foreach($perPageOptions as $option)<option value="{{ $option }}" @selected($uploads->perPage() === $option)>{{ $option }} per page</option>@endforeach</select>
                        <img src="{{ asset('images/leadmax/upload/pager-chevron-sm.svg') }}" width="12" height="12" alt="">
                    </form>
                    <nav class="lm-up-pages" aria-label="Pagination">
                        @if($uploads->onFirstPage())<span class="is-arrow is-disabled" aria-hidden="true">&lt;</span>@else<a class="is-arrow" href="{{ $uploads->previousPageUrl() }}" aria-label="Previous page">&lt;</a>@endif
                        @foreach($pages as $page)<a href="{{ $uploads->url($page) }}" class="{{ $page === $uploads->currentPage() ? 'active' : '' }}" @if($page === $uploads->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach
                        @if($uploads->hasMorePages())<a class="is-arrow" href="{{ $uploads->nextPageUrl() }}" aria-label="Next page">&gt;</a>@else<span class="is-arrow is-disabled" aria-hidden="true">&gt;</span>@endif
                    </nav>
                </div>
            </footer>
        </section>
    @endif

    @if($tab === 'mapping')
        <form class="lm-up-card lm-up-mapping" method="POST" action="{{ route('admin.leadmax.upload-leads.settings') }}">@csrf<input type="hidden" name="section" value="mapping">
            <h2>Field Mapping</h2>
            <p>Uploads match column headers automatically. Add your own header name for any field your files label differently.</p>
            <div class="lm-up-map-head"><span>Lead Field</span><span>Recognised Headers</span><span>Custom Header</span></div>
            @foreach(\App\Jobs\ProcessLeadImport::ALIASES as $field => $aliases)
                <label class="lm-up-map-row"><span><b>{{ $fieldLabels[$field] }}</b>@if(in_array($field, \App\Jobs\ProcessLeadImport::REQUIRED, true))<small>Required</small>@endif</span><span class="is-muted">{{ collect($aliases)->map(fn ($a) => ucwords($a))->implode(', ') }}</span><input name="mapping[{{ $field }}]" value="{{ $mapping[$field] ?? '' }}" maxlength="100" placeholder="Your column header" @disabled(! is_admin())></label>
            @endforeach
            @if(is_admin())<button type="submit" class="lm-up-save">Save mapping</button>@endif
        </form>
    @endif
</div>

@if($details)
    @php $closeUrl = route('admin.leadmax.upload-leads', array_filter(['tab' => $tab === 'upload' ? null : $tab])); @endphp
    <div class="lm-modal-backdrop lm-apikey-backdrop" data-lm-dismiss-url="{{ $closeUrl }}">
    <section class="lm-apikey-modal lm-up-details" role="dialog" aria-modal="true" aria-labelledby="lm-up-details-title" tabindex="-1" data-lm-dialog>
        <header class="lm-apikey-head"><div><h2 id="lm-up-details-title">Upload Details</h2>{!! $statusBadge($details->status) !!}</div><a class="lm-apikey-close" href="{{ $closeUrl }}" aria-label="Close"><img src="{{ asset('images/leadmax/affiliates/x-circle.svg') }}" width="14" height="14" alt=""></a></header>
        <div class="lm-apikey-section">
            <div class="lm-apikey-field"><small>File Name</small><strong>{{ $details->file_name }}</strong></div>
            <div class="lm-apikey-dates"><div class="lm-apikey-field"><small>Uploaded</small><strong>{{ $details->created_at->format('M d, Y H:i') }}</strong></div><div class="lm-apikey-field"><small>Uploaded By</small><strong>{{ $details->uploader?->name ?? 'System' }}</strong></div></div>
            @if($details->message)<p class="lm-up-message">{{ $details->message }}</p>@endif
        </div>
        <div class="lm-apikey-usage">
            <h3>Result</h3>
            <div><div><strong>{{ number_format($details->total_rows) }}</strong><small>Total Rows</small></div><div><strong class="is-green">{{ number_format($details->imported) }}{{ $details->updated ? ' + '.number_format($details->updated).' updated' : '' }}</strong><small>Imported</small></div><div><strong>{{ number_format($details->failed) }}</strong><small>Failed</small></div></div>
        </div>
        @if(! empty($details->errors))
            <div class="lm-up-errors"><h3>Rejected Rows</h3><ul>@foreach(array_slice($details->errors, 0, 20) as $error)<li><b>Row {{ $error['row'] }}</b><span>{{ $error['reason'] }}</span></li>@endforeach</ul>@if(count($details->errors) > 20)<p class="is-muted">Showing 20 of {{ count($details->errors) }} errors — download the report for the full list.</p>@endif</div>
        @endif
        <footer class="lm-apikey-foot">
            @if(! empty($details->errors))<a class="lm-apikey-revoke" href="{{ route('admin.leadmax.upload-leads.errors', $details->id) }}">Download error report</a>@endif
            <a class="lm-apikey-close-btn" href="{{ $closeUrl }}">Close</a>
        </footer>
    </section>
    </div>
@endif
</x-leadmax-layout>
