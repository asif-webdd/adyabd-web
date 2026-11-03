# adyabd-web — মাল্টি-ভেন্ডর ই-কমার্স (Laravel + Filament + Nuxt)

```
adyabd-web/
├── backend/    Laravel 11 API + Filament 3 অ্যাডমিন প্যানেল   → http://localhost:8000
├── frontend/   Nuxt 3 ওয়েবসাইট (SSR)                         → http://localhost:3000
├── setup.sh / setup.ps1   এক-কমান্ড সেটআপ (Mac·Linux / Windows)
└── start.sh / start.ps1   দুটো সার্ভার একসাথে চালু
```

## ১) আগে যা ইনস্টল থাকা লাগবে

| টুল | ভার্সন | মন্তব্য |
|---|---|---|
| PHP | **8.2 – 8.4** | extensions: `mbstring intl gd curl zip pdo_sqlite` (Mac: `brew install php` এ সব আছে) |
| Composer | 2.x | https://getcomposer.org |
| Node.js | **20.19+ অথবা 22+** (24 ও চলে) | পুরনো Node (18, 20.0–20.18) এ `npm install`/`nuxt` **ফেইল করবে** — `nvm install 22` |

> ডাটাবেস আলাদা ইনস্টল লাগে না — ডিফল্টে SQLite (ফাইল)। MySQL চাইলে নিচে "MySQL" দেখুন।

## ২) সেটআপ (একবারই)

**Mac / Linux**
```bash
cd adyabd-web
./setup.sh
```
**Windows (PowerShell)**
```powershell
cd adyabd-web
Set-ExecutionPolicy -Scope Process Bypass
.\setup.ps1
```
স্ক্রিপ্ট যা করে: টুল/ভার্সন চেক → `composer install` → `.env` বানায় → অ্যাপ কী → টেবিল + ডেমো ডেটা → `npm install`।
আবার চালালেও **আপনার ডেটা মোছে না** (ডাটাবেসে ডেটা থাকলে শুধু নতুন মাইগ্রেশন চালায়)।

## ৩) চালু করুন
```bash
./start.sh          # Windows: .\start.ps1
```
| কী | ঠিকানা | লগইন |
|---|---|---|
| ওয়েবসাইট | http://localhost:3000 | ডেমো গ্রাহক `01711000001` / `Password@123` |
| অ্যাডমিন প্যানেল | http://localhost:8000/admin | `admin@bazar.com.bd` / `Admin@12345` (`backend/.env` এ বদলানো যায়) |
| ডেমো সেলার | http://localhost:3000/seller/dashboard | সেলারের ফোন নম্বর (Admin → Sellers এ দেখুন) / `Password@123` |

> OTP/SMS লোকালে যায় না; কোড দেখতে `backend/storage/logs/laravel.log`।

### হাতে হাতে চালাতে চাইলে (স্ক্রিপ্ট ছাড়া)
```bash
# টার্মিনাল ১
cd backend && composer install && cp .env.example .env && touch database/database.sqlite \
  && php artisan key:generate && php artisan shop:setup && php artisan serve
# টার্মিনাল ২
cd frontend && cp .env.example .env && npm install && npm run dev
```

## ৪) সমস্যা হলে (Troubleshooting)

| সমস্যা | সমাধান |
|---|---|
| `npm install` এ `EBADENGINE` / nuxt চলে না | Node ভার্সন (উপরে টেবিল)। `node -v` দেখুন → `nvm install 22 && nvm use 22` → `rm -rf node_modules .nuxt && npm install` |
| `npm install` এ `ERESOLVE` | `npm install --legacy-peer-deps` |
| `composer install` এ "requires ext-xxx" | সেই PHP extension চালু করুন (`php -m` দিয়ে দেখুন) |
| `composer install` এ "lock file is not up to date" | এই ভার্সনে ঠিক করা আছে। তবুও এলে: `composer install --no-interaction` (সতর্কবার্তা, এরর নয়) |
| `Class "..." not found` / পুরনো ক্যাশ | `cd backend && composer dump-autoload && php artisan optimize:clear` |
| অ্যাডমিন প্যানেলে ডেটা খালি / সিড মাঝপথে থেমেছে | `cd backend && php artisan migrate:fresh --seed` (⚠ সব ডেটা মোছে) |
| `Invalid key supplied` / Passport কী | `php artisan passport:keys --force` |
| ফ্রন্টএন্ডে API এরর / CORS | `frontend/.env` এ `NUXT_PUBLIC_API_BASE=http://localhost:8000` আছে কিনা + backend চালু কিনা; `.env` বদলালে `npm run dev` আবার চালান |
| পোর্ট ব্যস্ত | `php artisan serve --port=8001` এবং `NUXT_PUBLIC_API_BASE` মিলিয়ে নিন; Nuxt: `npm run dev -- --port 3001` |
| সব কিছু নতুন করে | `backend/database/database.sqlite` মুছে `./setup.sh` |

