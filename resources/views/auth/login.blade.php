@extends('layouts.guest')

@section('title', 'Login')

@section('promo_title')
    Login To Stitch Krafts &amp;
    <strong>Start Creating</strong>
@endsection

@section('promo_lead')
    Bring Your Vision to Life with Unmatched Craftsmanship<br>
    <em>Create boldly. Track confidently. Trust the craftsmanship behind every detail.</em>
@endsection

@section('content')
    <h2 class="oms-guest__heading">{{ __('Welcome back!') }}</h2>
    <p class="oms-guest__switch">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}">{{ __('Sign up') }}</a>
    </p>

    <hr class="oms-guest__rule">

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="oms-guest__grid oms-guest__grid--single">

            <div class="oms-guest__field">
                <label for="email" class="form-label">
                    <i class="fa-regular fa-envelope"></i>{{ __('Email Address') }}
                </label>

                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="oms-guest__field">
                <div class="oms-guest__label-row">
                    <label for="password" class="form-label">
                        <i class="fa-solid fa-shield-halved"></i>{{ __('Password') }}
                    </label>

                    @if (Route::has('password.request'))
                        <a class="oms-guest__link" href="{{ route('password.request') }}">
                            {{ __('Forgot Password') }}
                        </a>
                    @endif
                </div>

                <div class="oms-guest__pw">
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                    <button type="button" class="oms-guest__pw-toggle" data-pw-toggle="password" aria-label="{{ __('Show password') }}">
                        <i class="fa-solid fa-eye"></i>
                    </button>

                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="oms-guest__options">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                    <label class="form-check-label" for="remember">
                        {{ __('Keep me logged in') }}
                    </label>
                </div>

                <button type="submit" class="btn btn-brand oms-guest__submit">
                    <i class="fa-solid fa-right-to-bracket"></i>{{ __('Log in now') }}
                </button>
            </div>

        </div>
    </form>
@endsection
