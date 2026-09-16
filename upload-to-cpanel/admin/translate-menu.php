<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();
$pdo = sg_db();

// Kateqoriya tərcümələri: id => [EN, RU]
$categoryTranslations = [
    1 => ['Starters', 'Закуски'],
    2 => ['Salads', 'Салаты'],
    3 => ['Nigiri & Sashimi', 'Нигири и сашими'],
    4 => ['Classic Rolls', 'Классические роллы'],
    5 => ['Baked Rolls', 'Запечённые роллы'],
    6 => ['Premium Rolls', 'Премиум роллы'],
    7 => ['Vegetarian Rolls', 'Вегетарианские роллы'],
    8 => ['Hot Dishes', 'Горячие блюда'],
    9 => ['Sets', 'Сеты'],
    10 => ['Desserts & Drinks', 'Десерты и напитки'],
];

// Məhsul tərcümələri: id => [Name EN, Desc EN, Name RU, Desc RU]
$productTranslations = [
    1 => ['Miso Soup', 'Traditional soybean paste with tofu and seaweed', 'Мисо-суп', 'Традиционная соевая паста с тофу и водорослями'],
    2 => ['Edamame', 'Salted, steamed soybeans', 'Эдамаме', 'Солёные, приготовленные на пару соевые бобы'],
    3 => ['Spicy Edamame', 'Spicy version with chili oil', 'Эдамаме острые', 'Острый вариант с маслом чили'],
    4 => ['Gyoza (Chicken, 5 pcs)', 'Pan-seared chicken dumplings', 'Гёдза (курица, 5 шт)', 'Обжаренные пельмени с курицей'],
    5 => ['Gyoza (Vegetable, 5 pcs)', 'Vegetarian dumplings with soy sauce', 'Гёдза (овощи, 5 шт)', 'Вегетарианские пельмени с соевым соусом'],
    6 => ['Shrimp Tempura (4 pcs)', 'Deep-fried crispy shrimp', 'Темпура из креветок (4 шт)', 'Хрустящие жареные креветки'],
    7 => ['Vegetable Tempura', 'Mixed seasonal vegetable tempura', 'Темпура из овощей', 'Ассорти сезонных овощей темпура'],
    8 => ['Kimchi', 'Traditional spicy fermented cabbage', 'Кимчи', 'Традиционная острая квашеная капуста'],
    9 => ['Spring Rolls (3 pcs)', 'Crispy vegetable spring rolls', 'Спринг-роллы (3 шт)', 'Хрустящие овощные спринг-роллы'],
    10 => ['Tori Karaage', 'Spiced fried chicken pieces', 'Тори карааге', 'Пряные жареные кусочки курицы'],
    11 => ['Fried Tofu', 'In sweet-savory soy sauce', 'Жареный тофу', 'В сладко-солёном соевом соусе'],
    12 => ['Takoyaki (5 pcs)', 'Octopus-filled batter balls', 'Такояки (5 шт)', 'Шарики с осьминогом'],
    13 => ['Ebi Furai (2 pcs)', 'Deep-fried jumbo shrimp', 'Эби фурай (2 шт)', 'Хрустящие жареные крупные креветки'],
    14 => ['Crab Croquette', 'Crab filling in a crispy crust', 'Крабовые крокеты', 'Крабовая начинка в хрустящей корочке'],
    15 => ['Wakame Salad', 'Vinegared seaweed salad', 'Салат вакаме', 'Салат из морских водорослей с уксусом'],
    16 => ['Chuka Salad', 'Spiced seaweed salad with sesame', 'Салат чука', 'Пряный салат из водорослей с кунжутом'],
    17 => ['Fried Surimi Sticks', 'Crab sticks in a crispy coating', 'Жареные крабовые палочки', 'Крабовые палочки в хрустящей панировке'],
    18 => ['Avocado Toast', 'Avocado cream on toasted bread', 'Тост с авокадо', 'Крем из авокадо на поджаренном хлебе'],
    19 => ['Miso Eggplant', 'Eggplant cooked in sweet miso sauce', 'Баклажан мисо', 'Баклажан, приготовленный в сладком соусе мисо'],
    20 => ['Garlic Fried Rice', 'Also great as a side dish', 'Жареный рис с чесноком', 'Отлично подходит и как гарнир'],

    21 => ['Caesar Salad (Chicken)', 'Lettuce, parmesan, croutons, Caesar dressing', 'Салат Цезарь (с курицей)', 'Салат, пармезан, крутоны, соус Цезарь'],
    22 => ['Caesar Salad (Shrimp)', 'Classic Caesar with grilled shrimp', 'Салат Цезарь (с креветками)', 'Классический Цезарь с креветками гриль'],
    23 => ['California Salad', 'Crab stick, avocado, cucumber', 'Салат Калифорния', 'Крабовые палочки, авокадо, огурец'],
    24 => ['Salmon Salad', 'Light citrus dressing, fresh greens', 'Салат с лососем', 'Лёгкая цитрусовая заправка, свежая зелень'],
    25 => ['Tuna Poke Salad', 'Hawaiian-style spiced tuna', 'Салат поке с тунцом', 'Пряный тунец по-гавайски'],
    26 => ['Chuka Seaweed Salad', 'Traditional spiced seaweed', 'Салат чука из водорослей', 'Традиционные пряные водоросли'],
    27 => ['Green Salad', 'With lemon-olive oil dressing', 'Зелёный салат', 'С лимонно-оливковой заправкой'],
    28 => ['Avocado-Shrimp Salad', 'Shrimp, avocado, light dressing', 'Салат авокадо-креветки', 'Креветки, авокадо, лёгкий соус'],
    29 => ['Cucumber-Sesame Salad', 'Fresh salad with vinegar dressing', 'Салат огурец-кунжут', 'Свежий салат с уксусной заправкой'],
    30 => ['Chicken Teriyaki Salad', 'Grilled chicken, teriyaki sauce', 'Салат с курицей терияки', 'Курица гриль, соус терияки'],
    31 => ['Tofu Salad (Vegan)', 'Tofu, vegetables, sesame dressing', 'Салат с тофу (веган)', 'Тофу, овощи, кунжутный соус'],
    32 => ['Mango-Avocado Salad', 'Tropical, refreshing mix', 'Салат манго-авокадо', 'Тропическое освежающее сочетание'],
    33 => ['Spinach-Parmesan Salad', 'With walnuts and parmesan', 'Салат шпинат-пармезан', 'С грецким орехом и пармезаном'],
    34 => ['Shrimp Salad', 'Grilled shrimp with special sauce', 'Салат с креветками', 'Креветки гриль под фирменным соусом'],
    35 => ['Mixed Vegetable Salad', 'Fresh seasonal vegetables', 'Салат из свежих овощей', 'Свежие сезонные овощи'],
    36 => ['Unagi Salad', 'Eel with unagi sauce', 'Салат унаги', 'Угорь с соусом унаги'],
    37 => ['Surimi Salad', 'Crab stick, celery, mayo dressing', 'Салат с крабовыми палочками', 'Крабовые палочки, сельдерей, майонезный соус'],
    38 => ['Spicy Tuna Salad', 'Spicy tuna, chili-mayo', 'Салат спайси тунец', 'Острый тунец, чили-майонез'],
    39 => ['Tuna Tataki Salad', 'Lightly seared tuna', 'Салат татаки из тунца', 'Слегка обжаренный тунец'],
    40 => ['Edamame Salad', 'Light salad based on green soybeans', 'Салат эдамаме', 'Лёгкий салат на основе зелёных соевых бобов'],

    41 => ['Salmon Nigiri (2 pcs)', 'Fresh salmon over hand-pressed rice', 'Нигири с лососем (2 шт)', 'Свежий лосось на рисе ручной лепки'],
    42 => ['Tuna Nigiri (2 pcs)', 'Fresh tuna', 'Нигири с тунцом (2 шт)', 'Свежий тунец'],
    43 => ['Unagi Nigiri (2 pcs)', 'Eel glazed with sweet sauce', 'Нигири унаги (2 шт)', 'Угорь в сладком соусе'],
    44 => ['Shrimp Nigiri (2 pcs)', 'Cooked shrimp', 'Нигири с креветкой (2 шт)', 'Варёная креветка'],
    45 => ['White Fish Nigiri (2 pcs)', 'Seasonal white fish', 'Нигири с белой рыбой (2 шт)', 'Сезонная белая рыба'],
    46 => ['Mackerel Nigiri (2 pcs)', 'Marinated mackerel', 'Нигири со скумбрией (2 шт)', 'Маринованная скумбрия'],
    47 => ['Tofu Nigiri (2 pcs)', 'Vegetarian option', 'Нигири с тофу (2 шт)', 'Вегетарианский вариант'],
    48 => ['Tamago Nigiri (2 pcs)', 'Sweet egg omelet', 'Нигири тамаго (2 шт)', 'Сладкий яичный омлет'],
    49 => ['Uni Gunkan', 'Sea urchin in gunkan style', 'Гункан с уни', 'Морской ёж в форме гункан'],
    50 => ['Crab Gunkan', 'Crab salad in gunkan style', 'Гункан с крабом', 'Крабовый салат в форме гункан'],
    51 => ['Salmon Sashimi (5 slices)', 'Thinly sliced fresh salmon', 'Сашими из лосося (5 ломтиков)', 'Тонко нарезанный свежий лосось'],
    52 => ['Tuna Sashimi (5 slices)', 'Thinly sliced fresh tuna', 'Сашими из тунца (5 ломтиков)', 'Тонко нарезанный свежий тунец'],
    53 => ['White Fish Sashimi (5 slices)', 'Seasonal white fish', 'Сашими из белой рыбы (5 ломтиков)', 'Сезонная белая рыба'],
    54 => ['Unagi Sashimi (5 slices)', 'Marinated eel', 'Сашими унаги (5 ломтиков)', 'Маринованный угорь'],
    55 => ['Mackerel Sashimi (5 slices)', 'Salt-vinegar marinade', 'Сашими из скумбрии (5 ломтиков)', 'Кисло-солёный маринад'],
    56 => ['Octopus Sashimi (5 slices)', 'Tender cooked octopus', 'Сашими из осьминога (5 ломтиков)', 'Нежно приготовленный осьминог'],
    57 => ['Mixed Sashimi (9 slices)', 'Salmon, tuna and white fish', 'Сашими ассорти (9 ломтиков)', 'Лосось, тунец и белая рыба'],
    58 => ['Aburi Salmon Nigiri (2 pcs)', 'Torch-seared salmon', 'Нигири абури с лососем (2 шт)', 'Слегка обожжённый лосось'],
    59 => ['Spicy Tuna Gunkan', 'Spicy tuna mix', 'Гункан спайси тунец', 'Острая тунцовая смесь'],
    60 => ['Sashimi Platter (15 slices)', 'A large platter of 3 fish varieties', 'Тарелка сашими (15 ломтиков)', 'Большая тарелка из 3 видов рыбы'],

    61 => ['California Roll', 'Crab stick, avocado, cucumber, sesame', 'Ролл Калифорния', 'Крабовые палочки, авокадо, огурец, кунжут'],
    62 => ['Philadelphia Roll', 'Salmon, cream cheese, avocado', 'Ролл Филадельфия', 'Лосось, сливочный сыр, авокадо'],
    63 => ['Spicy Tuna Roll', 'Spicy tuna, chili-mayo, green onion', 'Ролл спайси тунец', 'Острый тунец, чили-майонез, зелёный лук'],
    64 => ['Crab Roll', 'Crab stick, cucumber, mayo', 'Крабовый ролл', 'Крабовые палочки, огурец, майонез'],
    65 => ['Avocado Roll', 'Simple vegan option', 'Ролл с авокадо', 'Простой веганский вариант'],
    66 => ['Cucumber Roll', 'Light and refreshing', 'Ролл с огурцом', 'Лёгкий, освежающий'],
    67 => ['Salmon-Avocado Roll', 'A combination of two classic flavors', 'Ролл лосось-авокадо', 'Сочетание двух классических вкусов'],
    68 => ['Tuna Roll', 'Classic à la carte tuna', 'Ролл с тунцом', 'Классический тунец'],
    69 => ['Ebi Tempura Roll', 'Fried shrimp, avocado', 'Ролл эби темпура', 'Жареная креветка, авокадо'],
    70 => ['Sake Maki (Salmon)', 'Thin salmon roll', 'Сяке маки (лосось)', 'Тонкий ролл с лососем'],
    71 => ['Tekka Maki (Tuna)', 'Thin tuna roll', 'Текка маки (тунец)', 'Тонкий ролл с тунцом'],
    72 => ['Kappa Maki (Cucumber)', 'Thin cucumber roll', 'Каппа маки (огурец)', 'Тонкий ролл с огурцом'],
    73 => ['Unagi-Avocado Roll', 'Sweet eel, avocado', 'Ролл унаги-авокадо', 'Сладкий угорь, авокадо'],
    74 => ['Salmon Cream Cheese Roll', 'Soft, creamy texture', 'Ролл лосось со сливочным сыром', 'Мягкая, кремовая текстура'],
    75 => ['Spicy Crab Roll', 'Spicy crab mix', 'Ролл спайси краб', 'Острая крабовая смесь'],
    76 => ['Green Garden Roll', 'Vegetarian mixed vegetables', 'Ролл зелёный сад', 'Вегетарианское овощное ассорти'],
    77 => ['Tuna-Garlic Roll', 'Enriched with garlic oil', 'Ролл тунец-чеснок', 'Обогащён чесночным маслом'],
    78 => ['Tempura Shrimp-Avocado Roll', 'Crispy and creamy texture', 'Ролл темпура креветка-авокадо', 'Хрустящая и кремовая текстура'],
    79 => ['Surimi-Cucumber Roll', 'Light and cooling', 'Ролл крабовые палочки-огурец', 'Лёгкий и освежающий'],
    80 => ['Spicy Salmon Roll', 'Spicy salmon mix', 'Ролл спайси лосось', 'Острая смесь с лососем'],

    81 => ['Tempura Roll (Shrimp)', 'With fried shrimp', 'Ролл темпура (креветка)', 'С жареной креветкой'],
    82 => ['Ebi Katsu Roll', 'Panko-fried shrimp', 'Ролл эби кацу', 'Креветка в панировке панко'],
    83 => ['Fried Philadelphia Roll', 'Crispy coating, cream cheese', 'Жареный ролл Филадельфия', 'Хрустящая корочка, сливочный сыр'],
    84 => ['Crispy Crab Roll', 'Crunchy crab mix', 'Хрустящий крабовый ролл', 'Хрустящая крабовая смесь'],
    85 => ['Hot Cheese Salmon Roll', 'Topped with baked cheese', 'Ролл лосось хот-чиз', 'Запечён с сыром сверху'],
    86 => ['Fried Vegetable Roll', 'Vegetarian, crispy', 'Жареный овощной ролл', 'Вегетарианский, хрустящий'],
    87 => ['Panko-Tuna Roll', 'Tuna fried in panko', 'Ролл панко-тунец', 'Тунец, жаренный в панко'],
    88 => ['Fried Unagi Roll', 'Topped with sweet sauce', 'Жареный ролл унаги', 'Полит сладким соусом сверху'],
    89 => ['Volcano Roll', 'Torched with spicy hot sauce on top', 'Ролл Вулкан', 'Запечён с острым соусом сверху'],
    90 => ['Dragon Roll', 'Topped with eel and avocado', 'Ролл Дракон', 'Сверху угорь и авокадо'],
    91 => ['Phoenix Roll', 'Topped with red caviar', 'Ролл Феникс', 'Украшен красной икрой сверху'],
    92 => ['Fire Shrimp Roll', 'Sweet-spicy fried shrimp', 'Ролл огненная креветка', 'Сладко-острая жареная креветка'],
    93 => ['Fried Spicy Tuna Roll', 'Crispy coating, spicy tuna', 'Жареный ролл спайси тунец', 'Хрустящая корочка, острый тунец'],
    94 => ['Crispy Chicken Tempura Roll', 'Fried chicken, special sauce', 'Хрустящий ролл темпура с курицей', 'Жареная курица, фирменный соус'],
    95 => ['Fried Spinach-Cheese Roll', 'Creamy, warm', 'Жареный ролл шпинат-сыр', 'Кремовый, тёплый'],
    96 => ['Baked Salmon Roll', 'Salmon baked in sauce', 'Запечённый ролл с лососем', 'Лосось, запечённый в соусе'],
    97 => ['Tiger Roll', 'Topped with shrimp and avocado', 'Ролл Тигр', 'Сверху креветка и авокадо'],
    98 => ['Rainbow Roll', 'Topped with assorted fish varieties', 'Ролл Радуга', 'Сверху ассорти из разных видов рыбы'],
    99 => ['Samurai Roll', 'Fried chicken and special sauce', 'Ролл Самурай', 'Жареная курица и фирменный соус'],
    100 => ['Godzilla Roll', 'Large size, mixed filling', 'Ролл Годзилла', 'Большой размер, смешанная начинка'],

    101 => ['Truffle Salmon Roll', 'Enriched with truffle oil', 'Ролл лосось с трюфелем', 'Обогащён трюфельным маслом'],
    102 => ['Black Caviar Tuna Roll', 'Premium tuna, black caviar', 'Ролл тунец с чёрной икрой', 'Премиальный тунец, чёрная икра'],
    103 => ['Wagyu-Avocado Roll', 'Wagyu beef, avocado', 'Ролл вагю-авокадо', 'Говядина вагю, авокадо'],
    104 => ['Foie Gras Sushi', 'French-Japanese fusion', 'Суши фуа-гра', 'Франко-японский фьюжн'],
    105 => ['Gold Leaf Dragon Roll', 'With edible gold leaf', 'Ролл Дракон с золотом', 'С съедобным сусальным золотом'],
    106 => ['Premium Uni Roll', 'With sea urchin', 'Премиум ролл с уни', 'С морским ежом'],
    107 => ['King Crab Roll', 'With large king crab meat', 'Ролл с королевским крабом', 'С мясом крупного краба'],
    108 => ['Toro (Fatty Tuna) Roll', 'The fattiest part of the tuna', 'Ролл Торо (жирный тунец)', 'Самая жирная часть тунца'],
    109 => ['Premium Unagi-Foie Roll', 'Eel and foie gras', 'Премиум ролл унаги-фуа', 'Угорь и фуа-гра'],
    110 => ['Caviar Aburi Salmon Roll', 'Torch-seared salmon, black caviar', 'Ролл абури лосось с икрой', 'Обожжённый лосось, чёрная икра'],
    111 => ['Truffle Oil Philadelphia Roll', 'A premium take on the classic', 'Ролл Филадельфия с трюфельным маслом', 'Премиальная версия классики'],
    112 => ['Gold Dust California Roll', 'With edible gold dust', 'Ролл Калифорния с золотой пудрой', 'Со съедобной золотой пудрой'],
    113 => ['Lobster Roll', 'Premium seafood crustacean', 'Ролл с лобстером', 'Премиальный морской деликатес'],
    114 => ['Wagyu Tataki Roll', 'Lightly seared wagyu beef', 'Ролл вагю татаки', 'Слегка обжаренная говядина вагю'],
    115 => ['Premium Sashimi Deluxe Roll', 'The finest selection of fish', 'Премиум ролл Сашими Делюкс', 'Отборные виды рыбы'],
    116 => ['Scallop Roll', 'Delicate scallop meat', 'Ролл с морским гребешком', 'Нежное мясо морского гребешка'],
    117 => ['Black Sesame Toro Roll', 'Fatty tuna, black sesame', 'Ролл Торо с чёрным кунжутом', 'Жирный тунец, чёрный кунжут'],
    118 => ['Caviar Gunkan Set', 'Premium red caviar', 'Сет гункан с икрой', 'Премиальная красная икра'],
    119 => ['VIP Sushi Garden Roll', "Our house's most premium choice", 'VIP-ролл Sushi Garden', 'Самый премиальный выбор нашего дома'],
    120 => ["Chef's Special Roll", 'Premium pick of the day (may vary)', 'Особый ролл от шефа', 'Премиальный выбор дня (может меняться)'],

    121 => ['Avocado Roll', 'Simple, fresh vegan option', 'Ролл с авокадо', 'Простой, свежий веганский вариант'],
    122 => ['Cucumber Roll', 'Light and cooling', 'Ролл с огурцом', 'Лёгкий и освежающий'],
    123 => ['Green Garden Roll', 'Mixed seasonal vegetables', 'Ролл зелёный сад', 'Ассорти сезонных овощей'],
    124 => ['Tofu-Avocado Roll', 'Protein-rich vegan option', 'Ролл тофу-авокадо', 'Богатый белком веганский вариант'],
    125 => ['Spinach Cream Cheese Roll', 'Vegetarian, creamy', 'Ролл шпинат со сливочным сыром', 'Вегетарианский, кремовый'],
    126 => ['Sweet Potato Tempura Roll', 'Fried, sweet flavor', 'Ролл темпура из батата', 'Жареный, сладкий вкус'],
    127 => ['Shiitake Mushroom Roll', 'Marinated shiitake mushrooms', 'Ролл с грибами шиитаке', 'Маринованные грибы шиитаке'],
    128 => ['Mango-Cucumber Roll', 'Tropical and refreshing', 'Ролл манго-огурец', 'Тропический и освежающий'],
    129 => ['Eggplant Tempura Roll', 'Fried eggplant', 'Ролл темпура с баклажаном', 'Жареный баклажан'],
    130 => ['Vegan California Roll', 'Classic style with tofu', 'Веганский ролл Калифорния', 'Классика с тофу'],
    131 => ['Fried Vegetable Roll', 'Crispy mixed vegetables', 'Жареный овощной ролл', 'Хрустящее овощное ассорти'],
    132 => ['Garlic Spinach Roll', 'Aromatic vegetarian option', 'Ролл со шпинатом и чесноком', 'Ароматный вегетарианский вариант'],
    133 => ['Kimchi-Tofu Roll', 'With spicy-sour kimchi', 'Ролл кимчи-тофу', 'С острым квашеным кимчи'],
    134 => ['Avocado-Mango Roll', 'Creamy texture of two fruits', 'Ролл авокадо-манго', 'Кремовая текстура двух фруктов'],
    135 => ['Green Noodle Roll', 'With thin vegetable "noodle" strips', 'Ролл зелёная лапша', 'С тонкими овощными полосками'],
    136 => ['Sweet Pepper-Cheese Roll', 'Creamy and mildly sweet', 'Ролл сладкий перец-сыр', 'Кремовый и слегка сладкий'],
    137 => ['Vegan Dragon Roll', 'All-vegetable, dramatic presentation', 'Веганский ролл Дракон', 'Полностью овощной, эффектная подача'],
    138 => ['Olive-Tomato Roll', 'A Mediterranean fusion touch', 'Ролл оливки-помидор', 'Средиземноморский штрих фьюжн'],
    139 => ['Vegan Rainbow Roll', 'Colorful vegetable mix', 'Веганский ролл Радуга', 'Разноцветное овощное ассорти'],
    140 => ['Mini Vegan Set Roll', '4 different vegetarian fillings', 'Мини веганский сет', '4 разные вегетарианские начинки'],

    141 => ['Chicken Teriyaki', 'With rice, in teriyaki sauce', 'Курица терияки', 'С рисом, в соусе терияки'],
    142 => ['Beef Teriyaki', 'With rice, sweet-savory sauce', 'Говядина терияки', 'С рисом, сладко-солёный соус'],
    143 => ['Udon (Chicken)', 'Thick wheat noodles, chicken broth', 'Удон (с курицей)', 'Толстая пшеничная лапша, куриный бульон'],
    144 => ['Udon (Seafood)', 'With mixed seafood', 'Удон (морепродукты)', 'С ассорти морепродуктов'],
    145 => ['Yaki Soba (Vegetable)', 'Fried noodles with vegetables', 'Якисоба (овощная)', 'Жареная лапша с овощами'],
    146 => ['Yaki Soba (Chicken)', 'Fried noodles with chicken', 'Якисоба (с курицей)', 'Жареная лапша с курицей'],
    147 => ['Chicken Katsu', 'Panko-fried chicken breast', 'Куриный кацу', 'Куриная грудка в панировке панко'],
    148 => ['Wok-Fried Vegetables', 'Mixed seasonal vegetables', 'Овощи вок', 'Ассорти сезонных овощей'],
    149 => ['Shrimp Wok', 'Sautéed shrimp with vegetables', 'Креветки вок', 'Обжаренные креветки с овощами'],
    150 => ['Tempura Combo', 'Shrimp and vegetable tempura', 'Комбо темпура', 'Темпура из креветок и овощей'],
    151 => ['Ramen (Chicken)', 'Traditional hot soup with chicken', 'Рамен (с курицей)', 'Традиционный горячий суп с курицей'],
    152 => ['Ramen (Seafood)', 'Rich broth with seafood', 'Рамен (морепродукты)', 'Насыщенный бульон с морепродуктами'],
    153 => ['Miso Ramen', 'Miso-based broth', 'Мисо рамен', 'Бульон на основе мисо'],
    154 => ['Teppanyaki Shrimp', 'Shrimp cooked on a hot griddle', 'Тэппаньяки с креветками', 'Креветки, приготовленные на горячей плите'],
    155 => ['Teppanyaki Beef', 'Beef cooked on a hot griddle', 'Тэппаньяки с говядиной', 'Говядина, приготовленная на горячей плите'],
    156 => ['Fried Rice (Chicken)', 'With egg and vegetables', 'Жареный рис (с курицей)', 'С яйцом и овощами'],
    157 => ['Fried Rice (Seafood)', 'With mixed seafood', 'Жареный рис (морепродукты)', 'С ассорти морепродуктов'],
    158 => ['Donburi (Salmon)', 'Hot rice topped with salmon', 'Донбури (лосось)', 'Горячий рис с лососем сверху'],
    159 => ['Donburi (Tuna)', 'Hot rice topped with tuna', 'Донбури (тунец)', 'Горячий рис с тунцом сверху'],
    160 => ['Hot Tofu Pot', 'Vegetarian, in spiced sauce', 'Горячий тофу в горшочке', 'Вегетарианское, в пряном соусе'],

    161 => ['Sushi Garden Set (32 pcs)', 'Mix of 4 roll types, serves 2–3', 'Сет Sushi Garden (32 шт)', 'Микс из 4 видов роллов, на 2–3 персоны'],
    162 => ["Lovers' Set (24 pcs)", 'A choice for two', 'Сет для влюблённых (24 шт)', 'Выбор на двоих'],
    163 => ['Solo Set (16 pcs)', 'A compact choice for one', 'Сет Соло (16 шт)', 'Компактный выбор на одного'],
    164 => ['Family Set (48 pcs)', 'A large mix for 3–4 people', 'Семейный сет (48 шт)', 'Большое ассорти на 3–4 персоны'],
    165 => ['Vegetarian Set (20 pcs)', 'Fully vegetarian mix', 'Вегетарианский сет (20 шт)', 'Полностью вегетарианское ассорти'],
    166 => ['Premium Set (30 pcs)', 'With VIP fish varieties', 'Премиум сет (30 шт)', 'С VIP-видами рыбы'],
    167 => ['Nigiri Set (12 pcs)', 'Mixed nigiri selection', 'Сет нигири (12 шт)', 'Ассорти нигири'],
    168 => ['Sashimi Set (15 slices)', 'Sashimi from 3 fish varieties', 'Сет сашими (15 ломтиков)', 'Сашими из 3 видов рыбы'],
    169 => ['Classic Rolls Set (24 pcs)', 'The most loved classics', 'Сет классических роллов (24 шт)', 'Самая любимая классика'],
    170 => ['Baked Rolls Set (20 pcs)', 'Hot, fried rolls', 'Сет запечённых роллов (20 шт)', 'Горячие жареные роллы'],
    171 => ["Kids' Set (10 pcs)", 'A mild-flavored mini set for children', 'Детский сет (10 шт)', 'Мини-сет с мягким вкусом для детей'],
    172 => ['Business Lunch Set', 'Soup + roll + drink', 'Бизнес-ланч сет', 'Суп + ролл + напиток'],
    173 => ['Office Set (40 pcs)', 'An office order for 4–5 people', 'Офисный сет (40 шт)', 'Заказ на 4–5 человек'],
    174 => ['Banquet Set (60 pcs)', 'A large set for a gathering of 6', 'Банкетный сет (60 шт)', 'Большой сет для компании из 6 человек'],
    175 => ['Duo Set', '2 rolls + 2 nigiri', 'Дуо сет', '2 ролла + 2 нигири'],
    176 => ["Chef's Choice Set", 'Premium pick of the day', 'Сет выбор шефа', 'Премиальный выбор дня'],
    177 => ['Party Set (80 pcs)', 'For large gatherings', 'Party сет (80 шт)', 'Для больших компаний'],
    178 => ['Poke Bowl Set (2 bowls)', '2 different poke bowls', 'Сет поке боул (2 чаши)', '2 разных поке боула'],
    179 => ['Fusion Set', 'A mix of premium and classic', 'Фьюжн сет', 'Смесь премиум и классики'],
    180 => ['Romantic Evening Set (for 2)', 'A choice for special occasions', 'Сет романтический вечер (на 2)', 'Выбор для особых случаев'],

    181 => ['Mochi Ice Cream (3 pcs)', 'Ice cream in soft rice dough', 'Мороженое моти (3 шт)', 'Мороженое в мягком рисовом тесте'],
    182 => ['Cheesecake (Japanese Style)', 'Light and fluffy', 'Чизкейк (по-японски)', 'Лёгкий и воздушный'],
    183 => ['Banana Tempura', 'Fried, served with honey', 'Темпура из банана', 'Жареный, с мёдом'],
    184 => ['Chocolate Fondant', 'Warm with a melted chocolate center', 'Шоколадный фондан', 'Тёплый, с растопленной шоколадной начинкой'],
    185 => ['Matcha Ice Cream', 'Green tea flavored', 'Мороженое матча', 'Со вкусом зелёного чая'],
    186 => ['Ice Cream (Choice)', 'Vanilla / chocolate / strawberry', 'Мороженое (на выбор)', 'Ваниль / шоколад / клубника'],
    187 => ['Fruit Platter', 'Fresh seasonal fruits', 'Фруктовая тарелка', 'Свежие сезонные фрукты'],
    188 => ['Dorayaki', 'Japanese pancake with sweet filling', 'Дораяки', 'Японские панкейки со сладкой начинкой'],
    189 => ['Green Tea', 'Traditional hot drink', 'Зелёный чай', 'Традиционный горячий напиток'],
    190 => ['Black Tea', 'Classic hot drink', 'Чёрный чай', 'Классический горячий напиток'],
    191 => ['Japanese Lemonade (Yuzu)', 'Citrus freshness', 'Японский лимонад (юдзу)', 'Цитрусовая свежесть'],
    192 => ['Sake (100 ml)', 'Traditional Japanese drink', 'Саке (100 мл)', 'Традиционный японский напиток'],
    193 => ['Sake (250 ml)', 'Traditional Japanese drink, large size', 'Саке (250 мл)', 'Традиционный японский напиток, большой объём'],
    194 => ['Japanese Beer', 'Cold, classic', 'Японское пиво', 'Холодное, классическое'],
    195 => ['Coke / Fanta / Sprite', 'Cold refreshing drink', 'Кола / Фанта / Спрайт', 'Холодный освежающий напиток'],
    196 => ['Mineral Water', '0.5L', 'Минеральная вода', '0.5Л'],
    197 => ['Fresh Orange Juice', 'Fresh daily', 'Свежевыжатый апельсиновый сок', 'Свежий каждый день'],
    198 => ['Cucumber-Mint Lemonade', 'A refreshing summer drink', 'Лимонад огурец-мята', 'Освежающий летний напиток'],
    199 => ['Ice Matcha Latte', 'Cold matcha and milk', 'Айс матча латте', 'Холодная матча с молоком'],
    200 => ['Espresso', 'Short, strong coffee', 'Эспрессо', 'Короткий, крепкий кофе'],
];

