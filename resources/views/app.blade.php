<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        {{-- Progress bar --}}
        <style>
            #page-progress {
                position: fixed;
                top: 0;
                left: 0;
                width: 0%;
                height: 2px;
                z-index: 9999;
                opacity: 0;
                pointer-events: none;
                background: #000;
            }

            #page-progress-tip {
                display: none;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <div id="page-progress"><div id="page-progress-tip"></div></div>

        @inertia

        <script>
        (function () {
            var bar  = document.getElementById('page-progress');
            var tip  = document.getElementById('page-progress-tip');
            var timers = [];
            var delayTimer = null;
            var shown = false;

            function clearAll() {
                timers.forEach(clearTimeout);
                timers = [];
            }

            function after(fn, ms) {
                var t = setTimeout(fn, ms);
                timers.push(t);
            }

            function setWidth(w, durationMs, easing) {
                bar.style.transition = 'width ' + durationMs + 'ms ' + (easing || 'cubic-bezier(0.4,0,0.2,1)');
                bar.style.width = w + '%';
            }

            function show() {
                shown = true;
                tip.style.display = 'block';

                /* reset without transition */
                bar.style.transition = 'none';
                bar.style.opacity    = '1';
                bar.style.width      = '0%';
                bar.offsetHeight; /* force reflow */

                /* stage 1 – burst: 0 → 30% in 300 ms */
                setWidth(30, 300);

                /* stage 2 – steady: 30 → 60% starting at 300 ms */
                after(function () { setWidth(60, 900); }, 300);

                /* stage 3 – slowing: 60 → 80% starting at 1200 ms */
                after(function () { setWidth(80, 1500); }, 1200);

                /* stage 4 – crawl: 80 → 90% starting at 2700 ms */
                after(function () { setWidth(90, 5000, 'cubic-bezier(0.1,0,0.05,1)'); }, 2700);
            }

            function done() {
                clearAll();
                shown = false;
                tip.style.display = 'none';

                /* snap to 100% */
                setWidth(100, 180);

                /* fade out */
                after(function () {
                    bar.style.transition = 'opacity 280ms ease';
                    bar.style.opacity    = '0';
                }, 180);

                /* reset width silently */
                after(function () {
                    bar.style.transition = 'none';
                    bar.style.width      = '0%';
                }, 480);
            }

            document.addEventListener('inertia:start', function () {
                clearTimeout(delayTimer);
                clearAll();
                shown = false;
                delayTimer = setTimeout(show, 200);
            });

            document.addEventListener('inertia:finish', function () {
                clearTimeout(delayTimer);
                if (shown) {
                    done();
                }
            });
        })();
        </script>
    </body>
</html>
