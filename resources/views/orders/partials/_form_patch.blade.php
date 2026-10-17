{{--
    Shared patch order form.

    Patch is the strict one — nearly every field is required. Note there is no
    `unit` select (inches are baked into the labels) and the turnaround field
    is `expected_delivery`, measured in days rather than hours.
--}}
@php
    $order     = $order     ?? null;
    $method    = $method    ?? 'POST';
    $customers = $customers ?? null;
    $isEdit    = $order !== null;

    $val = fn (string $field, $default = null) => old($field, $isEdit ? ($order->{$field} ?? $default) : $default);

    $selects = [
        'patch_type'       => ['label' => 'Patches Type',       'options' => config('orders.patch_types')],
        'patch_background' => ['label' => 'Patches Background', 'options' => config('orders.patch_backgrounds')],
        'backing_type'     => ['label' => 'Backing Type',       'options' => config('orders.backing_types')],
        'patch_border'     => ['label' => 'Patches Border',     'options' => config('orders.patch_borders')],
    ];
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
            <label class="form-label" for="height">Height (inches) <span class="oms-req">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control @error('height') is-invalid @enderror"
                   id="height" name="height" placeholder="Height" value="{{ $val('height') }}" required>
            @error('height')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="width">Width (inches) <span class="oms-req">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control @error('width') is-invalid @enderror"
                   id="width" name="width" placeholder="Width" value="{{ $val('width') }}" required>
            @error('width')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="quantity">Quantity <span class="oms-req">*</span></label>
            <input type="number" min="1" class="form-control @error('quantity') is-invalid @enderror"
                   id="quantity" name="quantity" placeholder="Quantity" value="{{ $val('quantity') }}" required>
            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="colors">Colors <span class="oms-req">*</span></label>
            <input type="number" min="1" class="form-control @error('colors') is-invalid @enderror"
                   id="colors" name="colors" placeholder="Colors" value="{{ $val('colors') }}" required>
            @error('colors')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        @foreach ($selects as $field => $meta)
            <div class="col-12 col-md-6">
                <label class="form-label" for="{{ $field }}">{{ $meta['label'] }} <span class="oms-req">*</span></label>
                <select class="form-select @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" required>
                    <option value="" disabled @selected(! $val($field))>Select</option>
                    @foreach ($meta['options'] as $value => $label)
                        <option value="{{ $value }}" @selected($val($field) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach

        <div class="col-12 col-md-6">
            <label class="form-label" for="expected_delivery">Expected Delivery <span class="oms-req">*</span></label>
            <select class="form-select @error('expected_delivery') is-invalid @enderror" id="expected_delivery" name="expected_delivery" required>
                @foreach (App\Enums\ExpectedDelivery::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($val('expected_delivery')?->value ?? $val('expected_delivery') ?? 'normal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('expected_delivery')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- Force a fresh row so the notes and the upload always sit side by
             side, whatever the field count above happens to be. --}}
        <div class="w-100"></div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="instructions">Special Instructions</label>
            <textarea class="form-control @error('instructions') is-invalid @enderror" id="instructions" name="instructions" rows="5"
                      placeholder="Anything the production team should know about this patch">{{ $val('instructions') }}</textarea>
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
