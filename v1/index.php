<?php
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
    <title>MinhHuyDev Profile - AntiBot Request</title>
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
            <div>
                <!-- <p>Đang xác thực trình duyệt...</p> -->
                <p style="margin-top:8px;font-size:0.78rem;opacity:0.5;word-break:break-all;"><?= substr($encodedRuntimeToken, 0, 20) ?></p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var token = "<?= $encodedToken ?>";
            var runtimeToken = "<?= $encodedRuntimeToken ?>";
            document.cookie = "mh_browser_proof=" + encodeURIComponent(token) + "; Max-Age=21600; Path=/; SameSite=Lax<?= $secureSuffix ?>";
            document.cookie = "mh_js_runtime=" + encodeURIComponent(runtimeToken) + "; Max-Age=<?= $runtimeCookieMaxAge ?>; Path=/; SameSite=Lax<?= $secureSuffix ?>";
            window.setTimeout(function () {
                window.location.reload();
            }, 180);
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
    $runtimeToken = getQueuedJavaScriptRuntimeRefreshToken();

    if ($runtimeToken === '') {
        return;
    }

    $encodedRuntimeToken = htmlspecialchars($runtimeToken, ENT_QUOTES, 'UTF-8');
    $secureSuffix = isHttpsRequest() ? '; Secure' : '';
    $runtimeCookieMaxAge = getJavaScriptRuntimeWindowSeconds() + 60;
    ?>
    <script>
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

function loadProfileData(string $path): array
{
    $raw = @file_get_contents($path);

    if (!is_string($raw) || $raw === '') {
        throw new RuntimeException('Khong doc duoc file JSON thong tin.');
    }

    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('Noi dung JSON thong tin khong hop le.');
    }

    return $decoded;
}

function expandProfileHighlights(array $highlights): array
{
    foreach ($highlights as &$highlight) {
        if (!is_array($highlight)) {
            continue;
        }

        if (($highlight['period'] ?? '') === '__CURRENT_MONTH_YEAR__') {
            $from = (string) ($highlight['period_from'] ?? '');
            $highlight['period'] = $from !== '' ? ($from . ' - ' . date('m/Y')) : date('m/Y');
        }
    }
    unset($highlight);

    return $highlights;
}

$profileData = loadProfileData(__DIR__ . '/profile.json');

$profile = (array) ($profileData['profile'] ?? []);
$stats = is_array($profileData['stats'] ?? null) ? $profileData['stats'] : [];
$highlights = expandProfileHighlights(is_array($profileData['highlights'] ?? null) ? $profileData['highlights'] : []);
$skills = is_array($profileData['skills'] ?? null) ? $profileData['skills'] : [];
$languages = is_array($profileData['languages'] ?? null) ? $profileData['languages'] : [];
$projects = is_array($profileData['projects'] ?? null) ? $profileData['projects'] : [];
$socials = is_array($profileData['socials'] ?? null) ? $profileData['socials'] : [];

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function assetVersion(string $relativePath): string
{
    $normalized = ltrim($relativePath, '/\\');
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $normalized);
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();

    return $normalized . '?v=' . $version;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($profile['name']) ?> | <?= h($profile['brand']) ?> Profile - CV</title>
    <meta name="description" content="Nguyễn Minh Huy - Profile của tui ở đây nèeeee!">
    <meta name="keywords" content="Nguyễn Minh Huy, MinhHuyDev, HokariYai, web developer">
    <meta name="author" content="<?= h($profile['brand']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NguyenMinhHuy Profile">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="NguyenMinhHuy | Profile">
    <meta property="og:description" content="Nguyễn Minh Huy - Profile của tui ở đây nèeeee!">
	<link rel="canonical" href="https://nqminkhuy.com/information/">
	<meta property="og:url" content="/">
	<meta property="og:image" content="/">
	<meta property="og:image:width" content="1200">
	<meta property="og:image:height" content="630">
    <meta property="og:image" content="https://nqminkhuy.com/information/assets/images/nmh-logo.jpg">
    <meta name="theme-color" content="#f3ebdf" media="(prefers-color-scheme: light)" id="theme-color-meta-light">
    <meta name="theme-color" content="#0a101c" media="(prefers-color-scheme: dark)" id="theme-color-meta-dark">
    <meta name="theme-color" content="#0a101c" id="theme-color-meta">
    <link rel="icon" href="https://nqminkhuy.com/information/assets/images/nmh-logo.jpg">
    <link rel="apple-touch-icon" href="https://nqminkhuy.com/information/assets/images/nmh-logo.jpg">
    <link rel="manifest" href="<?= h(assetVersion('manifest.json')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($profile['name']) ?> | <?= h($profile['brand']) ?>">
    <meta name="twitter:description" content="Portfolio của Nguyễn Minh Huy.">
    <meta name="twitter:image" content="https://nqminkhuy.com/og.php">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    keyframes: {
                        floatSlow: {
                            '0%, 100%': { transform: 'translate3d(0, 0, 0)' },
                            '50%': { transform: 'translate3d(0, -22px, 0)' }
                        },
                        drift: {
                            '0%, 100%': { transform: 'translate3d(0, 0, 0) scale(1)' },
                            '50%': { transform: 'translate3d(18px, -10px, 0) scale(1.08)' }
                        }
                    },
                    animation: {
                        'float-slow': 'floatSlow 9s ease-in-out infinite',
                        drift: 'drift 12s ease-in-out infinite'
                    }
                }
            }
        };
    </script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="<?= h(assetVersion('assets/js/theme-init.js')) ?>"></script>
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Person",
            "name": "<?= h($profile['name']) ?>",
            "alternateName": ["<?= h($profile['brand']) ?>", "<?= h($profile['alias']) ?>"],
            "image": "<?= h($profile['avatar']) ?>",
            "birthDate": "2007-10-30",
            "address": {
                "@type": "PostalAddress",
                "addressLocality": "Bạc Liêu",
                "addressCountry": "VN"
            },
            "email": "<?= h($profile['email']) ?>",
            "sameAs": [
                <?php foreach ($socials as $index => $social): ?>
                "<?= h($social['url']) ?>"<?= $index < count($socials) - 1 ? ',' : '' ?>
                <?php endforeach; ?>
            ]
        }
    </script>
    <link rel="stylesheet" href="<?= h(assetVersion('assets/css/style.css')) ?>">
