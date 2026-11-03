# পরিবর্তনের তালিকা

## ধাপ ১ – নিরাপত্তা
- PaymentController: ZiniPay ইনভয়েসের অঙ্ক এখন সার্ভারের `order.total_amount` থেকে; ক্লায়েন্টের `amount` উপেক্ষিত। verify/webhook এ পরিশোধিত অঙ্ক যাচাই। পরিশোধিত/বাতিল/ভুল-মেথডের অর্ডারে পেমেন্ট তৈরি বন্ধ।
- ইনভয়েস: `/bulk-invoice` এ অ্যাডমিন অথ; গ্রাহক ইনভয়েস শুধু ৩০ মিনিটের signed URL (`GET /api/v1/orders/{id}/invoice` → `invoice_url`)।
- AuthController: OTP-লগইনের টোকেন বাগ (`plainTextToken` → `accessToken`), `random_int`, প্রতি OTP-তে ৫ বার ভুলের সীমা, `hash_equals`।
- রেট-লিমিট: login/register/send-otp/verify-otp/forgot/reset।
- AdminUserSeeder: হার্ডকোড পাসওয়ার্ড বাদ (`ADMIN_SEED_PASSWORD`), প্রোডাকশনে ডেমো ম্যানেজার নেই।
- scheduler: ভুল `sanctum:prune-expired` → `passport:purge`।

## ধাপ ২ – অর্ডার সততা
- **ফ্যাটাল বাগ ফিক্স:** `BlockingService.php` এ সিনট্যাক্স এরর (`\$user`) ছিল – ফলে কোনো অর্ডারই প্লেস হতো না।
- **API স্ট্যাটাস কোড বাগ:** `Controller::error($msg, 422)` আসলে 404 পাঠাত, `success(..., 201)` সবসময় 200 দিত। এখন সঠিক কোড যায়।
- OrderController::store: সবকিছু একটি DB ট্রানজেকশনে; `lockForUpdate` (ওভারসেলিং বন্ধ); একই পণ্যের ডুপ্লিকেট লাইন মার্জ; ভ্যারিয়েন্ট-পণ্য মিল যাচাই; অকার্যকর/মেয়াদোত্তীর্ণ কুপন এখন ত্রুটি (আগে নীরবে উপেক্ষিত) + প্রতি-ইউজার লিমিট + নতুন-ইউজার কুপন; ওয়ালেট কাটা ও লেজার এন্ট্রি (balance_before/after) এক ট্রানজেকশনে; SMS/কুরিয়ার কমিটের পরে (ব্যর্থ হলে অর্ডার নষ্ট হয় না)।
- cancel: লক + ডাবল-ক্যানসেল বন্ধ। স্টক/কুপন/ওয়ালেট-রিফান্ড এখন `Order::handleCancelled()` এ, ফলে অ্যাডমিন/অটো-ব্লক ক্যানসেলেও স্টক ফেরে; রিফান্ড দুবার হয় না।
- অর্ডার নম্বর: সফট-ডিলিটেড সহ গণনা + ফাঁকা নম্বর খুঁজে নেয় (ডুপ্লিকেট এড়ায়)।
- `track()`: অর্ডার না পেলে 404 (আগে 500)।
- মাইগ্রেশন: MySQL-only `MODIFY ENUM` SQLite এ স্কিপ; `zinipay` বেস enum এ যুক্ত।

## ধাপ ২.৫ – অ্যাডমিন প্যানেল ফিক্স (আপনার রিপোর্ট অনুযায়ী)
- **ডেটা খালি:** রুট কজ – `passport:keys` না থাকলে `UserSeeder` এ "Invalid key supplied" এরর, সিডিং থেমে যেত (শুধু অ্যাডমিন তৈরি হতো)। এখন `DatabaseSeeder` শুরুতেই Passport কী ও personal client তৈরি করে।
- **Delivery Charges:** `DeliveryCharge` ছিল সাধারণ ক্লাস, Eloquent Model নয় → `Call to undefined method ::query()`। এখন Model; চার্জ প্রথমে `delivery_charges` টেবিল (উপজেলা > জেলা > বিভাগ), না পেলে পুরনো সেটিং, শেষে ডিফল্ট।
- **Profit/Revenue রিপোর্ট:** MySQL-only `DATE_FORMAT` → ডাটাবেস অনুযায়ী (`strftime` SQLite এ)।
- **Employee create:** `FileUpload::circular()` নেই → `avatar()`।
- **My Profile:** view ফাইল `Profile.blade.php` → `profile.blade.php` (Linux সার্ভারে কেস-সেনসিটিভ)।
- `checkout/config` রুট ডুপ্লিকেট ছিল (অথ-গ্রুপেরটি পাবলিকটাকে ঢেকে দিত) → ডুপ্লিকেট সরানো।
- নতুন টুল: `backend/tools/crawl_admin.php` – সব ৭৮টি অ্যাডমিন পেজ (list/create/view/edit) খুলে ব্যর্থগুলো দেখায়। এখন সবগুলো ok।

