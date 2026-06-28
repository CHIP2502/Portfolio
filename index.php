<?php
declare(strict_types=1);

$nonce = base64_encode(random_bytes(16));



function isHttpsRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function getBotGuardSecret(): string
{
    $secret = getenv('BOT_GUARD_SECRET');

    if (is_string($secret) && $secret !== '') {
        return $secret;
    }

    return hash('sha256', __FILE__ . '|minhhuydev|bot-guard');
}

function buildBrowserProofToken(string $userAgent, string $sessionId, int $timeSlice): string
{
    $payload = $sessionId . '|' . $userAgent . '|' . $timeSlice;
    return hash_hmac('sha256', $payload, getBotGuardSecret());
}

function getJavaScriptRuntimeWindowSeconds(): int
{
    return 45;
}

function buildJavaScriptRuntimeToken(string $userAgent, string $sessionId, string $nonce): string
{
    $payload = 'js-runtime|' . $sessionId . '|' . $userAgent . '|' . $nonce;
    return hash_hmac('sha256', $payload, getBotGuardSecret());
}

function createJavaScriptRuntimeNonce(): string
{
    try {
        return bin2hex(random_bytes(16));
    } catch (Throwable $exception) {
        return hash('sha256', microtime(true) . '|' . mt_rand());
    }
}

function issueJavaScriptRuntimeToken(string $userAgent, string $sessionId): string
{
    $nonce = createJavaScriptRuntimeNonce();
    $signature = buildJavaScriptRuntimeToken($userAgent, $sessionId, $nonce);
    $token = $nonce . '.' . $signature;

    $_SESSION['mh_js_runtime_expected'] = $token;
    $_SESSION['mh_js_runtime_expected_until'] = time() + getJavaScriptRuntimeWindowSeconds();

    return $token;
}

function hasValidJavaScriptRuntimeCookie(): bool
{
    $expectedRuntime = (string) ($_SESSION['mh_js_runtime_expected'] ?? '');
    $expectedUntil = (int) ($_SESSION['mh_js_runtime_expected_until'] ?? 0);
    $cookieRuntime = (string) ($_COOKIE['mh_js_runtime'] ?? '');

    if ($expectedRuntime === '' || $cookieRuntime === '' || $expectedUntil < time()) {
        return false;
    }

    return hash_equals($expectedRuntime, $cookieRuntime);
}

function queueJavaScriptRuntimeRefreshToken(string $userAgent, string $sessionId): void
{
    $GLOBALS['mh_js_runtime_refresh_token'] = issueJavaScriptRuntimeToken($userAgent, $sessionId);
}

function getQueuedJavaScriptRuntimeRefreshToken(): string
{
    return (string) ($GLOBALS['mh_js_runtime_refresh_token'] ?? '');
}

function isJavaScriptDisabledByDevTools(): bool
{
    return !hasValidJavaScriptRuntimeCookie();
}

function requestBotRiskScore(): array
{
    $score = 0;
    $reasons = [];

    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $acceptLanguage = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    $secFetchSite = (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
    $secChUa = (string) ($_SERVER['HTTP_SEC_CH_UA'] ?? '');

    if ($ua === '') {
        $score += 60;
        $reasons[] = 'missing_user_agent';
    }

    if (preg_match('/python-requests|aiohttp|httpx|curl|wget|scrapy|go-http-client|postmanruntime|insomnia|okhttp|libwww-perl|java\//i', $ua)) {
        $score += 90;
        $reasons[] = 'known_automation_user_agent';
    }

    if ($accept === '' || stripos($accept, 'text/html') === false) {
        $score += 18;
        $reasons[] = 'missing_html_accept';
    }

    if ($acceptLanguage === '') {
        $score += 12;
        $reasons[] = 'missing_accept_language';
    }

    if ($secFetchSite === '' && $secChUa === '') {
        $score += 8;
        $reasons[] = 'missing_browser_fetch_headers';
    }

    return [$score, $reasons];
}

function renderBotBlockedPage(): void
{
    http_response_code(403);
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/m008v.png">
    <link rel="apple-touch-icon" href="assets/images/m008v.png">
    <meta property="og:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <meta name="twitter:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <meta name="description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <meta name="author" content="MinhHuyDev">
    <meta name="theme-color" content="#000000">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="Nguyễn Minh Huy | MinhHuyDev Portfolio">
    <meta property="og:description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <meta name="keywords" content="Nguyễn Minh Huy, MinhHuyDev, m008v, web developer, portfolio">
    <meta property="og:site_name" content="MinhHuyDev Portfolio">
    <meta property="og:url" content="https://nqminkhuy.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Nguyễn Minh Huy | MinhHuyDev Portfolio">
    <meta name="twitter:description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <title>Bot Request Blocked</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #090f1a;
            --card: #111a2a;
            --line: rgba(183, 198, 220, 0.22);
            --ink: #f3f7ff;
            --muted: #a7b7d1;
            --accent: #39c2b7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Manrope", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 16% 18%, rgba(57, 194, 183, 0.22), transparent 34%),
                radial-gradient(circle at 82% 20%, rgba(213, 154, 103, 0.2), transparent 30%),
                var(--bg);
            padding: 20px;
        }

        .bot-card {
            width: min(560px, 100%);
            border: 1px solid var(--line);
            border-radius: 24px;
            background: linear-gradient(165deg, rgba(17, 26, 42, 0.96), rgba(13, 21, 34, 0.94));
            padding: 28px;
            text-align: center;
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.46);
        }

        h1 {
            margin: 0;
            font-size: clamp(1.1rem, 2.6vw, 1.42rem);
            line-height: 1.5;
            letter-spacing: 0.02em;
        }

        p {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 0.98rem;
        }

        .reload-btn {
            margin-top: 22px;
            border: 0;
            border-radius: 14px;
            padding: 12px 18px;
            font-size: 0.96rem;
            font-weight: 800;
            color: #03171a;
            background: var(--accent);
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .reload-btn:hover,
        .reload-btn:focus-visible {
            transform: translateY(-1px);
            filter: brightness(1.08);
            outline: none;
        }
    </style>
</head>
<body>
    <main class="bot-card">
        <h1>VUI LÒNG KHÔNG SỬ DỤNG BOT REQUESTS HOẶC RELOAD LẠI TRANG</h1>
        <p>Nếu bạn là người dùng thật, hãy nhấn reload để tải lại.</p>
        <button class="reload-btn" type="button" onclick="window.location.reload();">Reload</button>
    </main>
    <?php renderJavaScriptRuntimeRefreshScript(); ?>
</body>
</html>
<?php
}