</head>
<body>
    <div class="page-loader" aria-hidden="true">
        <div class="page-loader-inner">
            <div class="premium-spinner">
                <div class="atom-orbit orbit-1"><div class="atom-electron"></div></div>
                <div class="atom-orbit orbit-2"><div class="atom-electron"></div></div>
                <div class="atom-orbit orbit-3"><div class="atom-electron"></div></div>
                <div class="atom-core"></div>
            </div>
        </div>
    </div>

    <div class="theme-transition-layer" aria-hidden="true">
        <div class="theme-transition-core">
            <span class="theme-transition-icon theme-transition-icon-sun">
                <svg viewBox="0 0 24 24" focusable="false">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2.5v2.2M12 19.3v2.2M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.9 19.1l1.6-1.6M17.5 6.5l1.6-1.6"></path>
                </svg>
            </span>
            <span class="theme-transition-icon theme-transition-icon-moon">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M20 14.5A8.5 8.5 0 1 1 9.5 4 6.8 6.8 0 0 0 20 14.5Z"></path>
                </svg>
            </span>
            <span class="theme-transition-ring"></span>
        </div>
    </div>

    <div class="page-shell">
        <header class="site-header">
            <a class="brand" href="#hero" aria-label="<?= h($profile['brand']) ?>">
                <span class="brand-text"><?= h($profile['brand']) ?></span>
            </a>
            <nav class="site-nav" id="site-nav">
                <a class="section-link" href="#about">Giới thiệu</a>
                <a class="section-link" href="#skills">Kỹ năng</a>
                <a class="section-link" href="#projects">Dự án</a>
                <a href="#contact" class="nav-cta">Liên hệ</a>
            </nav>
            <button class="theme-toggle" type="button" aria-pressed="true" aria-label="Chuyển sang giao diện sáng" data-theme="dark">
                <span class="theme-icon theme-icon-sun" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2.5v2.2M12 19.3v2.2M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.9 19.1l1.6-1.6M17.5 6.5l1.6-1.6"></path>
                    </svg>
                </span>
                <span class="theme-icon theme-icon-moon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M20 14.5A8.5 8.5 0 1 1 9.5 4 6.8 6.8 0 0 0 20 14.5Z"></path>
                    </svg>
                </span>
            </button>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Mở menu điều hướng">
                <span></span>
                <span></span>
            </button>
        </header>

        <main>
            <section class="section hero" id="hero">
                <div data-reveal>
                    <span class="eyebrow">build v2026.<?php echo date('m'); ?></span>
                    <h1><?= h($profile['name']) ?></h1>
                    <p class="hero-alias"><?= h($profile['brand']) ?> / <?= h($profile['alias']) ?></p>
                    <p class="lead"><?= h($profile['headline']) ?></p>
                    <div class="button-row">
                        <a class="button button-primary button-led" href="https://api.nqminkhuy.com/" target="_blank" rel="noreferrer">Tài liệu API</a>
                        <a class="button button-primary button-dashed" href="https://deeptalk.nqminkhuy.com/" rel="noreferrer">Deeptalk with self</a>
                    </div>
                    <div class="stat-grid">
                        <?php foreach ($stats as $stat): ?>
                            <article class="stat-card">
                                <div class="stat-value"><?= h($stat['value']) ?></div>
                                <div class="stat-label"><?= h($stat['label']) ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hero-visual" data-reveal>
                    <article class="profile-panel">
                        <div class="profile-top">
                            <img class="avatar" src="<?= h($profile['avatar']) ?>" alt="Avatar của <?= h($profile['name']) ?>" loading="eager" decoding="async">
                            <div>
                                <h2 class="profile-name"><?= h($profile['name']) ?></h2>
                                <p class="profile-role"><?= h($profile['brand']) ?> / <?= h($profile['alias']) ?></p>
                            </div>
                        </div>
                        <div class="status-widget" role="status" aria-live="polite">
                            <span class="status-head">
                                <span class="status-signal" aria-hidden="true"></span>
                                <span class="label">Trạng thái</span>
                            </span>
                            <strong class="status-value">Còn sống tốt</strong>
                        </div>
                        <div class="profile-meta">
                            <div>
                                <span class="meta-head">
                                    <span class="meta-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false">
                                            <path d="M12 21s6-5.5 6-10a6 6 0 1 0-12 0c0 4.5 6 10 6 10Z"></path>
                                            <circle cx="12" cy="11" r="2.4"></circle>
                                        </svg>
                                    </span>
                                    <span class="label">Vị trí</span>
                                </span>
                                <span class="meta-value"><?= h($profile['location']) ?></span>
                            </div>
                            <div>
                                <span class="meta-head">
                                    <span class="meta-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false">
                                            <rect x="3.5" y="4.5" width="17" height="16" rx="3"></rect>
                                            <path d="M8 2.5V6.5M16 2.5V6.5M3.5 9.5h17M8.5 13.5h2M13.5 13.5h2M8.5 17h2"></path>
                                        </svg>
                                    </span>
                                    <span class="label">Sinh nhật</span>
                                </span>
                                <span class="meta-value"><?= h($profile['birthday']) ?></span>
                            </div>
                        </div>
                        <div class="chip-row">
                            <span>
                                <img class="chip-icon" src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg" alt="" loading="lazy" decoding="async">
                                Python
                            </span>
                            <span>
                                <img class="chip-icon" src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/php/php-original.svg" alt="" loading="lazy" decoding="async">
                                PHP
                            </span>
                            <span>
                                <img class="chip-icon" src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/javascript/javascript-original.svg" alt="" loading="lazy" decoding="async">
                                JavaScript
                            </span>
                            <span>
                                <img class="chip-icon" src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/cplusplus/cplusplus-original.svg" alt="" loading="lazy" decoding="async">
                                C++
                            </span>
                        </div>
                    </article>

                </div>
            </section>

            <section class="section" id="about">
                <div class="section-heading" data-reveal>
                    <!-- <span class="eyebrow">Giới thiệu</span> -->
                    <h2>Một số thông tin cơ bản của tớ</h2>
                    <p>My information - A brief overview of my background.</p>
                </div>
                <div class="about-grid">
                    <article class="panel" data-reveal>
                        <h3>Tóm tắt về mình</h3>
                        <p><?= h($profile['summary']) ?></p>
                        <div class="info-list">
                            <div class="info-item">
                                <span>Họ tên</span>
                                <strong><?= h($profile['name']) ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Alias</span>
                                <strong><?= h($profile['brand']) ?> / <?= h($profile['alias']) ?></strong>
                            </div>
                            <div class="info-item">
                                <span>Quốc gia</span>
                                <strong class="country-value">
                                    <svg class="country-flag" viewBox="0 0 28 20" aria-hidden="true" focusable="false">
                                        <rect width="28" height="20" fill="#da251d"></rect>
                                        <polygon points="14,3.2 15.9,8 21.2,8.1 17,11.3 18.5,16.4 14,13.3 9.5,16.4 11,11.3 6.8,8.1 12.1,8" fill="#ffde00"></polygon>
                                    </svg>
                                    <?= h($profile['country']) ?>
                                </strong>
                            </div>
                            <div class="info-item">
                                <span>Sinh nhật</span>
                                <strong><?= h($profile['birthday']) ?></strong>
                            </div>
                        </div>
                    </article>

                    <article class="panel" data-reveal>
                        <h3>Quá trình học tập</h3>
                        <div class="highlight-stack">
                            <?php foreach ($highlights as $highlight): ?>
                                <div class="highlight-card">
                                    <strong><?= h($highlight['title']) ?></strong>
                                    <span class="meta-copy"><?= h($highlight['location']) ?></span>
                                    <span class="highlight-period"><?= h($highlight['period']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </div>
            </section>

            <section class="section" id="skills">
                <div class="section-heading" data-reveal>
                    <!-- <span class="eyebrow">Kỹ năng</span> -->
                    <h2>Các kĩ năng mà tớ có</h2>
                    <p>My skills and expertise.</p>
                </div>

                <div class="skills-grid">
                    <article class="panel" data-reveal>
                        <h3>Kỹ năng chính</h3>
                        <div class="skill-list">
                            <?php foreach ($skills as $skill): ?>
                                <div class="skill-row">
                                    <div class="skill-head">
                                        <div class="skill-meta">
                                            <span class="skill-icon-wrap">
                                                <img class="skill-icon" src="<?= h($skill['icon']) ?>" alt="Logo <?= h($skill['name']) ?>" loading="lazy" decoding="async">
                                            </span>
                                            <div>
                                                <?= h($skill['name']) ?>
                                                <span class="skill-group">· <?= h($skill['group']) ?></span>
                                            </div>
                                        </div>
                                        <span class="skill-percent" data-target="<?= (int) $skill['level'] ?>"><?= (int) $skill['level'] ?>%</span>
                                    </div>
                                    <div class="skill-track">
                                        <div class="skill-bar" data-target="<?= (int) $skill['level'] ?>" style="width: <?= (int) $skill['level'] ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <article class="panel" data-reveal>
                        <h3>Ngôn ngữ & cách làm việc</h3>
                        <div class="language-list">
                            <?php foreach ($languages as $language): ?>
                                <div class="language-card">
                                    <div class="language-head">
                                        <strong><?= h($language['name']) ?></strong>
                                        <span class="language-icons">
                                            <?php foreach ($language['icons'] as $icon): ?>
                                                <img src="<?= h($icon['src']) ?>" alt="<?= h($icon['alt']) ?>" loading="lazy" decoding="async">
                                            <?php endforeach; ?>
                                        </span>
                                    </div>
                                    <span><?= h($language['description']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="tag-cloud" style="margin-top: 20px;">
                            <span>Quyết đoán</span>
                            <span>Tự tin</span>
                            <span>Hướng ngoại</span>
                            <span>Thích khám phá</span>
                            <span>Chăm chỉ</span>
                            <span>Trung thực</span>
                        </div>
                    </article>
                </div>
            </section>

            <section class="section" id="projects">
                <div class="section-heading" data-reveal>
                    <h2>Một vài dự án nổi bật</h2>
                    <p>Selected work — những thứ mình đã và đang xây dựng.</p>
                </div>
                <div class="projects-marquee" data-projects-marquee data-reveal>
                    <div class="projects-track" data-projects-track>
                        <?php for ($pass = 0; $pass < 2; $pass++): ?>
                        <?php foreach ($projects as $project): ?>
                            <article class="panel project-card" data-accent="<?= h($project['accent'] ?? 'violet') ?>" aria-hidden="<?= $pass === 1 ? 'true' : 'false' ?>">
                                <div class="project-card-head">
                                    <span class="project-tag"><?= h($project['tag']) ?></span>
                                    <span class="project-glyph" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3"></path></svg>
                                    </span>
                                </div>
                                <h3 class="project-title"><?= h($project['title']) ?></h3>
                                <p class="project-desc"><?= h($project['description']) ?></p>
                                <ul class="project-stack">
                                    <?php foreach ($project['stack'] as $tech): ?>
                                        <li><?= h($tech) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (!empty($project['url']) && $project['url'] !== '#'): ?>
                                    <a class="project-link" href="<?= h($project['url']) ?>" target="_blank" rel="noreferrer" tabindex="<?= $pass === 1 ? '-1' : '0' ?>">
                                        Xem chi tiết
                                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                <?php else: ?>
                                    <span class="project-link is-soon">Sắp công bố</span>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <p class="projects-hint">← Kéo để xem các dự án khác →</p>
            </section>

            <section class="section" id="contact">
                <div class="contact-grid">
                    <article class="contact-panel primary" data-reveal>
                        <span class="eyebrow">Liên hệ</span>
                        <h2>Muốn kết nối hoặc trao đổi dự án?</h2>
                        <p>Nếu bạn muốn thuê code, đóng góp cho dự án của mình. Bạn có thể liên hệ các thông tin bên dưới nha >.<</p>
                        <div class="button-row" style="margin-bottom: 0;">
                            <a class="button button-primary" href="https://m.me/zminhhuydev" target="_blank" rel="noreferrer">Nhắn Messenger</a>
                            <a class="button button-secondary" href="https://t.me/Minhhuydev" target="_blank" rel="noreferrer">Mở Telegram</a>
                        </div>
                        <div class="contact-metrics" style="margin-top: 24px;">
                            <div class="contact-metric">
                                <span class="label" style="color: rgba(255,255,255,0.65);">Email</span>
                                <div class="meta-copy" style="color: rgba(255,255,255,0.92);"><?= h($profile['email']) ?></div>
                            </div>
                            <div class="contact-metric">
                                <span class="label" style="color: rgba(255,255,255,0.65);">Địa điểm</span>
                                <div class="meta-copy" style="color: rgba(255,255,255,0.92);"><?= h($profile['location']) ?></div>
                            </div>
                        </div>

                    </article>

                    <div class="contact-links" data-reveal>
                        <?php foreach ($socials as $social): ?>
                            <a class="contact-card" href="<?= h($social['url']) ?>" target="_blank" rel="noreferrer">
                                <div class="social-mark">
                                    <img src="<?= h($social['icon']) ?>" alt="" loading="lazy" decoding="async" aria-hidden="true">
                                </div>
                                <strong><?= h($social['label']) ?></strong>
                                <small><?= h($social['handle']) ?></small>
                                <span>Kết nối ngay →</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <p>© <span id="year"></span> <?= h($profile['brand']) ?>.</p>
        </footer>
    </div>

    <div id="react-fx-root" aria-hidden="true"></div>
    <canvas class="cursor-smoke-layer" id="cursor-smoke" aria-hidden="true"></canvas>
    <?php 
        renderJavaScriptRuntimeRefreshScript();
    ?>
    <script disable-devtool-auto>
        !function(e,t){"object"==typeof exports&&"undefined"!=typeof module?module.exports=t():"function"==typeof define&&define.amd?define(t):(e="undefined"!=typeof globalThis?globalThis:e||self).DisableDevtool=t()}(this,function(){"use strict";function i(e){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function r(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function u(e,t,n){t&&r(e.prototype,t),n&&r(e,n),Object.defineProperty(e,"prototype",{writable:!1})}function e(e,t,n){t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n}function c(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),Object.defineProperty(e,"prototype",{writable:!1}),t&&n(e,t)}function a(e){return(a=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function n(e,t){return(n=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(e,t){return e.__proto__=t,e})(e,t)}function U(e,t){if(t&&("object"==typeof t||"function"==typeof t))return t;if(void 0!==t)throw new TypeError("Derived constructors may only return object or undefined");t=e;if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}function l(n){var o=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],function(){})),!0}catch(e){return!1}}();return function(){var e,t=a(n);return U(this,o?(e=a(this).constructor,Reflect.construct(t,arguments,e)):t.apply(this,arguments))}}function f(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,o=new Array(t);n<t;n++)o[n]=e[n];return o}function s(e,t){var n,o="undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(!o){if(Array.isArray(e)||(o=function(e,t){if(e){if("string"==typeof e)return f(e,t);var n=Object.prototype.toString.call(e).slice(8,-1);return"Map"===(n="Object"===n&&e.constructor?e.constructor.name:n)||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?f(e,t):void 0}}(e))||t&&e&&"number"==typeof e.length)return o&&(e=o),n=0,{s:t=function(){},n:function(){return n>=e.length?{done:!0}:{done:!1,value:e[n++]}},e:function(e){throw e},f:t};throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var i,r=!0,u=!1;return{s:function(){o=o.call(e)},n:function(){var e=o.next();return r=e.done,e},e:function(e){u=!0,i=e},f:function(){try{r||null==o.return||o.return()}finally{if(u)throw i}}}}function t(){if(d.url)window.location.href=d.url;else if(d.rewriteHTML)try{document.documentElement.innerHTML=d.rewriteHTML}catch(e){document.documentElement.innerText=d.rewriteHTML}else{try{window.opener=null,window.open("","_self"),window.close(),window.history.back()}catch(e){console.log(e)}setTimeout(function(){window.location.href=d.timeOutUrl||"https://theajack.github.io/disable-devtool/404.html?h=".concat(encodeURIComponent(location.host))},500)}}var d={md5:"",ondevtoolopen:t,ondevtoolclose:null,url:"",timeOutUrl:"",tkName:"ddtk",interval:500,disableMenu:!0,stopIntervalTime:5e3,clearIntervalWhenDevOpenTrigger:!1,detectors:[1,3,4,5,6,7],clearLog:!0,disableSelect:!1,disableInputSelect:!1,disableCopy:!1,disableCut:!1,disablePaste:!1,ignore:null,disableIframeParents:!0,seo:!0,rewriteHTML:""},H=["detectors","ondevtoolclose","ignore"];function q(e){var t,n=0<arguments.length&&void 0!==e?e:{};for(t in n.onDevtoolOpen&&(n.ondevtoolopen=n.onDevtoolOpen),n.onDevtoolClose&&(n.ondevtoolclose=n.onDevtoolClose),d){var o=t;void 0===n[o]||i(d[o])!==i(n[o])&&-1===H.indexOf(o)||(d[o]=n[o])}"function"==typeof d.ondevtoolclose&&!0===d.clearIntervalWhenDevOpenTrigger&&(d.clearIntervalWhenDevOpenTrigger=!1,console.warn("【DISABLE-DEVTOOL】clearIntervalWhenDevOpenTrigger 在使用 ondevtoolclose 时无效"))}function v(){return(new Date).getTime()}function h(e){var t=v();return e(),v()-t}function z(n,o){function e(t){return function(){n&&n();var e=t.apply(void 0,arguments);return o&&o(),e}}var t=window.alert,i=window.confirm,r=window.prompt;try{window.alert=e(t),window.confirm=e(i),window.prompt=e(r)}catch(e){}}var p,y,B,b={iframe:!1,pc:!1,qqBrowser:!1,firefox:!1,macos:!1,edge:!1,oldEdge:!1,ie:!1,iosChrome:!1,iosEdge:!1,chrome:!1,seoBot:!1,mobile:!1};function W(){function e(e){return-1!==t.indexOf(e)}var t=navigator.userAgent.toLowerCase(),n=function(){var e=navigator,t=e.platform,e=e.maxTouchPoints;if("number"==typeof e)return 1<e;if("string"==typeof t){e=t.toLowerCase();if(/(mac|win)/i.test(e))return!1;if(/(android|iphone|ipad|ipod|arch)/i.test(e))return!0}return/(iphone|ipad|ipod|ios|android)/i.test(navigator.userAgent.toLowerCase())}(),o=!!window.top&&window!==window.top,i=!n,r=e("qqbrowser"),u=e("firefox"),c=e("macintosh"),a=e("edge"),l=a&&!e("chrome"),f=l||e("trident")||e("msie"),s=e("crios"),d=e("edgios"),v=e("chrome")||s,h=!n&&/(googlebot|baiduspider|bingbot|applebot|petalbot|yandexbot|bytespider|chrome\-lighthouse|moto g power)/i.test(t);Object.assign(b,{iframe:o,pc:i,qqBrowser:r,firefox:u,macos:c,edge:a,oldEdge:l,ie:f,iosChrome:s,iosEdge:d,chrome:v,seoBot:h,mobile:n})}function M(){for(var e=function(){for(var e={},t=0;t<500;t++)e["".concat(t)]="".concat(t);return e}(),t=[],n=0;n<50;n++)t.push(e);return t}function g(){d.clearLog&&B()}var K="",V=!1;function N(){var e=d.ignore;if(e){if("function"==typeof e)return e();if(0!==e.length){var t=location.href;if(K===t)return V;K=t;var n,o=!1,i=s(e);try{for(i.s();!(n=i.n()).done;){var r=n.value;if("string"==typeof r){if(-1!==t.indexOf(r)){o=!0;break}}else if(r.test(t)){o=!0;break}}}catch(e){i.e(e)}finally{i.f()}return V=o}}}var X=function(){return!1};function w(n){var t,e,o=74,i=73,r=85,u=83,c=123,a=b.macos?function(e,t){return e.metaKey&&e.altKey&&(t===i||t===o)}:function(e,t){return e.ctrlKey&&e.shiftKey&&(t===i||t===o)},l=b.macos?function(e,t){return e.metaKey&&e.altKey&&t===r||e.metaKey&&t===u}:function(e,t){return e.ctrlKey&&(t===u||t===r)};n.addEventListener("keydown",function(e){var t=(e=e||n.event).keyCode||e.which;if(t===c||a(e,t)||l(e,t))return T(n,e)},!0),t=n,d.disableMenu&&t.addEventListener("contextmenu",function(e){if("touch"!==e.pointerType)return T(t,e)}),e=n,(d.disableSelect||d.disableInputSelect)&&m(e,"selectstart"),e=n,d.disableCopy&&m(e,"copy"),e=n,d.disableCut&&m(e,"cut"),e=n,d.disablePaste&&m(e,"paste")}function m(o,e){o.addEventListener(e,function(e){if(!(t=e.target)||"INPUT"!==t.tagName&&"TEXTAREA"!==t.tagName&&"true"!==(null==(n=t.getAttribute)?void 0:n.call(t,"contenteditable"))){if(d.disableSelect)return T(o,e)}else if(d.disableInputSelect)return T(o,e);var t,n})}function T(e,t){if(!N()&&!X())return(t=t||e.event).returnValue=!1,t.preventDefault(),!1}var O,D=!1,S={};function F(e){S[e]=!1}function $(){for(var e in S)if(S[e])return D=!0;return D=!1}(A=O=O||{})[A.Unknown=-1]="Unknown",A[A.RegToString=0]="RegToString",A[A.DefineId=1]="DefineId",A[A.Size=2]="Size",A[A.DateToString=3]="DateToString",A[A.FuncToString=4]="FuncToString",A[A.Debugger=5]="Debugger",A[A.Performance=6]="Performance",A[A.DebugLib=7]="DebugLib";var k=function(){function n(e){var t=e.type,e=e.enabled,e=void 0===e||e;o(this,n),this.type=O.Unknown,this.enabled=!0,this.type=t,this.enabled=e,this.enabled&&(t=this,Q.push(t),this.init())}return u(n,[{key:"onDevToolOpen",value:function(){var e;console.warn("You don't have permission to use DEVTOOL!【type = ".concat(this.type,"】")),d.clearIntervalWhenDevOpenTrigger&&te(),window.clearTimeout(J),d.ondevtoolopen(this.type,t),e=this.type,S[e]=!0}},{key:"init",value:function(){}}]),n}(),G=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.DebugLib})}return u(t,[{key:"init",value:function(){}},{key:"detect",value:function(){var e;(!0===(null==(e=null==(e=window.eruda)?void 0:e._devTools)?void 0:e._isShow)||window._vcOrigConsole&&window.document.querySelector("#__vconsole.vc-toggle"))&&this.onDevToolOpen()}}],[{key:"isUsing",value:function(){return!!window.eruda||!!window._vcOrigConsole}}]),t}(),Y=0,J=0,Q=[],Z=0;function ee(i){function e(){l=!0}function t(){l=!1}var n,o,r,u,c,a,l=!1;function f(){(a[u]===r?o:n)()}z(e,t),n=t,o=e,void 0!==(a=document).hidden?(r="hidden",c="visibilitychange",u="visibilityState"):void 0!==a.mozHidden?(r="mozHidden",c="mozvisibilitychange",u="mozVisibilityState"):void 0!==a.msHidden?(r="msHidden",c="msvisibilitychange",u="msVisibilityState"):void 0!==a.webkitHidden&&(r="webkitHidden",c="webkitvisibilitychange",u="webkitVisibilityState"),a.removeEventListener(c,f,!1),a.addEventListener(c,f,!1),Y=window.setInterval(function(){if(!(i.isSuspend||l||N())){var e,t,n=s(Q);try{for(n.s();!(e=n.n()).done;){var o=e.value;F(o.type),o.detect(Z++)}}catch(e){n.e(e)}finally{n.f()}g(),"function"==typeof d.ondevtoolclose&&(t=D,!$()&&t&&d.ondevtoolclose())}},d.interval),J=setTimeout(function(){b.pc||G.isUsing()||te()},d.stopIntervalTime)}function te(){window.clearInterval(Y)}var P=8;function ne(e){for(var t=function(e,t){e[t>>5]|=128<<t%32,e[14+(t+64>>>9<<4)]=t;for(var n=1732584193,o=-271733879,i=-1732584194,r=271733878,u=0;u<e.length;u+=16){var c=n,a=o,l=i,f=r;n=E(n,o,i,r,e[u+0],7,-680876936),r=E(r,n,o,i,e[u+1],12,-389564586),i=E(i,r,n,o,e[u+2],17,606105819),o=E(o,i,r,n,e[u+3],22,-1044525330),n=E(n,o,i,r,e[u+4],7,-176418897),r=E(r,n,o,i,e[u+5],12,1200080426),i=E(i,r,n,o,e[u+6],17,-1473231341),o=E(o,i,r,n,e[u+7],22,-45705983),n=E(n,o,i,r,e[u+8],7,1770035416),r=E(r,n,o,i,e[u+9],12,-1958414417),i=E(i,r,n,o,e[u+10],17,-42063),o=E(o,i,r,n,e[u+11],22,-1990404162),n=E(n,o,i,r,e[u+12],7,1804603682),r=E(r,n,o,i,e[u+13],12,-40341101),i=E(i,r,n,o,e[u+14],17,-1502002290),o=E(o,i,r,n,e[u+15],22,1236535329),n=I(n,o,i,r,e[u+1],5,-165796510),r=I(r,n,o,i,e[u+6],9,-1069501632),i=I(i,r,n,o,e[u+11],14,643717713),o=I(o,i,r,n,e[u+0],20,-373897302),n=I(n,o,i,r,e[u+5],5,-701558691),r=I(r,n,o,i,e[u+10],9,38016083),i=I(i,r,n,o,e[u+15],14,-660478335),o=I(o,i,r,n,e[u+4],20,-405537848),n=I(n,o,i,r,e[u+9],5,568446438),r=I(r,n,o,i,e[u+14],9,-1019803690),i=I(i,r,n,o,e[u+3],14,-187363961),o=I(o,i,r,n,e[u+8],20,1163531501),n=I(n,o,i,r,e[u+13],5,-1444681467),r=I(r,n,o,i,e[u+2],9,-51403784),i=I(i,r,n,o,e[u+7],14,1735328473),o=I(o,i,r,n,e[u+12],20,-1926607734),n=j(n,o,i,r,e[u+5],4,-378558),r=j(r,n,o,i,e[u+8],11,-2022574463),i=j(i,r,n,o,e[u+11],16,1839030562),o=j(o,i,r,n,e[u+14],23,-35309556),n=j(n,o,i,r,e[u+1],4,-1530992060),r=j(r,n,o,i,e[u+4],11,1272893353),i=j(i,r,n,o,e[u+7],16,-155497632),o=j(o,i,r,n,e[u+10],23,-1094730640),n=j(n,o,i,r,e[u+13],4,681279174),r=j(r,n,o,i,e[u+0],11,-358537222),i=j(i,r,n,o,e[u+3],16,-722521979),o=j(o,i,r,n,e[u+6],23,76029189),n=j(n,o,i,r,e[u+9],4,-640364487),r=j(r,n,o,i,e[u+12],11,-421815835),i=j(i,r,n,o,e[u+15],16,530742520),o=j(o,i,r,n,e[u+2],23,-995338651),n=L(n,o,i,r,e[u+0],6,-198630844),r=L(r,n,o,i,e[u+7],10,1126891415),i=L(i,r,n,o,e[u+14],15,-1416354905),o=L(o,i,r,n,e[u+5],21,-57434055),n=L(n,o,i,r,e[u+12],6,1700485571),r=L(r,n,o,i,e[u+3],10,-1894986606),i=L(i,r,n,o,e[u+10],15,-1051523),o=L(o,i,r,n,e[u+1],21,-2054922799),n=L(n,o,i,r,e[u+8],6,1873313359),r=L(r,n,o,i,e[u+15],10,-30611744),i=L(i,r,n,o,e[u+6],15,-1560198380),o=L(o,i,r,n,e[u+13],21,1309151649),n=L(n,o,i,r,e[u+4],6,-145523070),r=L(r,n,o,i,e[u+11],10,-1120210379),i=L(i,r,n,o,e[u+2],15,718787259),o=L(o,i,r,n,e[u+9],21,-343485551),n=C(n,c),o=C(o,a),i=C(i,l),r=C(r,f)}return Array(n,o,i,r)}(function(e){for(var t=Array(),n=(1<<P)-1,o=0;o<e.length*P;o+=P)t[o>>5]|=(e.charCodeAt(o/P)&n)<<o%32;return t}(e),e.length*P),n="0123456789abcdef",o="",i=0;i<4*t.length;i++)o+=n.charAt(t[i>>2]>>i%4*8+4&15)+n.charAt(t[i>>2]>>i%4*8&15);return o}function x(e,t,n,o,i,r){return C((t=C(C(t,e),C(o,r)))<<i|t>>>32-i,n)}function E(e,t,n,o,i,r,u){return x(t&n|~t&o,e,t,i,r,u)}function I(e,t,n,o,i,r,u){return x(t&o|n&~o,e,t,i,r,u)}function j(e,t,n,o,i,r,u){return x(t^n^o,e,t,i,r,u)}function L(e,t,n,o,i,r,u){return x(n^(t|~o),e,t,i,r,u)}function C(e,t){var n=(65535&e)+(65535&t);return(e>>16)+(t>>16)+(n>>16)<<16|65535&n}var A=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.RegToString,enabled:b.qqBrowser||b.firefox})}return u(t,[{key:"init",value:function(){var t=this;this.lastTime=0,this.reg=/./,p(this.reg),this.reg.toString=function(){var e;return b.qqBrowser?(e=(new Date).getTime(),t.lastTime&&e-t.lastTime<100?t.onDevToolOpen():t.lastTime=e):b.firefox&&t.onDevToolOpen(),""}}},{key:"detect",value:function(){p(this.reg)}}]),t}(),oe=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.DefineId})}return u(t,[{key:"init",value:function(){var e=this;this.div=document.createElement("div"),this.div.__defineGetter__("id",function(){e.onDevToolOpen()}),Object.defineProperty(this.div,"id",{get:function(){e.onDevToolOpen()}})}},{key:"detect",value:function(){p(this.div)}}]),t}(),ie=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.Size,enabled:!b.iframe&&!b.edge})}return u(t,[{key:"init",value:function(){var e=this;this.checkWindowSizeUneven(),window.addEventListener("resize",function(){setTimeout(function(){e.checkWindowSizeUneven()},100)},!0)}},{key:"detect",value:function(){}},{key:"checkWindowSizeUneven",value:function(){var e=function(){if(re(window.devicePixelRatio))return window.devicePixelRatio;var e=window.screen;return!!(re(e)&&e.deviceXDPI&&e.logicalXDPI)&&e.deviceXDPI/e.logicalXDPI}();if(!1!==e){var t=200<window.outerWidth-window.innerWidth*e,e=300<window.outerHeight-window.innerHeight*e;if(t||e)return this.onDevToolOpen(),!1;F(this.type)}return!0}}]),t}();function re(e){return null!=e}var _,ue=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.DateToString,enabled:!b.iosChrome&&!b.iosEdge})}return u(t,[{key:"init",value:function(){var e=this;this.count=0,this.date=new Date,this.date.toString=function(){return e.count++,""}}},{key:"detect",value:function(){this.count=0,p(this.date),g(),2<=this.count&&this.onDevToolOpen()}}]),t}(),ce=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.FuncToString,enabled:!b.iosChrome&&!b.iosEdge})}return u(t,[{key:"init",value:function(){var e=this;this.count=0,this.func=function(){},this.func.toString=function(){return e.count++,""}}},{key:"detect",value:function(){this.count=0,p(this.func),g(),2<=this.count&&this.onDevToolOpen()}}]),t}(),ae=function(){c(t,k);var e=l(t);function t(){return o(this,t),e.call(this,{type:O.Debugger,enabled:b.iosChrome||b.iosEdge})}return u(t,[{key:"detect",value:function(){var e=v();100<v()-e&&this.onDevToolOpen()}}]),t}(),le=function(){c(n,k);var t=l(n);function n(){var e;return o(this,n),(e=t.call(this,{type:O.Performance,enabled:b.chrome||!b.mobile})).count=0,e}return u(n,[{key:"init",value:function(){this.maxPrintTime=0,this.largeObjectArray=M()}},{key:"detect",value:function(){var e=this,t=h(function(){y(e.largeObjectArray)}),n=h(function(){p(e.largeObjectArray)});if(this.maxPrintTime=Math.max(this.maxPrintTime,n),g(),0===t||0===this.maxPrintTime)return!1;t>10*this.maxPrintTime&&(2<=this.count?this.onDevToolOpen():(this.count++,this.detect()))}}]),n}(),fe=(e(_={},O.RegToString,A),e(_,O.DefineId,oe),e(_,O.Size,ie),e(_,O.DateToString,ue),e(_,O.FuncToString,ce),e(_,O.Debugger,ae),e(_,O.Performance,le),e(_,O.DebugLib,G),_);var R=Object.assign(function(e){function t(){var e=0<arguments.length&&void 0!==arguments[0]?arguments[0]:"";return{success:!e,reason:e}}var n;if(R.isRunning)return t("already running");if(W(),n=window.console||{log:function(){},table:function(){},clear:function(){}},B=b.ie?(p=function(){return n.log.apply(n,arguments)},y=function(){return n.table.apply(n,arguments)},function(){return n.clear()}):(p=n.log,y=n.table,n.clear),q(e),d.md5&&ne(function(e){var t=window.location.search,n=window.location.hash;if(""!==(t=""===t&&""!==n?"?".concat(n.split("?")[1]):t)&&void 0!==t){n=new RegExp("(^|&)"+e+"=([^&]*)(&|$)","i"),e=t.substr(1).match(n);if(null!=e)return unescape(e[2])}return""}(d.tkName))===d.md5)return t("token passed");if(d.seo&&b.seoBot)return t("seobot");R.isRunning=!0,ee(R);var o=R,i=(X=function(){return o.isSuspend},window.top),r=window.parent;if(w(window),d.disableIframeParents&&i&&r&&i!==window){for(;r!==i;)w(r),r=r.parent;w(i)}return("all"===d.detectors?Object.keys(fe):d.detectors).forEach(function(e){new fe[e]}),t()},{isRunning:!1,isSuspend:!1,md5:ne,version:"0.3.9",DetectorType:O,isDevToolOpened:$});A=function(){if("undefined"==typeof window||!window.document)return null;var n=document.querySelector("[disable-devtool-auto]");if(!n)return null;var o=["disable-menu","disable-select","disable-copy","disable-cut","disable-paste","clear-log"],i=["interval"],r={};return["md5","url","tk-name","detectors"].concat(o,i).forEach(function(e){var t=n.getAttribute(e);null!==t&&(-1!==i.indexOf(e)?t=parseInt(t):-1!==o.indexOf(e)?t="false"!==t:"detector"===e&&"all"!==t&&(t=t.split(" ")),r[function(e){if(-1===e.indexOf("-"))return e;var t=!1;return e.split("").map(function(e){return"-"===e?(t=!0,""):t?(t=!1,e.toUpperCase()):e}).join("")}(e)]=t)}),r}();return A&&R(A),R});
    </script>
    <script type="text/babel">
        (function () {
            const rootNode = document.getElementById('react-fx-root');
            if (!rootNode || !window.React || !window.ReactDOM) {
                return;
            }

            function FxLayer() {
                return (
                    <div className="pointer-events-none fixed inset-0 z-[1] overflow-hidden">
                        <div className="absolute -top-16 -left-16 h-64 w-64 rounded-full bg-cyan-400/10 blur-3xl animate-float-slow" />
                        <div className="absolute top-[20%] -right-20 h-80 w-80 rounded-full bg-fuchsia-400/10 blur-3xl animate-drift" />
                        <div className="absolute bottom-[-100px] left-1/3 h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl animate-float-slow" style={{ animationDelay: '1.2s' }} />
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.04),transparent_55%),radial-gradient(circle_at_70%_80%,rgba(56,189,248,0.07),transparent_45%)]" />
                    </div>
                );
            }

            window.ReactDOM.createRoot(rootNode).render(<FxLayer />);
        })();
    </script>
    <script src="<?= h(assetVersion('assets/js/main.js')) ?>"></script>

    <!-- PWA / Offline support: đăng ký Service Worker để truy cập được khi mất mạng -->
    <script>
        (function () {
            if (!('serviceWorker' in navigator)) return;
            // Chỉ đăng ký trên HTTPS hoặc localhost (giới hạn của SW)
            var host = window.location.hostname;
            var isLocal = host === 'localhost' || host === '127.0.0.1' || host === '::1';
            if (window.location.protocol !== 'https:' && !isLocal) return;

            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function (reg) {
                        // Khi có bản SW mới, kích hoạt ngay để cache không bị lỗi thời lâu
                        if (reg && reg.waiting) reg.waiting.postMessage('SKIP_WAITING');
                        if (reg) {
                            reg.addEventListener('updatefound', function () {
                                var nw = reg.installing;
                                if (!nw) return;
                                nw.addEventListener('statechange', function () {
                                    if (nw.state === 'installed' && navigator.serviceWorker.controller) {
                                        nw.postMessage('SKIP_WAITING');
                                    }
                                });
                            });
                        }
                    })
                    .catch(function () { /* im lặng — không có SW thì site vẫn chạy bình thường */ });

                // Reload trang khi controller đổi (SW mới activate)
                var refreshing = false;
                navigator.serviceWorker.addEventListener('controllerchange', function () {
                    if (refreshing) return;
                    refreshing = true;
                    window.location.reload();
                });
            });

            // Popup trạng thái mạng — pill toast ở GÓC DƯỚI
            // - Offline: persistent (bám lại đến khi có mạng)
            // - Online: hiện 2.2s rồi tự ẩn
            var netToastEl = null;
            function ensureNetToastStyles() {
                if (document.getElementById('mh-net-toast-style')) return;
                var s = document.createElement('style');
                s.id = 'mh-net-toast-style';
                s.textContent = [
                    '#mh-net-toast .mh-dot{position:relative;width:9px;height:9px;border-radius:999px;flex:0 0 auto;transition:background .25s ease,box-shadow .25s ease;}',
                    '#mh-net-toast .mh-dot::before,#mh-net-toast .mh-dot::after{content:"";position:absolute;inset:0;border-radius:inherit;background:inherit;pointer-events:none;}',
                    '#mh-net-toast .mh-dot::before{animation:mhDotPulse 1.6s ease-out infinite;}',
                    '#mh-net-toast .mh-dot::after{opacity:.55;animation:mhDotGlow 1.6s ease-in-out infinite;}',
                    '#mh-net-toast.is-online .mh-dot::before{animation-duration:1.1s;}',
                    '#mh-net-toast.is-online .mh-dot::after{animation:mhDotBlink 1.1s ease-in-out infinite;}',
                    '@keyframes mhDotPulse{0%{transform:scale(1);opacity:.7;}80%,100%{transform:scale(2.6);opacity:0;}}',
                    '@keyframes mhDotGlow{0%,100%{transform:scale(1);opacity:.55;}50%{transform:scale(1.35);opacity:.15;}}',
                    '@keyframes mhDotBlink{0%,100%{opacity:.3;}50%{opacity:.9;}}',
                    '@media (prefers-reduced-motion: reduce){#mh-net-toast .mh-dot::before,#mh-net-toast .mh-dot::after{animation:none!important;}}'
                ].join('');
                document.head.appendChild(s);
            }
            function ensureNetToast() {
                ensureNetToastStyles();
                if (netToastEl && document.body.contains(netToastEl)) return netToastEl;
                netToastEl = document.createElement('div');
                netToastEl.id = 'mh-net-toast';
                netToastEl.setAttribute('role', 'status');
                netToastEl.setAttribute('aria-live', 'polite');
                netToastEl.style.cssText = [
                    'position:fixed',
                    'left:50%',
                    'bottom:calc(20px + env(safe-area-inset-bottom, 0px))',
                    'transform:translate(-50%, 24px)',
                    'z-index:99999',
                    'display:inline-flex',
                    'align-items:center',
                    'gap:10px',
                    'padding:10px 18px',
                    'border-radius:999px',
                    'background:rgba(17,23,43,.92)',
                    'color:#e6ecff',
                    'border:1px solid rgba(255,255,255,.12)',
                    'backdrop-filter:blur(14px)',
                    '-webkit-backdrop-filter:blur(14px)',
                    'box-shadow:0 14px 40px -10px rgba(0,0,0,.55)',
                    'font:600 13px/1.2 Manrope,system-ui,-apple-system,sans-serif',
                    'opacity:0',
                    'pointer-events:auto',
                    'transition:opacity .25s ease, transform .25s ease',
                    'max-width:calc(100% - 24px)',
                    'white-space:nowrap'
                ].join(';');
                netToastEl.innerHTML = '' +
                    '<span class="mh-dot" style="background:#ff5d7a;"></span>' +
                    '<span class="mh-msg">Bạn đang offline</span>';
                document.body.appendChild(netToastEl);
                return netToastEl;
            }
            function showNetToast(text, color, persistent) {
                var el = ensureNetToast();
                var dot = el.querySelector('.mh-dot');
                var msg = el.querySelector('.mh-msg');
                if (msg) msg.textContent = text;
                if (dot) {
                    dot.style.background = color;
                    dot.style.boxShadow = '0 0 0 4px ' + color + '33';
                }
                el.classList.toggle('is-online', !persistent);
                void el.offsetWidth;
                el.style.opacity = '1';
                el.style.transform = 'translate(-50%, 0)';
                if (el._hideTimer) { clearTimeout(el._hideTimer); el._hideTimer = null; }
                if (!persistent) {
                    el._hideTimer = setTimeout(function () {
                        el.style.opacity = '0';
                        el.style.transform = 'translate(-50%, 24px)';
                    }, 2200);
                }
            }
            window.addEventListener('offline', function () {
                showNetToast('Cá mập cắn cáp mất rồi ;(  Bạn đang offline', '#ff5d7a', true);
            });
            window.addEventListener('online', function () {
                showNetToast('Đã có kết nối mạng trở lại!', '#22c55e', false);
            });
            // Vào trang khi đã offline sẵn → hiện luôn
            if (typeof navigator !== 'undefined' && navigator.onLine === false) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () {
                        showNetToast('Cá mập cắn cáp mất rồi ;(  Bạn đang offline', '#ff5d7a', true);
                    });
                } else {
                    showNetToast('Cá mập cắn cáp mất rồi ;(  Bạn đang offline', '#ff5d7a', true);
                }
            }
        })();
    </script>
</body>
</html>