## ধাপ ৩ – অসম্পূর্ণ ফিচার সম্পূর্ণ করা
**ব্যাকএন্ড** (নতুন মাইগ্রেশন: `2026_10_26_000001_add_contact_returns_followers_and_social` – `php artisan migrate` চালান)
- **সেলার রেজিস্ট্রেশন:** `POST /seller/register` (লগইন লাগে) → `pending` সেলার তৈরি; অ্যাডমিন অনুমোদন করলে `active`।
- **সেলার রোল-চেক:** নতুন `seller` মিডলওয়্যার – সেলার নয় / পেন্ডিং / ব্লকড হলে 403 (`NOT_A_SELLER`, `SELLER_PENDING`…)। আগে যেকোনো লগইন ইউজার সেলার API ধরতে পারত।
- **সেলার API:** ফিল্ড হোয়াইটলিস্ট (mass-assignment বন্ধ), সেলার নিজে `delivered` বা পণ্য অনুমোদন করতে পারে না, নতুন পণ্য ডিফল্টে অনুমোদনের অপেক্ষায় (`seller_products_auto_approve` সেটিং), ছবি আপলোড।
- **সেলার পাবলিক পেজ:** শুধু active সেলার দেখা যায়; ফলো/আনফলো (`POST /sellers/{slug}/follow`), `followers_count`, `is_following`।
- **Contact ফর্ম:** `contact_messages` টেবিল + `POST /contact` + Admin → Contact Messages।
- **রিটার্ন:** `return_requests` টেবিল; গ্রাহক ৭ দিনের মধ্যে অনুরোধ করে (ডুপ্লিকেট বন্ধ); Admin → Return Requests থেকে approve / reject / ওয়ালেটে রিফান্ড।
- **Google লগইন:** `POST /auth/social-login` – Google ID-token সার্ভারে যাচাই (aud/iss/email_verified)। `GOOGLE_CLIENT_ID` সেট না থাকলে 501। ফোন ছাড়া ইউজার তৈরি হয়, পরে প্রোফাইলে ফোন যোগ করা যায়।
- **ওয়ালেট উইথড্র:** অ্যাটমিক (লক + লেজার), সর্বোচ্চ ৩টি পেন্ডিং; অ্যাকাউন্ট ডিলিটে ফোন/ইমেইল অ্যানোনিমাইজ (পরে একই নম্বরে নতুন রেজিস্ট্রেশন সম্ভব)।
- **কুরিয়ার স্ট্যাটাস:** SteadFast ওয়েবহুক + `php artisan bazar:update-courier-status` (পোলিং) – দুটোই `Order::applyCourierStatus()` দিয়ে যায়, তাই ডেলিভারড হলে সেলারের ব্যালেন্স ঠিক একবারই জমা হয়।
- CORS: `FRONTEND_URL` থেকে অনুমোদিত অরিজিন।

**ফ্রন্টএন্ড**
- Auth store-এ অনুপস্থিত অ্যাকশন যোগ: `register`, `sendLoginOtp`, `loginWithOtp`, `socialLogin`, `updateProfile` (আগে এগুলো না থাকায় রেজিস্ট্রেশন/OTP লগইন ভাঙা ছিল)।
- Contact পেজ API-তে যুক্ত; সাপোর্ট চ্যাটের ভুয়া "প্রতিনিধি টাইপ করছেন" বট সরিয়ে আসল টিকেট তৈরি।
- লগইন পেজে Google সাইন-ইন (`NUXT_PUBLIC_GOOGLE_CLIENT_ID`), Facebook প্লেসহোল্ডার সরানো।
- নতুন পেজ `/seller/dashboard` (পরিসংখ্যান, অর্ডার স্ট্যাটাস, পণ্য যোগ); অ্যাকাউন্ট মেনুতে বিক্রেতা লিংক; সেলার রেজিস্ট্রেশনে লগইন-গেট; ফলো স্টেট সার্ভার থেকে; প্রোফাইলে ফোন না থাকলে যোগ করা যায়।
- ⚠️ ফ্রন্টএন্ড এই পরিবেশে বিল্ড/চালানো যায়নি (npm ব্লকড); শুধু স্ক্রিপ্ট সিনট্যাক্স চেক হয়েছে। প্রথমবার `npm run dev` চালিয়ে পেজগুলো ঘুরে দেখুন।

## ধাপ ৪ – পরিচ্ছন্নতা
- অপ্রয়োজনীয় ফাইল মুছেছি: `*.bak`, `backend/.rnd`, `.idea`, ভাঙা `pages/indexRaw.vue`।
- প্রজেক্টের নাম `adyabd-web` (composer/package নাম, APP_NAME, ডকুমেন্ট)। দোকানের দৃশ্যমান নাম/লোগো Admin → Appearance থেকে বদলান।
- `backend/tools/smoke_step3.py` – চালু সার্ভারের বিপরীতে সেলার/কনট্যাক্ট/রিটার্ন/উইথড্র টেস্ট।

