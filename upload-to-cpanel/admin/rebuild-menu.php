<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();

// Yeni kateqoriyalar: [key, AZ ad, RU ad, EN ad]
$categoriesData = [
    'sushi_set' => ['Sushi Set', 'Суши сеты', 'Sushi Sets'],
    'sushi_roll' => ['Sushi Roll', 'Суши роллы', 'Sushi Rolls'],
    'hot_roll' => ['Hot Roll', 'Горячие роллы', 'Hot Rolls'],
    'burrito' => ['Burrito', 'Буррито', 'Burrito'],
    'noodles' => ['Noodles', 'Лапша', 'Noodles'],
    'salat' => ['Salat', 'Салаты', 'Salads'],
    'ickiler' => ['İçkilər', 'Напитки', 'Drinks'],
];

// Məhsullar: kateqoriya açarı => [AZ ad, AZ təsvir, EN ad, EN təsvir, RU ad, RU təsvir, qiymət]
$productsData = [
    'sushi_set' => [
        ['Sushi Garden Seti (32 əd)', '4 növ rulonun qarışığı, 2–3 nəfərlik', 'Sushi Garden Set (32 pcs)', 'Mix of 4 roll types, serves 2–3', 'Сет Sushi Garden (32 шт)', 'Микс из 4 видов роллов, на 2–3 персоны', 45],
        ['Aşiqlər Seti (24 əd)', '2 nəfərlik seçim', "Lovers' Set (24 pcs)", 'A choice for two', 'Сет для влюблённых (24 шт)', 'Выбор на двоих', 38],
        ['Solo Seti (16 əd)', '1 nəfərlik yığcam seçim', 'Solo Set (16 pcs)', 'A compact choice for one', 'Сет Соло (16 шт)', 'Компактный выбор на одного', 26],
        ['Ailə Seti (48 əd)', '3–4 nəfərlik böyük qarışıq', 'Family Set (48 pcs)', 'A large mix for 3–4 people', 'Семейный сет (48 шт)', 'Большое ассорти на 3–4 персоны', 62],
        ['Vegetarian Set (20 əd)', 'Tam vegetarian qarışıq', 'Vegetarian Set (20 pcs)', 'Fully vegetarian mix', 'Вегетарианский сет (20 шт)', 'Полностью вегетарианское ассорти', 30],
        ['Premium Set (30 əd)', 'VIP balıq növləri ilə', 'Premium Set (30 pcs)', 'With VIP fish varieties', 'Премиум сет (30 шт)', 'С VIP-видами рыбы', 68],
        ['Nigiri Seti (12 əd)', 'Qarışıq nigiri seçimi', 'Nigiri Set (12 pcs)', 'Mixed nigiri selection', 'Сет нигири (12 шт)', 'Ассорти нигири', 32],
        ['Sashimi Seti (15 dilim)', '3 növ balıqdan sashimi', 'Sashimi Set (15 slices)', 'Sashimi from 3 fish varieties', 'Сет сашими (15 ломтиков)', 'Сашими из 3 видов рыбы', 40],
        ['Klassik Rollar Seti (24 əd)', 'Ən çox sevilən klassiklər', 'Classic Rolls Set (24 pcs)', 'The most loved classics', 'Сет классических роллов (24 шт)', 'Самая любимая классика', 36],
        ['Bişmiş Rollar Seti (20 əd)', 'İsti, qızardılmış rulonlar', 'Baked Rolls Set (20 pcs)', 'Hot, fried rolls', 'Сет запечённых роллов (20 шт)', 'Горячие жареные роллы', 34],
        ['Uşaq Seti (10 əd)', 'Uşaqlar üçün yumşaq dadlı mini set', "Kids' Set (10 pcs)", 'A mild-flavored mini set for children', 'Детский сет (10 шт)', 'Мини-сет с мягким вкусом для детей', 20],
        ['Ofis Seti (40 əd)', '4–5 nəfərlik ofis sifarişi', 'Office Set (40 pcs)', 'An office order for 4–5 people', 'Офисный сет (40 шт)', 'Заказ на 4–5 человек', 55],
        ['Ziyafət Seti (60 əd)', '6 nəfərlik böyük məclis seti', 'Banquet Set (60 pcs)', 'A large set for a gathering of 6', 'Банкетный сет (60 шт)', 'Большой сет для компании из 6 человек', 78],
        ['Duo Set', '2 rulon + 2 nigiri', 'Duo Set', '2 rolls + 2 nigiri', 'Дуо сет', '2 ролла + 2 нигири', 28],
        ["Şəfin Seçimi Seti", 'Günün premium seçimi', "Chef's Choice Set", 'Premium pick of the day', 'Сет выбор шефа', 'Премиальный выбор дня', 42],
    ],
    'sushi_roll' => [
        ['Kaliforniya rolu', 'Krab çubuğu, avokado, xiyar, kunjut', 'California Roll', 'Crab stick, avocado, cucumber, sesame', 'Ролл Калифорния', 'Крабовые палочки, авокадо, огурец, кунжут', 12],
        ['Filadelfiya rolu', 'Qızıl losos, krem pendir, avokado', 'Philadelphia Roll', 'Salmon, cream cheese, avocado', 'Ролл Филадельфия', 'Лосось, сливочный сыр, авокадо', 14],
        ['Spaysi tuna rolu', 'Kəskin tuna, çili-mayonez, yaşıl soğan', 'Spicy Tuna Roll', 'Spicy tuna, chili-mayo, green onion', 'Ролл спайси тунец', 'Острый тунец, чили-майонез, зелёный лук', 16],
        ['Krab rolu', 'Krab çubuğu, xiyar, mayonez', 'Crab Roll', 'Crab stick, cucumber, mayo', 'Крабовый ролл', 'Крабовые палочки, огурец, майонез', 11],
        ['Avokado rolu', 'Sadə vegan seçim', 'Avocado Roll', 'Simple vegan option', 'Ролл с авокадо', 'Простой веганский вариант', 9],
        ['Xiyar rolu', 'Yüngül, təzələndirici', 'Cucumber Roll', 'Light and refreshing', 'Ролл с огурцом', 'Лёгкий, освежающий', 8],
        ['Losos-avokado rolu', 'İki əsas ləzzətin birləşməsi', 'Salmon-Avocado Roll', 'A combination of two classic flavors', 'Ролл лосось-авокадо', 'Сочетание двух классических вкусов', 13],
        ['Ton balığı rolu', 'Klassik ala-karte tuna', 'Tuna Roll', 'Classic à la carte tuna', 'Ролл с тунцом', 'Классический тунец', 15],
        ['Ebi tempura rolu', 'Qızardılmış krevet, avokado', 'Ebi Tempura Roll', 'Fried shrimp, avocado', 'Ролл эби темпура', 'Жареная креветка, авокадо', 15],
        ['Sake maki (losos)', 'Nazik losos rulonu', 'Sake Maki (Salmon)', 'Thin salmon roll', 'Сяке маки (лосось)', 'Тонкий ролл с лососем', 10],
        ['Tekka maki (tuna)', 'Nazik tuna rulonu', 'Tekka Maki (Tuna)', 'Thin tuna roll', 'Текка маки (тунец)', 'Тонкий ролл с тунцом', 11],
        ['Kappa maki (xiyar)', 'Nazik xiyar rulonu', 'Kappa Maki (Cucumber)', 'Thin cucumber roll', 'Каппа маки (огурец)', 'Тонкий ролл с огурцом', 8],
        ['Unagi-avokado rolu', 'Şirin ilan balığı, avokado', 'Unagi-Avocado Roll', 'Sweet eel, avocado', 'Ролл унаги-авокадо', 'Сладкий угорь, авокадо', 16],
        ['Krem pendirli losos rolu', 'Yumşaq, krem toxuma', 'Salmon Cream Cheese Roll', 'Soft, creamy texture', 'Ролл лосось со сливочным сыром', 'Мягкая, кремовая текстура', 13],
        ['Spaysi krab rolu', 'Acılı krab qarışığı', 'Spicy Crab Roll', 'Spicy crab mix', 'Ролл спайси краб', 'Острая крабовая смесь', 12],
        ['Yaşıl bağça rolu', 'Vegetarian qarışıq tərəvəz', 'Green Garden Roll', 'Vegetarian mixed vegetables', 'Ролл зелёный сад', 'Вегетарианское овощное ассорти', 10],
        ['Ton-sarımsaq rolu', 'Sarımsaqlı yağla zənginləşdirilmiş', 'Tuna-Garlic Roll', 'Enriched with garlic oil', 'Ролл тунец-чеснок', 'Обогащён чесночным маслом', 15],
        ['Tempura krevet-avokado rolu', 'Xırtıldayan və krem toxuma', 'Tempura Shrimp-Avocado Roll', 'Crispy and creamy texture', 'Ролл темпура креветка-авокадо', 'Хрустящая и кремовая текстура', 16],
        ['Sudzhi-xiyar rolu', 'Yüngül və sərinlədici', 'Surimi-Cucumber Roll', 'Light and cooling', 'Ролл крабовые палочки-огурец', 'Лёгкий и освежающий', 10],
        ['Spaysi losos rolu', 'Acılı losos qarışığı', 'Spicy Salmon Roll', 'Spicy salmon mix', 'Ролл спайси лосось', 'Острая смесь с лососем', 14],
        ['Tofu-avokado rolu', 'Zülal zəngin vegan seçim', 'Tofu-Avocado Roll', 'Protein-rich vegan option', 'Ролл тофу-авокадо', 'Богатый белком веганский вариант', 10],
        ['İspanaq-krem pendir rolu', 'Vegetarian, kremli', 'Spinach Cream Cheese Roll', 'Vegetarian, creamy', 'Ролл шпинат со сливочным сыром', 'Вегетарианский, кремовый', 10],
        ['Şirin kartof tempura rolu', 'Qızardılmış, şirin dad', 'Sweet Potato Tempura Roll', 'Fried, sweet flavor', 'Ролл темпура из батата', 'Жареный, сладкий вкус', 12],
        ['Şitake göbələk rolu', 'Marinələnmiş şitake göbələyi', 'Shiitake Mushroom Roll', 'Marinated shiitake mushrooms', 'Ролл с грибами шиитаке', 'Маринованные грибы шиитаке', 11],
        ['Mango-xiyar rolu', 'Tropik və təzələndirici', 'Mango-Cucumber Roll', 'Tropical and refreshing', 'Ролл манго-огурец', 'Тропический и освежающий', 10],
        ['Badımcan tempura rolu', 'Qızardılmış badımcan', 'Eggplant Tempura Roll', 'Fried eggplant', 'Ролл темпура с баклажаном', 'Жареный баклажан', 11],
        ['Vegan Kaliforniya rolu', 'Tofu ilə klassik üslub', 'Vegan California Roll', 'Classic style with tofu', 'Веганский ролл Калифорния', 'Классика с тофу', 11],
        ['Qızardılmış tərəvəz rolu', 'Xırtıldayan qarışıq tərəvəz', 'Fried Vegetable Roll', 'Crispy mixed vegetables', 'Жареный овощной ролл', 'Хрустящее овощное ассорти', 12],
        ['Sarımsaqlı ispanaq rolu', 'Aromatik vegetarian seçim', 'Garlic Spinach Roll', 'Aromatic vegetarian option', 'Ролл со шпинатом и чесноком', 'Ароматный вегетарианский вариант', 9],
        ['Kimçi-tofu rolu', 'Acılı-turş kimçi ilə', 'Kimchi-Tofu Roll', 'With spicy-sour kimchi', 'Ролл кимчи-тофу', 'С острым квашеным кимчи', 10],
        ['Avokado-mango rolu', 'İki meyvənin krem toxuması', 'Avocado-Mango Roll', 'Creamy texture of two fruits', 'Ролл авокадо-манго', 'Кремовая текстура двух фруктов', 11],
        ['Yaşıl noodle rolu', 'Nazik tərəvəz "noodle" lentləri ilə', 'Green Noodle Roll', 'With thin vegetable "noodle" strips', 'Ролл зелёная лапша', 'С тонкими овощными полосками', 10],
        ['Şirin bibər-pendir rolu', 'Kremli və yüngül şirin', 'Sweet Pepper-Cheese Roll', 'Creamy and mildly sweet', 'Ролл сладкий перец-сыр', 'Кремовый и слегка сладкий', 10],
        ['Vegan Dragon rolu', 'Tam tərəvəz əsaslı, dramatik görünüş', 'Vegan Dragon Roll', 'All-vegetable, dramatic presentation', 'Веганский ролл Дракон', 'Полностью овощной, эффектная подача', 13],
        ['Zeytun-pomidor rolu', 'Aralıq dənizi fusion toxunuşu', 'Olive-Tomato Roll', 'A Mediterranean fusion touch', 'Ролл оливки-помидор', 'Средиземноморский штрих фьюжн', 10],
        ['Vegan Rainbow rolu', 'Rəngarəng tərəvəz qarışığı', 'Vegan Rainbow Roll', 'Colorful vegetable mix', 'Веганский ролл Радуга', 'Разноцветное овощное ассорти', 13],
        ['Mini Vegan Set rolu', '4 fərqli vegetarian dolğu', 'Mini Vegan Set Roll', '4 different vegetarian fillings', 'Мини веганский сет', '4 разные вегетарианские начинки', 16],
        ['Trüfel losos rolu', 'Trüfel yağı ilə zənginləşdirilmiş', 'Truffle Salmon Roll', 'Enriched with truffle oil', 'Ролл лосось с трюфелем', 'Обогащён трюфельным маслом', 24],
        ['Qara kürülü tuna rolu', 'Premium tuna, qara kürü', 'Black Caviar Tuna Roll', 'Premium tuna, black caviar', 'Ролл тунец с чёрной икрой', 'Премиальный тунец, чёрная икра', 26],
        ['Wagyu-avokado rolu', 'Wagyu mal əti, avokado', 'Wagyu-Avocado Roll', 'Wagyu beef, avocado', 'Ролл вагю-авокадо', 'Говядина вагю, авокадо', 32],
        ['Toro (yağlı tuna) rolu', 'Ton balığının ən yağlı hissəsi', 'Toro (Fatty Tuna) Roll', 'The fattiest part of the tuna', 'Ролл Торо (жирный тунец)', 'Самая жирная часть тунца', 29],
        ['VIP Sushi Garden rolu', 'Evimizin ən premium seçimi', 'VIP Sushi Garden Roll', "Our house's most premium choice", 'VIP-ролл Sushi Garden', 'Самый премиальный выбор нашего дома', 35],
        ['Kaliforniya rolu (Krevetli)', 'Bişmiş krevet, avokado, xiyar', 'California Roll (Shrimp)', 'Cooked shrimp, avocado, cucumber', 'Ролл Калифорния (с креветкой)', 'Варёная креветка, авокадо, огурец', 13],
        ['Filadelfiya rolu (Tuna)', 'Tuna, krem pendir, xiyar', 'Philadelphia Roll (Tuna)', 'Tuna, cream cheese, cucumber', 'Ролл Филадельфия (с тунцом)', 'Тунец, сливочный сыр, огурец', 15],
        ['Ebi Sake rolu', 'Krevet və losos qarışığı', 'Ebi Sake Roll', 'Shrimp and salmon combination', 'Ролл эби сяке', 'Сочетание креветки и лосося', 15],
    ],
    'hot_roll' => [
        ['Tempura rolu (krevet)', 'Qızardılmış krevet ilə', 'Tempura Roll (Shrimp)', 'With fried shrimp', 'Ролл темпура (креветка)', 'С жареной креветкой', 15],
        ['Ebi Katsu rolu', 'Pankolu qızardılmış krevet', 'Ebi Katsu Roll', 'Panko-fried shrimp', 'Ролл эби кацу', 'Креветка в панировке панко', 16],
        ['Qızardılmış Filadelfiya rolu', 'Xırtıldayan qat, krem pendir', 'Fried Philadelphia Roll', 'Crispy coating, cream cheese', 'Жареный ролл Филадельфия', 'Хрустящая корочка, сливочный сыр', 15],
        ['Krispi krab rolu', 'Xırtıldayan krab qarışığı', 'Crispy Crab Roll', 'Crunchy crab mix', 'Хрустящий крабовый ролл', 'Хрустящая крабовая смесь', 14],
        ['Hot Cheese losos rolu', 'Üstü qızardılmış pendirlə', 'Hot Cheese Salmon Roll', 'Topped with baked cheese', 'Ролл лосось хот-чиз', 'Запечён с сыром сверху', 17],
        ['Qızardılmış tərəvəz rolu', 'Vegetarian, xırtıldayan', 'Fried Vegetable Roll', 'Vegetarian, crispy', 'Жареный овощной ролл', 'Вегетарианский, хрустящий', 12],
        ['Qızardılmış unagi rolu', 'Şirin sousla üstdən örtülmüş', 'Fried Unagi Roll', 'Topped with sweet sauce', 'Жареный ролл унаги', 'Полит сладким соусом сверху', 18],
        ['Volkan rolu', 'Üstü isti sousla yandırılmış', 'Volcano Roll', 'Torched with spicy hot sauce on top', 'Ролл Вулкан', 'Запечён с острым соусом сверху', 19],
        ['Drakon rolu', 'Unagi və avokado ilə üst-üstə', 'Dragon Roll', 'Topped with eel and avocado', 'Ролл Дракон', 'Сверху угорь и авокадо', 18],
        ['Feniks rolu', 'Üstü qırmızı kürü ilə bəzədilmiş', 'Phoenix Roll', 'Topped with red caviar', 'Ролл Феникс', 'Украшен красной икрой сверху', 20],
        ['Alovlu krevet rolu', 'Acılı-şirin qızardılmış krevet', 'Fire Shrimp Roll', 'Sweet-spicy fried shrimp', 'Ролл огненная креветка', 'Сладко-острая жареная креветка', 17],
        ['Qızardılmış spaysi tuna rolu', 'Xırtıldayan qat, acılı tuna', 'Fried Spicy Tuna Roll', 'Crispy coating, spicy tuna', 'Жареный ролл спайси тунец', 'Хрустящая корочка, острый тунец', 17],
        ['Baked losos rolu', 'Sousla sobada bişirilmiş losos', 'Baked Salmon Roll', 'Salmon baked in sauce', 'Запечённый ролл с лососем', 'Лосось, запечённый в соусе', 18],
        ['Tiger rolu', 'Krevet və avokado üst-üstə', 'Tiger Roll', 'Topped with shrimp and avocado', 'Ролл Тигр', 'Сверху креветка и авокадо', 19],
        ['Göy qurşağı (Rainbow) rolu', 'Üstü müxtəlif balıq növləri ilə', 'Rainbow Roll', 'Topped with assorted fish varieties', 'Ролл Радуга', 'Сверху ассорти из разных видов рыбы', 20],
    ],
    'burrito' => [
        ['Losos Burrito', 'Sushi düyüsü, losos, avokado, acılı mayonez ilə böyük əl rulonu', 'Salmon Burrito', 'Sushi rice, salmon, avocado, spicy mayo — a large hand roll', 'Лосось буррито', 'Суши-рис, лосось, авокадо, острый майонез — большой рулет', 16],
        ['Tempura Burrito', 'Qızardılmış krevet, xiyar, sriracha sousu', 'Tempura Burrito', 'Fried shrimp, cucumber, sriracha sauce', 'Темпура буррито', 'Жареная креветка, огурец, соус шрирача', 17],
        ['Spaysi Tuna Burrito', 'Acılı tuna, avokado, çili-mayonez', 'Spicy Tuna Burrito', 'Spicy tuna, avocado, chili-mayo', 'Спайси тунец буррито', 'Острый тунец, авокадо, чили-майонез', 17],
        ['Toyuq Teriyaki Burrito', 'İzgara toyuq, teriyaki sousu, tərəvəz', 'Chicken Teriyaki Burrito', 'Grilled chicken, teriyaki sauce, vegetables', 'Курица терияки буррито', 'Курица гриль, соус терияки, овощи', 15],
        ['Krab Burrito', 'Krab çubuğu, krem pendir, xiyar', 'Crab Burrito', 'Crab stick, cream cheese, cucumber', 'Краб буррито', 'Крабовые палочки, сливочный сыр, огурец', 15],
        ['Vegan Burrito', 'Tofu, avokado, tərəvəz qarışığı', 'Vegan Burrito', 'Tofu, avocado, mixed vegetables', 'Веган буррито', 'Тофу, авокадо, овощное ассорти', 14],
        ['Unagi Burrito', 'Şirin ilan balığı, avokado, susam', 'Unagi Burrito', 'Sweet eel, avocado, sesame', 'Унаги буррито', 'Сладкий угорь, авокадо, кунжут', 18],
        ['Qızardılmış Krevet Burrito', 'Panko krevet, salat, xüsusi sous', 'Crispy Shrimp Burrito', 'Panko shrimp, lettuce, special sauce', 'Хрустящая креветка буррито', 'Креветка в панко, салат, фирменный соус', 17],
        ['Wagyu Burrito', 'Wagyu mal əti, avokado, teriyaki qlazur', 'Wagyu Burrito', 'Wagyu beef, avocado, teriyaki glaze', 'Вагю буррито', 'Говядина вагю, авокадо, глазурь терияки', 24],
        ['King Krab Burrito', 'İri krab əti, krem pendir, mango sousu', 'King Crab Burrito', 'Large king crab meat, cream cheese, mango sauce', 'Королевский краб буррито', 'Мясо крупного краба, сливочный сыр, манговый соус', 20],
    ],
    'noodles' => [
        ['Udon (toyuqlu)', 'Qalın buğda əriştəsi, toyuq bulyonu', 'Udon (Chicken)', 'Thick wheat noodles, chicken broth', 'Удон (с курицей)', 'Толстая пшеничная лапша, куриный бульон', 15],
        ['Udon (dəniz məhsulları)', 'Qarışıq dəniz məhsulları ilə', 'Udon (Seafood)', 'With mixed seafood', 'Удон (морепродукты)', 'С ассорти морепродуктов', 18],
        ['Yaki soba (tərəvəzli)', 'Qızardılmış əriştə, tərəvəz', 'Yaki Soba (Vegetable)', 'Fried noodles with vegetables', 'Якисоба (овощная)', 'Жареная лапша с овощами', 13],
        ['Yaki soba (toyuqlu)', 'Qızardılmış əriştə, toyuq əti', 'Yaki Soba (Chicken)', 'Fried noodles with chicken', 'Якисоба (с курицей)', 'Жареная лапша с курицей', 15],
        ['Ramen (toyuqlu)', 'Ənənəvi isti şorba, toyuq əti', 'Ramen (Chicken)', 'Traditional hot soup with chicken', 'Рамен (с курицей)', 'Традиционный горячий суп с курицей', 16],
        ['Ramen (dəniz məhsulları)', 'Zəngin bulyon, dəniz məhsulları', 'Ramen (Seafood)', 'Rich broth with seafood', 'Рамен (морепродукты)', 'Насыщенный бульон с морепродуктами', 19],
        ['Miso Ramen', 'Miso əsaslı bulyon', 'Miso Ramen', 'Miso-based broth', 'Мисо рамен', 'Бульон на основе мисо', 15],
        ['Udon (Mal ətli)', 'Qalın əriştə, uzun bişmiş mal əti', 'Beef Udon', 'Thick noodles with slow-braised beef', 'Удон (с говядиной)', 'Толстая лапша, тушёная говядина', 17],
        ['Udon (Tofu, vegan)', 'Vegan bulyon, tofu, tərəvəz', 'Tofu Udon (Vegan)', 'Vegan broth, tofu, vegetables', 'Удон с тофу (веган)', 'Веганский бульон, тофу, овощи', 14],
        ['Şoyu Ramen (toyuqlu)', 'Soya sousu əsaslı klassik bulyon', 'Shoyu Ramen (Chicken)', 'Classic soy-sauce based broth', 'Сёю рамен (с курицей)', 'Классический бульон на основе соевого соуса', 16],
        ['Karri Ramen', 'Yapon karri sousu, toyuq əti', 'Curry Ramen', 'Japanese curry broth, chicken', 'Карри рамен', 'Японский соус карри, курица', 17],
        ['Acılı Tantanmen (toyuqlu)', 'Ədviyyəli susam bulyonu, toyuq qıyması', 'Spicy Tantanmen (Chicken)', 'Spicy sesame broth, minced chicken', 'Острый тантанмен (с курицей)', 'Острый кунжутный бульон, куриный фарш', 17],
        ['Qara Sarımsaq Ramen', 'Qara sarımsaq yağlı bulyon, toyuq əti', 'Black Garlic Ramen', 'Black garlic oil broth, chicken', 'Рамен с чёрным чесноком', 'Бульон с маслом чёрного чеснока, курица', 16],
        ['Soba (soyuq, yay üslubu)', 'Soyuq soba əriştəsi, batırma sousu ilə', 'Cold Soba', 'Chilled soba noodles with dipping sauce', 'Холодная соба', 'Охлаждённая лапша соба с соусом для макания', 13],
        ['Pad Thai (krevetli)', 'Tay üslubunda qızardılmış əriştə, krevet', 'Pad Thai (Shrimp)', 'Thai-style stir-fried noodles with shrimp', 'Пад тай (с креветками)', 'Жареная лапша по-тайски с креветками', 16],
        ['Pad Thai (toyuqlu)', 'Tay üslubunda qızardılmış əriştə, toyuq', 'Pad Thai (Chicken)', 'Thai-style stir-fried noodles with chicken', 'Пад тай (с курицей)', 'Жареная лапша по-тайски с курицей', 15],
        ['Dan Dan Noodles (toyuqlu)', 'Ədviyyəli susam sousu, toyuq qıyması', 'Dan Dan Noodles (Chicken)', 'Spicy sesame sauce, minced chicken', 'Дан дан лапша (с курицей)', 'Острый кунжутный соус, куриный фарш', 16],
        ['Şüşə əriştə salatı', 'Soyuq şüşə əriştə, tərəvəz qarışığı', 'Glass Noodle Salad', 'Cold glass noodle salad with vegetables', 'Салат из стеклянной лапши', 'Холодный салат из стеклянной лапши с овощами', 12],
        ['Yaki Udon (dəniz məhsulları)', 'Qızardılmış udon, qarışıq dəniz məhsulları', 'Yaki Udon (Seafood)', 'Fried udon with mixed seafood', 'Яки удон (морепродукты)', 'Жареный удон с ассорти морепродуктов', 18],
        ['Teriyaki Əriştə Qabı', 'Toyuq teriyaki, əriştə, tərəvəz', 'Teriyaki Noodle Bowl', 'Teriyaki chicken, noodles, vegetables', 'Тарелка лапши терияки', 'Курица терияки, лапша, овощи', 16],
    ],
    'salat' => [
        ['Sezar Salatı (toyuqlu)', 'Marul, parmezan, krutonlar, sezar sousu', 'Caesar Salad (Chicken)', 'Lettuce, parmesan, croutons, Caesar dressing', 'Салат Цезарь (с курицей)', 'Салат, пармезан, крутоны, соус Цезарь', 13],
        ['Kaliforniya salatı', 'Krab çubuğu, avokado, xiyar', 'California Salad', 'Crab stick, avocado, cucumber', 'Салат Калифорния', 'Крабовые палочки, авокадо, огурец', 12],
        ['Losos salatı', 'Yüngül sitrus sousu, təzə göyərti', 'Salmon Salad', 'Light citrus dressing, fresh greens', 'Салат с лососем', 'Лёгкая цитрусовая заправка, свежая зелень', 14],
        ['Tuna poke salatı', 'Havay üslubunda ədviyyəli tuna', 'Tuna Poke Salad', 'Hawaiian-style spiced tuna', 'Салат поке с тунцом', 'Пряный тунец по-гавайски', 16],
        ['Avokado-krevet salatı', 'Krevet, avokado, yüngül sous', 'Avocado-Shrimp Salad', 'Shrimp, avocado, light dressing', 'Салат авокадо-креветки', 'Креветки, авокадо, лёгкий соус', 14],
        ['Chuka yosun salatı', 'Ənənəvi ədviyyəli yosun', 'Chuka Seaweed Salad', 'Traditional spiced seaweed', 'Салат чука из водорослей', 'Традиционные пряные водоросли', 9],
        ['Spaysi tuna salatı', 'Acılı tuna, çili-mayonez', 'Spicy Tuna Salad', 'Spicy tuna, chili-mayo', 'Салат спайси тунец', 'Острый тунец, чили-майонез', 15],
        ['Edamame salatı', 'Yaşıl soya lobyası əsaslı yüngül salat', 'Edamame Salad', 'Light salad based on green soybeans', 'Салат эдамаме', 'Лёгкий салат на основе зелёных соевых бобов', 9],
    ],
    'ickiler' => [
        ['Yaşıl çay', 'Ənənəvi isti içki', 'Green Tea', 'Traditional hot drink', 'Зелёный чай', 'Традиционный горячий напиток', 3],
        ['Yapon limonadı (yuzu)', 'Sitrus təravəti', 'Japanese Lemonade (Yuzu)', 'Citrus freshness', 'Японский лимонад (юдзу)', 'Цитрусовая свежесть', 5],
        ['Kola / Fanta / Sprite', 'Soyuq sərinləşdirici içki', 'Coke / Fanta / Sprite', 'Cold refreshing drink', 'Кола / Фанта / Спрайт', 'Холодный освежающий напиток', 4],
        ['Mineral su', '0.5L', 'Mineral Water', '0.5L', 'Минеральная вода', '0.5Л', 2],
        ['Təzə sıxılmış portağal şirəsi', 'Gündəlik təzə', 'Fresh Orange Juice', 'Fresh daily', 'Свежевыжатый апельсиновый сок', 'Свежий каждый день', 7],
        ['Ice Matcha Latte', 'Soyuq matcha və süd', 'Ice Matcha Latte', 'Cold matcha and milk', 'Айс матча латте', 'Холодная матча с молоком', 9],
        ['Espresso', 'Qısa, güclü qəhvə', 'Espresso', 'Short, strong coffee', 'Эспрессо', 'Короткий, крепкий кофе', 5],
    ],
];

