@extends('layouts.guest')

@section('title', 'Sign Up')

@section('box_class', 'oms-guest__box--wide')

@section('promo_title')
    Transform Your Vision into
    <strong>Stunning Designs</strong>
@endsection

@section('promo_lead')
    Join Stitch Krafts to manage and track your orders, and bring your ideas
    to life with expert craftsmanship and precision.
@endsection

@section('content')
    <h2 class="oms-guest__heading">{{ __('Sign Up') }}</h2>
    <p class="oms-guest__switch">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}">{{ __('LOGIN') }}</a>
    </p>

    <hr class="oms-guest__rule">

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="oms-guest__grid">

            <div class="oms-guest__field">
                <label for="name" class="form-label">
                    <i class="fa-regular fa-user"></i>{{ __('Your Name') }}
                </label>

                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="oms-guest__field">
                <label for="email" class="form-label">
                    <i class="fa-regular fa-envelope"></i>{{ __('Email') }}
                </label>

                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="oms-guest__field">
                <label for="contact_number" class="form-label">
                    <i class="fa-solid fa-mobile-screen"></i>{{ __('Contact Number') }}
                </label>

                <input id="contact_number" type="text" class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" autocomplete="tel">

                @error('contact_number')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="oms-guest__field">
                <label for="company_name" class="form-label">
                    <i class="fa-solid fa-building"></i>{{ __('Company Name') }}
                </label>

                <input id="company_name" type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name') }}" autocomplete="organization">

                @error('company_name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="oms-guest__field">
                <label for="username" class="form-label">
                    <i class="fa-solid fa-at"></i>{{ __('Username') }}
                </label>

                <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autocomplete="username">

                @error('username')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="oms-guest__field">
                <label for="country" class="form-label">
                    <i class="fa-solid fa-globe"></i>{{ __('Country') }}
                </label>

                <select id="country" class="form-select @error('country') is-invalid @enderror" name="country" required>
                    <option value="" disabled @selected(! old('country'))>{{ __('Select') }}</option>
                    @foreach (App\Enums\Country::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('country') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                @error('country')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="oms-guest__field">
                <label for="password" class="form-label">
                    <i class="fa-solid fa-shield-halved"></i>{{ __('Password') }}
                </label>

                <div class="oms-guest__pw">
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

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

            <div class="oms-guest__field">
                <label for="password-confirm" class="form-label">
                    <i class="fa-solid fa-shield-halved"></i>{{ __('Confirm Password') }}
                </label>

                <div class="oms-guest__pw">
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

                    <button type="button" class="oms-guest__pw-toggle" data-pw-toggle="password-confirm" aria-label="{{ __('Show password') }}">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="oms-guest__full">
                <button type="submit" class="btn btn-brand oms-guest__submit w-100">
                    <i class="fa-solid fa-paper-plane"></i>{{ __('Signup') }}
                </button>
            </div>

        </div>
    </form>
@endsection