function renderBrowserProofPage(string $proofToken, string $runtimeToken): void
{
    $encodedToken = htmlspecialchars($proofToken, ENT_QUOTES, 'UTF-8');
    $encodedRuntimeToken = htmlspecialchars($runtimeToken, ENT_QUOTES, 'UTF-8');
    $secureSuffix = isHttpsRequest() ? '; Secure' : '';
    $runtimeCookieMaxAge = getJavaScriptRuntimeWindowSeconds() + 60;
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/m008v.png">
    <link rel="apple-touch-icon" href="assets/images/m008v.png">
    <meta property="og:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <meta name="twitter:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <meta name="description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <meta name="author" content="MinhHuyDev">
    <meta name="theme-color" content="#000000">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="Nguyễn Minh Huy | MinhHuyDev Portfolio">
    <meta property="og:description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <meta name="keywords" content="Nguyễn Minh Huy, MinhHuyDev, m008v, web developer, portfolio">
    <meta property="og:site_name" content="MinhHuyDev Portfolio">
    <meta property="og:url" content="https://nqminkhuy.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Nguyễn Minh Huy | MinhHuyDev Portfolio">
    <meta name="twitter:description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <title>MinhHuyDev | Initializing Terminal...</title>
    <style>
        :root {
            --term-bg: #050914;
            --term-fg: #10b981;
            --term-fg-dim: #065f46;
        }

        body {
            margin: 0;
            background: var(--term-bg);
            color: var(--term-fg);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            text-transform: uppercase;
        }

        .terminal-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }

        .sys-info {
            font-size: 0.75rem;
            color: var(--term-fg-dim);
            margin-bottom: 2rem;
            text-transform: none;
        }

        .line {
            font-size: 0.95rem;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .spinner {
            color: var(--term-fg);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="terminal-container">
        <div class="sys-info">SYSTEM: verifyBrowser v2.0.4 <br>SESSION: <?= substr($encodedRuntimeToken, 0, 16) ?>...</div>
        
        <div class="line">
            <span class="spinner" id="spinner">⠋</span>
            <span id="status-text">INITIALIZING SECURE CONNECTION...</span>
        </div>
    </div>

    <script>
        (function () {
            var token = "<?= $encodedToken ?>";
            var runtimeToken = "<?= $encodedRuntimeToken ?>";
            document.cookie = "mh_browser_proof=" + encodeURIComponent(token) + "; Max-Age=21600; Path=/; SameSite=Lax<?= $secureSuffix ?>";
            document.cookie = "mh_js_runtime=" + encodeURIComponent(runtimeToken) + "; Max-Age=<?= $runtimeCookieMaxAge ?>; Path=/; SameSite=Lax<?= $secureSuffix ?>";

            var frames = ["⠋", "⠙", "⠹", "⠸", "⠼", "⠴", "⠦", "⠧", "⠇", "⠏"];
            var f = 0;
            setInterval(function() {
                document.getElementById('spinner').innerText = frames[f];
                f = (f + 1) % frames.length;
            }, 80);

            var steps = [
                "INITIALIZING SECURE CONNECTION...",
                "VERIFYING BROWSER SIGNATURE...",
                "CHECKING RUNTIME INTEGRITY...",
                "ACCESS GRANTED. REDIRECTING..."
            ];
            var s = 0;
            setInterval(function() {
                s++;
                if(s < steps.length) {
                    document.getElementById('status-text').innerText = steps[s];
                }
            }, 300);

            window.setTimeout(function () {
                window.location.reload();
            }, 1200);
        })();
    </script>
</body>
</html>
<?php
}

function renderJavaScriptRuntimePage(string $runtimeToken): void
{
    $encodedRuntimeToken = htmlspecialchars($runtimeToken, ENT_QUOTES, 'UTF-8');
    $secureSuffix = isHttpsRequest() ? '; Secure' : '';
    $runtimeCookieMaxAge = getJavaScriptRuntimeWindowSeconds() + 60;
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/m008v.png">
    <link rel="apple-touch-icon" href="assets/images/m008v.png">
    <meta property="og:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <meta name="twitter:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <meta name="description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <meta name="author" content="MinhHuyDev">
    <meta name="theme-color" content="#000000">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="Nguyễn Minh Huy | MinhHuyDev Portfolio">
    <meta property="og:description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <meta name="keywords" content="Nguyễn Minh Huy, MinhHuyDev, m008v, web developer, portfolio">
    <meta property="og:site_name" content="MinhHuyDev Portfolio">
    <meta property="og:url" content="https://nqminkhuy.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Nguyễn Minh Huy | MinhHuyDev Portfolio">
    <meta name="twitter:description" content="Portfolio của Nguyễn Minh Huy (MinhHuyDev) - Lập trình viên tại Cần Thơ">
    <title>JavaScript is disabled by DevTools | MinhHuyDev Profile</title>
    <style>
        :root {
            color-scheme: dark;
            --accent: #39c2b7;
            --accent-2: #d59a67;
            --ink: #eaf1ff;
            --muted: #9bb0cf;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            background: #0b1220;
            color: var(--ink);
            font-family: "Manrope", "Segoe UI", sans-serif;
            overflow: hidden;
            transform: translateZ(0);
        }

        .page-loader {
            position: fixed;
            inset: 0;
            z-index: 95;
            display: grid;
            place-items: center;
        }

        .page-loader::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 18% 22%, rgba(57, 194, 183, 0.2), transparent 34%),
                radial-gradient(circle at 84% 18%, rgba(213, 154, 103, 0.22), transparent 31%),
                rgba(11, 18, 32, 0.74);
            backdrop-filter: blur(10px);
            transform: translateZ(0);
        }

        .page-loader-inner {
            position: relative;
            z-index: 1;
            display: grid;
            justify-items: center;
            gap: 14px;
        }

        .premium-spinner {
            position: relative;
            width: 160px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 1000px;
            transform: translateZ(0);
            will-change: transform;
        }

        .atom-orbit {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 1px solid rgba(176, 194, 219, 0.2);
            transform-style: preserve-3d;
            will-change: transform;
        }

        .orbit-1 { animation: spinOrbit1 3.5s linear infinite; }
        .orbit-2 { animation: spinOrbit2 3.5s linear infinite; }
        .orbit-3 { animation: spinOrbit3 3.5s linear infinite; }

        .atom-electron {
            position: absolute;
            top: -4px;
            left: 50%;
            width: 8px;
            height: 8px;
            margin-left: -4px;
            background: var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 14px var(--accent), 0 0 28px var(--accent);
        }

        .orbit-2 .atom-electron {
            background: var(--accent-2);
            box-shadow: 0 0 14px var(--accent-2), 0 0 28px var(--accent-2);
        }

        .orbit-3 .atom-electron {
            background: var(--ink);
            box-shadow: 0 0 14px var(--ink), 0 0 28px var(--ink);
        }

        .atom-core {
            position: absolute;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            box-shadow: 0 0 40px rgba(57, 194, 183, 0.6);
            animation: corePulse 2s ease-in-out infinite;
        }

        p {
            margin: 0;
            color: var(--muted);
            text-align: center;
        }

        @keyframes spinOrbit1 {
            0% { transform: rotateY(0deg) rotateX(70deg) rotateZ(0deg); }
            100% { transform: rotateY(0deg) rotateX(70deg) rotateZ(360deg); }
        }

        @keyframes spinOrbit2 {
            0% { transform: rotateY(120deg) rotateX(70deg) rotateZ(0deg); }
            100% { transform: rotateY(120deg) rotateX(70deg) rotateZ(360deg); }
        }

        @keyframes spinOrbit3 {
            0% { transform: rotateY(240deg) rotateX(70deg) rotateZ(0deg); }
            100% { transform: rotateY(240deg) rotateX(70deg) rotateZ(360deg); }
        }

        @keyframes corePulse {
            0%, 100% { transform: scale(0.9); opacity: 0.8; filter: hue-rotate(0deg); }
            50% { transform: scale(1.2); opacity: 1; filter: hue-rotate(30deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .orbit-1,
            .orbit-2,
            .orbit-3,
            .atom-core {
                animation: none;
            }
        }
    </style>
</head>
<body>
    <div class="page-loader">
        <div class="page-loader-inner">
            <div class="premium-spinner" aria-hidden="true">
                <div class="atom-orbit orbit-1"><div class="atom-electron"></div></div>
                <div class="atom-orbit orbit-2"><div class="atom-electron"></div></div>
                <div class="atom-orbit orbit-3"><div class="atom-electron"></div></div>
                <div class="atom-core"></div>
            </div>
            <noscript>
                <p>Bạn đang tắt JavaScript trong trình duyệt hoặc DevTools.<br>Vui lòng bật lại rồi tải lại trang.</p>
            </noscript>
        </div>
    </div>

    <script>
        (function () {
            var runtimeToken = "<?= $encodedRuntimeToken ?>";
            document.cookie = "mh_js_runtime=" + encodeURIComponent(runtimeToken) + "; Max-Age=<?= $runtimeCookieMaxAge ?>; Path=/; SameSite=Lax<?= $secureSuffix ?>";
            document.cookie = "raintee_antibot=1; Max-Age=60; Path=/; SameSite=Lax<?= $secureSuffix ?>";
            window.setTimeout(function () {
                window.location.reload();
            }, 160);
        })();
    </script>
</body>
</html>
<?php
}

function renderJavaScriptRuntimeRefreshScript(): void
{
    global $nonce;
    $runtimeToken = getQueuedJavaScriptRuntimeRefreshToken();

    if ($runtimeToken === '') {
        return;
    }

    $encodedRuntimeToken = htmlspecialchars($runtimeToken, ENT_QUOTES, 'UTF-8');
    $secureSuffix = isHttpsRequest() ? '; Secure' : '';
    $runtimeCookieMaxAge = getJavaScriptRuntimeWindowSeconds() + 60;
    ?>
    <script nonce="<?= h($nonce) ?>">
        (function () {
            var runtimeToken = "<?= $encodedRuntimeToken ?>";
            var runtimeCookie = "mh_js_runtime=" + encodeURIComponent(runtimeToken) + "; Max-Age=<?= $runtimeCookieMaxAge ?>; Path=/; SameSite=Lax<?= $secureSuffix ?>";
            var hasWrittenRuntimeCookie = false;

            var writeRuntimeCookie = function () {
                if (hasWrittenRuntimeCookie) {
                    return;
                }

                document.cookie = runtimeCookie;
                hasWrittenRuntimeCookie = true;
            };

            window.addEventListener('beforeunload', writeRuntimeCookie);
            window.addEventListener('pagehide', writeRuntimeCookie);
        })();
    </script>
    <?php
}

function enforceAntiBotGuard(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isHttpsRequest(),
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if (!in_array($method, ['GET', 'HEAD'], true)) {
        return;
    }

    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $sessionId = session_id();
    $timeSlice = (int) floor(time() / 21600);
    $expectedCurrent = buildBrowserProofToken($userAgent, $sessionId, $timeSlice);
    $expectedPrevious = buildBrowserProofToken($userAgent, $sessionId, $timeSlice - 1);
    $cookieProof = (string) ($_COOKIE['mh_browser_proof'] ?? '');
    $isCookieVerified = $cookieProof !== '' && (
        hash_equals($expectedCurrent, $cookieProof) || hash_equals($expectedPrevious, $cookieProof)
    );
    $isRuntimeVerified = hasValidJavaScriptRuntimeCookie();

    $verifiedUntil = (int) ($_SESSION['mh_browser_verified_until'] ?? 0);

    if (($isCookieVerified || $verifiedUntil > time()) && $isRuntimeVerified) {
        $_SESSION['mh_browser_verified_until'] = time() + 21600;
        $_SESSION['mh_guard_failures'] = 0;
        queueJavaScriptRuntimeRefreshToken($userAgent, $sessionId);
        return;
    }

    if (($isCookieVerified || $verifiedUntil > time()) && isJavaScriptDisabledByDevTools()) {
        $runtimeToken = issueJavaScriptRuntimeToken($userAgent, $sessionId);
        $_SESSION['mh_browser_verified_until'] = time() + 21600;
        $_SESSION['mh_guard_failures'] = 0;
        renderJavaScriptRuntimePage($runtimeToken);
        exit;
    }

    [$riskScore] = requestBotRiskScore();
    $failures = (int) ($_SESSION['mh_guard_failures'] ?? 0);

    if ($riskScore >= 70 || $failures >= 2) {
        renderBotBlockedPage();
        exit;
    }

    $_SESSION['mh_guard_failures'] = $failures + 1;
    $runtimeToken = issueJavaScriptRuntimeToken($userAgent, $sessionId);
    renderBrowserProofPage($expectedCurrent, $runtimeToken);
    exit;
}

enforceAntiBotGuard();

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'nonce-{$nonce}'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; upgrade-insecure-requests");
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function loadProfileData(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return [];
    }

    return is_array($data) ? $data : [];
}

