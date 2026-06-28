/* eslint-disable no-restricted-globals */
/**
 * MinhHuyDev — Service Worker (offline-only fallback)
 *
 * Yêu cầu:
 *  - KHÔNG cache trang portfolio chính (HTML navigation luôn đi mạng).
 *  - Khi mất mạng, hiển thị một trang offline render trực tiếp tại đây
 *    với UI/theme đồng bộ index.php (Manrope, #0a101c, gradient #7c5cff → #22d3ee).
 *  - Không tạo file HTML thứ 3.
 *
 * Static asset (css/js/font ảnh) vẫn cache để load nhanh khi online.
 */

const SW_VERSION = 'v2026-04-30-2';
const RUNTIME_CACHE = `runtime-${SW_VERSION}`;

self.addEventListener('install', (event) => {
	event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
	event.waitUntil((async () => {
		const keys = await caches.keys();
		await Promise.all(keys.map((key) => key === RUNTIME_CACHE ? null : caches.delete(key)));
		await self.clients.claim();
	})());
});

self.addEventListener('message', (event) => {
	if (event.data === 'SKIP_WAITING') self.skipWaiting();
});

// ---- Offline page render inline (đồng bộ theme với index.php) ----
function renderOfflineHtml() {
	return `<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mất kết nối · MinhHuyDev</title>
<meta name="theme-color" content="#0a101c">
<link rel="icon" href="https://nqminkhuy.com/information/assets/images/nmh-logo.jpg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after { box-sizing:border-box; }
html,body { margin:0; padding:0; height:100%; }
body {
	font-family:'Manrope',system-ui,-apple-system,sans-serif;
	color:#e6ecff; min-height:100vh; display:grid; place-items:center; padding:24px;
	background:
		radial-gradient(1000px 600px at 12% -10%, rgba(124,92,255,.22), transparent 60%),
		radial-gradient(900px 520px at 110% 110%, rgba(34,211,238,.16), transparent 60%),
		radial-gradient(circle at 30% 20%, rgba(255,255,255,.04), transparent 55%),
		#0a101c;
	overflow:hidden;
}
.fx { position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
.fx span {
	position:absolute; border-radius:9999px; filter:blur(60px); opacity:.55;
	animation: float 9s ease-in-out infinite;
}
.fx .a { width:280px; height:280px; top:-60px; left:-60px; background:rgba(34,211,238,.18); }
.fx .b { width:340px; height:340px; top:25%; right:-80px; background:rgba(232,121,249,.16); animation-duration:12s; }
.fx .c { width:300px; height:300px; bottom:-100px; left:35%; background:rgba(110,231,183,.16); animation-delay:1.2s; }
@keyframes float { 0%,100% { transform:translate3d(0,0,0); } 50% { transform:translate3d(0,-22px,0); } }

.card {
	position:relative; z-index:1;
	width:min(100%, 460px);
	background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
	border:1px solid rgba(255,255,255,.08);
	border-radius:24px; padding:34px 30px; text-align:center;
	backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px);
	box-shadow:0 30px 80px -30px rgba(0,0,0,.6);
}
.brand {
	display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:999px;
	background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08);
	font-size:11.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:#aab2d5;
	margin-bottom:18px;
}
.brand .dot { width:6px; height:6px; border-radius:999px; background:#ff5d7a; box-shadow:0 0 0 4px rgba(255,93,122,.18); animation:blink 1.6s ease-in-out infinite; }
.brand.online .dot { background:#22c55e; box-shadow:0 0 0 4px rgba(34,197,94,.2); }
@keyframes blink { 50% { opacity:.55; } }

.icon {
	width:72px; height:72px; margin:0 auto 18px; border-radius:20px;
	background:linear-gradient(135deg,#7c5cff,#22d3ee);
	display:grid; place-items:center; font-size:34px;
	box-shadow:0 14px 40px -12px rgba(124,92,255,.55);
	animation:pulse 2.4s ease-in-out infinite;
}
@keyframes pulse {
	0%,100% { transform:scale(1); box-shadow:0 14px 40px -12px rgba(124,92,255,.55), 0 0 0 0 rgba(124,92,255,.4); }
	50% { transform:scale(1.05); box-shadow:0 14px 40px -12px rgba(124,92,255,.55), 0 0 0 16px rgba(124,92,255,0); }
}

h1 {
	margin:0 0 8px; font-family:'Space Grotesk','Manrope',sans-serif;
	font-size:24px; font-weight:700; letter-spacing:-.3px;
}
.sub { margin:0 0 22px; color:#aab2d5; font-size:14px; line-height:1.65; }

.actions { display:flex; flex-direction:column; gap:10px; }
.btn {
	display:inline-flex; align-items:center; justify-content:center; gap:8px;
	padding:12px 18px; border-radius:14px; border:1px solid rgba(255,255,255,.1);
	background:rgba(255,255,255,.05); color:#e6ecff; font-size:14px; font-weight:600;
	text-decoration:none; cursor:pointer; transition:background .15s, transform .1s, border-color .15s;
	font-family:inherit;
}
.btn:hover { background:rgba(255,255,255,.09); border-color:rgba(255,255,255,.18); }
.btn:active { transform:scale(.98); }
.btn.primary {
	background:linear-gradient(135deg,#7c5cff,#22d3ee); border-color:transparent; color:#fff;
	box-shadow:0 12px 30px -10px rgba(124,92,255,.55);
}
.btn.primary:hover { background:linear-gradient(135deg,#8a6dff,#3edcef); }

.foot { margin-top:20px; font-size:11.5px; color:#6a72a1; letter-spacing:.4px; }

@media (max-width:480px) {
	.card { padding:26px 22px; border-radius:20px; }
	h1 { font-size:21px; }
	.icon { width:60px; height:60px; font-size:28px; }
}
</style>
</head>
<body>
<div class="fx" aria-hidden="true">
	<span class="a"></span><span class="b"></span><span class="c"></span>
</div>
<main class="card" role="alert">
	<div class="brand" id="netBrand">
		<span class="dot"></span>
		<span id="netText">Đang offline</span>
	</div>
	<div class="icon">📡</div>
	<h1>Mất kết nối Internet</h1>
	<p class="sub">
		Bạn đang offline. Để bảo vệ trải nghiệm, MinhHuyDev không lưu cache trang portfolio
		chính — khi có mạng trở lại, trang sẽ tự động tải mới.
	</p>
	<div class="actions">
		<button class="btn primary" id="retryBtn">↻ Thử lại</button>
		<a class="btn" href="https://nqminkhuy.com/information/" rel="external">Mở trang gốc</a>
	</div>
	<div class="foot">MinhHuyDev · Service Worker offline page</div>
</main>
<script>
(function(){
	var brand = document.getElementById('netBrand');
	var text = document.getElementById('netText');
	function sync() {
		var on = navigator.onLine;
		brand.classList.toggle('online', on);
		text.textContent = on ? 'Đã có mạng — bấm Thử lại' : 'Đang offline';
	}
	sync();
	window.addEventListener('online', function(){ sync(); setTimeout(function(){ location.reload(); }, 800); });
	window.addEventListener('offline', sync);
	document.getElementById('retryBtn').addEventListener('click', function(){ location.reload(); });
})();
</script>
</body>
</html>`;
}

