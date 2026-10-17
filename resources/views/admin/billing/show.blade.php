@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page_title', 'Invoice')
@section('page_heading', $invoice->invoice_number)

@section('breadcrumb')
    <li><a href="{{ route('admin.billing') }}">Billing</a></li>
@endsection

@section('content')

    @include('billing.partials._invoice')

    <div class="oms-form-actions">
        <a href="{{ route('admin.billing') }}" class="btn btn-brand">Back to Billing</a>
    </div>

@endsection