function safeUrl(mixed $url, string $fallback = '#'): string
{
    $value = trim((string) $url);
    if ($value === '') {
        return $fallback;
    }

    if ($value === '#' || str_starts_with($value, '/') || str_starts_with($value, './') || str_starts_with($value, '../')) {
        return $value;
    }

    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) ? $value : $fallback;
}

function safeImageUrl(mixed $url): string
{
    $value = trim((string) $url);
    if ($value === '') {
        return '';
    }

    if (str_starts_with($value, '/') || str_starts_with($value, './') || str_starts_with($value, '../')) {
        return $value;
    }

    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https', 'data'], true) ? $value : '';
}

function normalizedHighlights(array $highlights): array
{
    $now = new DateTimeImmutable('now');
    $currentPeriod = $now->format('m/Y');

    foreach ($highlights as &$highlight) {
        if (!is_array($highlight)) {
            $highlight = [];
            continue;
        }

        if (($highlight['period'] ?? '') === '__CURRENT_MONTH_YEAR__') {
            $from = trim((string) ($highlight['period_from'] ?? ''));
            $highlight['period'] = $from !== '' ? "{$from} - {$currentPeriod}" : $currentPeriod;
        }
    }
    unset($highlight);

    return $highlights;
}

function words(string $value, int $limit): string
{
    $items = preg_split('/\s+/u', trim($value)) ?: [];
    if (count($items) <= $limit) {
        return $value;
    }

    return implode(' ', array_slice($items, 0, $limit)) . '...';
}

function splitNameForHero(string $value): array
{
    $parts = preg_split('/\s+/u', trim($value)) ?: [];
    if (count($parts) <= 1) {
        return [$value, ''];
    }

    $last = array_pop($parts);
    return [implode(' ', $parts), (string) $last];
}

$data = loadProfileData(__DIR__ . '/profile.json');

$profile = is_array($data['profile'] ?? null) ? $data['profile'] : [];
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$highlights = normalizedHighlights(is_array($data['highlights'] ?? null) ? $data['highlights'] : []);
$skills = is_array($data['skills'] ?? null) ? $data['skills'] : [];
$languages = is_array($data['languages'] ?? null) ? $data['languages'] : [];
$projects = is_array($data['projects'] ?? null) ? $data['projects'] : [];
$socials = is_array($data['socials'] ?? null) ? $data['socials'] : [];