## সেটিংস পেজ ফিক্স (আপনার রিপোর্ট)
- **সমস্যা:** একটি Save বাটনে সব ট্যাব একসাথে যাচাই হতো, তাই অন্য ট্যাবে সেভ করতেও SMTP-র "Mail Driver required" এরর আসত।
- **এখন:** প্রতিটি ট্যাবের নিচে নিজস্ব "Save … Settings" বাটন; শুধু ওই ট্যাবের ফিল্ড যাচাই ও সেভ হয়, অন্য ট্যাব অপরিবর্তিত। সেভ না করা ফিল্ডে ডিফল্ট মান (যেমন Mail Driver = smtp) দেখায়।
- "Send Test Email" আর অন্য ট্যাবের ফর্ম-ভ্যালিডেশনে আটকায় না।
- বাগ: Delivery-তে এলাকাভিত্তিক চার্জ একবার সেভ করলে পেজ 500 দিত (JSON স্ট্রিং রিপিটারে ঢুকত) – ঠিক করা হয়েছে।

## ধাপ ৫ – অ্যাডমিন সেটিংস সংযোগ + মাল্টি-ভেন্ডর (আপনার অনুরোধে)
**পপআপ:** কারণ – শুধু "আর দেখাবেন না" চাপলেই মনে রাখা হতো; X/বাইরে ক্লিকে বন্ধ করলে প্রতি রিলোডে আবার আসত। এখন Admin → Appearance → Popup Banner এ নতুন "How often to show" (একবারই / প্রতি ভিজিটে একবার / ২৪ ঘণ্টায় একবার / প্রতিবার)। ডিফল্ট: প্রতি ভিজিটে একবার।

**অ্যাডমিনে সেভ হতো কিন্তু কোড পড়ত না – এখন কাজ করে:**
- SMS গেটওয়ে টোকেন/SID/Sender ID (আগে শুধু `.env` পড়ত)।
- Settings → SMS এর "On Order Placed/Confirmed/Shipped/Delivered/Cancelled/User Blocked" সুইচ।
- Notification Templates: এডিট করা টেক্সটই SMS-এ যায়; টেমপ্লেট বন্ধ করলে ওই SMS যায় না; ভ্যারিয়েবল `{shop_name} {helpline} {site_url} {order_number} {customer_name} {total_amount} {tracking_id} {tracking_url} {otp} {reason}`। অর্ডার-প্লেসড, OTP, ব্লক/আনব্লক, ডেলিভারি-ব্যর্থ সবই টেমপ্লেট ব্যবহার করে। কুরিয়ার বুক করলে ডাবল SMS যেত – বন্ধ।
- **নতুন ইনস্টলে SMS টেমপ্লেট, সেটিংস ডিফল্ট ও অটো-ব্লক রুল সিডই হতো না** (পেজ খালি দেখাত) – `DatabaseSeeder` এ যুক্ত; পুরনো ইনস্টলের জন্য `migrate` খালি টেবিল ভরে দেয় (এডিট করা কিছু মোছে না)।
- Automatic Order Confirmation, Auto Blocking / Auto IP Blocking সুইচ, Enable Reviews / Auto Approve Reviews, Products per page, Mail Driver (DB থেকে), Referral (প্রথম ডেলিভারড অর্ডারে রেফারারের ওয়ালেটে বোনাস, লেজারসহ, একবারই), Social/Footer লিংক ফলব্যাক।
- রিভিউ: শুধু ডেলিভারড ক্রেতা রিভিউ দিতে পারে (verified purchase); ডুপ্লিকেট রিভিউতে আগে 500 আসত – এখন 422।

**মাল্টি-ভেন্ডর (গুরুতর বাগ ঠিক):**
- আগে একাধিক সেলারের পণ্য একসাথে কিনলে পুরো অর্ডার ও সম্পূর্ণ payout প্রথম সেলারের নামে যেত। এখন চেকআউটে **প্রতি সেলারের আলাদা অর্ডার** (আলাদা শিপমেন্ট ⇒ আলাদা ডেলিভারি চার্জ), কুপন ছাড় আনুপাতিক ভাগ, ওয়ালেট একবারই কাটে (প্রতি অর্ডারে লেজার), ZiniPay একটি ইনভয়েসে সব অর্ডারের মোট টাকা নেয় ও সব অর্ডার paid করে। ফ্রন্টএন্ডে একাধিক অর্ডার নম্বর দেখায়।
- কমিশন এখন **সেলারের নিজস্ব `commission_rate`** (আগে সবার জন্য গ্লোবাল ১০%)।
- suspended/pending সেলারের পণ্য কেনা যায় না।
- Admin → Sellers: "Make Payment" এ ব্যালেন্সের বেশি দেওয়া যায় না, অ্যাটমিক + লেজার; নতুন Suspend / Reactivate।
- টেস্ট: `tools/smoke_step5.py`, `tools/inproc_step5.php` (তাজা সিড করা ডাটাবেসে চালান)।

