{{--
    Shared create/edit form for a user account.

    Expects:
      $action       string
      $submitLabel  string
      $method       'POST' (create) or 'PUT' (edit)
      $user         ?array  set on edit
--}}
@php
    $user   = $user   ?? null;
    $method = $method ?? 'POST';
    $isEdit = $user !== null;

    $val = fn (string $field, $default = null) => old($field, $isEdit ? ($user[$field] ?? $default) : $default);
@endphp

<form action="{{ $action }}" method="POST" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">

        <div class="col-12 col-md-6">
            <label class="form-label" for="name">Full Name <span class="oms-req">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                   placeholder="Full Name" value="{{ $val('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="email">Email <span class="oms-req">*</span></label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                   placeholder="name@example.com" value="{{ $val('email') }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="type">Role <span class="oms-req">*</span></label>
            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                @unless ($isEdit)
                    <option value="" disabled @selected(! $val('type'))>Select</option>
                @endunless
                @foreach (App\Enums\UserType::options() as $value => $label)
                    <option value="{{ $value }}" @selected($val('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="status">Status <span class="oms-req">*</span></label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" @selected($val('status', 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="phone">Phone</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                   placeholder="Phone" value="{{ $val('phone') }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="company">Company</label>
            <input type="text" class="form-control @error('company') is-invalid @enderror" id="company" name="company"
                   placeholder="Company" value="{{ $val('company') }}">
            @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Staff profile. These are what the Employees screens display; without
             them a newly created employee shows dashes on their profile. --}}
        <div class="col-12">
            <hr class="my-1">
            <small class="text-muted">Staff details — shown on the Employees screens</small>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="designation">Designation</label>
            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation"
                   name="designation" placeholder="Senior Digitizer" value="{{ $val('designation') }}">
            @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="department">Department</label>
            <input type="text" class="form-control @error('department') is-invalid @enderror" id="department"
                   name="department" placeholder="Digitizing" value="{{ $val('department') }}">
            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="shift">Shift</label>
            <input type="text" class="form-control @error('shift') is-invalid @enderror" id="shift"
                   name="shift" placeholder="Morning (09:00 - 18:00)" value="{{ $val('shift') }}">
            @error('shift')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12"><hr class="my-1"></div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="password">
                Password @unless($isEdit)<span class="oms-req">*</span>@endunless
            </label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                   name="password" autocomplete="new-password" @unless($isEdit) required @endunless>
            <div class="form-text">
                {{ $isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters.' }}
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="password_confirmation">
                Confirm Password @unless($isEdit)<span class="oms-req">*</span>@endunless
            </label>
            <input type="password" class="form-control" id="password_confirmation"
                   name="password_confirmation" autocomplete="new-password" @unless($isEdit) required @endunless>
        </div>

        <div class="col-12">
            <label class="form-label" for="notes">Internal Notes</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4"
                      placeholder="Only visible to the admin team">{{ $val('notes') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

    </div>

    <div class="oms-form-actions">
        <a href="{{ route('admin.users.index') }}" class="btn btn-brand">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</form>