$name = (string) ($profile['name'] ?? 'Nguyễn Minh Huy');
$brand = (string) ($profile['brand'] ?? 'MinhHuyDev');
$alias = (string) ($profile['alias'] ?? 'm008v');
$headline = (string) ($profile['headline'] ?? 'Một portfolio cá nhân cho dev thích ship sản phẩm thật.');
$summary = (string) ($profile['summary'] ?? $headline);
$location = (string) ($profile['location'] ?? 'Việt Nam');
$micro_card = (string) ($profile['micro-card'] ?? '');
$country = (string) ($profile['country'] ?? 'Việt Nam');
$birthday = (string) ($profile['birthday'] ?? '');
$email = (string) ($profile['email'] ?? '');
$avatar = safeImageUrl($profile['avatar'] ?? '');
$githubUrl = '#';
$primarySocial = '#';

foreach ($socials as $social) {
    $label = strtolower((string) ($social['label'] ?? ''));
    $url = (string) ($social['url'] ?? '#');
    if ($primarySocial === '#') {
        $primarySocial = $url;
    }
    if ($label === 'github') {
        $githubUrl = $url;
    }
}

$topSkills = array_slice($skills, 0, 6);
$topProjects = array_slice($projects, 0, 8);
$displayStats = array_slice($stats, 0, 3);
[$heroNameLine1, $heroNameLine2] = splitNameForHero($name);

$featureCards = [
    ['title' => 'Xây dựng sản phẩm', 'copy' => 'Tập trung vào những trải nghiệm có thể triển khai thực tế, có cấu trúc rõ ràng và dễ phát triển tiếp.'],
    ['title' => 'AI + RAG', 'copy' => 'Kết hợp mô hình ngôn ngữ, truy hồi tài liệu và workflow học tập để tạo ra sản phẩm có chiều sâu sử dụng.'],
    ['title' => 'Backend thực dụng', 'copy' => 'Làm việc với PHP, Python, MySQL, API và automation theo hướng ổn định, dễ vận hành và dễ mở rộng.'],
    ['title' => 'Thiết kế giao diện', 'copy' => 'Ưu tiên bố cục rõ nhịp, nội dung dễ đọc và trải nghiệm nhất quán trên desktop lẫn mobile.'],
    ['title' => 'Học nhanh, thích nghi nhanh', 'copy' => 'Khi gặp domain mới, bắt đầu từ dữ liệu, luồng chạy và ngữ cảnh thực tế trước khi đưa ra giải pháp.'],
    ['title' => 'Dễ bảo trì', 'copy' => 'Tổ chức dữ liệu, escape output và giữ cấu trúc gọn để dự án vẫn dễ đọc khi quay lại sau này.'],
];

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $name,
    'alternateName' => [$brand, $alias],
    'email' => $email,
    'image' => $avatar,
    'url' => 'https://nqminkhuy.com/',
    'sameAs' => array_values(array_filter(array_map(static fn ($social): string => safeUrl($social['url'] ?? '', ''), $socials))),
    'knowsAbout' => array_values(array_filter(array_map(static fn ($skill): string => (string) ($skill['name'] ?? ''), $skills))),
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($name) ?> | <?= h($brand) ?> Portfolio</title>
    <meta name="description" content="<?= h(words($summary, 28)) ?>">
    <meta name="author" content="<?= h($brand) ?>">
    <meta name="theme-color" content="#000000">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="<?= h($name) ?> | <?= h($brand) ?> Portfolio">
    <meta property="og:description" content="<?= h(words($summary, 28)) ?>">
    <meta name="keywords" content="<?= h($name) ?>, <?= h($brand) ?>, <?= h($alias) ?>, web developer, portfolio">
    <meta property="og:site_name" content="<?= h($brand) ?> Portfolio">
    <meta property="og:url" content="https://nqminkhuy.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($name) ?> | <?= h($brand) ?> Portfolio">
    <meta name="twitter:description" content="<?= h(words($summary, 28)) ?>">
    <link rel="canonical" href="https://nqminkhuy.com/">
    <link rel="manifest" href="manifest.json?v=<?= filemtime(__DIR__ . '/manifest.json') ?>">
    <link rel="icon" type="image/png" href="assets/images/m008v.png">
    <link rel="apple-touch-icon" href="assets/images/m008v.png">
    <meta property="og:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <meta name="twitter:image" content="https://nqminkhuy.com/assets/images/m008v.png">
    <link rel="stylesheet" href="assets/css/fonts.css?v=<?= filemtime(__DIR__ . '/assets/css/fonts.css') ?>">
    <script type="application/ld+json" nonce="<?= h($nonce) ?>"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>
    <div class="page-intro" aria-hidden="true">
        <div class="page-intro__inner">

            <div class="page-intro__npm-spinner">INITIALIZING EXPERIENCE...</div>
        </div>
    </div>

    <a class="skip-link" href="#main">Bỏ qua menu</a>

    <header class="topbar">
        <div class="shell nav">
            <a class="wordmark" href="#top" aria-label="<?= h($brand) ?> home">
                <img src="assets/images/m008v-logo.png" alt="<?= h($brand) ?> logo">
            </a>

            <nav class="nav-links" id="navLinks" aria-label="Điều hướng chính">

                <a href="#about" data-spy>About</a>
                <a href="#stack" data-spy>Stack</a>
                <a href="#projects" data-spy>Projects</a>
                <a href="#contact" data-spy>Contact</a>
            </nav>

            <div class="nav-action">

                <button class="menu-btn" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="navLinks" data-menu>
                    <span class="menu-btn__box" aria-hidden="true">
                        <span class="menu-btn__line"></span>
                        <span class="menu-btn__line"></span>
                        <span class="menu-btn__line"></span>
                    </span>
                </button>
            </div>
        </div>
    </header>

    <main id="main" class="shell page-frame">
        <span class="frame-node node-tl" aria-hidden="true"></span>
        <span class="frame-node node-tr" aria-hidden="true"></span>
        <span class="frame-node node-bl" aria-hidden="true"></span>
        <span class="frame-node node-br" aria-hidden="true"></span>

        <section class="announce motion-stack" id="top" aria-label="Thông báo">
            <div class="announce-left">
                <span class="command-key">⌘</span>
                <strong id="sys-realtime">realtime: --:--:--</strong>
            </div>
            <div class="announce-right">
                <span class="deal">Stack</span>
                <?php foreach (array_slice($skills, 0, 3) as $skill): ?>
                    <span><?= h($skill['name'] ?? '') ?> <strong><?= h((string) ($skill['level'] ?? '')) ?>×</strong></span>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="hero motion-stack" aria-label="Hero">
            <div class="hero-copy">
                <h1>
                    <span class="hero-name-line"><?= h($heroNameLine1) ?></span>
                    <?php if ($heroNameLine2 !== ''): ?>
                        <span class="hero-name-line"><?= h($heroNameLine2) ?></span>
                    <?php endif; ?>
                </h1>
                <p class="hero-lead"><?= h($headline) ?></p>

                <div class="hero-actions">
                    <a class="command-pill" href="https://api.nqminkhuy.com/">
                        <span>API Documents ping --brief</span>
                        <span class="copy-icon" aria-hidden="true"></span>
                    </a>
                    <a class="plain-cta" href="#projects">Deeptalk</a>
                </div>

                <?php if ($displayStats !== []): ?>
                    <p class="hero-proof">
                        <?php foreach ($displayStats as $index => $stat): ?>
                            <?php if ($index > 0): ?> · <?php endif; ?>
                            <strong><?= h($stat['value'] ?? '') ?></strong> <?= h($stat['label'] ?? '') ?>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
                <p class="micro"><?= h($micro_card) ?></p>
            </div>

            <div class="visual-grid" aria-label="Profile visual">
                <?php for ($tile = 1; $tile <= 11; $tile++): ?>
                    <span class="tile tile-<?= $tile ?>" aria-hidden="true"></span>
                <?php endfor; ?>

                <aside class="profile-card motion-card">
                    <div class="terminal-bar">
                        <span>~/m008v/profile</span>
                        <span class="dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    </div>
                    <div class="terminal-content">
                        <div class="avatar-row">
                            <?php if ($avatar !== ''): ?>
                                <img class="avatar" src="<?= h($avatar) ?>" alt="Avatar của <?= h($name) ?>" loading="eager" decoding="async">
                            <?php else: ?>
                                <div class="avatar avatar-fallback" aria-hidden="true">m</div>
                            <?php endif; ?>
                            <div>
                                <h2><?= h($name) ?></h2>
                                <p><?= h($brand) ?> / <?= h($alias) ?></p>
                            </div>
                        </div>
                        <ul class="terminal-list">
                            <li><span>where</span><strong><?= h($location) ?></strong></li>
                            <?php if ($birthday !== ''): ?>
                                <li><span>born</span><strong><?= h($birthday) ?></strong></li>
                            <?php endif; ?>
                            <?php if ($email !== ''): ?>
                                <li><span>mail</span><strong><?= h($email) ?></strong></li>
                            <?php endif; ?>
                            <li><span>mode</span><strong>ship · fix · test · refactor</strong></li>
                        </ul>
                    </div>
                </aside>
            </div>
        </section>


        <div class="quote-strip motion-stack">
            <strong>
                <span class="quote-mark">“</span>
                <span class="quote-text">Cách tốt nhất để dự đoán tương lai là tự mình phát minh ra nó.</span>
            </strong>
        </div>


        <section class="section split-section motion-stack" id="about">
            <div class="split-copy">
                <h2>A brief overview of my background.</h2>
                <p><?= h($summary) ?></p>
            </div>
            <div class="console-demo">
                <div class="console-box">
                    <div class="terminal-bar">
                        <span>~/m008v/information/</span>
                        <span class="dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    </div>
                    <pre><span class="line-dim">> my name ping -alias</span>
     Nguyen Minh Huy; alias: m008v;
