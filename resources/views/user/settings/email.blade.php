@extends('layouts.app')

@section('title', 'Email Settings')
@section('page_title', 'Email Settings')
@section('page_heading', 'Email Settings')

@section('breadcrumb')
    <li>Settings</li>
@endsection

@section('content')

@php
    /* $preferences comes from EmailPreferencesController. */
@endphp

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-body p-3 p-md-4">

                    <h2 class="oms-form-title">Email Settings</h2>

                    <p class="text-center text-muted mb-4">
                        Set your email preferences — which emails you want to receive and which you don't.
                    </p>

                    <form action="{{ route('user.settings.email.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        @foreach ($preferences as $preference)
                            <div class="oms-switch">
                                <div>
                                    <label class="oms-switch__label" for="pref_{{ $preference['key'] }}">{{ $preference['label'] }}</label>
                                    <div class="oms-switch__hint">
                                        {{ $preference['hint'] }}
                                        @if ($preference['pending'] ?? false)
                                            <span class="oms-badge oms-badge--pending ms-1">Coming with Billing</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="pref_{{ $preference['key'] }}"
                                           name="preferences[{{ $preference['key'] }}]"
                                           value="1" @checked($preference['on'])>
                                </div>
                            </div>
                        @endforeach

                        <div class="oms-form-actions">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
