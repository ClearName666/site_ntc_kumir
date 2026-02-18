<?php

// Подключаем функции
require_once 'includes/functions.php';

// --- БЛОК ОБРАБОТКИ ФОРМЫ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_feedback') {
    header('Content-Type: application/json');
    
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Пожалуйста, заполните обязательные поля.']);
        exit;
    }

    // Используем функцию saveFeedback, которую ты добавил в includes/functions.php
    if (saveFeedback($name, $email, $phone, $subject, $message)) {
        echo json_encode(['status' => 'success', 'message' => 'Сообщение успешно отправлено!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка при сохранении в базу данных.']);
    }
    exit; 
}


// Получаем данные
$contacts = getContactsByType();
$offices = getOffices();
$mainOffice = getMainOffice();

// Устанавливаем мета-данные
$pageTitle = 'Контакты - ' . getSetting('site_title');
$pageDescription = 'Контактная информация компании НТЦ КУМИР. Адреса, телефоны, email для связи.';

// Определяем пути
$headerPath = 'includes/header.php';
$footerPath = 'includes/footer.php';

// Координаты для карты (основной офис)
$mapLat = $mainOffice ? $mainOffice['latitude'] : 52.275444;
$mapLng = $mainOffice ? $mainOffice['longitude'] : 104.278817;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $pageDescription ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $pageDescription ?>">
    <meta property="og:image" content="<?= getSetting('logo_path') ?>">
    <meta property="og:type" content="website">
    
    <!-- Yandex Maps API -->
    <script src="https://api-maps.yandex.ru/2.1/?apikey=942369a1-f9ea-437f-a44a-460ac101ca32&lang=ru_RU" type="text/javascript"></script>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= getSetting('favicon_path') ?>" type="image/x-icon">
    
    <!-- Стили -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/contacts.css">

