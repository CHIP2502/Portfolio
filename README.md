# MinhHuyDev - Professional Profile & Web Portfolio

![MinhHuyDev Portfolio Banner](https://via.placeholder.com/1200x400.png?text=MinhHuyDev+Portfolio+Architecture)

Chào mừng bạn đến với tài liệu hướng dẫn cực kỳ chi tiết của dự án **MinhHuyDev Profile**. Đây không chỉ là một trang web cá nhân thông thường, mà là một hệ thống tinh gọn, tối ưu hiệu suất, được bảo vệ nghiêm ngặt bởi hệ thống Anti-Bot tự động và hoàn toàn điều khiển nội dung thông qua một file JSON duy nhất.

Tài liệu này được viết với mức độ chuyên sâu nhằm giúp các developer, maintainer hoặc bất kỳ ai muốn clone/fork dự án này hiểu tường tận từ triết lý thiết kế, kiến trúc mã nguồn cho đến hướng dẫn triển khai thực tế.

---

## 📑 Mục lục

1. [Triết lý phát triển (Philosophy)](#1-triết-lý-phát-triển-philosophy)
2. [Tính năng nổi bật (Key Features)](#2-tính-năng-nổi-bật-key-features)
3. [Stack công nghệ (Tech Stack)](#3-stack-công-nghệ-tech-stack)
4. [Kiến trúc & Cấu trúc thư mục (Architecture)](#4-kiến-trúc--cấu-trúc-thư-mục-architecture)
5. [Cơ chế bảo mật Anti-Bot Guard](#5-cơ-chế-bảo-mật-anti-bot-guard)
6. [Quản lý nội dung qua JSON (Data Model)](#6-quản-lý-nội-dung-qua-json-data-model)
7. [Hệ thống Styling & Giao diện (Theming)](#7-hệ-thống-styling--giao-diện-theming)
8. [Tương tác JavaScript (Interactions)](#8-tương-tác-javascript-interactions)
9. [Hướng dẫn cài đặt Local (Local Development)](#9-hướng-dẫn-cài-đặt-local-local-development)
10. [Hướng dẫn triển khai Production (Deployment)](#10-hướng-dẫn-triển-khai-production-deployment)
11. [Troubleshooting (Khắc phục sự cố)](#11-troubleshooting-khắc-phục-sự-cố)
12. [Đóng góp & Giấy phép (License)](#12-đóng-góp--giấy-phép-license)

---

## 1. Triết lý phát triển (Philosophy)

Dự án này được xây dựng dựa trên 3 nguyên tắc cốt lõi:
- **Tách biệt nội dung và logic**: Code PHP/JS/CSS hoàn toàn không chứa văn bản cá nhân. Mọi thứ được định nghĩa trong `profile.json`. Bạn có thể thay tên, kỹ năng, dự án mà không sợ làm hỏng logic của web.
- **Tối giản nhưng bảo mật cao**: Không cần cơ sở dữ liệu (MySQL/PostgreSQL) cồng kềnh, không cần Framework (Laravel/Symfony) nặng nề. Mọi thứ chỉ cần PHP thuần (Vanilla PHP), nhưng lại được trang bị lớp bảo mật cực mạnh chống lại các scraper bot.
- **Trải nghiệm người dùng (UX) hiện đại**: Tốc độ load cực nhanh (0.01s), hiệu ứng animation mượt mà (60fps), hỗ trợ Dark/Light mode tự động và responsive hoàn hảo trên mọi thiết bị di động.

---

## 2. Tính năng nổi bật (Key Features)

- 🛡️ **Hệ thống xác thực trình duyệt (Browser Proof Guard)**: Trình duyệt của người dùng bắt buộc phải chạy được JavaScript và giải được "bài toán" sinh token để có thể truy cập nội dung. Ngăn chặn triệt để các curl bot và web scraper.
- 🎨 **Giao diện mang phong cách Terminal (CLI UI)**: Thiết kế đặc trưng của dân IT với các thành phần như `Command Pill`, `Terminal Box`, phông chữ Monospace kết hợp Sans-Serif hiện đại.
- ⏱️ **Đồng hồ Realtime**: Đồng hồ live-ticking trực tiếp trên thanh công cụ terminal (`mm/yyyy hh:mm`) tạo cảm giác web app đang sống.
- 🚀 **Zero-Dependency Backend**: Backend được viết 100% bằng Vanilla PHP 8, không cần chạy composer install, không phụ thuộc thư viện ngoài, giúp việc deploy lên mọi shared hosting trở nên dễ dàng.
- 📱 **Mobile-First & Fully Responsive**: Giao diện được thiết kế bắt đầu từ mobile, đảm bảo không có bất kỳ thành phần nào bị tràn màn hình (overflow), từ text dài cho đến hệ thống dạng lưới (grid).
- 🌗 **Auto Dark Mode**: Tự động nhận diện theme hệ thống của người dùng (prefers-color-scheme) nhưng vẫn cho phép user tự lưu cấu hình ghi đè nếu muốn (thông qua `theme-init.js`).

---

## 3. Stack công nghệ (Tech Stack)

### Backend (Server-Side)
- **PHP 8.x**: Ngôn ngữ xử lý chính. Xử lý HTTP Headers, Cookies, Sessions, đọc file JSON và Server-Side Rendering (SSR).
- **Native Sessions & Cookies**: Dùng để lưu trữ trạng thái vượt rào bảo mật (Bot Guard).

### Frontend (Client-Side)
- **Vanilla JavaScript (ES6+)**: Xử lý logic như ScrollSpy, IntersectionObserver (cho animation), Realtime Clock và Mobile Menu. Không sử dụng jQuery.
- **CSS3 (Vanilla CSS)**: Quản lý biến CSS Variables cho Theming, CSS Grid/Flexbox cho Layout, và CSS Keyframes cho toàn bộ hệ thống animation (loading spinner, sweeping sheen, fade-up, blink).
- **HTML5 Semantic**: Đảm bảo cấu trúc SEO và Accessibility (ARIA labels, roles).

---

## 4. Kiến trúc & Cấu trúc thư mục (Architecture)

Toàn bộ thư mục được thiết kế theo hướng phẳng hóa (flat design) nhưng vẫn rạch ròi giữa code hệ thống và tài nguyên tĩnh.

```text
m008v-Profile/
├── index.php             # Entry point duy nhất. Chứa Bot Guard và SSR Logic.
├── profile.json          # Database thu nhỏ. Nơi chứa toàn bộ nội dung hiển thị.
├── README.md             # Tài liệu bạn đang đọc.
├── manifest.json         # PWA Manifest (tùy chọn bật).
└── assets/               # Chứa tài nguyên tĩnh
    ├── css/
    │   └── style.css     # Định dạng duy nhất cho toàn bộ hệ thống
    ├── fonts/
    │   └── webfonts/     # Local fonts (woff/woff2) đảm bảo tốc độ và FOIT/FOUT
    └── js/
        ├── main.js       # Logic frontend (Clock, ScrollSpy, Animations)
        └── theme-init.js # Script siêu nhỏ chạy chặn đầu head để tránh Flash of Unstyled Content (FOUC)
```

---

## 5. Cơ chế bảo mật Anti-Bot Guard

Đây là một trong những tính năng đáng tự hào nhất của dự án này, được thiết kế nằm ngay trong top của file `index.php`.

### Cách hoạt động (Workflow):
1. **Kiểm tra Cookie Session**: Khi một request đến, PHP kiểm tra xem có biến `$_SESSION['mh_bot_passed']` chưa.
2. **Khởi tạo Token (Lần đầu truy cập)**: Nếu chưa có, PHP sẽ không trả về HTML thật. Thay vào đó, nó tạo một mã `nonce` ngẫu nhiên và lưu giá trị mã băm SHA-256 của nó vào Session.
3. **Màn hình Loading Ảo (No-JS / CLI Loader)**: PHP trả về một trang HTML tối giản có hiệu ứng npm install spinner (braille loader). Trong trang này có nhúng một đoạn script JavaScript siêu nhỏ.
4. **JS Runtime Proof**: Đoạn script sẽ lấy mã `nonce` kia, băm nó bằng SubtleCrypto API trực tiếp trên trình duyệt của người dùng. Trình duyệt sau đó tự động set cookie `mh_js_proof` và reload lại trang.
5. **Xác thực và mở khoá (Verify & Unlock)**: Lần request thứ hai, PHP nhận cookie `mh_js_proof`, so khớp với dữ liệu trong Session. Nếu khớp, PHP gán cờ `mh_bot_passed = true` và cuối cùng là parse `profile.json` để render trang web xịn sò.

**Kết quả:** Các dạng bot crawl dữ liệu thuần túy (không execute được JS hoặc không hỗ trợ Crypto API) sẽ vĩnh viễn bị kẹt ở màn hình loading CLI ảo.

---

## 6. Quản lý nội dung qua JSON (Data Model)

Tất cả dữ liệu được lưu tại `profile.json`. Nếu file này lỗi cú pháp (Syntax Error), trang web sẽ dừng hoạt động. Vì thế hãy chú ý sử dụng các trình linter JSON khi chỉnh sửa.

### Cấu trúc cơ bản:
```json
{
  "profile": {
    "name": "Tên Hiển Thị",
    "brand": "Brand Cá Nhân",
    "email": "contact@example.com",
    "headline": "Mô tả siêu ngắn gọn xuất hiện ở Hero Section"
  },
  "stats": [
    { "value": "5+", "label": "Năm kinh nghiệm" }
  ],
  "highlights": [
    {
      "period": "10/2022 - __CURRENT_MONTH_YEAR__",
      "period_from": "10/2022",
      "title": "Chức danh",
      "company": "Tên công ty",
      "bullets": ["Thành tựu 1", "Thành tựu 2"]
    }
  ],
  "skills": [],
  "projects": [],
  "socials": []
}
```

**Tính năng đặc biệt `__CURRENT_MONTH_YEAR__`**:
Trong mảng `highlights`, nếu bạn muốn một mốc thời gian luôn là hiện tại (Ví dụ: "10/2022 - Nay"), hãy sử dụng token `__CURRENT_MONTH_YEAR__`. PHP sẽ tự động parse token này thành `MM/YYYY` thực tế lúc runtime.

---

## 7. Hệ thống Styling & Giao diện (Theming)

File `assets/css/style.css` không chỉ dùng để làm đẹp, mà còn thể hiện tư duy thiết kế hệ thống.

- **CSS Variables**: Các màu sắc cốt lõi như `--bg`, `--surface`, `--ink`, `--muted`, `--purple` được khai báo trong `:root`. Nhờ đó, việc đổi màu toàn bộ hệ thống hoặc implement theme mới chỉ mất 5 giây.
- **Fluid Typography**: Kích thước chữ như `font-size: clamp(2rem, 5vw, 3.5rem)` giúp chữ tự động co giãn êm ái trên từ màn hình iPhone SE cho tới màn hình 4K siêu rộng, không cần viết quá nhiều Media Queries rườm rà.
- **Animations (Keyframes)**: 
  - `introPulse`: Làm nút thương hiệu mờ ảo.
  - `ctaSheen`: Hiệu ứng vệt sáng lướt qua nút `.command-pill` cực kỳ mượt mà, bao trọn toàn bộ khối container (fix lỗi stutter).
  - `npmSpin`: Hiệu ứng loader Braille cực ngầu ở trang xác minh.

---

## 8. Tương tác JavaScript (Interactions)

File `assets/js/main.js` chịu trách nhiệm thổi hồn vào trang web.

- **Realtime Clock**: Thay thế dòng text nhàm chán bằng một đồng hồ sống động (format `mm/yyyy hh:mm`) tick mỗi giây trên thanh điều hướng góc màn hình.
- **ScrollSpy (IntersectionObserver)**: Khi bạn cuộn trang, thanh menu sẽ tự động bôi sáng (highlight) thẻ tương ứng với section bạn đang nhìn thấy.
- **Reveal on Scroll**: Các khối nội dung (`.motion-stack`, `.motion-card`) mặc định bị ẩn và dời xuống dưới một chút. Khi người dùng cuộn tới, chúng sẽ tự động mờ dần và trượt lên (fade-up). Logic này sẽ tự động tắt nếu người dùng bật chế độ `prefers-reduced-motion` trong OS.
- **Random Blink**: Các tag kỹ năng (`.tile`) lâu lâu sẽ tự chớp nháy giả lập tín hiệu đèn LED của server đang hoạt động.

---

## 9. Hướng dẫn cài đặt Local (Local Development)

Mọi thứ cực kỳ đơn giản vì không có package manager.

**Yêu cầu:**
- Cài đặt sẵn PHP 8.0+ trong máy (đã thêm vào PATH).

**Các bước chạy:**
1. Clone dự án về máy.
2. Mở Terminal / PowerShell, điều hướng (cd) vào thư mục dự án.
3. Chạy lệnh:
```bash
php -S localhost:8000 index.php
```
4. Mở trình duyệt và truy cập `http://localhost:8000`.

**Lưu ý khi test local:**
Khi sửa `profile.json`, đôi khi bạn viết sai cú pháp. Hãy luôn chạy:
```bash
php -l index.php
```
để đảm bảo file PHP không có lỗi cú pháp trước khi chạy.

---

## 10. Hướng dẫn triển khai Production (Deployment)

Dự án này là hoàn hảo để đẩy lên các Shared Hosting truyền thống (cPanel/DirectAdmin) hoặc VPS cài Nginx/Apache.

### Deploy bằng Nginx
Tạo một block server mới:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/m008v-Profile;
    index index.php;

    # Chống xem trộm JSON (RẤT QUAN TRỌNG)
    location ~ \.json$ {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
}
```
*Ghi chú cực quan trọng: File `profile.json` chứa email hoặc thông tin cá nhân mà bạn có thể không muốn bị crawl trực tiếp. Dòng cấu hình trên chặn bot truy cập thẳng vào URL `yourdomain.com/profile.json`.*

### Biến Môi Trường (Environment Variables)
Để tăng tính bảo mật cho thuật toán mã hóa cookie chống bot, hãy khai báo biến:
`BOT_GUARD_SECRET="chuoi_bi_mat_cua_rieng_ban"`
Nếu bạn dùng cPanel, có thể cấu hình biến này thông qua `.htaccess`:
```apache
SetEnv BOT_GUARD_SECRET "chuoi_bi_mat_cua_rieng_ban"
```

---

## 11. Troubleshooting (Khắc phục sự cố)

### Lỗi 1: Màn hình trắng toát hoặc báo Lỗi 500
- **Nguyên nhân**: Thường do file `profile.json` bị lỗi cú pháp (thiếu dấu phẩy, dư ngoặc kép).
- **Cách khắc phục**: Mở file `profile.json` bằng VSCode, tìm đến dòng báo đỏ và sửa lại cho chuẩn format JSON. Cẩn thận với các dấu phẩy ở phần tử cuối cùng của mảng (JSON không cho phép trailing comma).

### Lỗi 2: Bị kẹt vĩnh viễn ở màn hình "INITIALIZING EXPERIENCE..."
- **Nguyên nhân**: Trình duyệt của bạn đang tắt JavaScript, hoặc bạn đang dùng một Extension chặn Cookie/LocalStorage/Script quá mạnh bạo (như NoScript).
- **Cách khắc phục**: Hãy tạm thời tắt các trình chặn quảng cáo siêu cứng hoặc bật lại tính năng thực thi JS trên trình duyệt.

### Lỗi 3: Nút `command-pill` (nút email) bị biến thành dấu chấm nhỏ xíu
- **Khắc phục**: Đã được fix ở phiên bản mới nhất trên nhánh `main`. CSS đã được cập nhật `max-width: 100%` kết hợp cấu trúc Grid linh hoạt. Đảm bảo bạn đã clear cache trình duyệt `Ctrl + F5`.

---

## 12. Đóng góp & Giấy phép (License)

Dự án này mang tinh thần mở. Cảm ơn bạn đã đọc đến cuối tài liệu khổng lồ này!

- **License**: MIT License. Bạn có quyền tải về, sửa đổi, mang đi kinh doanh, xào nấu lại làm trang cá nhân của bạn mà không cần xin phép. Tuy nhiên, nếu bạn để lại một dòng nhỏ ghi danh tác giả gốc `MinhHuyDev` thì đó là một sự tôn trọng rất lớn.
- **Đóng góp**: Pull Requests (PR) luôn được chào đón. Nếu bạn tìm ra bug bảo mật trong Bot Guard hoặc cách tối ưu CSS tốt hơn, đừng ngần ngại gửi PR.

---
*Bản README được viết và format chuẩn Markdown, tự động generate dưới sự hỗ trợ chuyên sâu của AI Assistant.*