## ধাপ ৬ — সেলার withdraw, clawback, নোটিফিকেশন, অ্যাডমিন ডাইনামিক

**সেলার টাকা উত্তোলন (withdraw):** সেলার ড্যাশবোর্ডে নতুন "আয় ও উত্তোলন" ট্যাব — ব্যালেন্স, সর্বনিম্ন উত্তোলন, bKash/Nagad/ব্যাংক থেকে বেছে অনুরোধ, ইতিহাস ও আয়-ব্যয়ের খাতা। অনুরোধ করলে টাকা সঙ্গে সঙ্গে ব্যালেন্স থেকে আটকে যায় (সর্বোচ্চ ৩টি pending)। Admin → Product Management → **Seller Withdrawals** থেকে *Mark paid* (রেফারেন্স বাধ্যতামূলক) বা *Reject* (কারণ বাধ্যতামূলক, টাকা ফেরত)। দুই ক্ষেত্রেই সেলারকে নোটিফিকেশন + SMS।
**Clawback:** ডেলিভারির পর অর্ডার returned হলে বা Return Request এ refund দিলে সেলারের আগে পাওয়া payout একবারই কেটে নেওয়া হয় (লেজার `order_payout_reversal`; ব্যালেন্স নেগেটিভ হতে পারে)।
**সেলার নোটিফিকেশন:** নতুন অর্ডার / বাতিল / withdraw পরিশোধ-বাতিল — ড্যাশবোর্ডের "নোটিফিকেশন" ট্যাব (unread badge) + SMS টেমপ্লেট (`seller_new_order` ইত্যাদি, Admin → SMS/Notification Templates এ এডিটযোগ্য)। Settings → SMS এ `sms_on_seller_new_order` টগল।
**payout একবারই:** ডেলিভারিতে `creditSeller()` শুধু একবার (`orders.seller_credited_at`)।
**ফ্ল্যাশ সেল:** চেকআউটে ফ্ল্যাশ-সেল দাম ও লিমিট কার্যকর, বাতিলে `sold_count` ফেরত।
**Settings সুইচ এখন সত্যিই কাজ করে:** `enable_cod / enable_zinipay / enable_wallet`, `enable_standard / express / same_day_shipping` — বন্ধ থাকলে চেকআউটে 422।
**সেলার-নির্দিষ্ট কুপন:** শুধু ওই সেলারের পণ্যের উপর ছাড়।
**নতুন Settings:** `seller_min_withdrawal` (ডিফল্ট ৫০০), `seller_products_auto_approve`, Shop address, Office hours (প্রতি লাইনে `লেবেল | সময়`)।
**CMS (Admin → Content):** *Site Pages* (about/privacy/terms/return — খালি রাখলে সাইটের ডিফল্ট লেখা থাকে, লিখলে সেটি দেখায়; HTML sanitize করা) ও *FAQs* (contact পেজে দেখায়)।
**Contact/Footer ডাইনামিক:** ঠিকানা, ফোন, ইমেইল, অফিস সময়, সোশ্যাল লিংক এখন Settings/Appearance থেকে; WhatsApp নম্বর দিলে ভাসমান WhatsApp বাটন। `custom_head_script` / `custom_body_script` সাইটে প্রয়োগ হয়।
**ডেমো সেলার লগইন:** সিডে প্রতিটি active সেলারের জন্য ইউজার তৈরি — ফোন = সেলারের ফোন, পাসওয়ার্ড `Password@123`।
**নতুন মাইগ্রেশন:** `2026_10_27_000003` (withdrawals + payout tracking), `000004` (সেলার SMS টেমপ্লেট), `000005` (site_pages, faqs)। পুরনো ডাটাবেসে শুধু `php artisan migrate`।
**টেস্ট:** `tools/inproc_step6.php` (25), `tools/cms_test.php` (10), `tools/crawl_admin.php` (৮৫ অ্যাডমিন পেজ) — তাজা সিড করা DB তে চালান।

## ধাপ ৮ — সাপোর্ট রিপ্লাই, কন্ট্যাক্ট রিপ্লাই, অ্যাডমিন role, সহজ সেটআপ

