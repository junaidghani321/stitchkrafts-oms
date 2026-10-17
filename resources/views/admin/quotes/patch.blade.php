@extends('layouts.app')

@section('title', 'New Patch Quote')
@section('page_title', 'New Patch Quote')
@section('page_heading', 'New Patch Quote')

@section('breadcrumb')
    <li><a href="{{ route('admin.quotes.index') }}">Quotes</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">New Patch Quote</h2>

            {{-- The very same partial the order form uses: a quote asks for an
                 identical spec, it just asks for a price instead of the work. --}}
            @include('orders.partials._form_patch', [
                'action'      => route('admin.quotes.patch.store'),
                'submitLabel' => 'Request Quote',
                'customers'   => $customers,
            ])

        </div>
    </div>

@endsection
