@extends('layouts.app')

@section('title', 'Quote ' . $quote->quote_number)
@section('page_title', 'Quote Details')
@section('page_heading', $quote->quote_number)

@section('breadcrumb')
    <li><a href="{{ route('user.quotes.index') }}">Quotes</a></li>
@endsection

@section('content')

    @include('quotes.partials._detail')

    <div class="oms-form-actions">
        <a href="{{ route('user.quotes.index') }}" class="btn btn-brand">Back to Quotes</a>
    </div>

@endsection
