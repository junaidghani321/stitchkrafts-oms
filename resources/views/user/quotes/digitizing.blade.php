@extends('layouts.app')

@section('title', 'New Digitizing Quote')
@section('page_title', 'New Digitizing Quote')
@section('page_heading', 'New Digitizing Quote')

@section('breadcrumb')
    <li><a href="{{ route('user.quotes.index') }}">Quotes</a></li>
@endsection

@section('content')

    <div class="card">
        <div class="card-body p-3 p-md-4">

            <h2 class="oms-form-title">New Digitizing Quote</h2>

            {{-- The very same partial the order form uses: a quote asks for an
                 identical spec, it just asks for a price instead of the work. --}}
            @include('orders.partials._form_digitizing', [
                'action'      => route('user.quotes.digitizing.store'),
                'submitLabel' => 'Request Quote',
            ])

        </div>
    </div>

@endsection
