@extends('layouts.app')

@section('title', 'My Quotes')
@section('page_title', 'My Quotes')
@section('page_heading', 'My Quotes')

@section('breadcrumb')
    <li><a href="{{ route('user.quotes.index') }}">Quotes</a></li>
@endsection

@section('content')

@php
    /* $quotes, $statusLabels and $statusStyles come from the controller. */
    $typeLabels   = ['digitizing' => 'Digitizing', 'vector' => 'Vector', 'patch' => 'Patch'];
@endphp

    <div class="card">
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
                        Type
                        <select class="form-select" data-oms-filter="type">
                            <option value="">All</option>
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Status
                        <select class="form-select" data-oms-filter="status">
                            <option value="">All</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
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
                            <th>Quote #</th>
                            <th>Type</th>
                            <th>Design Name</th>
                            <th>Quote Date</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotes as $quote)
                            <tr data-row data-type="{{ $quote['type'] }}" data-status="{{ $quote['status'] }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $quote['no'] }}</td>
                                <td>{{ $typeLabels[$quote['type']] }}</td>
                                <td>{{ $quote['design'] }}</td>
                                <td>{{ $quote['date'] }}</td>
                                <td><span class="oms-badge oms-badge--{{ $statusStyles[$quote['status']] }}">{{ $statusLabels[$quote['status']] }}</span></td>
                                <td><span class="oms-price {{ $quote['price'] == 0 ? 'oms-price--zero' : '' }}">$ {{ number_format($quote['price'], 2) }}</span></td>
                                <td>
                                    <div class="oms-actions">
                                        <a href="{{ route('user.quotes.show', $quote['id']) }}" class="oms-action" title="View quote" aria-label="View quote"><i class="fa-solid fa-eye"></i></a>

                                        {{-- Only once a price exists: accepting an unpriced
                                             quote is meaningless, and the policy rejects it. --}}
                                        @if ($quote['decidable'])
                                            <form action="{{ route('user.quotes.approve', $quote['id']) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="oms-action" title="Accept quote" aria-label="Accept quote">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('user.quotes.reject', $quote['id']) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="oms-action oms-action--danger" title="Decline quote" aria-label="Decline quote">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                        @endif
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

@endsection
