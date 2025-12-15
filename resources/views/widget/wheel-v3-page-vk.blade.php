<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">

    @php
        $metaDescription = $wheel->description
            ? \Illuminate\Support\Str::limit(strip_tags($wheel->description), 160)
            : 'Крутите колесо фортуны и выигрывайте призы! Участвуйте в акции и получите шанс выиграть ценные подарки.';
        $ogDescription = $wheel->description
            ? \Illuminate\Support\Str::limit(strip_tags($wheel->description), 200)
            : 'Крутите колесо фортуны и выигрывайте призы! Участвуйте в акции и получите шанс выиграть ценные подарки.';
        $pageTitle = ($wheel->name ?? 'Колесо Фортуны') . ' - Крутите колесо и выигрывайте призы!';
        $currentUrl = url()->current();
    @endphp

    {{-- SEO Meta Tags --}}
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="колесо фортуны, розыгрыш, призы, акция, выигрыш, лотерея, конкурс">
    <meta name="author" content="LuckyWheel">
    <link rel="canonical" href="{{ $currentUrl }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ asset('images/wheel-og-image.jpg') }}">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="Колесо Фортуны">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $currentUrl }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ asset('images/wheel-og-image.jpg') }}">

    {{-- Additional Meta Tags --}}
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#667eea">

    {{-- JSON-LD Structured Data --}}
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Game',
            'name' => $wheel->name ?? 'Колесо Фортуны',
            'description' => $wheel->description ? strip_tags($wheel->description) : 'Крутите колесо фортуны и выигрывайте призы!',
            'url' => $currentUrl,
            'gameLocation' => [
                '@type' => 'WebPage',
                'url' => $currentUrl
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'RUB'
            ]
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        }
        .lucky-wheel-content{
            padding: 0 !important;
        }
        .lucky-wheel-container{
            padding: 0px 20px !important;
        }
        .wheel-container {
            max-width: 500px !important;
        }
        .wheel {
            max-width: 500px !important;
            max-height: 500px !important;
        }
        .win-notification {
            width: 600px !important;
        }
        .lucky-wheel-content {
            max-height: 700px;
        }
        @media (min-width: 480px) {
            .wheel-content-contener{
                padding: 10px 0 !important;
                max-height: 700px !important;
            }
        }
        .wheel-content-contener {
            @media (max-width: 480px) {
                padding: 10px 0 !important;
                height: 100vh !important;
            }
        }
    </style>
</head>
<body>

<div class="lucky-wheel-content">
    @include('widget.wheel-v3')
</div>

<script src="https://unpkg.com/@vkontakte/vk-bridge/dist/browser.min.js"></script>
<script>
    vkBridge.send("VKWebAppInit", {});
</script>
</body>
</html> 
