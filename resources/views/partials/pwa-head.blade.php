<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#0b1f33">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="PractisBase">
<link rel="apple-touch-icon" href="/images/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/images/icons/icon-192.png">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }
</script>