$done = false;
$stats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rebuild') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: rebuild-menu.php');
        exit;
    }

    $pdo->beginTransaction();
    try {
        // Köhnə hər şeyi sil (kateqoriyaları silmək FK CASCADE ilə məhsulları da silir)
        $pdo->exec('DELETE FROM products');
        $pdo->exec('DELETE FROM categories');

        $catStmt = $pdo->prepare('INSERT INTO categories (name, name_ru, name_en, sort_order, active) VALUES (?, ?, ?, ?, 1)');
        $prodStmt = $pdo->prepare('
            INSERT INTO products (category_id, name, name_ru, name_en, description, description_ru, description_en, price, active, featured, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?)
        ');

        $catIds = [];
        $ci = 1;
        foreach ($categoriesData as $key => $c) {
            $catStmt->execute([$c[0], $c[1], $c[2], $ci]);
            $catIds[$key] = (int)$pdo->lastInsertId();
            $ci++;
        }

        $totalProducts = 0;
        foreach ($productsData as $key => $items) {
            $catId = $catIds[$key];
            $pi = 1;
            foreach ($items as $p) {
                // p: [nameAZ, descAZ, nameEN, descEN, nameRU, descRU, price]
                $prodStmt->execute([$catId, $p[0], $p[4], $p[2], $p[1], $p[5], $p[3], $p[6], $pi]);
                $pi++;
                $totalProducts++;
            }
        }

        $pdo->commit();
        $done = true;
        $stats = ['categories' => count($catIds), 'products' => $totalProducts];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_err'] = 'Xəta baş verdi, heç nə dəyişmədi: ' . $e->getMessage();
        header('Location: rebuild-menu.php');
        exit;
    }
}

