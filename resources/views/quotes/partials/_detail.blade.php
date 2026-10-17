{{--
    Shared quote detail body.

    Expects: $quote, $role, $canOwn (may this actor put a price on it?)
--}}
@php
    $role   = $role   ?? 'user';
    $canOwn = $canOwn ?? false;

    $specs = collect(match ($quote->type) {
        App\Enums\OrderType::Digitizing => [
            'Size'             => $quote->height && $quote->width ? $quote->height.' x '.$quote->width.' '.($quote->unit ?? '') : null,
            'Number of Colors' => $quote->colors,
            'Fabric'           => $quote->fabric,
            'Placement'        => config('orders.placements')[$quote->placement] ?? null,
            'Required Format'  => config('orders.formats.digitizing')[$quote->required_format] ?? null,
            'Time Frame'       => $quote->time_frame?->label(),
            '3D Puff'          => $quote->puff_3d === null ? null : ($quote->puff_3d ? 'Yes' : 'No'),
            'Applique'         => $quote->applique === null ? null : ($quote->applique ? 'Yes' : 'No'),
            'Trims'            => $quote->trims === null ? null : ($quote->trims ? 'Yes' : 'No'),
        ],
        App\Enums\OrderType::Vector => [
            'Required Format'  => config('orders.formats.vector')[$quote->required_format] ?? null,
            'Color Type'       => config('orders.color_types')[$quote->color_type] ?? null,
            'Printing Process' => config('orders.printing_processes')[$quote->printing_process] ?? null,
            'Time Frame'       => $quote->time_frame?->label(),
        ],
        App\Enums\OrderType::Patch => [
            'Size'               => $quote->height && $quote->width ? $quote->height.' x '.$quote->width.' inches' : null,
            'Quantity'           => $quote->quantity,
            'Colors'             => $quote->colors,
            'Patches Type'       => config('orders.patch_types')[$quote->patch_type] ?? null,
            'Patches Background' => config('orders.patch_backgrounds')[$quote->patch_background] ?? null,
            'Backing Type'       => config('orders.backing_types')[$quote->backing_type] ?? null,
            'Patches Border'     => config('orders.patch_borders')[$quote->patch_border] ?? null,
            'Expected Delivery'  => $quote->expected_delivery?->label(),
        ],
    })->filter(fn ($value) => $value !== null && $value !== '');
@endphp

<div class="row g-3">

    <div class="col-12 col-xl-8">
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0">{{ $quote->quote_number }} — {{ $quote->design_name }}</h2>
                <span class="oms-badge oms-badge--{{ $quote->status->badge() }}">{{ $quote->status->label() }}</span>
            </div>
            <div class="card-body p-3 p-md-4">
                <dl class="oms-detail mb-0">
                    <dt>Type</dt>
                    <dd>{{ $quote->type->label() }}</dd>

                    @if ($role !== 'user')
                        <dt>Customer</dt>
                        <dd>{{ $quote->customer?->name ?? '—' }}</dd>
                    @endif

                    <dt>Requested</dt>
                    <dd>{{ $quote->created_at->format('d-m-Y H:i') }}</dd>

                    <dt>Quoted Price</dt>
                    <dd>
                        <span class="oms-price {{ $quote->isPriced() ? '' : 'oms-price--zero' }}">
                            $ {{ number_format($quote->display_price, 2) }}
                        </span>
                        @unless ($quote->isPriced())
                            <small class="text-muted">awaiting pricing</small>
                        @endunless
                    </dd>

                    @if ($quote->valid_until)
                        <dt>Valid Until</dt>
                        <dd>
                            {{ $quote->valid_until->format('d-m-Y') }}
                            @if ($quote->hasExpired())
                                <span class="oms-badge oms-badge--unpaid">Expired</span>
                            @endif
                        </dd>
                    @endif

                    @if ($quote->priced_at && $role !== 'user')
                        <dt>Priced By</dt>
                        <dd>{{ $quote->pricedBy?->name ?? '—' }} on {{ $quote->priced_at->format('d-m-Y H:i') }}</dd>
                    @endif

                    @if ($quote->instructions)
                        <dt>Instructions</dt>
                        <dd>{{ $quote->instructions }}</dd>
                    @endif

                    @if ($quote->rejection_reason)
                        <dt>Decline Reason</dt>
                        <dd>{{ $quote->rejection_reason }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Once accepted, this is where the work actually lives. --}}
        @if ($quote->convertedOrder)
            <div class="card mb-3">
                <div class="card-header"><h2 class="h6 mb-0">Converted to an Order</h2></div>
                <div class="card-body p-3 p-md-4">
                    <p class="mb-0">
                        This quote was accepted and became
                        @if (Route::has($role.'.orders.show'))
                            <a href="{{ route($role.'.orders.show', $quote->converted_order_id) }}">
                                <strong>{{ $quote->convertedOrder->order_number }}</strong>
                            </a>
                        @else
                            <strong>{{ $quote->convertedOrder->order_number }}</strong>
                        @endif
                        on {{ $quote->approved_at?->format('d-m-Y H:i') }}.
                    </p>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h2 class="h6 mb-0">Specification</h2></div>
            <div class="card-body p-3 p-md-4">
                @if ($specs->isEmpty())
                    <p class="text-muted mb-0">No specification was supplied.</p>
                @else
                    <dl class="oms-detail mb-0">
                        @foreach ($specs as $label => $value)
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">

        @include('orders.partials._files', [
            'title' => 'Artwork',
            'files' => $quote->artwork,
            'empty' => 'No artwork was attached.',
        ])

        {{-- Pricing panel — admin, manager and employees. --}}
        @if ($canOwn)
            <div class="card mb-3">
                <div class="card-header"><h2 class="h6 mb-0">Price This Quote</h2></div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route($role.'.quotes.price', $quote) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label" for="price">Quoted Price ($) <span class="oms-req">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror"
                                   id="price" name="price" value="{{ old('price', $quote->price) }}" placeholder="0.00" required>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="valid_until">Valid Until</label>
                            <input type="date" class="form-control @error('valid_until') is-invalid @enderror"
                                   id="valid_until" name="valid_until" value="{{ old('valid_until', $quote->valid_until?->format('Y-m-d')) }}">
                            @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Leave blank for no expiry.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Send Price to Customer</button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Accept / decline. The policy allows this only while status = quoted. --}}
        @can('decide', $quote)
            <div class="card">
                <div class="card-header"><h2 class="h6 mb-0">Decision</h2></div>
                <div class="card-body p-3 p-md-4 d-grid gap-2">
                    <form action="{{ route($role.'.quotes.approve', $quote) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-check"></i> Accept &amp; Place Order
                        </button>
                    </form>

                    <form action="{{ route($role.'.quotes.reject', $quote) }}" method="POST">
                        @csrf
                        <textarea class="form-control mb-2" name="rejection_reason" rows="2"
                                  placeholder="Reason (optional)"></textarea>
                        <button type="submit" class="btn btn-danger w-100">Decline</button>
                    </form>

                    <p class="text-muted small mb-0">
                        Accepting creates a real order at the quoted price and carries the artwork over.
                    </p>
                </div>
            </div>
        @endcan

    </div>
</div>