**Support Tickets:** টিকেট খুললে নিচে পুরো কথোপকথন (গ্রাহক + সাপোর্ট টিম) ও "Reply to customer" বাটন। রিপ্লাইয়ে স্ট্যাটাস `in_progress`, গ্রাহক অ্যাপ-নোটিফিকেশন + SMS (টেমপ্লেট `support_reply`, টগল `sms_on_support_reply`) পায়। ফ্রন্টএন্ডে /support → "সাপোর্ট টিকেট" এ টিকেটে ক্লিক করে উত্তর দেখা ও আবার লেখা যায়। (আগে `assignedTo` রিলেশন না থাকায় টিকেট এডিট পেজ ভাঙা ছিল — ঠিক।)
**Contact Messages:** "Reply" বাটন — SMS / ইমেইল / দুটোই; কিছু একটা পৌঁছালে উত্তর সংরক্ষণ + status resolved; কিছুই না গেলে স্পষ্ট এরর ও message open থাকে।
**Admin Users + Roles (Settings গ্রুপে):** নিজস্ব সরল role সিস্টেম (নতুন প্যাকেজ ছাড়া)। ১২টি area (orders, catalog, customers, sellers, finance, content, marketing, support, hr, reports, settings, system)। প্রতিটি রিসোর্স/পেজ/ড্যাশবোর্ড উইজেট area দিয়ে সুরক্ষিত — URL সরাসরি লিখলেও 403। role ছাড়া অ্যাডমিন = পূর্ণ অ্যাক্সেস (আপগ্রেডে কেউ বাইরে পড়ে না)। Admin Users/Roles শুধু পূর্ণ-অ্যাক্সেস অ্যাডমিন দেখে; নিজেকে মোছা যায় না; ব্যবহারে থাকা role মোছা যায় না। ৫টি রেডিমেড role সিড হয়।
**মাইগ্রেশন:** `2026_10_28_000001` (contact reply কলাম, admin_roles, admin_users.role_id, SMS টেমপ্লেট)।
**সেটআপ সহজ:** `setup.sh`/`setup.ps1` (এক কমান্ড), `start.sh`/`start.ps1`, নতুন `php artisan shop:setup` (খালি DB তে মাইগ্রেট+ডেমো ডেটা, ডেটা থাকলে শুধু মাইগ্রেট)। `.env.example` এখন লোকাল-বান্ধব (SQLite/sync); লাইভের জন্য `.env.production.example`।
**ভার্সন সমস্যা ঠিক:** `composer.lock` ও `composer.json` মিলছিল না (রিনেমে hash ভেঙেছিল) → ঠিক; আমার যোগ করা `require-dev` সরানো (lock অক্ষত); `tailwind.config.js` ESM এ (প্রজেক্ট `"type": "module"`); `engines`/`.nvmrc` এ Node ভার্সন; সেটআপ স্ক্রিপ্ট PHP/Node/extension আগে চেক করে স্পষ্ট বার্তা দেয়।
**টেস্ট:** `SupportAndRolesTest` (৬) সহ মোট ১৮টি PHPUnit টেস্ট; `shop:setup` ও crawl (৮৭ অ্যাডমিন পেজ) যাচাই।
**README:** পুরনো `README-LOCAL.md` ও বিভ্রান্তিকর ব্যাকএন্ড README (ভুল পাসওয়ার্ডসহ) বাদ; রুটে একটিই `README.md`।

## ধাপ ৭ — SSR/SEO, PHPUnit, queue, হার্ডকোডেড লেখা

**SSR / SEO:** পণ্য ও ক্যাটাগরি পেজ এখন সার্ভারে ডেটা নিয়ে রেন্ডার হয় (`useAsyncData`), তাই Google/Facebook প্রিভিউ পুরো কনটেন্ট পায়। প্রতি পণ্যে title, description, Open Graph, canonical ও schema.org `Product` (দাম/স্টক) যোগ। হোম পেজের meta দোকানের নাম + ট্যাগলাইন থেকে। নতুন `/sitemap.xml` (পণ্য + ক্যাটাগরি, ব্যাকএন্ডের `GET /sitemap-data` থেকে, ১০ মিনিট ক্যাশ) ও `/robots.txt` (account/cart/checkout ইত্যাদি বাদ)। `<html lang="bn">`।
**PHPUnit:** `composer.json` এ `require-dev` (phpunit ^11, mockery, faker) ও `composer test`। নতুন টেস্ট: `CheckoutRulesTest`, `SellerPayoutTest`, `CmsContentTest`, সাথে `AdminPagesTest`। চালাতে: `composer install && php artisan test`।
**Queue:** আলাদা worker লাগে না — scheduler প্রতি মিনিটে `queue:work --stop-when-empty` চালায়; সার্ভারে শুধু একটি cron লাইন: `* * * * * cd /path/backend && php artisan schedule:run >> /dev/null 2>&1`। `.env.example` এ `QUEUE_CONNECTION=database`। অ্যাডমিন প্যানেলে cron না চললে/জব আটকে থাকলে/ফেইল হলে হলুদ সতর্কতা ব্যানার দেখায়।
**হার্ডকোডেড লেখা:** "বাজার" নাম ও ১৬৪৮৮ ফোন কোড থেকে সরানো — এখন Settings থেকে (`useShopName()`); support পেজের ফোন/সময়, seller register, terms/privacy, 404, SEO title সব ডাইনামিক। About পেজের বানানো সংখ্যা/টিম/ইতিহাস সরিয়ে নিরপেক্ষ ডিফল্ট; আসল লেখা Admin → Content → Site Pages থেকে। `nuxt.config.ts` এ bazar.com.bd ডিফল্ট সরানো (`.env` এ `NUXT_PUBLIC_API_BASE` ও `NUXT_PUBLIC_SITE_URL` দিন)।
**ছোট ঠিক:** ক্যাটাগরি পেজে slug বদলালে ক্যাটাগরির নাম/সাবক্যাটাগরি আর পুরনো থাকে না।

