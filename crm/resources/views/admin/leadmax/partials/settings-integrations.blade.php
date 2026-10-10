{{-- Settings > Integrations. Google Drive is the only one so far. --}}
@php
    $icon = fn (string $name, $w, $h = null) => '<img src="'.asset('images/leadmax/settings/'.$name.'.svg').'" width="'.$w.'" height="'.($h ?? $w).'" alt="">';
    // Connecting hands the CRM standing access to a real person's Google account, so this is
    // admin / tenant_admin / superadmin only — the same bar the controller enforces.
    $canManage = is_admin();
    $connected = $drive && $drive->isUsable();
    // A row exists and previously worked, but Google has since refused us.
    $needsReconnect = $drive && ! $connected && filled($drive->last_error);
    $ownClient = $drive && $drive->usesOwnOAuthClient();
@endphp
<div class="lm-set-grid">
    <div class="lm-set-main">
        <section class="lm-set-card" data-lm-drive-scope>
            <header class="lm-set-card-head">
                <div class="lm-set-integration">
                    {{-- Google's own mark, so the row is recognisable at a glance. Decorative:
                         the heading beside it already names the service for screen readers. --}}
                    <img src="{{ asset('images/leadmax/settings/google-drive.svg') }}" width="28" height="25" alt="" class="lm-set-integration-logo">
                    <div>
                        <h2>Google Drive</h2>
                        <p>Import lead files straight from Drive, save exports back to it, and pick your logo from it.</p>
                    </div>
                </div>
                @if($connected && $canManage)
                    <button type="submit" form="lm-drive-disconnect" class="lm-set-btn"
                            data-lm-confirm-submit="Disconnect Google Drive? Imports and exports will go back to file upload and download only.">
                        {!! $icon('remove', 9, 9.75) !!}<span>Disconnect</span>
                    </button>
                @endif
            </header>

            @if($connected)
                <dl class="lm-set-kv">
                    <div>
                        <dt>Connected account</dt>
                        <dd>
                            <strong>{{ $drive->google_email ?: 'Google account' }}</strong>
                            @if($drive->google_name)<span class="lm-set-muted">{{ $drive->google_name }}</span>@endif
                        </dd>
                    </div>
                    <div>
                        <dt>Connected by</dt>
                        <dd>
                            {{ $drive->connectedBy?->name ?? 'Unknown user' }}
                            @if($drive->connected_at)<span class="lm-set-muted">{{ $drive->connected_at->format('j M Y') }}</span>@endif
                        </dd>
                    </div>
                    <div>
                        <dt>Exports are saved to</dt>
                        <dd>
                            <strong data-lm-drive-folder-name>{{ $drive->export_folder_name ?: 'Not chosen yet' }}</strong>
                            @if($canManage)
                                <button type="button" class="lm-set-btn is-inline"
                                        data-lm-drive-pick="folder"
                                        data-lm-drive-form="lm-drive-folder-form"
                                        data-lm-drive-label="[data-lm-drive-folder-name]"
                                        data-lm-drive-submit="true">
                                    <span>{{ $drive->export_folder_id ? 'Change folder' : 'Choose folder' }}</span>
                                </button>
                            @endif
                        </dd>
                    </div>
                </dl>

                {{-- Say plainly what the CRM can and cannot see. This is the narrow drive.file
                     scope, and the reassurance is worth stating rather than leaving implied. --}}
                {{-- Picker failures land here rather than in an alert(). --}}
                <p class="lm-set-note lm-set-note-warning" data-lm-drive-error hidden role="alert"></p>

                <p class="lm-set-note">
                    CRM-Max can only open files you choose from the picker, and files it creates itself.
                    It cannot browse or read the rest of this Drive.
                </p>

                @if($canManage)
                    <form method="POST" action="{{ route('admin.leadmax.settings.google-drive.disconnect') }}" id="lm-drive-disconnect" hidden>
                        @csrf @method('DELETE')
                    </form>
                    <form method="POST" action="{{ route('admin.leadmax.settings.google-drive.export-folder') }}" id="lm-drive-folder-form" hidden>
                        @csrf
                        <input type="hidden" name="folder_id" value="">
                        <input type="hidden" name="folder_name" value="">
                    </form>
                @endif

            @else
                @if($needsReconnect)
                    <p class="lm-set-note lm-set-note-warning">
                        Google access for <strong>{{ $drive->google_email ?: 'the linked account' }}</strong> is no longer working.
                        @if($drive->last_error) {{ $drive->last_error }} @endif
                        Reconnect to resume importing and exporting through Drive.
                    </p>
                @elseif(! $driveConfigured)
                    <p class="lm-set-note lm-set-note-warning">
                        No Google credentials are set up yet. Either your system administrator adds them for the
                        whole platform, or you add this company&rsquo;s own below.
                    </p>
                @else
                    <p class="lm-set-note">
                        Connect a Google account to turn on Drive options across the CRM. Everything keeps working
                        exactly as it does now if you do not &mdash; file upload and download are unaffected.
                    </p>
                    <ul class="lm-set-bullets">
                        <li>Import leads from a CSV or Google Sheet held in Drive</li>
                        <li>Save lead, finance and report exports straight to a folder you choose</li>
                        <li>Pick your company logo from Drive</li>
                    </ul>
                @endif

                @if($canManage && $driveConfigured)
                    <a href="{{ route('admin.leadmax.settings.google-drive.connect') }}" class="lm-set-save is-link">
                        <span>{{ $needsReconnect ? 'Reconnect Google Drive' : 'Connect Google Drive' }}</span>
                    </a>
                    <p class="lm-set-note lm-set-muted">
                        Whichever administrator connects becomes the account the whole company uses, so a shared
                        company Google account works better here than a personal one.
                    </p>
                @elseif(! $canManage)
                    <p class="lm-set-note lm-set-muted">Only an administrator can connect Google Drive.</p>
                @endif
            @endif
        </section>

        {{-- Optional. Most tenants never open this: they ride on the platform's Google client
             and simply press Connect above. A tenant fills this in when it wants its own
             consent-screen branding, its own API quota, or to carry its own Google
             verification instead of the platform's. --}}
        @if($canManage)
            <details class="lm-set-card lm-set-advanced" @if($ownClient || ! $drivePlatformConfigured) open @endif>
                <summary>
                    <h3>Use this company&rsquo;s own Google Cloud project</h3>
                    <p>
                        @if($ownClient)
                            This company authenticates with its own Google credentials.
                        @elseif($drivePlatformConfigured)
                            Optional &mdash; leave blank to use the platform&rsquo;s shared Google app.
                        @else
                            Required, because the platform has no Google credentials configured.
                        @endif
                    </p>
                </summary>

                <p class="lm-set-note">
                    Create a project at <span class="lm-set-mono">console.cloud.google.com</span>, enable the
                    <strong>Google Drive API</strong> and the <strong>Google Picker API</strong>, then create an
                    OAuth client of type <strong>Web application</strong>. Creating the project and using the API
                    are free.
                </p>

                @if($driveRedirectUri)
                    <p class="lm-set-note">
                        Register this exact redirect URI on that client, or Google will refuse the sign-in:
                        <span class="lm-set-mono lm-set-copy">{{ $driveRedirectUri }}</span>
                    </p>
                @endif

                <form method="POST" action="{{ route('admin.leadmax.settings.google-drive.credentials') }}" class="lm-set-stack">
                    @csrf
                    <div class="lm-set-fields">
                        <label class="lm-set-field">
                            <span>Client ID</span>
                            <input type="text" name="client_id" value="{{ old('client_id', $drive->client_id ?? '') }}"
                                   maxlength="255" autocomplete="off" placeholder="000000000000-xxxxxxxx.apps.googleusercontent.com">
                        </label>
                        <label class="lm-set-field">
                            <span>Client secret</span>
                            {{-- Never echoed back. Left blank on save, the stored secret is kept. --}}
                            <input type="password" name="client_secret" value="" maxlength="255" autocomplete="new-password"
                                   placeholder="{{ $ownClient ? 'Saved — leave blank to keep it' : '' }}">
                        </label>
                        <label class="lm-set-field">
                            <span>Picker API key <em>(optional)</em></span>
                            <input type="text" name="picker_api_key" value="{{ old('picker_api_key', $drive->picker_api_key ?? '') }}"
                                   maxlength="255" autocomplete="off">
                        </label>
                        <label class="lm-set-field">
                            <span>Project number <em>(optional)</em></span>
                            <input type="text" name="project_number" value="{{ old('project_number', $drive->project_number ?? '') }}"
                                   maxlength="64" autocomplete="off" placeholder="000000000000">
                        </label>
                    </div>
                    <button type="submit" class="lm-set-save">{!! $icon('save', 10.5) !!}<span>Save credentials</span></button>
                    <p class="lm-set-note lm-set-muted">
                        Changing the Client ID clears the current connection, because tokens issued by one Google
                        client cannot be refreshed by another. You will be asked to connect again.
                    </p>
                </form>
            </details>
        @endif
    </div>
</div>
