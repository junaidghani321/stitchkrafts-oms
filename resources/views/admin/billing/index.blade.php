@extends('layouts.app')

@section('title', 'Billing')
@section('page_title', 'Billing')
@section('page_heading', 'Billing')

@section('content')

@php
    /* $totals, $invoices and $ungenerated come from Admin\BillingController. */
@endphp

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="oms-stat__label">Total Business</div>
                    <div class="oms-stat__value">$ {{ number_format($totals['business'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--good"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="oms-stat__label">Total Paid</div>
                    <div class="oms-stat__value">$ {{ number_format($totals['paid'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="oms-stat__icon oms-stat__icon--bad"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="oms-stat__label">Total Payable</div>
                    <div class="oms-stat__value">$ {{ number_format($totals['payable'], 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="oms-stat">
                <div class="w-100 text-center">
                    {{-- No orders ticked means "bill everything outstanding". --}}
                    <form action="{{ route('admin.billing.generate') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100" @disabled($ungenerated->isEmpty())>
                            <i class="fa-solid fa-file-circle-plus me-1"></i> Generate Invoice
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Paid & unpaid invoices --}}
    <div class="card mb-3">
        <div class="card-header">Paid and Unpaid Invoices</div>
        <div class="card-body p-3 p-md-4" data-oms-table>

            <div class="oms-table-toolbar">
                <div class="oms-table-filters">
                    <label>
                        Show
                        <select class="form-select" data-oms-entries>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </label>

                    <label>
                        Status
                        <select class="form-select" data-oms-filter="status">
                            <option value="">All</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </label>
                </div>

                <label>
                    Search:
                    <input type="search" class="form-control" data-oms-search placeholder="Type to filter…">
                </label>
            </div>

            <div class="oms-table-wrap">
                <table class="oms-table">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Invoice Date</th>
                            <th>Total Payable</th>
                            <th>Status</th>
                            <th>Paid Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr data-row data-status="{{ $invoice['status'] }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $invoice['no'] }}</td>
                                <td>{{ $invoice['customer'] }}</td>
                                <td>{{ $invoice['date'] }}</td>
                                <td><span class="oms-price">$ {{ number_format($invoice['payable'], 2) }}</span></td>
                                <td><span class="oms-badge oms-badge--{{ $invoice['status'] }}">{{ ucfirst($invoice['status']) }}</span></td>
                                <td>{{ $invoice['paid_date'] ?? '—' }}</td>
                                <td>
                                    <div class="oms-actions">
                                        <a href="{{ route('admin.billing.show', $invoice['id']) }}" class="oms-action" title="View invoice" aria-label="View invoice"><i class="fa-solid fa-eye"></i></a>
                                        <a href="{{ route('invoices.download', $invoice['id']) }}" class="oms-action" title="Download PDF" aria-label="Download PDF"><i class="fa-solid fa-download"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr data-oms-empty hidden>
                            <td colspan="8" class="oms-table-empty">No data available in table</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="oms-table-foot">
                <div data-oms-info></div>
                <div class="oms-pager" data-oms-pager></div>
            </div>

        </div>
    </div>

    {{-- Delivered orders not yet invoiced --}}
    <form action="{{ route('admin.billing.generate') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Ungenerated Invoices</span>
            <button type="submit" class="btn btn-brand btn-sm" @disabled($ungenerated->isEmpty())>
                <i class="fa-solid fa-file-circle-plus me-1"></i> Generate For Selected
            </button>
        </div>
        <div class="card-body p-3 p-md-4" data-oms-table>

            <div class="oms-table-toolbar">
                <div class="oms-table-filters">
                    <label>
                        Show
                        <select class="form-select" data-oms-entries>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </label>
                </div>

                <label>
                    Search:
                    <input type="search" class="form-control" data-oms-search placeholder="Type to filter…">
                </label>
            </div>

            <div class="oms-table-wrap" id="ungeneratedInvoices">
                <table class="oms-table">
                    <thead>
                        <tr>
                            <th>
                                Check
                                <a href="#" class="text-white ms-1" data-oms-check-all="#ungeneratedInvoices" data-state="all">All</a> /
                                <a href="#" class="text-white" data-oms-check-all="#ungeneratedInvoices" data-state="none">None</a>
                            </th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Order Date</th>
                            <th>Sent Date</th>
                            <th>Design Name</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ungenerated as $order)
                            <tr data-row>
                                <td>
                                    {{-- The id, not the display number: the old markup posted
                                         "ADDO-161", which nothing could resolve. --}}
                                    <input class="form-check-input" type="checkbox" name="orders[]"
                                           value="{{ $order['id'] }}" aria-label="Select {{ $order['no'] }}">
                                </td>
                                <td>{{ $order['no'] }}</td>
                                <td>{{ $order['customer'] }}</td>
                                <td>{{ $order['order_date'] }}</td>
                                <td>{{ $order['sent_date'] }}</td>
                                <td>{{ $order['design'] }}</td>
                                <td><span class="oms-price">$ {{ number_format($order['price'], 2) }}</span></td>
                            </tr>
                        @endforeach

                        <tr data-oms-empty hidden>
                            <td colspan="7" class="oms-table-empty">No data available in table</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="oms-table-foot">
                <div data-oms-info></div>
                <div class="oms-pager" data-oms-pager></div>
            </div>

        </div>
    </div>

    </form>

@endsection
