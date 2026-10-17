{{--
    Shared invoice detail.

    Expects: $invoice (Invoice, loaded with customer/items.order/payments), $role
--}}
@php
    $isAdmin = ($role ?? 'user') === 'admin';
@endphp

<div class="row g-3">

    <div class="col-12 col-xl-8">
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0">{{ $invoice->invoice_number }}</h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="oms-badge oms-badge--{{ $invoice->payment_status->badge() }}">
                        {{ $invoice->payment_status->label() }}
                    </span>
                    <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-download me-1"></i> PDF
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <dl class="oms-detail mb-0">
                    @if ($isAdmin)
                        <dt>Customer</dt>
                        <dd>{{ $invoice->customer?->name ?? '—' }}</dd>
                    @endif

                    <dt>Issued</dt>
                    <dd>{{ $invoice->issued_date->format('d-m-Y') }}</dd>

                    <dt>Due</dt>
                    <dd>
                        {{ $invoice->due_date?->format('d-m-Y') ?? '—' }}
                        @if ($invoice->due_date && $invoice->due_date->isPast() && $invoice->isPayable())
                            <span class="oms-badge oms-badge--unpaid">Overdue</span>
                        @endif
                    </dd>

                    <dt>Total</dt>
                    <dd><span class="oms-price">$ {{ number_format($invoice->total, 2) }}</span></dd>

                    <dt>Paid</dt>
                    <dd><span class="oms-price">$ {{ number_format($invoice->paid, 2) }}</span></dd>

                    <dt>Outstanding</dt>
                    <dd>
                        <span class="oms-price {{ $invoice->outstanding > 0 ? '' : 'oms-price--zero' }}">
                            $ {{ number_format($invoice->outstanding, 2) }}
                        </span>
                    </dd>

                    @if ($invoice->paid_at)
                        <dt>Settled</dt>
                        <dd>{{ $invoice->paid_at->format('d-m-Y H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Line items: one per order billed. --}}
        <div class="card mb-3">
            <div class="card-header"><h2 class="h6 mb-0">Items</h2></div>
            <div class="card-body p-3 p-md-4">
                <div class="oms-table-wrap">
                    <table class="oms-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td>
                                        @if ($item->order && Route::has($role.'.orders.show'))
                                            <a href="{{ route($role.'.orders.show', $item->order_id) }}">
                                                {{ $item->order->order_number }}
                                            </a>
                                        @else
                                            {{ $item->order?->order_number ?? '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $item->description }}</td>
                                    <td><span class="oms-price">$ {{ number_format($item->amount, 2) }}</span></td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="2" class="text-end fw-semibold">Total</td>
                                <td><span class="oms-price">$ {{ number_format($invoice->total, 2) }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment history --}}
        <div class="card">
            <div class="card-header"><h2 class="h6 mb-0">Payments</h2></div>
            <div class="card-body p-3 p-md-4">
                @forelse ($invoice->payments as $payment)
                    <div class="oms-review mb-2 d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <strong>$ {{ number_format($payment->amount, 2) }}</strong> — {{ $payment->label() }}
                            <div class="oms-meter__label">
                                {{ $payment->paid_at?->format('d-m-Y H:i') ?? $payment->created_at->format('d-m-Y H:i') }}
                                @if ($payment->reference) · ref {{ $payment->reference }} @endif
                                @if ($isAdmin && $payment->recordedBy) · by {{ $payment->recordedBy->name }} @endif
                            </div>
                        </div>
                        <span class="oms-badge oms-badge--{{ $payment->status === 'succeeded' ? 'paid' : 'pending' }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                @empty
                    <p class="text-muted mb-0">Nothing has been paid against this invoice yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        {{-- Record an offline payment — bank transfer, cash, cheque. --}}
        @can('recordPayment', $invoice)
            <div class="card mb-3">
                <div class="card-header"><h2 class="h6 mb-0">Record a Payment</h2></div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('admin.billing.payments.store', $invoice) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="amount">Amount ($) <span class="oms-req">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror"
                                   id="amount" name="amount"
                                   value="{{ old('amount', number_format($invoice->outstanding, 2, '.', '')) }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Outstanding: $ {{ number_format($invoice->outstanding, 2) }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="reference">Reference</label>
                            <input type="text" class="form-control" id="reference" name="reference"
                                   placeholder="Bank transfer ref, cheque no." value="{{ old('reference') }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Record Payment</button>
                    </form>
                </div>
            </div>
        @endcan

        {{-- Customers settle up outside the system; an admin records it above. --}}
        @if (! $isAdmin && $invoice->isPayable())
            <div class="card">
                <div class="card-header"><h2 class="h6 mb-0">Outstanding</h2></div>
                <div class="card-body p-3 p-md-4">
                    <p class="mb-0">
                        <strong>$ {{ number_format($invoice->outstanding, 2) }}</strong> is still due.
                    </p>
                    <div class="form-text">
                        Payment is arranged offline. Once it reaches us the invoice is marked paid.
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