## ধাপ ৯ — এক্সপোর্ট, ইমেইল, payroll, blog SSR, OTP বাগ

**CSV/Excel এক্সপোর্ট:** Order, User, Transaction, Seller, Product, Seller Withdrawal, Return Request, Contact Message, Employee, Payroll — প্রতিটি লিস্টে "Export CSV" বাটন। ফিল্টার/সার্চ/সর্ট যা দেখছেন ঠিক তাই নামে; সর্বোচ্চ ৫০,০০০ সারি; Excel-এ বাংলা ঠিক দেখাতে UTF-8 BOM; `=`, `+`, `-`, `@` দিয়ে শুরু হওয়া টেক্সটে formula-injection সুরক্ষা।
**কাস্টমার ইমেইল:** অর্ডার প্লেস/কনফার্ম/শিপ/ডেলিভারি/ক্যানসেল এবং support ticket reply-তে কাস্টমারের ইমেইল থাকলে মেইল যায় (SMS-এর পাশাপাশি)। Settings → SMTP → "Customer emails" থেকে তিনটি টগল। মেইল ফেল করলে অর্ডার আটকায় না।
**HR Payroll অটো-জেনারেট:** Payroll পেজে "Generate payroll" (মাস/বছর) — সক্রিয় কর্মীদের জন্য attendance + leave থেকে হিসাব (শুক্রবার ছুটি; অনুপস্থিত দিন × দৈনিক বেতন কর্তন; মাসে attendance ডেটা না থাকলে কর্তন নেই)। আগে তৈরি রেকর্ড স্কিপ হয় (দুইবার চালালেও ডুপ্লিকেট নয়)। "Mark paid" অ্যাকশন। মাসের নাম এক ঘর সরে দেখানোর বাগ ঠিক।
**Blog SSR/SEO:** blog লিস্ট ও পোস্ট পেজ এখন সার্ভারে রেন্ডার (title, description, Open Graph); sitemap.xml এ `/blog` ও সব published পোস্ট; search পেজ `noindex`।
**বাগ ঠিক (গুরুত্বপূর্ণ):** `verify-otp` এ `otpLockedOut/otpFailed` মেথড অনুপস্থিত ছিল — OTP যাচাই 500 দিত। এখন ৫ বার ভুল OTP দিলে কোড বাতিল (429), নতুন OTP চাইলে কাউন্টার রিসেট।
**ফ্রন্টএন্ড SSR বাগ (Windows রান থেকে পাওয়া):** হোম/ক্যাটাগরি/পণ্য পেজে `useHead` এর ভেতরে `useRuntimeConfig()` ডাকা হতো → সার্ভারে "composable called outside of Nuxt context" 500। এখন setup-এ একবার পড়া হয়।
**বাগ ঠিক:** অ্যাকাউন্ট সাইডবারে সেলার থাকলেও সবসময় "বিক্রেতা হোন" দেখাত (`authStore.user` ref ছিল, `.value` বাদ পড়েছিল) → এখন সেলার হলে "বিক্রেতা ড্যাশবোর্ড"; পেজ খুললে ইউজার ডেটা রিফ্রেশ হয়।
**সেলার/অ্যাডমিন ফিক্স (Windows রান থেকে পাওয়া):**
- **Add to cart error** (`no such column: quantity`) ঠিক — এখন সংখ্যা যোগ হয় ও স্টকের বেশি হলে 422।
- **Admin product create error** (`products.unit NOT NULL`) ঠিক — ফাঁকা unit = `piece`।
- **ছবি/লোগো ভাঙা:** Windows-এ `storage:link` (symlink) প্রায়ই তৈরি হয় না; এখন `/storage/...` রুট ফাইল সরাসরি পরিবেশন করে (symlink থাকলে সেটাই চলে)। প্লেসহোল্ডার ছবিও যোগ।
- **সেলার গ্যালারি ছবি:** ফিচার্ড ছবির সাথে সর্বোচ্চ ৮টি গ্যালারি ছবি; পণ্য তৈরির পরেও ছবি যোগ/মোছা, প্রধান ছবি চিহ্নিত।
- **অ্যাডমিনে পণ্য Approve:** Products লিস্ট ও Seller → Products ট্যাবে *Approve* (একক ও bulk), "Waiting for approval" ফিল্টার; অনুমোদনে সেলার নোটিফিকেশন পায়।
- **অ্যাডমিন নোটিফিকেশন (ঘণ্টা আইকন):** সেলার নতুন পণ্য দিলে, withdrawal চাইলে, বা সেলার আবেদন করলে সব অ্যাডমিন পায় (৩০ সেকেন্ডে আপডেট)।
- নতুন টেস্ট `SellerProductFlowTest` (৫); মোট ২৮টি Feature টেস্ট পাস।
**অ্যাডমিন সম্পূর্ণ করা (ধাপ ১০):**
- **CSV এক্সপোর্ট** এখন ২৪টি লিস্টে (Review, Coupon, Brand, Category, Attendance, Leave, SMS Log, Support Ticket, Activity Log, Blog Post/Comment, IP Block, Department, Flash Sale সহ)।
- **বড় বাগ ঠিক — rating/sales কলাম:** `products`/`sellers` টেবিলে `rating`, `total_reviews`, `total_sales` কলাম ছিল না, অথচ সাইটের `sort=rating`/`sort=popular`, অ্যাডমিন Product/Seller টেবিল ও Top Products উইজেট সেগুলো পড়ত → SQL error। কলাম ফেরত (মাইগ্রেশন `2026_10_30_000001`, আগের ডেটা থেকে হিসাব করে ভরা) এবং রিভিউ অনুমোদন/বদল ও অর্ডার delivered/returned/cancelled হলে স্বয়ংক্রিয় আপডেট।
- **Review:** *Hide / Reject* অ্যাকশন; অনুমোদন ও অ্যাডমিন reply-তে গ্রাহক নোটিফিকেশন পান।
- **Return:** approve / reject / refund প্রতিটিতে গ্রাহক নোটিফিকেশন (ইমেইল থাকলে ইমেইলও)।
- **Dashboard তারিখ-ফিল্টার:** From/To বেছে Orders, Revenue, New Customers, Revenue চার্ট ও Top Products বদলায় (ডিফল্ট শেষ ৩০ দিন)।
- **Profit & Revenue Report:** তারিখ/সেলার ফিল্টার আসলে কাজ করত না (ফর্ম আলাদা state-এ লিখত) → ঠিক। নতুন: Summary / Products / Sellers CSV ডাউনলোড ও "Print / Save as PDF"।
- নতুন টেস্ট `ReportsReviewsReturnsTest` (৬); মোট ৩৪টি Feature টেস্ট পাস।
**ফ্রন্টএন্ড সম্পূর্ণ করা (ধাপ ১১):**
- **সেলার পণ্য এডিট/মোছা:** নাম, ক্যাটাগরি, দাম, স্টক, ওজন, বিবরণ বদলানো, পণ্য লুকানো, মোছা (নিজের পণ্য ছাড়া অন্যেরটা 404)।
- **সেলার ভ্যারিয়েন্ট:** বৈশিষ্ট্য (রঙ/সাইজ ইত্যাদি) + দাম + স্টক + SKU; মোট স্টক ভ্যারিয়েন্টের যোগফল স্বয়ংক্রিয়।
- **সেলার শপ প্রোফাইল:** লোগো, ব্যানার, বিবরণ, ফোন, ঠিকানা, bKash/ব্যাংক পেআউট তথ্য (শপের নাম/কমিশন/স্ট্যাটাস শুধু অ্যাডমিন)।
- **Facebook লগইন:** সার্ভারে টোকেন যাচাই (নিজের অ্যাপের কি না) + Graph API থেকে প্রোফাইল; `users.facebook_id` (মাইগ্রেশন `2026_10_30_000002`)। কনফিগার না থাকলে 501 ও বাটন লুকানো। Google অ্যাভাটার URL ভাঙার বাগও ঠিক।
- **নিজস্ব প্লেসহোল্ডার:** Unsplash নির্ভরতা সরানো (`public/images/placeholder.svg`, `banner-pattern.svg`)।
- **বাংলা/English:** হেডারে ভাষা বাটন (কুকিতে মনে রাখে, সার্ভারও সেই ভাষায় রেন্ডার, `<html lang>` বদলায়); ~৪৫০টি স্থির UI লেখার অনুবাদ `locales/en.js` এ।
- **ফ্রন্টএন্ড টেস্ট:** `npm test` (Node বিল্ট-ইন, কোনো প্যাকেজ লাগে না) — অনুবাদ সম্পূর্ণতা, সব `<script setup>` সিনট্যাক্স, normalizeProduct, Unsplash নেই।
- `normalizeProduct` এখন নতুন `rating`/`total_reviews` কলামও পড়ে।
- নতুন ব্যাকএন্ড টেস্ট: `SocialLoginTest` (৩) + সেলার এডিট/ভ্যারিয়েন্ট/প্রোফাইল টেস্ট; মোট ৩৮টি Feature টেস্ট।
**টেস্ট:** `ExportsPayrollEmailTest` (৬) যোগ; মোট ২৩টি Feature টেস্ট + ৮৭ অ্যাডমিন পেজ crawl + API smoke (step3: ৩২, smoke: ১৬) সব পাস। `ExampleTest` ঠিক (`/` → `/admin` redirect)।

