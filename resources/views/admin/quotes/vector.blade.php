@extends('layouts.app')

@section('title', 'New Vector Quote')
@section('page_title', 'New Vector Quote')
@section('page_heading', 'New Vector Quote')

@section('breadcrumb')
    <li><a href="{{ route('admin.quotes.index') }}">Quotes</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">New Vector Quote</h2>

            {{-- The very same partial the order form uses: a quote asks for an
                 identical spec, it just asks for a price instead of the work. --}}
            @include('orders.partials._form_vector', [
                'action'      => route('admin.quotes.vector.store'),
                'submitLabel' => 'Request Quote',
                'customers'   => $customers,
            ])

        </div>
    </div>

@endsection