</head>
<body>
    <!-- Header -->
    <?php include $headerPath; ?>
    
    <section class="contacts-page">
        <div class="container">
            <!-- Шапка страницы -->
            <header class="page-header">
                <h1 class="page-title">Контакты</h1>
                <p class="page-description">Свяжитесь с нами любым удобным способом. Мы всегда рады помочь!</p>
            </header>
            
            <!-- Контактная информация -->
            <section class="contacts-section">
                <h2 class="section-title">Контактная информация</h2>
                <div class="contacts-grid">
                    <?php renderContacts($contacts); ?>
                </div>
            </section>
            
            <!-- Карта -->
            <section class="map-section">
                <h2 class="section-title">Мы на карте</h2>
                <div class="map-container">
                    <div id="yandex-map"></div>
                    <div class="map-overlay">
                        <div class="map-info-card">
                            <h3 class="map-info-title"><?= $mainOffice ? htmlspecialchars($mainOffice['city']) : 'Иркутск' ?></h3>
                            <p class="map-address">
                                <?= $mainOffice ? htmlspecialchars($mainOffice['address']) : 'г. Иркутск, Университетский микрорайон, 114/1' ?>
                            </p>
                            <div class="map-actions">
                                <a href="https://yandex.ru/maps/?text=<?= urlencode($mainOffice ? $mainOffice['address'] : 'Иркутск Университетский микрорайон 114/1') ?>" 
                                   class="map-btn" target="_blank">
                                    Построить маршрут
                                </a>
                                <button onclick="copyAddress()" class="map-btn">
                                    Скопировать адрес
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Офисы -->
            <section class="offices-section">
                <h2 class="section-title">Наши офисы</h2>
                <div class="offices-grid">
                    <?php renderOffices($offices); ?>
                </div>
            </section>
            
            <!-- Форма обратной связи -->
            <section class="contact-form-section">
                <h2 class="form-title">Обратная связь</h2>
                <form class="contact-form" id="contactForm">
                    
                    <input type="hidden" name="action" value="send_feedback">

                    <div class="form-group">
                        <label class="form-label" for="name">Ваше имя *</label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="subject">Тема обращения</label>
                        <select id="subject" name="subject" class="form-input">
                            <option value="">Выберите тему</option>
                            <option value="general">Общий вопрос</option>
                            <option value="technical">Техническая поддержка</option>
                            <option value="commercial">Коммерческое предложение</option>
                            <option value="partnership">Сотрудничество</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="message">Сообщение *</label>
                        <textarea id="message" name="message" class="form-textarea" required></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Отправить сообщение</button>
                </form>
            </section>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include $footerPath; ?>
    
    <!-- Скрипты -->
    <script src="assets/js/main.js"></script>
    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('.submit-btn');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Отправка...';
            submitBtn.disabled = true;
            
            const formData = new FormData(form);
            
            // Отправляем запрос на ЭТОТ ЖЕ файл (contacts.php)
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    form.reset();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });



        // Инициализация Яндекс Карты
        ymaps.ready(initMap);
        
        function initMap() {
            const map = new ymaps.Map('yandex-map', {
                center: [<?= $mapLat ?>, <?= $mapLng ?>],
                zoom: 16,
                controls: ['zoomControl', 'fullscreenControl']
            });
            
            // Добавляем метку
            const placemark = new ymaps.Placemark([<?= $mapLat ?>, <?= $mapLng ?>], {
                balloonContent: '<?= addslashes($mainOffice ? $mainOffice['address'] : 'НТЦ КУМИР') ?>',
                hintContent: 'НТЦ КУМИР'
            }, {
                preset: 'islands#darkGreenDotIconWithCaption'
            });
            
            map.geoObjects.add(placemark);
            
            // Добавляем остальные офисы
            <?php foreach ($offices as $office): ?>
                <?php if (!$office['is_main'] && !empty($office['latitude']) && !empty($office['longitude'])): ?>
                    const officePlacemark = new ymaps.Placemark(
                        [<?= $office['latitude'] ?>, <?= $office['longitude'] ?>],
                        {
                            balloonContent: '<?= addslashes($office['city'] . ', ' . $office['address']) ?>',
                            hintContent: 'Офис в <?= addslashes($office['city']) ?>'
                        },
                        {
                            preset: 'islands#blueDotIconWithCaption'
                        }
                    );
                    map.geoObjects.add(officePlacemark);
                <?php endif; ?>
            <?php endforeach; ?>
            
            // Открываем балун главной метки
            placemark.balloon.open();
        }
        
        // Копирование адреса
        function copyAddress() {
            const address = '<?= addslashes($mainOffice ? $mainOffice['address'] : 'г. Иркутск, Университетский микрорайон, 114/1') ?>';
            
            navigator.clipboard.writeText(address).then(() => {
                alert('Адрес скопирован в буфер обмена: ' + address);
            }).catch(err => {
                console.error('Ошибка копирования: ', err);
            });
        }
        
        // Обработка формы
        // document.getElementById('contactForm').addEventListener('submit', function(e) {
        //     e.preventDefault();
            
        //     const formData = new FormData(this);
        //     const submitBtn = this.querySelector('.submit-btn');
        //     const originalText = submitBtn.textContent;
            
        //     submitBtn.textContent = 'Отправка...';
        //     submitBtn.disabled = true;
            
        //     // Здесь должна быть AJAX отправка формы
        //     // Для примера просто покажем сообщение
        //     setTimeout(() => {
        //         alert('Сообщение отправлено! Мы свяжемся с вами в ближайшее время.');
        //         this.reset();
        //         submitBtn.textContent = originalText;
        //         submitBtn.disabled = false;
        //     }, 1000);
        // });
        
        // Анимация появления элементов при скролле
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            // Наблюдаем за секциями
            document.querySelectorAll('.contacts-section, .map-section, .offices-section').forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                section.style.transition = 'opacity 0.6s, transform 0.6s';
                observer.observe(section);
            });
            
            // Анимация для карточек контактов
            document.querySelectorAll('.contact-item').forEach((item, index) => {
                item.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>