<span class="line-yellow">> birthday</span>
     30/10/2007
<span class="line-blue">> studies</span>
     Artificial Intelligence student at Can Tho University.
<span class="line-green">> hobbies</span>
     Reading, writing, coding, and exploring new technologies.
<span class="line-red">> language</span>
     <?= h(implode(' | ', array_map(static fn ($lang): string => (string) ($lang['name'] ?? '') . ' (' . ($lang['description'] ?? '') . ')', $languages))) ?>
                </div>
            </div>
        </section>

        <section class="section motion-stack" id="stack">
            <div class="section-head">
                <div class="section-title">
                    <h2>weapon_of_choice.</h2>
                </div>
                <div class="section-intro">
                    <p>Ai sợ thì đi về?</p>
                    <?php if ($githubUrl !== '#'): ?>
                        <a class="pill-btn" href="<?= h(safeUrl($githubUrl)) ?>" target="_blank" rel="noopener noreferrer">
                            GitHub
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="margin-left: 12px;">
                                <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($topSkills !== []): ?>
                <div class="skill-grid">
                    <?php foreach ($topSkills as $skill): ?>
                        <?php
                            $level = max(0, min(100, (int) ($skill['level'] ?? 0)));
                            $icon = safeImageUrl($skill['icon'] ?? '');
                        ?>
                        <article class="skill-card motion-card">
                            <div class="skill-top">
                                <span class="skill-icon">
                                    <?php if ($icon !== ''): ?>
                                        <img src="<?= h($icon) ?>" alt="Logo <?= h($skill['name'] ?? 'kỹ năng') ?>" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span aria-hidden="true">#</span>
                                    <?php endif; ?>
                                </span>
                                <span class="skill-name">
                                    <strong><?= h($skill['name'] ?? '') ?></strong>
                                    <small><?= h($skill['group'] ?? '') ?></small>
                                </span>
                                <span class="skill-percent" data-target="<?= h((string) $level) ?>">0%</span>
                            </div>
                            <div class="bar" aria-label="<?= h($skill['name'] ?? 'Kỹ năng') ?> <?= h((string) $level) ?>%">
                                <i class="skill-bar-fill" data-target="<?= h((string) $level) ?>" style="width: 0%;"></i>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="section motion-stack" id="projects">
            <div class="section-head">
                <div class="section-title">
                    <h2>works i've shipped.</h2>
                </div>
                <div class="section-intro">
                    <p>My Role • Tech Stack • Links</p>
                    <a class="pill-btn pill-btn-white" href="#contact">Contact ›</a>
                </div>
            </div>

            <?php if ($projects !== []): ?>
                <div class="project-grid">
                    <?php foreach ($projects as $index => $project): ?>
                        <?php $projectUrl = safeUrl($project['url'] ?? '#'); ?>
                        <article class="project-card motion-card">
                            <div class="project-meta">
                                <span><?= h(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                <span><?= h($project['tag'] ?? 'Project') ?></span>
                            </div>
                            <h3><?= h($project['title'] ?? '') ?></h3>
                            <p><?= h($project['description'] ?? '') ?></p>
                            <?php if (is_array($project['stack'] ?? null)): ?>
                                <ul class="stack">
                                    <?php foreach ($project['stack'] as $tech): ?>
                                        <li><?= h($tech) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php if ($projectUrl !== '#'): ?>
                                <a class="project-link" href="<?= h($projectUrl) ?>" target="_blank" rel="noopener noreferrer">Open project ›</a>
                            <?php else: ?>
                                <span class="project-link" aria-disabled="true">Updating</span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="section contact-grid motion-stack" id="contact">
            <div class="contact-copy">
                <h2>Let's build something crazy together.</h2>
                <p>Nếu bạn muốn trao đổi về sản phẩm, hợp tác phát triển hoặc chỉ đơn giản là kết nối chuyên môn, tớ rất sẵn lòng!</p>
                <?php if ($email !== ''): ?>
                    <a class="command-pill" href="mailto:<?= h($email) ?>">
                        <span><?= h($email) ?></span>
                        <span class="copy-icon" aria-hidden="true"></span>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($socials !== []): ?>
                <div class="social-panel" aria-label="Mạng xã hội">
                    <?php foreach ($socials as $social): ?>
                        <?php
                            $socialIcon = safeImageUrl($social['icon'] ?? '');
                            $socialUrl = safeUrl($social['url'] ?? '#');
                        ?>
                        <a class="social-card motion-card" href="<?= h($socialUrl) ?>" target="_blank" rel="noopener noreferrer">
                            <?php if ($socialIcon !== ''): ?>
                                <img src="<?= h($socialIcon) ?>" alt="" loading="lazy" decoding="async" aria-hidden="true">
                            <?php endif; ?>
                            <span>
                                <strong><?= h($social['label'] ?? '') ?></strong>
                                <span><?= h($social['handle'] ?? '') ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="final-cta motion-stack">
            <div>
                <h2>@m008v - Portfolio (⁠ツ⁠)</h2>
            </div>
            <a class="pill-btn pill-btn-white" href="<?= h($email !== '' ? 'mailto:' . $email : safeUrl($primarySocial)) ?>">Start ›</a>
        </section>
    </main>

    <footer class="shell footer">
        <span>© <?= h(date('Y')) ?> <?= h($brand) ?> / @m008v</span>
        <span>Pls don't steal.ツ⁠</span>
    </footer>

    <button class="to-top" type="button" aria-label="Lên đầu trang" data-top>↑</button>
    
    <script src="assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>" defer nonce="<?= h($nonce) ?>"></script>
    <script disable-devtool-auto nonce="<?= h($nonce) ?>">
        !function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e="undefined"!=typeof globalThis?globalThis:e||self).DisableDevtool=t()}(this,function(){"use strict";function i(e){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function r(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function u(e,t,n){t&&r(e.prototype,t),n&&r(e,n),Object.defineProperty(e,"prototype",{writable:!1})}function e(e,t,n){t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n}function c(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),Object.defineProperty(e,"prototype",{writable:!1}),t&&n(e,t)}function a(e){return(a=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function n(e,t){return(n=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e})(e,t)}function U(e,t){if(t&&("object"==typeof t||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");t=e;if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}function l(n){var o=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],function(){})),!0}catch(e){return!1}}();return function(){var e,t=a(n);return U(this,o?(e=a(this).constructor,Reflect.construct(t,arguments,e)):t.apply(this,arguments))}}function f(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,o=new Array(t);n<t;n++)o[n]=e[n];return o}function s(e,t){var n,o="undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(!o){if(Array.isArray(e)||(o=function(e,t){if(e){if("string"==typeof e)return f(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);return"Map"===(n="Object"===n&&e.constructor?e.constructor.name:n)||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?f(e,t):void 0}}(e))||t&&e&&"number"==typeof e.length)return o&&(e=o),n=0,{s:t=function(){},n:function(){return n>=e.length?{done:!0}:{done:!1,value:e[n++]}},e:function(e){throw e},f:t};throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var i,r=!0,u=!1;return{s:function(){o=o.call(e)},n:function(){var e=o.next();return r=e.done,e},e:function(e){u=!0,i=e},f:function(){try{r||null==o.return||o.return()}finally{if(u)throw i}}}}function t(){if(d.url)window.location.href=d.url;else if(d.rewriteHTML)try{document.documentElement.innerHTML=d.rewriteHTML}catch(e){document.documentElement.innerText=d.rewriteHTML}else{try{window.opener=null,window.open("","_self"),window.close(),window.history.back()}catch(e){console.log(e)}setTimeout(function(){window.location.href=d.timeOutUrl||"https://theajack.github.io/disable-devtool/404.html?h=".concat(encodeURIComponent(location.host))},500)}}var d={md5:"",ondevtoolopen:t,ondevtoolclose:null,url:"",timeOutUrl:"",tkName:"ddtk",interval:500,disableMenu:!0,stopIntervalTime:5e3,clearIntervalWhenDevOpenTrigger:!1,detectors:[1,3,4,5,6,7],clearLog:!0,disableSelect:!1,disableInputSelect:!1,disableCopy:!1,disableCut:!1,disablePaste:!1,ignore:null,disableIframeParents:!0,seo:!0,rewriteHTML:""},H=["detectors","ondevtoolclose","ignore"];function q(e){var t,n=0<arguments.length&&void 0!==e?e:{};for(t in n.onDevtoolOpen&&(n.ondevtoolopen=n.onDevtoolOpen),n.onDevtoolClose&&(n.ondevtoolclose=n.onDevtoolClose),d){var o=t;void 0===n[o]||i(d[o])!==i(n[o])&&-1===H.indexOf(o)||(d[o]=n[o])}"function"==typeof d.ondevtoolclose&&!0===d.clearIntervalWhenDevOpenTrigger&&(d.clearIntervalWhenDevOpenTrigger=!1,console.warn("【DISABLE-DEVTOOL】clearIntervalWhenDevOpenTrigger 在使用 ondevtoolclose 时无效"))}function v(){return(new Date).getTime()}function h(e){var t=v();return e(),v()-t}function z(n,o){function e(t){return function(){n&&n();var e=t.apply(void 0,arguments);return o&&o(),e}}var t=window.alert,i=window.confirm,r=window.prompt;try{window.alert=e(t),window.confirm=e(i),window.prompt=e(r)}catch(e){}}var p,y,B,b={iframe:!1,pc:!1,qqBrowser:!1,firefox:!1,macos:!1,edge:!1,oldEdge:!1,ie:!1,iosChrome:!1,iosEdge:!1,chrome:!1,seoBot:!1,mobile:!1};function W(){function e(e){return-1!==t.indexOf(e)}var t=navigator.userAgent.toLowerCase(),n=function(){var e=navigator,t=e.platform,e=e.maxTouchPoints;if("number"==typeof e)return 1<e;if("string"==typeof t){e=t.toLowerCase();if(/(mac|win)/i.test(e))return!1;if(/(android|iphone|ipad|ipod|arch)/i.test(e))return!0}return/(iphone|ipad|ipod|ios|android)/i.test(navigator.userAgent.toLowerCase())}(),o=!!window.top&&window!==window.top,i=!n,r=e("qqbrowser"),u=e("firefox"),c=e("macintosh"),a=e("edge"),l=a&&!e("chrome"),f=l||e("trident")||e("msie"),s=e("crios"),d=e("edgios"),v=e("chrome")||s,h=!n&&/(googlebot|baiduspider|bingbot|applebot|petalbot|yandexbot|bytespider|chrome\-lighthouse|moto g power)/i.test(t);Object.assign(b,{iframe:o,pc:i,qqBrowser:r,firefox:u,macos:c,edge:a,oldEdge:l,ie:f,iosChrome:s,iosEdge:d,chrome:v,seoBot:h,mobile:n})}function M(){for(var e=function(){for(var e={},t=0;t<500;t++)e["".concat(t)]="".concat(t);return e}(),t=[],n=0;n<50;n++)t.push(e);return t}function g(){d.clearLog&&B()}var K="",V=!1;function N(){var e=d.ignore;if(e){if("function"==typeof e)return e();if(0!==e.length){var t=location.href;if(K===t)return V;K=t;var n,o=!1,i=s(e);try{for(i.s();!(n=i.n()).done;){var r=n.value;if("string"==typeof r){if(-1!==t.indexOf(r)){o=!0;break}}else if(r.test(t)){o=!0;break}}}catch(e){i.e(e)}finally{i.f()}return V=o}}}var X=function(){return!1};function w(n){var t,e,o=74,i=73,r=85,u=83,c=123,a=b.macos?function(e,t){return e.metaKey&&e.altKey&&(t===i||t===o)}:function(e,t){return e.ctrlKey&&e.shiftKey&&(t===i||t===o)},l=b.macos?function(e,t){return e.metaKey&&e.altKey&&t===r||e.metaKey&&t===u}:function(e,t){return e.ctrlKey&&(t===u||t===r)};n.addEventListener("keydown",function(e){var t=(e=e||n.event).keyCode||e.which;if(t===c||a(e,t)||l(e,t))return T(n,e)},!0),t=n,d.disableMenu&&t.addEventListener("contextmenu",function(e){if("touch"!==e.pointerType)return T(t,e)}),e=n,(d.disableSelect||d.disableInputSelect)&&m(e,"selectstart"),e=n,d.disableCopy&&m(e,"copy"),e=n,d.disableCut&&m(e,"cut"),e=n,d.disablePaste&&m(e,"paste")}function m(o,e){o.addEventListener(e,function(e){if(!(t=e.target)||"INPUT"!==t.tagName&&"TEXTAREA"!==t.tagName&&"true"!==(null==(n=t.getAttribute)?void 0:n.call(t,"contenteditable"))){if(d.disableSelect)return T(o,e)}else if(d.disableInputSelect)return T(o,e);var t,n})}function T(e,t){if(!N()&&!X())return(t=t||e.event).returnValue=!1,t.preventDefault(),!1}var O,D=!1,S={};function F(e){S[e]=!1}function $(){for(var e in S)if(S[e])return D=!0;return D=!1}(A=O=O||{})[A.Unknown=-1]="Unknown",A[A.RegToString=0]="RegToString",A[A.DefineId=1]="DefineId",A[A.Size=2]="Size",A[A.DateToString=3]="DateToString",A[A.FuncToString=4]="FuncToString",A[A.Debugger=5]="Debugger",A[A.Performance=6]="Performance",A[A.DebugLib=7]="DebugLib";var k=function(){function n(e){var t=e.type,e=e.enabled,e=void 0===e||e;o(this,n),this.type=O.Unknown,this.enabled=!0,this.type=t,this.enabled=e,this.enabled&&(t=this,Q.push(t),this.init())}return u(n,[{key:"onDevToolOpen",value:function(){var e;console.warn("You don't have permission to use DEVTOOL!【type = ".concat(this.type,"】")),d.clearIntervalWhenDevOpenTrigger&&te(),window.clearTimeout(J),d.ondevtoolopen(this.type,t),e=this.type,S[e]=!0}},{key:"init",value:function(){}}]),n}(),G=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.DebugLib})}return u(t,[{key:"init",value:function(){}},{key:"detect",value:function(){var e;(!0===(null==(e=null==(e=window.eruda)?void 0:e._devTools)?void 0:e._isShow)||window._vcOrigConsole&&window.document.querySelector("#__vconsole.vc-toggle"))&&this.onDevToolOpen()}}],[{key:"isUsing",value:function(){return!!window.eruda||!!window._vcOrigConsole}}]),t}(),Y=0,J=0,Q=[],Z=0;function ee(i){function e(){l=!0}function t(){l=!1}var n,o,r,u,c,a,l=!1;function f(){(a[u]===r?o:n)()}z(e,t),n=t,o=e,void 0!==(a=document).hidden?(r="hidden",c="visibilitychange",u="visibilityState"):void 0!==a.mozHidden?(r="mozHidden",c="mozvisibilitychange",u="mozVisibilityState"):void 0!==a.msHidden?(r="msHidden",c="msvisibilitychange",u="msVisibilityState"):void 0!==a.webkitHidden&&(r="webkitHidden",c="webkitvisibilitychange",u="webkitVisibilityState"),a.removeEventListener(c,f,!1),a.addEventListener(c,f,!1),Y=window.setInterval(function(){if(!(i.isSuspend||l||N())){var e,t,n=s(Q);try{for(n.s();!(e=n.n()).done;){var o=e.value;F(o.type),o.detect(Z++)}}catch(e){n.e(e)}finally{n.f()}g(),"function"==typeof d.ondevtoolclose&&(t=D,!$()&&t&&d.ondevtoolclose())}},d.interval),J=setTimeout(function(){b.pc||G.isUsing()||te()},d.stopIntervalTime)}function te(){window.clearInterval(Y)}var P=8;function ne(e){for(var t=function(e,t){e[t>>5]|=128<<t%32,e[14+(t+64>>>9<<4)]=t;for(var n=1732584193,o=-271733879,i=-1732584194,r=271733878,u=0;u<e.length;u+=16){var c=n,a=o,l=i,f=r;n=E(n,o,i,r,e[u+0],7,-680876936),r=E(r,n,o,i,e[u+1],12,-389564586),i=E(i,r,n,o,e[u+2],17,606105819),o=E(o,i,r,n,e[u+3],22,-1044525330),n=E(n,o,i,r,e[u+4],7,-176418897),r=E(r,n,o,i,e[u+5],12,1200080426),i=E(i,r,n,o,e[u+6],17,-1473231341),o=E(o,i,r,n,e[u+7],22,-45705983),n=E(n,o,i,r,e[u+8],7,1770035416),r=E(r,n,o,i,e[u+9],12,-1958414417),i=E(i,r,n,o,e[u+10],17,-42063),o=E(o,i,r,n,e[u+11],22,-1990404162),n=E(n,o,i,r,e[u+12],7,1804603682),r=E(r,n,o,i,e[u+13],12,-40341101),i=E(i,r,n,o,e[u+14],17,-1502002290),o=E(o,i,r,n,e[u+15],22,1236535329),n=I(n,o,i,r,e[u+1],5,-165796510),r=I(r,n,o,i,e[u+6],9,-1069501632),i=I(i,r,n,o,e[u+11],14,643717713),o=I(o,i,r,n,e[u+0],20,-373897302),n=I(n,o,i,r,e[u+5],5,-701558691),r=I(r,n,o,i,e[u+10],9,38016083),i=I(i,r,n,o,e[u+15],14,-660478335),o=I(o,i,r,n,e[u+4],20,-405537848),n=I(n,o,i,r,e[u+9],5,568446438),r=I(r,n,o,i,e[u+14],9,-1019803690),i=I(i,r,n,o,e[u+3],14,-187363961),o=I(o,i,r,n,e[u+8],20,1163531501),n=I(n,o,i,r,e[u+13],5,-1444681467),r=I(r,n,o,i,e[u+2],9,-51403784),i=I(i,r,n,o,e[u+7],14,1735328473),o=I(o,i,r,n,e[u+12],20,-1926607734),n=j(n,o,i,r,e[u+5],4,-378558),r=j(r,n,o,i,e[u+8],11,-2022574463),i=j(i,r,n,o,e[u+11],16,1839030562),o=j(o,i,r,n,e[u+14],23,-35309556),n=j(n,o,i,r,e[u+1],4,-1530992060),r=j(r,n,o,i,e[u+4],11,1272893353),i=j(i,r,n,o,e[u+7],16,-155497632),o=j(o,i,r,n,e[u+10],23,-1094730640),n=j(n,o,i,r,e[u+13],4,681279174),r=j(r,n,o,i,e[u+0],11,-358537222),i=j(i,r,n,o,e[u+3],16,-722521979),o=j(o,i,r,n,e[u+6],23,76029189),n=j(n,o,i,r,e[u+9],4,-640364487),r=j(r,n,o,i,e[u+12],11,-421815835),i=j(i,r,n,o,e[u+15],16,530742520),o=j(o,i,r,n,e[u+2],23,-995338651),n=L(n,o,i,r,e[u+0],6,-198630844),r=L(r,n,o,i,e[u+7],10,1126891415),i=L(i,r,n,o,e[u+14],15,-1416354905),o=L(o,i,r,n,e[u+5],21,-57434055),n=L(n,o,i,r,e[u+12],6,1700485571),r=L(r,n,o,i,e[u+3],10,-1894986606),i=L(i,r,n,o,e[u+10],15,-1051523),o=L(o,i,r,n,e[u+1],21,-2054922799),n=L(n,o,i,r,e[u+8],6,1873313359),r=L(r,n,o,i,e[u+15],10,-30611744),i=L(i,r,n,o,e[u+6],15,-1560198380),o=L(o,i,r,n,e[u+13],21,1309151649),n=L(n,o,i,r,e[u+4],6,-145523070),r=L(r,n,o,i,e[u+11],10,-1120210379),i=L(i,r,n,o,e[u+2],15,718787259),o=L(o,i,r,n,e[u+9],21,-343485551),n=C(n,c),o=C(o,a),i=C(i,l),r=C(r,f)}return Array(n,o,i,r)}(function(e){for(var t=Array(),n=(1<<P)-1,o=0;o<e.length*P;o+=P)t[o>>5]|=(e.charCodeAt(o/P)&n)<<o%32;return t}(e),e.length*P),n="0123456789abcdef",o="",i=0;i<4*t.length;i++)o+=n.charAt(t[i>>2]>>i%4*8+4&15)+n.charAt(t[i>>2]>>i%4*8&15);return o}function x(e,t,n,o,i,r){return C((t=C(C(t,e),C(o,r)))<<i|t>>>32-i,n)}function E(e,t,n,o,i,r,u){return x(t&n|~t&o,e,t,i,r,u)}function I(e,t,n,o,i,r,u){return x(t&o|n&~o,e,t,i,r,u)}function j(e,t,n,o,i,r,u){return x(t^n^o,e,t,i,r,u)}function L(e,t,n,o,i,r,u){return x(n^(t|~o),e,t,i,r,u)}function C(e,t){var n=(65535&e)+(65535&t);return(e>>16)+(t>>16)+(n>>16)<<16|65535&n}var A=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.RegToString,enabled:b.qqBrowser||b.firefox})}return u(t,[{key:"init",value:function(){var t=this;this.lastTime=0,this.reg=/./,p(this.reg),this.reg.toString=function(){var e;return b.qqBrowser?(e=(new Date).getTime(),t.lastTime&&e-t.lastTime<100?t.onDevToolOpen():t.lastTime=e):b.firefox&&t.onDevToolOpen(),""}}},{key:"detect",value:function(){p(this.reg)}}]),t}(),oe=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.DefineId})}return u(t,[{key:"init",value:function(){var e=this;this.div=document.createElement("div"),this.div.__defineGetter__("id",function(){e.onDevToolOpen()}),Object.defineProperty(this.div,"id",{get:function(){e.onDevToolOpen()}})}},{key:"detect",value:function(){p(this.div)}}]),t}(),ie=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.Size,enabled:!b.iframe&&!b.edge})}return u(t,[{key:"init",value:function(){var e=this;this.checkWindowSizeUneven(),window.addEventListener("resize",function(){setTimeout(function(){e.checkWindowSizeUneven()},100)},!0)}},{key:"detect",value:function(){}},{key:"checkWindowSizeUneven",value:function(){var e=function(){if(re(window.devicePixelRatio))return window.devicePixelRatio;var e=window.screen;return!!(re(e)&&e.deviceXDPI&&e.logicalXDPI)&&e.deviceXDPI/e.logicalXDPI}();if(!1!==e){var t=200<window.outerWidth-window.innerWidth*e,e=300<window.outerHeight-window.innerHeight*e;if(t||e)return this.onDevToolOpen(),!1;F(this.type)}return!0}}]),t}();function re(e){return null!=e}var _,ue=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.DateToString,enabled:!b.iosChrome&&!b.iosEdge})}return u(t,[{key:"init",value:function(){var e=this;this.count=0,this.date=new Date,this.date.toString=function(){return e.count++,""}}},{key:"detect",value:function(){this.count=0,p(this.date),g(),2<=this.count&&this.onDevToolOpen()}}]),t}(),ce=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.FuncToString,enabled:!b.iosChrome&&!b.iosEdge})}return u(t,[{key:"init",value:function(){var e=this;this.count=0,this.func=function(){},this.func.toString=function(){return e.count++,""}}},{key:"detect",value:function(){this.count=0,p(this.func),g(),2<=this.count&&this.onDevToolOpen()}}]),t}(),ae=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.Debugger,enabled:b.iosChrome||b.iosEdge})}return u(t,[{key:"detect",value:function(){var e=v();100<v()-e&&this.onDevToolOpen()}}]),t}(),le=function(){c(n,k);var t=l(n);function n(){var e;return o(this,n),(e=t.call(this,{type:O.Performance,enabled:b.chrome||!b.mobile})).count=0,e}return u(n,[{key:"init",value:function(){this.maxPrintTime=0,this.largeObjectArray=M()}},{key:"detect",value:function(){var e=this,t=h(function(){y(e.largeObjectArray)}),n=h(function(){p(e.largeObjectArray)});if(this.maxPrintTime=Math.max(this.maxPrintTime,n),g(),0===t||0===this.maxPrintTime)return!1;t>10*this.maxPrintTime&&(2<=this.count?this.onDevToolOpen():(this.count++,this.detect()))}}]),n}(),fe=(e(_={},O.RegToString,A),e(_,O.DefineId,oe),e(_,O.Size,ie),e(_,O.DateToString,ue),e(_,O.FuncToString,ce),e(_,O.Debugger,ae),e(_,O.Performance,le),e(_,O.DebugLib,G),_);var R=Object.assign(function(e){function t(){var e=0<arguments.length&&void 0!==arguments[0]?arguments[0]:"";return{success:!e,reason:e}}var n;if(R.isRunning)return t("already running");if(W(),n=window.console||{log:function(){},table:function(){},clear:function(){}},B=b.ie?(p=function(){return n.log.apply(n,arguments)},y=function(){return n.table.apply(n,arguments)},function(){return n.clear()}):(p=n.log,y=n.table,n.clear),q(e),d.md5&&ne(function(e){var t=window.location.search,n=window.location.hash;if(""!==(t=""===t&&""!==n?"?".concat(n.split("?")[1]):t)&&void 0!==t){n=new RegExp("(^|&)"+e+"=([^&]*)(&|$)","i"),e=t.substr(1).match(n);if(null!=e)return unescape(e[2])}return""}(d.tkName))===d.md5)return t("token passed");if(d.seo&&b.seoBot)return t("seobot");R.isRunning=!0,ee(R);var o=R,i=(X=function(){return o.isSuspend},window.top),r=window.parent;if(w(window),d.disableIframeParents&&i&&r&&i!==window){for(;r!==i;)w(r),r=r.parent;w(i)}return("all"===d.detectors?Object.keys(fe):d.detectors).forEach(function(e){new fe[e]}),t()},{isRunning:!1,isSuspend:!1,md5:ne,version:"0.3.9",DetectorType:O,isDevToolOpened:$});A=function(){if("undefined"==typeof window||!window.document)return null;var n=document.querySelector("[disable-devtool-auto]");if(!n)return null;var o=["disable-menu","disable-select","disable-copy","disable-cut","disable-paste","clear-log"],i=["interval"],r={};return["md5","url","tk-name","detectors"].concat(o,i).forEach(function(e){var t=n.getAttribute(e);null!==t&&(-1!==i.indexOf(e)?t=parseInt(t):-1!==o.indexOf(e)?t="false"!==t:"detector"===e&&"all"!==t&&(t=t.split(" ")),r[function(e){if(-1===e.indexOf("-"))return e;var t=!1;return e.split("").map(function(e){return"-"===e?(t=!0,""):t?(t=!1,e.toUpperCase()):e}).join("")}(e)]=t)}),r}();return A&&R(A),R});
    </script>
    <?php renderJavaScriptRuntimeRefreshScript(); ?>
</body>
</html>