## এখনো বাকি (সৎ তালিকা)
- ফ্রন্টএন্ড এই পরিবেশে build/run করা যায়নি (npm registry ব্লক) — শুধু সিনট্যাক্স চেক। SSR বদল (পণ্য/ক্যাটাগরি/blog) `npm run dev` ও `npm run build && node .output/server/index.mjs` এ একবার দেখে নিন; View Source এ নাম/দাম থাকলে SSR ঠিক আছে।
- PHPUnit এখানে ইনস্টল করা যায়নি; টেস্টগুলো সাময়িক শিম দিয়ে চালিয়ে পাস দেখেছি। আসল `php artisan test` আপনার পরিবেশে চালান।
- bKash/Nagad সরাসরি API (merchant credential লাগে) — এখন ZiniPay aggregator দিয়ে অনলাইন পেমেন্ট। ZiniPay/SMS/কুরিয়ার আসল অ্যাকাউন্টে যাচাই হয়নি।
- Facebook লগইন নেই; SMS এখনো সিঙ্ক্রোনাস (ইমেইল queue-এর মাধ্যমে যেতে পারে)।
- search, account ইত্যাদি পেজ ক্লায়েন্ট-সাইড (ইচ্ছাকৃত; search noindex)।
- HR মৌলিক (ট্যাক্স/overtime/payslip PDF নেই)।
- সিডে ডেমো নাম "বাজার" ও `*@bazar.com.bd` আছে — Settings থেকে বদলান (ডেটা, কোড নয়)।
- `php artisan db:seed --class=SettingsSeeder` একা পুরনো ডাটাবেসে চালাবেন না।