## ৫) MySQL ব্যবহার (ঐচ্ছিক)
MySQL এ `adyabd_web_db` ডাটাবেস বানান। `backend/.env` এ `DB_CONNECTION=sqlite` লাইন মুছে/কমেন্ট করে দিন:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adyabd_web_db
DB_USERNAME=root
DB_PASSWORD=
```
তারপর `php artisan shop:setup`।

## ৬) অ্যাডমিন প্যানেলে কী কী আছে
- **Sales / Product / Customer:** অর্ডার, রিটার্ন, পণ্য, ক্যাটাগরি, ব্র্যান্ড, কুপন, ফ্ল্যাশ সেল, রিভিউ, ইউজার (ব্লক/ওয়ালেট), সেলার ও **Seller Withdrawals**।
- **Support:** **Support Tickets** (কাস্টমারের কথোপকথন দেখুন + *Reply to customer* — গ্রাহক অ্যাপে নোটিফিকেশন + SMS পায়), **Contact Messages** (*Reply* বাটনে SMS/ইমেইলে উত্তর)।
- **Content:** ব্যানার, ব্লগ, **Site Pages** (about/privacy/terms/return), **FAQs**।
- **Settings → Admin Users / Admin Roles:** স্টাফ অ্যাকাউন্ট ও role। Role = কোন কোন এলাকা (Orders, Catalog, Finance, Support…) খুলতে পারবে। *role না দিলে* = পূর্ণ অ্যাক্সেস (প্রথম অ্যাডমিন এভাবেই)। ডেমো `manager@example.com` এর role "Order Manager" — পাসওয়ার্ড `Admin@12345`। Admin Users ও Roles শুধু পূর্ণ-অ্যাক্সেস অ্যাডমিন দেখে।
- **Settings / Appearance:** সব ট্যাবের প্রতিটি অপশন সাইটে কার্যকর (নাম, ফোন, ঠিকানা, অফিস সময়, WhatsApp, SMS টগল ও টেমপ্লেট, পেমেন্ট/শিপিং সুইচ, সেলার মিনিমাম উত্তোলন, ট্র্যাকিং স্ক্রিপ্ট ইত্যাদি)।

- **এক্সপোর্ট ও ইমেইল:** প্রধান লিস্টগুলোতে *Export CSV* (Excel-এ বাংলা ঠিক দেখায়); অর্ডার/টিকিট আপডেটে কাস্টমারকে ইমেইল (Settings → SMTP → Customer emails)।

## ৭) ঐচ্ছিক ইন্টিগ্রেশন (না দিলেও সাইট চলে)
- **ZiniPay (অনলাইন পেমেন্ট):** Admin → Settings → Payment এ API key। না দিলে COD/ওয়ালেট চলে।
- **SMS:** Settings → SMS এ গেটওয়ে টোকেন। না দিলে SMS লগে ব্যর্থ দেখায়, অর্ডার আটকায় না।
- **ইমেইল (কন্ট্যাক্ট রিপ্লাই):** Settings → SMTP। লোকালে `MAIL_MAILER=log` (ইমেইল `storage/logs` এ লেখা হয়)।
- **কুরিয়ার:** Admin → Courier Merchants এ কী।
- **Google লগইন:** `backend/.env` এ `GOOGLE_CLIENT_ID`, `frontend/.env` এ `NUXT_PUBLIC_GOOGLE_CLIENT_ID`।
- **Facebook লগইন:** `backend/.env` এ `FACEBOOK_APP_ID` + `FACEBOOK_APP_SECRET`, `frontend/.env` এ `NUXT_PUBLIC_FACEBOOK_APP_ID` (developers.facebook.com থেকে; App-এ `email` permission ও আপনার ডোমেইন দিন)। কনফিগার না করলে বাটন দেখায় না।
- **ভাষা (বাংলা/English):** হেডারের `EN`/`বাং` বাটন। ইংরেজি লেখা `frontend/locales/en.js` এ — আরও অনুবাদ করতে টেমপ্লেটে `{{ tr('বাংলা লেখা') }}` দিয়ে এখানে জোড়া যোগ করুন।

## ৮) টেস্ট
```bash
cd backend
composer require --dev phpunit/phpunit:^11 mockery/mockery fakerphp/faker   # একবার
php artisan test                                                            # ৩৮টি টেস্ট
php tools/crawl_admin.php list                                             # সব অ্যাডমিন পেজ খোলে কিনা

# ফ্রন্টএন্ড (npm install ছাড়াই চলে, শুধু Node লাগে)
cd ../frontend && npm test                                                 # ১০টি টেস্ট: অনুবাদ, সিনট্যাক্স, normalizeProduct
```

## ৯) ওয়েবসাইট লাইভ সার্ভারে (Production)
1. `backend/.env.production.example` ও `frontend/.env.production.example` দেখে `.env` বানান (MySQL, `APP_ENV=production`, `APP_DEBUG=false`, আসল ডোমেইন)।
2. Backend: `composer install --no-dev -o && php artisan key:generate && php artisan shop:setup && php artisan storage:link && php artisan config:cache`
   ⚠ `shop:setup` খালি ডাটাবেসে **ডেমো ডেটা** ঢোকায় — লাইভে আসল ডেটা চাইলে ডেমো সিডার মুছে `php artisan migrate --force` ব্যবহার করুন ও ডেমো ইউজার/পণ্য অ্যাডমিন থেকে মুছুন।
3. **একটিই cron লাইন** (queue + schedule দুটোই চলে, আলাদা worker লাগে না):
   `* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1`
4. Frontend: `.env` এ `NUXT_PUBLIC_API_BASE`, `NUXT_PUBLIC_SITE_URL` → `npm install && npm run build && node .output/server/index.mjs`
5. Search Console এ `https://yourdomain.com/sitemap.xml` দিন।

## সীমাবদ্ধতা (সৎ তালিকা)
- ZiniPay, SMS গেটওয়ে ও কুরিয়ার API আসল অ্যাকাউন্টে এই প্রজেক্ট তৈরির পরিবেশে পরীক্ষা করা যায়নি (নকল রেসপন্স দিয়ে যাচাই) — প্রথমে একটি টেস্ট অর্ডার করে দেখুন।
- পেমেন্টে শুধু COD, ওয়ালেট ও ZiniPay; bKash/Nagad সরাসরি API নেই।
- HR মৌলিক: Payroll পেজে *Generate payroll* attendance/leave থেকে অটো-হিসাব করে, তবে ট্যাক্স/overtime/payslip PDF নেই।
