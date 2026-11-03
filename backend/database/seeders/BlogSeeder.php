<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $now     = now();
        $adminId = DB::table('admin_users')->value('id'); // first admin as author

        // ─── Categories ───────────────────────────────────────────
        $categories = [
            ['name' => 'Technology',      'name_bn' => 'প্রযুক্তি',       'color' => '#3B82F6', 'sort_order' => 1],
            ['name' => 'Lifestyle',       'name_bn' => 'জীবনধারা',         'color' => '#10B981', 'sort_order' => 2],
            ['name' => 'Health & Fitness','name_bn' => 'স্বাস্থ্য ও ফিটনেস','color' => '#F59E0B', 'sort_order' => 3],
            ['name' => 'Food & Recipes',  'name_bn' => 'খাবার ও রেসিপি',   'color' => '#EF4444', 'sort_order' => 4],
            ['name' => 'Travel',          'name_bn' => 'ভ্রমণ',            'color' => '#8B5CF6', 'sort_order' => 5],
            ['name' => 'Business',        'name_bn' => 'ব্যবসা',           'color' => '#006A4E', 'sort_order' => 6],
        ];

        $categoryIds = [];
        foreach ($categories as $cat) {
            $id = DB::table('blog_categories')->insertGetId([
                'name'       => $cat['name'],
                'name_bn'    => $cat['name_bn'],
                'slug'       => Str::slug($cat['name']),
                'color'      => $cat['color'],
                'is_active'  => true,
                'sort_order' => $cat['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $categoryIds[$cat['name']] = $id;
        }

        // ─── Posts ────────────────────────────────────────────────
        $posts = [
            [
                'blog_category_id' => $categoryIds['Technology'],
                'title'            => 'The Future of Artificial Intelligence in Everyday Life',
                'title_bn'         => 'দৈনন্দিন জীবনে কৃত্রিম বুদ্ধিমত্তার ভবিষ্যৎ',
                'slug'             => 'future-of-ai-in-everyday-life',
                'excerpt'          => 'AI is no longer a concept from science fiction. Discover how it is reshaping the way we live, work, and connect with the world around us.',
                'excerpt_bn'       => 'AI আর শুধু বিজ্ঞান কল্পকাহিনীর বিষয় নয়। জানুন কীভাবে এটি আমাদের জীবন, কাজ ও সংযোগ বদলে দিচ্ছে।',
                'content'          => '<h2>Introduction</h2><p>Artificial Intelligence has moved from research labs into our pockets, homes, and workplaces. From voice assistants like Siri and Google Assistant to recommendation engines on Netflix and Amazon, AI is quietly powering the decisions we make every day.</p><h2>AI in Healthcare</h2><p>One of the most promising applications of AI is in healthcare. Machine learning models can now detect certain cancers with accuracy rivalling that of experienced radiologists. AI-powered tools help doctors analyse medical images, predict patient outcomes, and personalise treatment plans.</p><h2>AI in Education</h2><p>Adaptive learning platforms use AI to tailor lessons to each student\'s pace and learning style. This technology identifies gaps in knowledge and adjusts content accordingly, making education more effective and accessible.</h2><h2>Challenges Ahead</h2><p>Despite its promise, AI raises important questions about privacy, job displacement, and algorithmic bias. Addressing these challenges requires collaboration between technologists, policymakers, and society at large.</p><h2>Conclusion</h2><p>The future of AI is not something happening to us — it is something we are actively shaping. Staying informed and engaged is the first step towards ensuring AI serves humanity well.</p>',
                'content_bn'       => '<h2>ভূমিকা</h2><p>কৃত্রিম বুদ্ধিমত্তা গবেষণাগার থেকে বেরিয়ে আমাদের পকেট, ঘর ও কর্মক্ষেত্রে প্রবেশ করেছে।</p>',
                'featured_image'   => 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=1200&q=80',
                'tags'             => json_encode(['ai', 'technology', 'future', 'machine learning']),
                'status'           => 'published',
                'is_featured'      => true,
                'allow_comments'   => true,
                'view_count'       => 3420,
                'meta_title'       => 'The Future of AI in Everyday Life',
                'meta_description' => 'Discover how Artificial Intelligence is reshaping daily life, healthcare, education, and more.',
                'published_at'     => now()->subDays(10),
            ],
            [
                'blog_category_id' => $categoryIds['Health & Fitness'],
                'title'            => '10 Simple Habits for a Healthier Life',
                'title_bn'         => 'সুস্থ জীবনের জন্য ১০টি সহজ অভ্যাস',
                'slug'             => '10-simple-habits-for-a-healthier-life',
                'excerpt'          => 'Small daily habits can make a big difference. Here are ten science-backed habits you can start today to improve your physical and mental wellbeing.',
                'excerpt_bn'       => 'ছোট ছোট দৈনন্দিন অভ্যাস বড় পরিবর্তন আনতে পারে। বিজ্ঞানসম্মত ১০টি অভ্যাস জানুন।',
                'content'          => '<h2>1. Start Your Day With Water</h2><p>Drinking a glass of water first thing in the morning kick-starts your metabolism, flushes out toxins, and helps you feel more alert.</p><h2>2. Move for 30 Minutes</h2><p>You do not need an intense gym session. A brisk 30-minute walk every day reduces the risk of heart disease, improves mood, and boosts energy levels.</p><h2>3. Prioritise Sleep</h2><p>Seven to nine hours of quality sleep is non-negotiable for good health. Sleep deprivation is linked to obesity, diabetes, and weakened immunity.</p><h2>4. Eat More Whole Foods</h2><p>Minimise processed foods and focus on vegetables, fruits, whole grains, and lean proteins. Your gut microbiome — and your brain — will thank you.</p><h2>5. Practice Mindfulness</h2><p>Even five minutes of meditation or deep breathing daily can significantly reduce stress, lower blood pressure, and improve focus.</p>',
                'content_bn'       => '<h2>১. সকালে পানি পান করুন</h2><p>সকালে উঠে এক গ্লাস পানি পান আপনার বিপাকক্রিয়া শুরু করে এবং শরীরের বিষাক্ত পদার্থ বের করে দেয়।</p>',
                'featured_image'   => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1200&q=80',
                'tags'             => json_encode(['health', 'habits', 'wellness', 'fitness', 'lifestyle']),
                'status'           => 'published',
                'is_featured'      => true,
                'allow_comments'   => true,
                'view_count'       => 5810,
                'meta_title'       => '10 Simple Habits for a Healthier Life',
                'meta_description' => 'Ten science-backed daily habits to improve your physical and mental wellbeing starting today.',
                'published_at'     => now()->subDays(7),
            ],
            [
                'blog_category_id' => $categoryIds['Food & Recipes'],
                'title'            => 'Classic Bangladeshi Hilsa Curry Recipe',
                'title_bn'         => 'ক্লাসিক বাংলাদেশি ইলিশ মাছের রেসিপি',
                'slug'             => 'classic-bangladeshi-hilsa-curry-recipe',
                'excerpt'          => 'The national fish of Bangladesh prepared in the most traditional way. A soul-satisfying recipe passed down through generations.',
                'excerpt_bn'       => 'বাংলাদেশের জাতীয় মাছ সবচেয়ে ঐতিহ্যবাহী উপায়ে রান্না করুন।',
                'content'          => '<h2>Ingredients</h2><ul><li>1 whole hilsa fish, cleaned and cut into pieces</li><li>4 tablespoons mustard oil</li><li>2 tablespoons mustard paste</li><li>1 teaspoon turmeric powder</li><li>1 teaspoon red chilli powder</li><li>Salt to taste</li><li>4–5 green chillies, slit</li><li>1 cup water</li></ul><h2>Method</h2><p>Marinate the fish pieces with turmeric, salt, and a little mustard oil for 15 minutes. Heat mustard oil in a pan until it smokes, then reduce the heat. Gently place the marinated fish pieces in the oil and fry lightly on both sides. Remove and set aside.</p><p>In the same oil, add mustard paste, turmeric, red chilli, and a splash of water. Cook the masala for two minutes, then add the fish back. Pour in the remaining water and add the green chillies. Cover and simmer on low heat for 10 minutes. Serve hot with steamed white rice.</p>',
                'content_bn'       => '<h2>উপকরণ</h2><ul><li>১টি ইলিশ মাছ, পরিষ্কার করে কাটা</li><li>৪ টেবিল চামচ সরিষার তেল</li></ul>',
                'featured_image'   => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=1200&q=80',
                'tags'             => json_encode(['recipe', 'hilsa', 'bangladeshi food', 'fish curry', 'traditional']),
                'status'           => 'published',
                'is_featured'      => false,
                'allow_comments'   => true,
                'view_count'       => 2190,
                'meta_title'       => 'Classic Bangladeshi Hilsa Curry Recipe',
                'meta_description' => 'Learn how to cook the traditional Bangladeshi hilsa fish curry with mustard oil and mustard paste.',
                'published_at'     => now()->subDays(5),
            ],
            [
                'blog_category_id' => $categoryIds['Travel'],
                'title'            => 'Exploring the Sundarbans: A Guide to the World\'s Largest Mangrove Forest',
                'title_bn'         => 'সুন্দরবন অন্বেষণ: বিশ্বের বৃহত্তম ম্যানগ্রোভ বনের গাইড',
                'slug'             => 'exploring-the-sundarbans-travel-guide',
                'excerpt'          => 'The Sundarbans is unlike anywhere else on earth. Home to the Royal Bengal Tiger and a vast network of tidal waterways, it is a bucket-list destination for every nature lover.',
                'excerpt_bn'       => 'সুন্দরবন পৃথিবীর অন্য কোথাও নেই। রয়েল বেঙ্গল টাইগারের আবাসস্থল এই বনটি প্রতিটি প্রকৃতিপ্রেমীর স্বপ্নের গন্তব্য।',
                'content'          => '<h2>Getting There</h2><p>The most common entry point is Mongla, a port city in Bagerhat district. From Dhaka, take a bus or train to Khulna (approximately 8–9 hours), then a local bus or boat to Mongla. Most tours depart from Mongla or Khulna.</p><h2>Best Time to Visit</h2><p>October to March is the ideal season. The weather is cooler, the waterways are calm, and wildlife sightings — especially deer and crocodiles — are more frequent. Avoid the monsoon months (June to September) when the forest is partially inaccessible.</p><h2>What to Expect</h2><p>Guided boat tours navigate through the dense network of channels. Keep your eyes peeled for spotted deer, Irrawaddy dolphins, estuarine crocodiles, and if you are very lucky, a tiger sighting from a safe distance.</p><h2>Tips</h2><ul><li>Always travel with a licensed guide — it is mandatory and for your safety</li><li>Carry insect repellent and full-sleeve clothing</li><li>Respect the forest — no plastic, no loud noise</li><li>Book accommodation in advance during peak season</li></ul>',
                'content_bn'       => '<h2>কীভাবে যাবেন</h2><p>সবচেয়ে সাধারণ প্রবেশপথ হল মোংলা, বাগেরহাট জেলার একটি বন্দর শহর।</p>',
                'featured_image'   => 'https://images.unsplash.com/photo-1609137144813-7d9921338f24?w=1200&q=80',
                'tags'             => json_encode(['sundarbans', 'travel', 'bangladesh', 'nature', 'wildlife']),
                'status'           => 'published',
                'is_featured'      => true,
                'allow_comments'   => true,
                'view_count'       => 4750,
                'meta_title'       => 'Exploring the Sundarbans — Complete Travel Guide',
                'meta_description' => 'Everything you need to know to visit the Sundarbans: how to get there, best time, and what to expect.',
                'published_at'     => now()->subDays(3),
            ],
            [
                'blog_category_id' => $categoryIds['Business'],
                'title'            => 'How to Start an E-Commerce Business in Bangladesh',
                'title_bn'         => 'বাংলাদেশে ই-কমার্স ব্যবসা শুরু করার উপায়',
                'slug'             => 'how-to-start-ecommerce-business-bangladesh',
                'excerpt'          => 'The e-commerce market in Bangladesh is booming. Here is a practical step-by-step guide to launching your online store successfully.',
                'excerpt_bn'       => 'বাংলাদেশের ই-কমার্স বাজার দ্রুত বাড়ছে। আপনার অনলাইন স্টোর চালু করার ব্যবহারিক গাইড।',
                'content'          => '<h2>Step 1: Choose Your Niche</h2><p>The most successful e-commerce businesses serve a specific audience well rather than trying to sell everything to everyone. Research demand using Google Trends, Facebook groups, and local market observations.</p><h2>Step 2: Register Your Business</h2><p>Register with the Registrar of Joint Stock Companies and Firms (RJSC) and obtain a trade licence from your local city corporation or municipality. You will also need a TIN certificate for tax purposes.</p><h2>Step 3: Set Up Your Platform</h2><p>You can start with a Facebook page or use platforms like Shopify or WooCommerce for a full website. For a growing business, a dedicated website builds credibility and gives you full control.</p><h2>Step 4: Payment & Logistics</h2><p>Integrate bKash, Nagad, or SSL Commerz for digital payments. Partner with a reliable courier service — Pathao, Paperfly, or Steadfast — for nationwide delivery.</p><h2>Step 5: Marketing</h2><p>Facebook and Instagram ads are the most cost-effective channels for reaching Bangladeshi consumers. Combine paid ads with organic content, influencer partnerships, and strong customer reviews.</p>',
                'content_bn'       => '<h2>ধাপ ১: আপনার নিশ বেছে নিন</h2><p>সবচেয়ে সফল ই-কমার্স ব্যবসাগুলো একটি নির্দিষ্ট দর্শকদের ভালোভাবে সেবা দেয়।</p>',
                'featured_image'   => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&q=80',
                'tags'             => json_encode(['ecommerce', 'business', 'bangladesh', 'startup', 'online store']),
                'status'           => 'published',
                'is_featured'      => false,
                'allow_comments'   => true,
                'view_count'       => 6300,
                'meta_title'       => 'How to Start an E-Commerce Business in Bangladesh',
                'meta_description' => 'A practical step-by-step guide to launching a successful e-commerce business in Bangladesh.',
                'published_at'     => now()->subDays(1),
            ],
            [
                'blog_category_id' => $categoryIds['Lifestyle'],
                'title'            => 'Minimalist Living: How to Own Less and Live More',
                'title_bn'         => 'মিনিমালিস্ট জীবন: কম রেখে বেশি বাঁচুন',
                'slug'             => 'minimalist-living-own-less-live-more',
                'excerpt'          => 'Minimalism is not about living with nothing — it is about making room for what truly matters. Here is how to get started.',
                'excerpt_bn'       => 'মিনিমালিজম মানে কিছুই না রাখা নয় — এটি গুরুত্বপূর্ণ বিষয়গুলোর জন্য জায়গা তৈরি করা।',
                'content'          => '<h2>What Is Minimalism?</h2><p>Minimalism is the intentional practice of owning only what adds value to your life. It is a response to the modern culture of overconsumption and the anxiety that comes with it.</p><h2>Start With a Declutter</h2><p>Go through your belongings room by room. Ask yourself: does this item serve a purpose or bring genuine joy? If not, donate, sell, or discard it. The KonMari method — popularised by Marie Kondo — is a great framework.</p><h2>Digital Minimalism</h2><p>Clutter is not just physical. Unsubscribe from email lists you never read, delete apps you have not opened in months, and consider limiting your social media consumption to one or two focused sessions per day.</p><h2>The Benefits</h2><p>People who embrace minimalism consistently report lower stress, more financial freedom, and a clearer sense of purpose. When you spend less time managing things, you have more time for relationships, experiences, and growth.</p>',
                'content_bn'       => '<h2>মিনিমালিজম কী?</h2><p>মিনিমালিজম হল ইচ্ছাকৃতভাবে শুধু সেই জিনিসগুলো রাখা যা আপনার জীবনে মূল্য যোগ করে।</p>',
                'featured_image'   => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=1200&q=80',
                'tags'             => json_encode(['minimalism', 'lifestyle', 'declutter', 'simple living']),
                'status'           => 'draft',
                'is_featured'      => false,
                'allow_comments'   => true,
                'view_count'       => 0,
                'meta_title'       => 'Minimalist Living: Own Less, Live More',
                'meta_description' => 'How to embrace minimalism, declutter your life, and make room for what truly matters.',
                'published_at'     => null,
            ],
        ];

        $postIds = [];
        foreach ($posts as $post) {
            $id = DB::table('blog_posts')->insertGetId(array_merge($post, [
                'author_id'  => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            $postIds[] = $id;
        }

        // ─── Comments ─────────────────────────────────────────────
        $comments = [
            // Post 1 — AI article
            ['post' => 0, 'name' => 'Rahim Uddin',   'email' => 'rahim@example.com',   'comment' => 'Excellent article! AI is truly changing the world. The healthcare examples were especially eye-opening.',   'approved' => true],
            ['post' => 0, 'name' => 'Fatema Begum',  'email' => 'fatema@example.com',  'comment' => 'Very well written. I am a bit worried about job displacement though — would love to see a follow-up post on that.',    'approved' => true],
            ['post' => 0, 'name' => 'Anonymous',     'email' => null,                  'comment' => 'Great read. Shared this with my team.',                                                                        'approved' => false],

            // Post 2 — Health habits
            ['post' => 1, 'name' => 'Karim Hossain', 'email' => 'karim@example.com',   'comment' => 'I started drinking water first thing in the morning two weeks ago and I already feel a difference. Thanks!',   'approved' => true],
            ['post' => 1, 'name' => 'Nasrin Akter',  'email' => 'nasrin@example.com',  'comment' => 'The sleep tip is so important. People underestimate how much sleep affects everything else.',                  'approved' => true],

            // Post 3 — Hilsa recipe
            ['post' => 2, 'name' => 'Sumaiya Islam', 'email' => 'sumaiya@example.com', 'comment' => 'Made this last night and the whole family loved it. The mustard paste really makes the difference!',           'approved' => true],
            ['post' => 2, 'name' => 'Arif Khan',     'email' => 'arif@example.com',    'comment' => 'Best hilsa recipe I have found online. Simple and authentic.',                                                  'approved' => true],

            // Post 4 — Sundarbans
            ['post' => 3, 'name' => 'Tanvir Ahmed',  'email' => 'tanvir@example.com',  'comment' => 'Visited the Sundarbans last December. This guide is very accurate. The boat tours at dawn are magical.',        'approved' => true],
            ['post' => 3, 'name' => 'Riya Das',      'email' => 'riya@example.com',    'comment' => 'Planning a trip in November. Is it safe to go without a tour package?',                                         'approved' => false],

            // Post 5 — E-commerce
            ['post' => 4, 'name' => 'Sabbir Rahman', 'email' => 'sabbir@example.com',  'comment' => 'This is exactly what I needed. Starting my own online store next month and this gave me a solid roadmap.',      'approved' => true],
            ['post' => 4, 'name' => 'Mitu Begum',    'email' => 'mitu@example.com',    'comment' => 'Could you write more about Facebook ad strategies for Bangladeshi businesses?',                                  'approved' => true],
        ];

        foreach ($comments as $c) {
            DB::table('blog_comments')->insert([
                'blog_post_id' => $postIds[$c['post']],
                'user_id'      => null,
                'author_name'  => $c['name'],
                'author_email' => $c['email'],
                'comment'      => $c['comment'],
                'is_approved'  => $c['approved'],
                'parent_id'    => null,
                'created_at'   => $now->copy()->subHours(rand(1, 72)),
                'updated_at'   => $now,
            ]);
        }

        // ─── Likes ────────────────────────────────────────────────
        $likeCounts = [42, 87, 35, 61, 94, 0];
        foreach ($postIds as $index => $postId) {
            $count = $likeCounts[$index] ?? 0;
            for ($i = 0; $i < $count; $i++) {
                DB::table('blog_likes')->insertOrIgnore([
                    'blog_post_id' => $postId,
                    'user_id'      => null,
                    'ip_address'   => '192.168.1.' . rand(1, 254),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }

        $this->command->info('BlogSeeder: ' . count($categories) . ' categories, ' . count($posts) . ' posts, ' . count($comments) . ' comments seeded.');
    }
}
