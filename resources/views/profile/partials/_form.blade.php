{{--
    Shared Settings → Profile body.

    Two independent forms — details and password — so a failed password change
    never discards edits to the name field, and vice versa.

    Expects: $profile (User), $role
--}}
@php
    $isCustomer = $role === 'user';
@endphp

<div class="row justify-content-center g-3">
    <div class="col-12 col-lg-10 col-xl-9">

        {{-- Details --}}
        <div class="card mb-3">
            <div class="card-body p-3 p-md-4">

                <h2 class="oms-form-title">My Profile</h2>

                <p class="text-center text-muted mb-4">
                    Your own details. Your role and account status are set by an
                    administrator and are shown here for reference only.
                </p>

                <form action="{{ route($role.'.settings.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="name">Full Name <span class="oms-req">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $profile->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email">Email <span class="oms-req">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email', $profile->email) }}"
                                   data-current-email="{{ $profile->email }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">This is also the address you sign in with.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone', $profile->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($isCustomer)
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="company">Company</label>
                                <input type="text" class="form-control @error('company') is-invalid @enderror"
                                       id="company" name="company" value="{{ old('company', $profile->company) }}">
                                @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        {{--
                            Only required when the address actually changes — the server
                            decides that, this just reveals the field so the requirement
                            is not a surprise on submit. Hidden by default; shown by the
                            script below, or immediately if validation already failed.

                            It keeps a row to itself: the field appears and disappears as
                            the email is edited, and a half-width box that comes and goes
                            would reshuffle every field after it.
                        --}}
                        <div class="col-12 col-md-6" data-confirm-password
                             @unless ($errors->has('current_password')) hidden @endunless>
                            <label class="form-label" for="current_password">
                                Current Password <span class="oms-req">*</span>
                            </label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password" name="current_password" autocomplete="current-password">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                Confirm your password to change the address you sign in with.
                            </div>
                        </div>

                    </div>

                    {{-- Read-only context. Not inputs: changing these is an admin act. --}}
                    <div class="oms-review mb-3">
                        <dl class="oms-detail mb-0">
                            <dt>Role</dt>
                            <dd class="text-capitalize">{{ $profile->type_enum->label() }}</dd>

                            <dt>Account Status</dt>
                            <dd>
                                <span class="oms-badge oms-badge--{{ $profile->status === 'active' ? 'done' : 'unpaid' }}">
                                    {{ ucfirst($profile->status) }}
                                </span>
                            </dd>

                            @if ($profile->designation)
                                <dt>Designation</dt>
                                <dd>{{ $profile->designation }}</dd>
                            @endif

                            @if ($profile->department)
                                <dt>Department</dt>
                                <dd>{{ $profile->department }}</dd>
                            @endif

                            <dt>Member Since</dt>
                            <dd>{{ $profile->created_at->format('d-m-Y') }}</dd>
                        </dl>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                </form>

            </div>
        </div>

        {{-- Password --}}
        <div class="card">
            <div class="card-header"><h2 class="h6 mb-0">Change Password</h2></div>
            <div class="card-body p-3 p-md-4">

                <form action="{{ route($role.'.settings.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="current_password">Current Password <span class="oms-req">*</span></label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                   id="current_password" name="current_password" autocomplete="current-password" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- The two new-password boxes belong together on their own row. --}}
                        <div class="w-100"></div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password">New Password <span class="oms-req">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" autocomplete="new-password" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">At least 8 characters.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm New Password <span class="oms-req">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" autocomplete="new-password" required>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary w-100">Change Password</button>
                </form>

            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    (function () {
        var email = document.getElementById('email');
        var block = document.querySelector('[data-confirm-password]');
        if (!email || !block) return;

        var current = (email.getAttribute('data-current-email') || '').trim().toLowerCase();

        function sync() {
            var changed = email.value.trim().toLowerCase() !== current;
            block.hidden = !changed;
            // Never leave a stale password sitting in a hidden field.
            if (!changed) block.querySelector('input').value = '';
        }

        email.addEventListener('input', sync);
        sync();
    })();
</script>
@endpush
