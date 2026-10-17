<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a4354">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Welcome') · {{ config('app.name', 'OMS') }}</title>

    <link rel="icon" href="{{ asset('assets/images/logo.webp') }}" type="image/webp">
    <link href="{{ asset('assets/bootstrap-5.3.8/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/fontawesome-7.3.1/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/style.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body class="oms-guest">

    <main class="oms-guest__main">

        <section class="oms-guest__promo">
            <div class="oms-guest__promo-head">
                <a href="{{ url('/') }}" class="oms-guest__logo">
                    <img src="{{ asset('assets/images/logo.webp') }}" alt="{{ config('app.name', 'OMS') }}">
                </a>

                <h1 class="oms-guest__title">
                    @hasSection('promo_title')
                        @yield('promo_title')
                    @else
                        Welcome To Stitch Krafts &amp;
                        <strong>Start Creating</strong>
                    @endif
                </h1>
            </div>

            <p class="oms-guest__lead">
                @hasSection('promo_lead')
                    @yield('promo_lead')
                @else
                    Bring Your Vision to Life with Unmatched Craftsmanship<br>
                    <em>Create boldly. Track confidently. Trust the craftsmanship behind every detail.</em>
                @endif
            </p>
        </section>

        <div class="oms-guest__box @yield('box_class')">
            <div class="oms-guest__card">

                @if (session('status'))
                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                @endif

                {{-- LoginController flashes its failure here rather than as a
                     field error, so the card has to render it or a wrong
                     password comes back silently. --}}
                @if (session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif

                @yield('content')

            </div>

            <p class="oms-guest__footer">
                Portal &copy; {{ date('Y') }} <strong>Stitch Krafts</strong>, All Rights Reserved.
            </p>
        </div>

    </main>

    <script src="{{ asset('assets/bootstrap-5.3.8/js/bootstrap.bundle.min.js') }}"></script>

    <script>
        /* Reveal toggle for password inputs. Presentation only — the field,
           its name and its validation are untouched. */
        document.querySelectorAll('[data-pw-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.pwToggle);

                if (! input) {
                    return;
                }

                var reveal = input.type === 'password';

                input.type = reveal ? 'text' : 'password';
                button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
                button.querySelector('i').className = reveal ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
