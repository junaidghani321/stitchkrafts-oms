{{--
    Invoice as a PDF.

    Standalone, not an @extends of layouts.app: dompdf runs no JavaScript and
    supports only a subset of CSS, so the app's Bootstrap layout would render
    as a mess. Everything here is inline, table-based and deliberately plain.

    Expects: $invoice, loaded with customer/items.order/payments.recordedBy
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 26mm 16mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #23272b;
            line-height: 1.5;
        }

        h1 { font-size: 20px; margin: 0 0 2px; }

        .muted { color: #6c757d; }
        .right { text-align: right; }
        .bold  { font-weight: bold; }

        table { width: 100%; border-collapse: collapse; }

        .head td { vertical-align: top; padding: 0; }

        .meta { margin-top: 18px; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta .label { color: #6c757d; width: 90px; }

        .items { margin-top: 22px; }
        .items th {
            text-align: left;
            border-bottom: 1.5px solid #23272b;
            padding: 7px 6px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .items td { padding: 7px 6px; border-bottom: 1px solid #e6e8ea; }
        .items tfoot td { border-bottom: none; padding-top: 9px; }
        .items tfoot .total td { border-top: 1.5px solid #23272b; font-size: 13px; }

        .status {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        .status--paid      { background: #d6f0e0; color: #146c43; }
        .status--unpaid    { background: #fbdcdd; color: #b02a2f; }
        .status--partial   { background: #fdeccd; color: #8a5a00; }
        .status--cancelled { background: #e4e6e8; color: #4d5154; }

        .payments { margin-top: 24px; }
        .payments th {
            text-align: left;
            border-bottom: 1px solid #c9cdd1;
            padding: 6px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .payments td { padding: 6px; border-bottom: 1px solid #eef0f1; }

        .foot {
            margin-top: 34px;
            padding-top: 10px;
            border-top: 1px solid #e6e8ea;
            font-size: 10px;
            color: #6c757d;
        }
    </style>
</head>
<body>

@php
    /* PaymentStatus::badge() speaks the app's CSS vocabulary; map it onto the
       four classes defined above so the two cannot drift apart. */
    $statusClass = match ($invoice->payment_status->value) {
        'paid'     => 'paid',
        'partial'  => 'partial',
        'refunded' => 'cancelled',
        default    => 'unpaid',
    };
@endphp

<table class="head">
    <tr>
        <td>
            <h1>{{ config('app.name', 'OMS') }}</h1>
            <div class="muted">Order Management</div>
        </td>
        <td class="right">
            <div class="bold" style="font-size: 15px;">INVOICE</div>
            <div class="muted">{{ $invoice->invoice_number }}</div>
            <div style="margin-top: 6px;">
                <span class="status status--{{ $statusClass }}">
                    {{ strtoupper($invoice->payment_status->label()) }}
                </span>
            </div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td style="width: 55%;">
            <table>
                <tr>
                    <td class="label">Billed To</td>
                    <td class="bold">{{ $invoice->customer?->name ?? '—' }}</td>
                </tr>
                @if ($invoice->customer?->company)
                    <tr><td class="label"></td><td>{{ $invoice->customer->company }}</td></tr>
                @endif
                @if ($invoice->customer?->email)
                    <tr><td class="label"></td><td>{{ $invoice->customer->email }}</td></tr>
                @endif
                @if ($invoice->customer?->phone)
                    <tr><td class="label"></td><td>{{ $invoice->customer->phone }}</td></tr>
                @endif
            </table>
        </td>
        <td>
            <table>
                <tr>
                    <td class="label">Issued</td>
                    <td>{{ $invoice->issued_date->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Due</td>
                    <td>{{ $invoice->due_date?->format('d-m-Y') ?? '—' }}</td>
                </tr>
                @if ($invoice->paid_at)
                    <tr>
                        <td class="label">Settled</td>
                        <td>{{ $invoice->paid_at->format('d-m-Y') }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Currency</td>
                    <td>{{ strtoupper($invoice->currency) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width: 22%;">Order #</th>
            <th>Description</th>
            <th class="right" style="width: 20%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($invoice->items as $item)
            <tr>
                <td>{{ $item->order?->order_number ?? '—' }}</td>
                <td>{{ $item->description }}</td>
                <td class="right">$ {{ number_format($item->amount, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">No line items on this invoice.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="right muted">Subtotal</td>
            <td class="right">$ {{ number_format($invoice->total, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="right muted">Paid</td>
            <td class="right">$ {{ number_format($invoice->paid, 2) }}</td>
        </tr>
        <tr class="total">
            <td colspan="2" class="right bold">Amount Due</td>
            <td class="right bold">$ {{ number_format($invoice->outstanding, 2) }}</td>
        </tr>
    </tfoot>
</table>

@if ($invoice->payments->isNotEmpty())
    <table class="payments">
        <thead>
            <tr>
                <th style="width: 22%;">Date</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="right" style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->payments as $payment)
                <tr>
                    <td>{{ ($payment->paid_at ?? $payment->created_at)->format('d-m-Y') }}</td>
                    <td>{{ $payment->label() }}</td>
                    <td>{{ $payment->reference ?: '—' }}</td>
                    <td class="right">$ {{ number_format($payment->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="foot">
    @if ($invoice->notes)
        <div style="margin-bottom: 6px;">{{ $invoice->notes }}</div>
    @endif
    Payment is arranged offline — please use {{ $invoice->invoice_number }} as the reference.
</div>

</body>
</html>