## ধাপ ১২ — মোবাইল হেডার, ডাইনামিক ট্যাব, ঠিকানা, লোগো, কার্ড হেডার

- **মোবাইল হেডার (<768px):** শুধু সার্চ আইকন; ট্যাপ করলে হেডারের নিচে সার্চ বার খোলে (সাজেশন সহ)।
- **ক্যাটাগরি স্লাইডার:** মোবাইলে হোমের ক্যাটাগরি অনুভূমিক স্লাইডার, স্ক্রলবার লুকানো। `scrollbar-hide` গ্লোবাল CSS-এ গেছে।
- **ডাইনামিক ফিল্টার ট্যাব:** ট্যাবের নাম আসল ক্যাটাগরি থেকে, ক্লিকে সেই ক্যাটাগরির পণ্য লোড হয়। Admin → Appearance → Featured Products Tabs এখন ক্যাটাগরি সিলেক্ট (ফাঁকা/১টা হলে স্বয়ংক্রিয়ভাবে "সব পণ্য" + ক্যাটাগরি)।
- **লোগো কাটা:** আপলোড ফিল্ডে 4:1 ক্রপ ছিল, সরানো হয়েছে। **পুরোনো লোগো একবার নতুন করে আপলোড দিন।**
- **Branding আপলোড "Loading":** public disk URL এখন যে হোস্ট থেকে অ্যাডমিন খোলা হয়েছে সেটা অনুসরণ করে (localhost বনাম 127.0.0.1)।
- **ঠিকানা ফর্ম (account + checkout):** শুধু নাম, মোবাইল, জেলা, উপজেলা/থানা, বিস্তারিত ঠিকানা। বিভাগ সার্ভার জেলা থেকে বের করে।
- **জেলা/উপজেলা:** ৮ বিভাগ, ৬৪ জেলা, ~৫৭০ উপজেলা/থানা (ঢাকা, চট্টগ্রাম, রাজশাহী, খুলনা শহরের থানা সহ)। `php artisan migrate` চালালে পুরোনো ডেটাবেসে বাকিগুলো যোগ হয়, পুরোনো id অপরিবর্তিত।
- **ডেলিভারি চার্জ:** Chittagong/Chattogram, Barisal/Barishal ইত্যাদি বানান-পার্থক্য মেলে।
- **ইনভয়েস:** `adyabd-web` টেক্সটের বদলে স্টোর লোগো (লোগো না থাকলে শপের নাম)।
- **অ্যাডমিন কার্ড হেডার:** প্রতিটি কার্ডের হেডারে আলাদা গ্রেডিয়েন্ট (১০টি রঙ ঘুরে ঘুরে), সাদা লেখা।
- **নোট:** উপজেলা/থানার তালিকা আমার জ্ঞান থেকে লেখা (অনলাইন সোর্স ব্লক ছিল); কোনো নাম ভুল/বাদ থাকলে Admin থেকে বা `database/data/bangladesh_geo.php` এ ঠিক করা যাবে।