$applied = false;
$catCount = 0;
$prodCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: translate-menu.php');
        exit;
    }

    $catStmt = $pdo->prepare('UPDATE categories SET name_en = ?, name_ru = ? WHERE id = ?');
    foreach ($categoryTranslations as $id => $t) {
        $catStmt->execute([$t[0], $t[1], $id]);
        $catCount++;
    }

    $prodStmt = $pdo->prepare('UPDATE products SET name_en = ?, description_en = ?, name_ru = ?, description_ru = ? WHERE id = ?');
    foreach ($productTranslations as $id => $t) {
        $prodStmt->execute([$t[0], $t[1], $t[2], $t[3], $id]);
        $prodCount++;
    }

    $applied = true;
}

$csrf = sg_csrf_token();
$pageTitle = 'Menyu Tərcüməsi';
$activeNav = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:640px;">
  <div class="panel-head"><h2>Nümunə menyunun RU/EN tərcüməsini tətbiq et</h2></div>

  <?php if ($applied): ?>
    <div class="flash ok"><?php echo $catCount; ?> kateqoriya və <?php echo $prodCount; ?> məhsul tərcüməsi tətbiq olundu. Sayta gedib RU/EN düymələrini sınayın.</div>
  <?php else: ?>
    <p style="color:var(--text-soft); font-size:.92rem;">
      Bu düymə hazırkı nümunə menyunuzdakı (10 kateqoriya, 200 məhsul) bütün adları
      və təsvirləri Rus və İngilis dillərinə əvvəlcədən hazırlanmış tərcümələrlə doldurur.
      Yalnız <strong>bu ID-lərə uyğun</strong> sətirlərə tətbiq olunur — özünüz artıq
      dəyişdiyiniz/əlavə etdiyiniz məhsulların adını dəyişmir, yalnız RU/EN sahələrini yazır.
      Təhlükəsizdir, bir neçə dəfə basmaq problem yaratmaz.
    </p>
    <form method="post">
      <input type="hidden" name="action" value="apply">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <button type="submit" class="btn btn-primary">Tərcümələri tətbiq et</button>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