$currentCatCount = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$currentProdCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$csrf = sg_csrf_token();
$pageTitle = 'Menyunu Yenidən Qur';
$activeNav = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:680px;">
  <div class="panel-head"><h2>Yeni menyu strukturuna keç</h2></div>

  <?php if ($done): ?>
    <div class="flash ok">
      Hazırdır! <?php echo $stats['categories']; ?> kateqoriya və <?php echo $stats['products']; ?> məhsul yaradıldı.
      İndi <a href="categories.php">Kateqoriyalar</a> və <a href="products.php">Menyu</a> bölmələrinə baxa bilərsiniz.
    </div>
  <?php else: ?>
    <p style="color:var(--text-soft); font-size:.92rem;">
      Hazırda bazada <strong><?php echo $currentCatCount; ?></strong> kateqoriya və <strong><?php echo $currentProdCount; ?></strong> məhsul var.
      Bu düymə onların HAMISINI siləcək və yerinə aşağıdakı yeni strukturu yaradacaq:
    </p>
    <ul style="font-size:.9rem; color:var(--text-soft); line-height:1.9; padding-left:1.2rem;">
      <?php foreach ($categoriesData as $key => $c): ?>
        <li><strong><?php echo h($c[0]); ?></strong> — <?php echo count($productsData[$key]); ?> məhsul</li>
      <?php endforeach; ?>
    </ul>
    <div class="flash err" style="margin-top:1rem;">
      ⚠️ DİQQƏT: Bu geri qaytarıla bilməz. Əgər hazırkı menyuda saxlamaq istədiyiniz fərdi düzəlişlər/fotolar varsa,
      davam etməzdən əvvəl cPanel-də "Full Backup" (JetBackup) alın.
    </div>
    <form method="post" onsubmit="return confirm('Əminsiniz? Bütün hazırkı kateqoriya və məhsullar silinəcək.');" style="margin-top:1.2rem;">
      <input type="hidden" name="action" value="rebuild">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <button type="submit" class="btn btn-danger">Bəli, köhnəni sil və yeni strukturu yarat</button>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
