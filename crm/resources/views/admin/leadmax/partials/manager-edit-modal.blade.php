{{-- One edit dialog, shared by the Managers list and a manager's own page.

     The list has many rows and only ever edits one at a time, so there is a single dialog
     here rather than one per row. Whatever opened it fills it in: see the
     [data-lm-edit-manager] handler in leadmax.js, which reads the fields off the button and
     points the form at that manager. --}}
<div id="lm-edit-manager-modal" class="lm-modal-backdrop" style="display:none" onclick="if(event.target===this)this.style.display='none'">
    <div class="lm-modal-card">
        <div class="lm-modal-header">
            <div>
                <span class="lm-modal-title">Edit Manager</span>
                <span class="lm-modal-subtitle">Change their details, or set a new password.</span>
            </div>
            <button type="button" class="lm-modal-close" onclick="document.getElementById('lm-edit-manager-modal').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="" class="lm-modal-form" data-lm-edit-manager-form>
            @csrf
            @method('PUT')
            <div class="lm-modal-section">
                <div class="lm-modal-section-title">Personal Information</div>
                <div class="lm-modal-row">
                    <div class="lm-modal-field"><label>First Name</label><input type="text" name="first_name" required maxlength="100"></div>
                    <div class="lm-modal-field"><label>Last Name</label><input type="text" name="last_name" required maxlength="100"></div>
                </div>
                <div class="lm-modal-row">
                    <div class="lm-modal-field"><label>Email Address</label><input type="email" name="email" required maxlength="200"></div>
                    <div class="lm-modal-field"><label>Phone Number</label><input type="text" name="phone" maxlength="50"></div>
                </div>
            </div>

            <div class="lm-modal-section">
                <div class="lm-modal-section-title">Access</div>
                <div class="lm-modal-row">
                    <div class="lm-modal-field">
                        <label>Department / Team</label>
                        <select name="team">
                            @foreach(['Management','CRM','Finance','Support','Reports','Marketing'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lm-modal-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                {{-- Blank leaves the current password alone, so an email can be corrected
                     without silently resetting someone's sign-in. --}}
                <div class="lm-modal-row">
                    <div class="lm-modal-field">
                        <label>New Password <small>(leave blank to keep the current one)</small></label>
                        <input type="password" name="password" minlength="6" autocomplete="new-password" placeholder="Unchanged">
                    </div>
                    <div class="lm-modal-field">
                        <label>Confirm New Password</label>
                        <input type="password" name="password_confirmation" minlength="6" autocomplete="new-password" placeholder="Unchanged">
                    </div>
                </div>
            </div>

            <div class="lm-modal-actions lm-modal-actions-footer">
                <button type="button" class="lm-attr-btn" onclick="document.getElementById('lm-edit-manager-modal').style.display='none'">Cancel</button>
                <button type="submit" class="lm-attr-btn lm-attr-btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>
