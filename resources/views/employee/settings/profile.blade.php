@extends('layouts.app')

@section('title', 'My Profile')
@section('page_title', 'My Profile')
@section('page_heading', 'My Profile')

@section('breadcrumb')
    <li>Settings</li>
@endsection

@section('content')

    @include('profile.partials._form', ['role' => 'employee'])

@endsection