function offlineResponse() {
	return new Response(renderOfflineHtml(), {
		status: 200,
		headers: {
			'Content-Type': 'text/html; charset=utf-8',
			'Cache-Control': 'no-store',
			'X-Offline-Page': 'sw-inline'
		}
	});
}

function isHtmlRequest(request) {
	if (request.mode === 'navigate') return true;
	const accept = request.headers.get('accept') || '';
	return accept.includes('text/html');
}

function isStaticAsset(url) {
	return /\.(?:css|js|mjs|woff2?|ttf|otf|eot|png|jpe?g|gif|svg|webp|ico)$/i.test(url.pathname);
}

async function staleWhileRevalidate(request) {
	const cache = await caches.open(RUNTIME_CACHE);
	const cached = await cache.match(request);
	const network = fetch(request).then((res) => {
		if (res && (res.status === 200 || res.type === 'opaque')) {
			cache.put(request, res.clone()).catch(() => {});
		}
		return res;
	}).catch(() => null);
	return cached || network || Promise.reject(new Error('offline'));
}

self.addEventListener('fetch', (event) => {
	const { request } = event;
	if (request.method !== 'GET') return;

	const url = new URL(request.url);

	// ---- Navigation (HTML): KHÔNG cache portfolio. Online → mạng. Offline → offline page inline. ----
	if (isHtmlRequest(request)) {
		event.respondWith((async () => {
			try {
				const fresh = await fetch(request, { cache: 'no-store' });
				return fresh;
			} catch (_) {
				return offlineResponse();
			}
		})());
		return;
	}

	// ---- Static asset same-origin: stale-while-revalidate (load nhanh, không ảnh hưởng HTML chính) ----
	if (url.origin === self.location.origin && isStaticAsset(url)) {
		event.respondWith(staleWhileRevalidate(request).catch(() => fetch(request)));
		return;
	}

	// ---- CDN cross-origin (fonts, tailwind, react...): stale-while-revalidate ----
	if (url.origin !== self.location.origin) {
		event.respondWith(staleWhileRevalidate(request).catch(() => fetch(request)));
		return;
	}

	// ---- Mặc định: thẳng mạng, fail thì kệ ----
	event.respondWith(fetch(request));
});
