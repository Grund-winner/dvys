<?php
/**
 * DVYS AI - Internationalisation (10 langues)
 */

function t(string $key, string $lang = ''): string {
    static $translations = null;

    if ($translations === null) {
        $translations = [
            // === Commun ===
            'app_name' => ['fr' => 'DVYS AI', 'en' => 'DVYS AI', 'es' => 'DVYS AI', 'pt' => 'DVYS AI', 'ru' => 'DVYS AI', 'ar' => 'DVYS AI', 'tr' => 'DVYS AI', 'hi' => 'DVYS AI', 'uz' => 'DVYS AI', 'az' => 'DVYS AI'],
            'home' => ['fr' => 'Accueil', 'en' => 'Home', 'es' => 'Inicio', 'pt' => 'Inicio', 'ru' => 'Главная', 'ar' => 'الرئيسية', 'tr' => 'Ana Sayfa', 'hi' => 'होम', 'uz' => 'Bosh sahifa', 'az' => 'Ev'],
            'chat' => ['fr' => 'Chat IA', 'en' => 'AI Chat', 'es' => 'Chat IA', 'pt' => 'Chat IA', 'ru' => 'Чат ИИ', 'ar' => 'محادثة الذكاء', 'tr' => 'AI Sohbet', 'hi' => 'AI चैट', 'uz' => 'AI Suhbat', 'az' => 'AI Söhbət'],
            'predictions' => ['fr' => 'Pronos', 'en' => 'Predictions', 'es' => 'Pronósticos', 'pt' => 'Previsões', 'ru' => 'Прогнозы', 'ar' => 'التوقعات', 'tr' => 'Tahminler', 'hi' => 'भविष्यवाणियां', 'uz' => 'Taxminlar', 'az' => 'Proqnozlar'],
            'referrals' => ['fr' => 'Parrainage', 'en' => 'Referrals', 'es' => 'Referidos', 'pt' => 'Indicações', 'ru' => 'Рефералы', 'ar' => 'الإحالات', 'tr' => 'Davetler', 'hi' => 'रेफरल', 'uz' => 'Takliflar', 'az' => 'Referral'],
            'login' => ['fr' => 'Connexion', 'en' => 'Login', 'es' => 'Iniciar sesión', 'pt' => 'Entrar', 'ru' => 'Вход', 'ar' => 'تسجيل الدخول', 'tr' => 'Giriş', 'hi' => 'लॉगिन', 'uz' => 'Kirish', 'az' => 'Giriş'],
            'register' => ['fr' => 'Inscription', 'en' => 'Register', 'es' => 'Registrarse', 'pt' => 'Registrar', 'ru' => 'Регистрация', 'ar' => 'التسجيل', 'tr' => 'Kayıt', 'hi' => 'रजिस्टर', 'uz' => "Ro'yxatdan o'tish", 'az' => 'Qeydiyyat'],
            'logout' => ['fr' => 'Déconnexion', 'en' => 'Logout', 'es' => 'Salir', 'pt' => 'Sair', 'ru' => 'Выход', 'ar' => 'تسجيل الخروج', 'tr' => 'Çıkış', 'hi' => 'लॉगआउट', 'uz' => 'Chiqish', 'az' => 'Çıxış'],
            'email' => ['fr' => 'Email', 'en' => 'Email', 'es' => 'Correo', 'pt' => 'Email', 'ru' => 'Email', 'ar' => 'البريد', 'tr' => 'E-posta', 'hi' => 'ईमेल', 'uz' => 'Email', 'az' => 'E-poçt'],
            'password' => ['fr' => 'Mot de passe', 'en' => 'Password', 'es' => 'Contraseña', 'pt' => 'Senha', 'ru' => 'Пароль', 'ar' => 'كلمة المرور', 'tr' => 'Şifre', 'hi' => 'पासवर्ड', 'uz' => 'Parol', 'az' => 'Şifrə'],
            'username' => ['fr' => "Nom d'utilisateur", 'en' => 'Username', 'es' => 'Usuario', 'pt' => 'Usuário', 'ru' => 'Имя', 'ar' => 'اسم المستخدم', 'tr' => 'Kullanıcı Adı', 'hi' => 'यूजरनेम', 'uz' => 'Foydalanuvchi nomi', 'az' => 'İstifadəçi adı'],
            'submit' => ['fr' => 'Continuer', 'en' => 'Continue', 'es' => 'Continuar', 'pt' => 'Continuar', 'ru' => 'Продолжить', 'ar' => 'متابعة', 'tr' => 'Devam', 'hi' => 'जारी रखें', 'uz' => 'Davom etish', 'az' => 'Davam et'],
            'dashboard' => ['fr' => 'Tableau de bord', 'en' => 'Dashboard', 'es' => 'Panel', 'pt' => 'Painel', 'ru' => 'Панель', 'ar' => 'لوحة التحكم', 'tr' => 'Panel', 'hi' => 'डैशबोर्ड', 'uz' => 'Boshqaruv paneli', 'az' => 'İdarə Paneli'],

            // === Landing ===
            'hero_title' => ['fr' => 'Ton assistant IA pour les jeux 1Win', 'en' => 'Your AI assistant for 1Win games', 'es' => 'Tu asistente IA para juegos de 1Win', 'pt' => 'Seu assistente IA para jogos da 1Win', 'ru' => 'Ваш ИИ-ассистент для игр 1Win', 'ar' => 'مساعدك الذكي لألعاب 1Win', 'tr' => '1Win oyunları için AI asistanınız', 'hi' => '1Win गेम्स के लिए आपका AI असिस्टेंट', 'uz' => '1Win o\'yinlari uchun AI yordamchingiz', 'az' => '1Win oyunları üçün AI köməkçiniz'],
            'hero_subtitle' => ['fr' => 'Reçois des conseils personnalisés, des pronostics exclusifs et accède à des bonus grâce à la puissance de l\'intelligence artificielle.', 'en' => 'Get personalized tips, exclusive predictions and unlock bonuses with the power of artificial intelligence.', 'es' => 'Recibe consejos personalizados, predicciones exclusivas y accede a bonificaciones con la IA.', 'pt' => 'Receba dicas personalizadas, previsões exclusivas e desbloqueie bônus com IA.', 'ru' => 'Получайте персональные советы, эксклюзивные прогнозы и бонусы с помощью ИИ.', 'ar' => 'احصل على نصائح مخصصة وتوقعات حصرية وأفتح المكافآت بالذكاء الاصطناعي.', 'tr' => 'AI ile kişiselleştirilmiş ipuçları, özel tahminler ve bonuslara erişin.', 'hi' => 'AI की शक्ति से व्यक्तिगत टिप्स, विशेष भविष्यवाणियां और बोनस प्राप्त करें।', 'uz' => 'AI yordamida shaxsiy maslahatlar, eksklyuziv taxminlar va bonuslarga ega bo\'ling.', 'az' => 'AI ilə fərdi məsləhətlər, eksklüziv proqnozlar və bonuslar əldə edin.'],
            'cta_button' => ['fr' => 'Commencer gratuitement', 'en' => 'Start for free', 'es' => 'Empezar gratis', 'pt' => 'Começar grátis', 'ru' => 'Начать бесплатно', 'ar' => 'ابدأ مجاناً', 'tr' => 'Ücretsiz başla', 'hi' => 'मुफ्त शुरू करें', 'uz' => 'Bepul boshlash', 'az' => 'Pulsuz başla'],
            'feature_1_title' => ['fr' => 'Chat IA intelligent', 'en' => 'Smart AI Chat', 'es' => 'Chat IA inteligente', 'pt' => 'Chat IA inteligente', 'ru' => 'Умный ИИ-чат', 'ar' => 'محادثة ذكية', 'tr' => 'Akıllı AI Sohbet', 'hi' => 'स्मार्ट AI चैट', 'uz' => 'Aqlli AI suhbat', 'az' => 'Ağıllı AI Söhbət'],
            'feature_1_desc' => ['fr' => 'Pose tes questions sur les jeux, les stratégies et les bonus. Notre IA te répond instantanément.', 'en' => 'Ask about games, strategies and bonuses. Our AI answers instantly.', 'es' => 'Pregunta sobre juegos, estrategias y bonos. Nuestra IA responde al instante.', 'pt' => 'Pergunte sobre jogos, estratégias e bônus. Nossa IA responde instantaneamente.', 'ru' => 'Спрашивайте об играх, стратегиях и бонусах. Наш ИИ отвечает мгновенно.', 'ar' => 'اسأل عن الألعاب والاستراتيجيات والمكافآت. ذكاؤنا يجيب فوراً.', 'tr' => 'Oyunlar, stratejiler ve bonuslar hakkında sorun. AI anında cevaplar.', 'hi' => 'गेम्स, रणनीतियों और बोनस के बारे में पूछें। हमारा AI तुरंत जवाब देता है।', 'uz' => "O'yinlar, strategiyalar va bonuslar haqida so'rang. AI darhol javob beradi.", 'az' => 'Oyunlar, strategiyalar və bonuslar haqqında sual verin. AI dərhal cavab verir.'],
            'feature_2_title' => ['fr' => 'Pronostics foot VIP', 'en' => 'VIP Football Predictions', 'es' => 'Pronósticos fútbol VIP', 'pt' => 'Previsões futebol VIP', 'ru' => 'VIP прогнозы на футбол', 'ar' => 'توقعات كرة القدم VIP', 'tr' => 'VIP Futbol Tahminleri', 'hi' => 'VIP फुटबॉल भविष्यवाणियां', 'uz' => 'VIP futbol taxminlari', 'az' => 'VIP Futbol Proqnozları'],
            'feature_2_desc' => ['fr' => 'Accède à des pronostics foot exclusifs chaque jour. Plus tu parraines, plus tu débloques.', 'en' => 'Access exclusive daily football predictions. The more you refer, the more you unlock.', 'es' => 'Accede a pronósticos futbol exclusivos. Cuanto más refieres, más desbloqueas.', 'pt' => 'Acesse previsões exclusivas de futebol. Quanto mais indica, mais desbloqueia.', 'ru' => 'Доступ к эксклюзивным прогнозам. Больше рефералов — больше возможностей.', 'ar' => 'احصل على توقعات كرة قدم حصرية يومياً. كلما أحلت أكثر، فتحت أكثر.', 'tr' => 'Her gün özel futbol tahminlerine erişin. Ne kadar çok davet ederseniz, o kadar çok açarsınız.', 'hi' => 'प्रतिदिन विशेष फुटबॉल भविष्यवाणियों तक पहुंचें। जितना अधिक रेफर करें, उतना अधिक अनलॉक करें।', 'uz' => 'Har kuni eksklyuziv futbol taxminlariga ega bo\'ling. Qanchalik ko\'p taklif qilsangiz, shunchalik ko\'p ochasiz.', 'az' => 'Hər gün eksklüziv futbol proqnozlarına çıxın. Nə qədər çox dəvət etsəniz, bir o qədər çox açarsınız.'],
            'feature_3_title' => ['fr' => 'Programme de parrainage', 'en' => 'Referral Program', 'es' => 'Programa de referidos', 'pt' => 'Programa de indicações', 'ru' => 'Реферальная программа', 'ar' => 'برنامج الإحالة', 'tr' => 'Davet Programı', 'hi' => 'रेफरल प्रोग्राम', 'uz' => 'Taklif dasturi', 'az' => 'Referral Proqramı'],
            'feature_3_desc' => ['fr' => 'Invite tes amis et débloque des avantages exclusifs : pronostics VIP, bonus et plus encore.', 'en' => 'Invite friends and unlock exclusive perks: VIP predictions, bonuses and more.', 'es' => 'Invita amigos y desbloquea ventajas: predicciones VIP, bonos y más.', 'pt' => 'Convide amigos e desbloqueie vantagens: previsões VIP, bônus e mais.', 'ru' => 'Приглашайте друзей и открывайте бонусы: VIP прогнозы и многое другое.', 'ar' => 'ادعُ أصدقاءك وافتح مزايا حصرية: توقعات VIP ومكافآت وأكثر.', 'tr' => 'Arkadaşlarınızı davet edin ve VIP tahminler, bonuslar ve daha fazlasını açın.', 'hi' => 'दोस्तों को इन्वाइट करें और VIP भविष्यवाणियां, बोनस और बहुत कुछ अनलॉक करें।', 'uz' => 'Do\'stlaringizni taklif qiling va VIP taxminlar, bonuslar va boshqalarni oching.', 'az' => 'Dostlarınızı dəvət edin və VIP proqnozlar, bonuslar və daha çoxunu açın.'],

            // === Dashboard ===
            'welcome' => ['fr' => 'Bienvenue', 'en' => 'Welcome', 'es' => 'Bienvenido', 'pt' => 'Bem-vindo', 'ru' => 'Добро пожаловать', 'ar' => 'مرحباً', 'tr' => 'Hoş geldin', 'hi' => 'स्वागत है', 'uz' => 'Xush kelibsiz', 'az' => 'Xoş Gəldiniz'],
            'activate_title' => ['fr' => 'Active ton compte', 'en' => 'Activate your account', 'es' => 'Activa tu cuenta', 'pt' => 'Ative sua conta', 'ru' => 'Активируйте аккаунт', 'ar' => 'فعّل حسابك', 'tr' => 'Hesabını etkinleştir', 'hi' => 'अपना खाता सक्रिय करें', 'uz' => 'Hisobingizni faollashtiring', 'az' => 'Hesabınızı aktivləşdirin'],
            'activate_desc' => ['fr' => 'Pour débloquer toutes les fonctionnalités, inscris-toi sur la plateforme partenaire en utilisant le code ci-dessous.', 'en' => 'To unlock all features, sign up on the partner platform using the code below.', 'es' => 'Para desbloquear todo, regístrate en la plataforma asociada con el código.', 'pt' => 'Para desbloquear tudo, registre-se na plataforma parceira com o código.', 'ru' => 'Чтобы разблокировать все функции, зарегистрируйтесь с кодом ниже.', 'ar' => 'لفتح جميع الميزات، سجل في المنصة الشريكة باستخدام الكود.', 'tr' => 'Tüm özellikleri açmak için aşağıdaki kodla kayıt olun.', 'hi' => 'सभी फीचर्स अनलॉक करने के लिए, नीचे दिए गए कोड से साइन अप करें।', 'uz' => 'Barcha funksiyalarni ochish uchun, quyidagi kod bilan ro\'yxatdan o\'ting.', 'az' => 'Bütün xüsusiyyətləri açmaq üçün aşağıdakı kodla qeydiyyatdan keçin.'],
            'promo_code_label' => ['fr' => 'Code promo', 'en' => 'Promo code', 'es' => 'Código promocional', 'pt' => 'Código promocional', 'ru' => 'Промокод', 'ar' => 'كود الترويجي', 'tr' => 'Promosyon kodu', 'hi' => 'प्रमो कोड', 'uz' => 'Promo kod', 'az' => 'Promo kod'],
            'go_to_partner' => ['fr' => "S'inscrire maintenant", 'en' => 'Sign up now', 'es' => 'Registrarse ahora', 'pt' => 'Registrar agora', 'ru' => 'Зарегистрироваться', 'ar' => 'سجل الآن', 'tr' => 'Şimdi kayıt ol', 'hi' => 'अभी साइन अप करें', 'uz' => 'Hozir ro\'yxatdan o\'ting', 'az' => 'İndi qeydiyyatdan keç'],
            'activated' => ['fr' => 'Compte activé !', 'en' => 'Account activated!', 'es' => 'Cuenta activada!', 'pt' => 'Conta ativada!', 'ru' => 'Аккаунт активирован!', 'ar' => 'تم تفعيل الحساب!', 'tr' => 'Hesap etkinleştirildi!', 'hi' => 'खाता सक्रिय हुआ!', 'uz' => 'Hisob faollashtirildi!', 'az' => 'Hesab aktivləşdirildi!'],
            'deposit_pending' => ['fr' => 'En attente de ton premier dépôt...', 'en' => 'Waiting for your first deposit...', 'es' => 'Esperando tu primer depósito...', 'pt' => 'Aguardando seu primeiro depósito...', 'ru' => 'Ожидаем ваш первый депозит...', 'ar' => 'في انتظار إيداعك الأول...', 'tr' => 'İlk depozitonuzu bekliyoruz...', 'hi' => 'आपके पहले डिपॉजिट का इंतज़ार है...', 'uz' => 'Birinchi depozitingizni kutamoz...', 'az' => 'İlk depozitiniz gözlənilir...'],

            // === Parrainage ===
            'your_referral_link' => ['fr' => 'Ton lien de parrainage', 'en' => 'Your referral link', 'es' => 'Tu enlace de referido', 'pt' => 'Seu link de indicação', 'ru' => 'Ваша реферальная ссылка', 'ar' => 'رابط الإحالة الخاص بك', 'tr' => 'Davet linkiniz', 'hi' => 'आपका रेफरल लिंक', 'uz' => 'Sizning taklif havolangangiz', 'az' => 'Sizin referral linkiniz'],
            'referral_code' => ['fr' => 'Code de parrainage', 'en' => 'Referral code', 'es' => 'Código de referido', 'pt' => 'Código de indicação', 'ru' => 'Реферальный код', 'ar' => 'كود الإحالة', 'tr' => 'Davet kodu', 'hi' => 'रेफरल कोड', 'uz' => 'Taklif kodi', 'az' => 'Referral kodu'],
            'share' => ['fr' => 'Partager', 'en' => 'Share', 'es' => 'Compartir', 'pt' => 'Compartilhar', 'ru' => 'Поделиться', 'ar' => 'مشاركة', 'tr' => 'Paylaş', 'hi' => 'शेयर करें', 'uz' => 'Ulashish', 'az' => 'Paylaş'],
            'referrals_count' => ['fr' => 'Filleuls', 'en' => 'Referrals', 'es' => 'Referidos', 'pt' => 'Indicações', 'ru' => 'Рефералы', 'ar' => 'الإحالات', 'tr' => 'Davetler', 'hi' => 'रेफरल', 'uz' => 'Takliflar', 'az' => 'Referral'],
            'next_reward' => ['fr' => 'Prochaine récompense', 'en' => 'Next reward', 'es' => 'Próxima recompensa', 'pt' => 'Próxima recompensa', 'ru' => 'Следующая награда', 'ar' => 'المكافأة التالية', 'tr' => 'Sonraki ödül', 'hi' => 'अगला इनाम', 'uz' => 'Keyingi mukofot', 'az' => 'Növbəti mükafat'],
            'copy_link' => ['fr' => 'Copier le lien', 'en' => 'Copy link', 'es' => 'Copiar enlace', 'pt' => 'Copiar link', 'ru' => 'Копировать ссылку', 'ar' => 'نسخ الرابط', 'tr' => 'Linki kopyala', 'hi' => 'लिंक कॉपी करें', 'uz' => 'Havolani nusxalash', 'az' => 'Linki kopyala'],

            // === Chat ===
            'chat_placeholder' => ['fr' => 'Pose ta question...', 'en' => 'Ask your question...', 'es' => 'Haz tu pregunta...', 'pt' => 'Faça sua pergunta...', 'ru' => 'Задайте вопрос...', 'ar' => 'اطرح سؤالك...', 'tr' => 'Sorunuzu sorun...', 'hi' => 'अपना सवाल पूछें...', 'uz' => 'Savolingizni bering...', 'az' => 'Sualınızı verin...'],
            'chat_welcome' => ['fr' => 'Salut ! Je suis DVYS AI, ton assistant casino. Comment puis-je t\'aider aujourd\'hui ?', 'en' => 'Hi! I\'m DVYS AI, your casino assistant. How can I help you today?', 'es' => '¡Hola! Soy DVYS AI, tu asistente casino. ¿Cómo puedo ayudarte?', 'pt' => 'Oi! Sou DVYS AI, seu assistente cassino. Como posso ajudar?', 'ru' => 'Привет! Я DVYS AI, ваш казино-ассистент. Чем помочь?', 'ar' => 'مرحباً! أنا DVYS AI، مساعدك الكازينو. كيف أساعدك؟', 'tr' => 'Merhaba! Ben DVYS AI, kumarhane asistanınız. Size nasıl yardımcı olabilirim?', 'hi' => 'नमस्ते! मैं DVYS AI हूं, आपका कैसीनो असिस्टेंट। मैं आपकी कैसे मदद कर सकता हूं?', 'uz' => 'Salom! Men DVYS AI, sizning kazino yordamchingiz. Sizga qanday yordam berishim mumkin?', 'az' => 'Salam! Mən DVYS AI, sizin kazino köməkçiniz. Sizə necə kömək edə bilərəm?'],

            // === Pronostics ===
            'todays_predictions' => ['fr' => 'Pronostics du jour', 'en' => "Today's predictions", 'es' => 'Pronósticos de hoy', 'pt' => 'Previsões de hoje', 'ru' => 'Прогнозы на сегодня', 'ar' => 'توقعات اليوم', 'tr' => 'Bugünün tahminleri', 'hi' => 'आज की भविष्यवाणियां', 'uz' => 'Bugungi taxminlar', 'az' => 'Bugünün proqnozları'],
            'no_predictions' => ['fr' => 'Aucun pronostic disponible pour le moment.', 'en' => 'No predictions available at the moment.', 'es' => 'No hay pronósticos disponibles.', 'pt' => 'Sem previsões disponíveis.', 'ru' => 'Прогнозов пока нет.', 'ar' => 'لا توقعات متاحة حالياً.', 'tr' => 'Şu anda tahmin yok.', 'hi' => 'अभी कोई भविष्यवाणी उपलब्ध नहीं।', 'uz' => 'Hozircha taxminlar yo\'q.', 'az' => 'Hazırda proqnoz yoxdur.'],
            'locked' => ['fr' => 'Verrouillé', 'en' => 'Locked', 'es' => 'Bloqueado', 'pt' => 'Bloqueado', 'ru' => 'Заблокировано', 'ar' => 'مقفل', 'tr' => 'Kilitli', 'hi' => 'लॉक', 'uz' => 'Qulflangan', 'az' => 'Kilitli'],
            'unlock_with_referrals' => ['fr' => 'Invite des amis pour débloquer les pronostics VIP', 'en' => 'Invite friends to unlock VIP predictions', 'es' => 'Invita amigos para desbloquear predicciones VIP', 'pt' => 'Convide amigos para desbloquear previsões VIP', 'ru' => 'Пригласите друзей для VIP прогнозов', 'ar' => 'ادعُ أصدقاء لفتح التوقعات VIP', 'tr' => 'VIP tahminleri açmak için arkadaş davet edin', 'hi' => 'VIP भविष्यवाणियां अनलॉक करने के लिए दोस्तों को इन्वाइट करें', 'uz' => 'VIP taxminlarni ochish uchun do\'stlaringizni taklif qiling', 'az' => 'VIP proqnozları açmaq üçün dostlarınızı dəvət edin'],
            'odds' => ['fr' => 'Cote', 'en' => 'Odds', 'es' => 'Cuota', 'pt' => 'Odd', 'ru' => 'Коэф.', 'ar' => 'الاحتمال', 'tr' => 'Oran', 'hi' => 'ऑड्स', 'uz' => 'Koeffitsient', 'az' => 'Əmsal'],
            'vs' => ['fr' => 'vs', 'en' => 'vs', 'es' => 'vs', 'pt' => 'vs', 'ru' => 'vs', 'ar' => 'ضد', 'tr' => 'vs', 'hi' => 'बनाम', 'uz' => 'vs', 'az' => 'vs'],

            // === Admin ===
            'total_users' => ['fr' => 'Total utilisateurs', 'en' => 'Total users', 'es' => 'Total usuarios', 'pt' => 'Total usuários', 'ru' => 'Всего пользователей', 'ar' => 'إجمالي المستخدمين', 'tr' => 'Toplam kullanıcı', 'hi' => 'कुल उपयोगकर्ता', 'uz' => 'Jami foydalanuvchilar', 'az' => 'Ümumi istifadəçilər'],
            'verified_users' => ['fr' => 'Utilisateurs vérifiés', 'en' => 'Verified users', 'es' => 'Usuarios verificados', 'pt' => 'Usuários verificados', 'ru' => 'Верифицированные', 'ar' => 'المستخدمون المفعلون', 'tr' => 'Onaylı kullanıcılar', 'hi' => 'सत्यापित उपयोगकर्ता', 'uz' => 'Tasdiqlangan foydalanuvchilar', 'az' => 'Təsdiqlənmiş istifadəçilər'],
            'total_deposits' => ['fr' => 'Total dépôts', 'en' => 'Total deposits', 'es' => 'Total depósitos', 'pt' => 'Total depósitos', 'ru' => 'Всего депозитов', 'ar' => 'إجمالي الإيداعات', 'tr' => 'Toplam depozitlar', 'hi' => 'कुल डिपॉजिट', 'uz' => 'Jami depozitlar', 'az' => 'Ümumi depozitlər'],
            'active_today' => ['fr' => 'Actifs aujourd\'hui', 'en' => 'Active today', 'es' => 'Activos hoy', 'pt' => 'Ativos hoje', 'ru' => 'Активны сегодня', 'ar' => 'نشطون اليوم', 'tr' => 'Bugün aktif', 'hi' => 'आज सक्रिय', 'uz' => 'Bugun faol', 'az' => 'Bugün aktiv'],
            'broadcast_title' => ['fr' => 'Envoyer un message', 'en' => 'Send a message', 'es' => 'Enviar mensaje', 'pt' => 'Enviar mensagem', 'ru' => 'Отправить сообщение', 'ar' => 'إرسال رسالة', 'tr' => 'Mesaj gönder', 'hi' => 'मैसेज भेजें', 'uz' => 'Xabar yuborish', 'az' => 'Mesaj göndər'],

            // === Erreurs ===
            'error_occurred' => ['fr' => 'Une erreur est survenue', 'en' => 'An error occurred', 'es' => 'Ocurrió un error', 'pt' => 'Ocorreu um erro', 'ru' => 'Произошла ошибка', 'ar' => 'حدث خطأ', 'tr' => 'Bir hata oluştu', 'hi' => 'एक त्रुटि हुई', 'uz' => 'Xato yuz berdi', 'az' => 'Xəta baş verdi'],
            'login_failed' => ['fr' => 'Identifiants incorrects', 'en' => 'Invalid credentials', 'es' => 'Credenciales incorrectas', 'pt' => 'Credenciais incorretas', 'ru' => 'Неверные данные', 'ar' => 'بيانات غير صحيحة', 'tr' => 'Geçersiz bilgiler', 'hi' => 'गलत क्रेडेंशियल', 'uz' => 'Noto\'g\'ri ma\'lumotlar', 'az' => 'Yanlış məlumatlar'],
            'register_failed' => ['fr' => "L'inscription a échoué", 'en' => 'Registration failed', 'es' => 'El registro falló', 'pt' => 'O registro falhou', 'ru' => 'Регистрация не удалась', 'ar' => 'فشل التسجيل', 'tr' => 'Kayıt başarısız', 'hi' => 'रजिस्ट्रेशन विफल', 'uz' => "Ro'yxatdan o'tish muvaffaqiyatsiz", 'az' => 'Qeydiyyat uğursuz oldu'],
            'must_login' => ['fr' => 'Tu dois te connecter', 'en' => 'You must login', 'es' => 'Debes iniciar sesión', 'pt' => 'Você deve entrar', 'ru' => 'Войдите в аккаунт', 'ar' => 'يجب تسجيل الدخول', 'tr' => 'Giriş yapmalısınız', 'hi' => 'आपको लॉगिन करना होगा', 'uz' => 'Tizimga kirishingiz kerak', 'az' => 'Daxil olmalısınız'],

            // === VIP ===
            'vip_badge' => ['fr' => 'VIP', 'en' => 'VIP', 'es' => 'VIP', 'pt' => 'VIP', 'ru' => 'VIP', 'ar' => 'VIP', 'tr' => 'VIP', 'hi' => 'VIP', 'uz' => 'VIP', 'az' => 'VIP'],
            'vip_active' => ['fr' => 'VIP Actif', 'en' => 'VIP Active', 'es' => 'VIP Activo', 'pt' => 'VIP Ativo', 'ru' => 'VIP активен', 'ar' => 'VIP نشط', 'tr' => 'VIP Aktif', 'hi' => 'VIP सक्रिय', 'uz' => 'VIP Faol', 'az' => 'VIP Aktiv'],
            'vip_expires' => ['fr' => 'Expire le', 'en' => 'Expires on', 'es' => 'Expira el', 'pt' => 'Expira em', 'ru' => 'Истекает', 'ar' => 'ينتهي في', 'tr' => 'Sona erme', 'hi' => 'समाप्ति', 'uz' => 'Tugash sanasi', 'az' => 'Bitmə tarixi'],

            // === Referral tiers ===
            'tier_1' => ['fr' => '3 filleuls → 7 jours VIP', 'en' => '3 referrals → 7 days VIP', 'es' => '3 referidos → 7 días VIP', 'pt' => '3 indicações → 7 dias VIP', 'ru' => '3 реферала → 7 дней VIP', 'ar' => '3 إحالات → 7 أيام VIP', 'tr' => '3 davet → 7 gün VIP', 'hi' => '3 रेफरल → 7 दिन VIP', 'uz' => '3 taklif → 7 kun VIP', 'az' => '3 referral → 7 gün VIP'],
            'tier_2' => ['fr' => '15 filleuls → 30 jours VIP', 'en' => '15 referrals → 30 days VIP', 'es' => '15 referidos → 30 días VIP', 'pt' => '15 indicações → 30 dias VIP', 'ru' => '15 рефералов → 30 дней VIP', 'ar' => '15 إحالة → 30 يوم VIP', 'tr' => '15 davet → 30 gün VIP', 'hi' => '15 रेफरल → 30 दिन VIP', 'uz' => '15 taklif → 30 kun VIP', 'az' => '15 referral → 30 gün VIP'],
            'tier_3' => ['fr' => '30 filleuls → VIP Illimité', 'en' => '30 referrals → Unlimited VIP', 'es' => '15 referidos → VIP Ilimitado', 'pt' => '30 indicações → VIP Ilimitado', 'ru' => '30 рефералов → Безлимит VIP', 'ar' => '30 إحالة → VIP بلا حدود', 'tr' => '30 davet → Sınırsız VIP', 'hi' => '30 रेफरल → अनलिमिटेड VIP', 'uz' => '30 taklif → Cheksiz VIP', 'az' => '30 referral → Limitsiz VIP'],
        ];
    }

    if (empty($lang)) {
        $lang = $_SESSION['lang'] ?? $_COOKIE['dvys_lang'] ?? DEFAULT_LANGUAGE;
        if (!in_array($lang, SUPPORTED_LANGUAGES)) {
            $lang = DEFAULT_LANGUAGE;
        }
    }

    return $translations[$key][$lang] ?? $translations[$key]['en'] ?? $key;
}

/**
 * Obtenir la langue actuelle de l'utilisateur
 */
function getCurrentLang(): string {
    $lang = $_SESSION['lang'] ?? $_COOKIE['dvys_lang'] ?? DEFAULT_LANGUAGE;
    return in_array($lang, SUPPORTED_LANGUAGES) ? $lang : DEFAULT_LANGUAGE;
}

/**
 * Définir la langue
 */
function setLang(string $lang): void {
    if (in_array($lang, SUPPORTED_LANGUAGES)) {
        $_SESSION['lang'] = $lang;
        setcookie('dvys_lang', $lang, [
            'expires' => time() + 86400 * 365,
            'path' => '/',
            'secure' => defined('COOKIE_SECURE') ? COOKIE_SECURE : false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

/**
 * Direction du texte (RTL pour l'arabe)
 */
function isRTL(string $lang = ''): bool {
    return ($lang ?: getCurrentLang()) === 'ar';
}
