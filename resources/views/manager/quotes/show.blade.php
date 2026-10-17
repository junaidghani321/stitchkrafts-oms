@extends('layouts.app')

@section('title', 'Quote ' . $quote->quote_number)
@section('page_title', 'Quote Details')
@section('page_heading', $quote->quote_number)

@section('breadcrumb')
    <li><a href="{{ route('manager.quotes.index') }}">Quotes</a></li>
@endsection

@section('content')

    @include('quotes.partials._detail')

    <div class="oms-form-actions">
        <a href="{{ route('manager.quotes.index') }}" class="btn btn-brand">Back to Quotes</a>
    </div>

@endsection
