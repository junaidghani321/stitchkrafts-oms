{{--
    Shared vector order form.

    Vector carries none of the digitizing/patch spec fields — no height, width,
    unit, colors, fabric, placement or yes/no options — and its required_format
    list is disjoint from the digitizing one.
--}}
@php
    $order     = $order     ?? null;
    $method    = $method    ?? 'POST';
    $customers = $customers ?? null;
    $isEdit    = $order !== null;

    $val = fn (string $field, $default = null) => old($field, $isEdit ? ($order->{$field} ?? $default) : $default);
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
            <label class="form-label" for="required_format">Required Format</label>
            <select class="form-select @error('required_format') is-invalid @enderror" id="required_format" name="required_format">
                <option value="" disabled @selected(! $val('required_format'))>Select</option>
                @foreach (config('orders.formats.vector') as $value => $label)
                    <option value="{{ $value }}" @selected($val('required_format') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('required_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="color_type">Color Type</label>
            <select class="form-select @error('color_type') is-invalid @enderror" id="color_type" name="color_type">
                <option value="" disabled @selected(! $val('color_type'))>Select</option>
                @foreach (config('orders.color_types') as $value => $label)
                    <option value="{{ $value }}" @selected($val('color_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('color_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="printing_process">Printing Process</label>
            <select class="form-select @error('printing_process') is-invalid @enderror" id="printing_process" name="printing_process">
                <option value="" disabled @selected(! $val('printing_process'))>Select</option>
                @foreach (config('orders.printing_processes') as $value => $label)
                    <option value="{{ $value }}" @selected($val('printing_process') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('printing_process')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="time_frame">Time Frame <span class="oms-req">*</span></label>
            <select class="form-select @error('time_frame') is-invalid @enderror" id="time_frame" name="time_frame" required>
                @foreach (App\Enums\TimeFrame::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($val('time_frame')?->value ?? $val('time_frame') ?? 'normal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('time_frame')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Force a fresh row so the notes and the upload always sit side by
             side, whatever the field count above happens to be. --}}
        <div class="w-100"></div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="instructions">Special Instructions</label>
            <textarea class="form-control @error('instructions') is-invalid @enderror" id="instructions" name="instructions" rows="5"
                      placeholder="Put size, number of colors or anything else worth telling us about this order">{{ $val('instructions') }}</textarea>
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
