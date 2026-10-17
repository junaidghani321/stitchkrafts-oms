{{--
    Shared digitizing order form — used by the customer and staff create pages
    and by the edit pages.

    Expects:
      $action       string  route to post to
      $submitLabel  string  button text
      $method       string  'POST' (create) or 'PUT' (edit), default POST
      $order        ?Order  set on edit, null on create
      $customers    ?Collection  set on staff forms, absent on customer forms
--}}
@php
    $order     = $order     ?? null;
    $method    = $method    ?? 'POST';
    $customers = $customers ?? null;
    $isEdit    = $order !== null;

    /* old() first so a failed submission repopulates, then the model on edit. */
    $val = fn (string $field, $default = null) => old($field, $isEdit ? ($order->{$field} ?? $default) : $default);

    /* The yes/no selects store nullable booleans but post strings. */
    $bool = function (string $field) use ($order, $isEdit) {
        $current = $isEdit ? $order->{$field} : null;
        return old($field, $current === null ? '' : ($current ? 'yes' : 'no'));
    };
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">

        @if ($customers !== null)
            <div class="col-12 col-md-6">
                <label class="form-label" for="customer_id">Customer <span class="oms-req">*</span></label>
                <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                    <option value="" disabled @selected(! $val('customer_id'))>Select</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((int) $val('customer_id') === $customer->id)>
                            {{ $customer->company ?: $customer->name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endif

        <div class="col-12 col-md-6">
            <label class="form-label" for="design_name">Design Name <span class="oms-req">*</span></label>
            <input type="text" class="form-control @error('design_name') is-invalid @enderror" id="design_name"
                   name="design_name" placeholder="Design Name" value="{{ $val('design_name') }}" required>
            @error('design_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="height">Height</label>
            <input type="number" step="0.01" min="0" class="form-control @error('height') is-invalid @enderror"
                   id="height" name="height" placeholder="Height" value="{{ $val('height') }}">
            @error('height')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="width">Width</label>
            <input type="number" step="0.01" min="0" class="form-control @error('width') is-invalid @enderror"
                   id="width" name="width" placeholder="Width" value="{{ $val('width') }}">
            @error('width')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="unit">Unit</label>
            <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit">
                <option value="" disabled @selected(! $val('unit'))>Select</option>
                @foreach (config('orders.units') as $value => $label)
                    <option value="{{ $value }}" @selected($val('unit') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="colors">Number of Colors</label>
            <input type="number" min="1" class="form-control @error('colors') is-invalid @enderror"
                   id="colors" name="colors" placeholder="Number of Colors" value="{{ $val('colors') }}">
            @error('colors')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="fabric">Fabric</label>
            <input type="text" class="form-control @error('fabric') is-invalid @enderror"
                   id="fabric" name="fabric" placeholder="Fabric" value="{{ $val('fabric') }}">
            @error('fabric')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="placement">Placement</label>
            <select class="form-select @error('placement') is-invalid @enderror" id="placement" name="placement">
                <option value="" disabled @selected(! $val('placement'))>Select</option>
                @foreach (config('orders.placements') as $value => $label)
                    <option value="{{ $value }}" @selected($val('placement') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('placement')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="required_format">Required Format</label>
            <select class="form-select @error('required_format') is-invalid @enderror" id="required_format" name="required_format">
                <option value="" disabled @selected(! $val('required_format'))>Select</option>
                @foreach (config('orders.formats.digitizing') as $value => $label)
                    <option value="{{ $value }}" @selected($val('required_format') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('required_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="time_frame">Time Frame <span class="oms-req">*</span></label>
            <select class="form-select @error('time_frame') is-invalid @enderror" id="time_frame" name="time_frame" required>
                <option value="" disabled @selected(! $val('time_frame'))>Select</option>
                @foreach (App\Enums\TimeFrame::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($val('time_frame')?->value ?? $val('time_frame')) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('time_frame')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @foreach (['puff_3d' => '3D Puff', 'applique' => 'Applique', 'trims' => 'Trims', 'patch_cost' => 'Want to know the cost of patch?'] as $field => $label)
            <div class="col-12 col-md-6">
                <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                <select class="form-select @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}">
                    <option value="" disabled @selected($bool($field) === '')>Select</option>
                    @foreach (config('orders.yes_no') as $value => $text)
                        <option value="{{ $value }}" @selected($bool($field) === $value)>{{ $text }}</option>
                    @endforeach
                </select>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach

        {{-- Force a fresh row so the notes and the upload always sit side by
             side, whatever the field count above happens to be. --}}
        <div class="w-100"></div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="instructions">Special Instructions</label>
            <textarea class="form-control @error('instructions') is-invalid @enderror" id="instructions" name="instructions" rows="5"
                      placeholder="Anything the digitizer should know about this design">{{ $val('instructions') }}</textarea>
            @error('instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="artwork">Art Work @unless($isEdit)<span class="oms-req">*</span>@endunless</label>
            <input type="file" class="form-control @error('artwork') is-invalid @enderror @error('artwork.*') is-invalid @enderror"
                   id="artwork" name="artwork[]" multiple @unless($isEdit) required @endunless>
            <div class="form-text">
                @if ($isEdit)
                    Adding files here keeps the existing artwork and appends to it.
                @else
                    You can add multiple files in a single selection.
                @endif
            </div>
            @error('artwork')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @foreach ($errors->get('artwork.*') as $messages)
                @foreach ($messages as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            @endforeach
        </div>

    </div>

    <div class="oms-form-actions">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</form>
