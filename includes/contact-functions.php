<?php
require_once __DIR__ . '/../config/database.php';

function getContactsByType($conn, $type = null) {
    global $cache;
    $cacheKey = "contacts_type_" . ($type ?? 'all');

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    if ($type) {
        $stmt = $conn->prepare("SELECT * FROM contacts WHERE contact_type = ? AND is_active = 1 ORDER BY sort_order");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $data = $conn->query("SELECT * FROM contacts WHERE is_active = 1 ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC);
    }
    
    $cache->set($cacheKey, $data);
    return $data;
}

function getOffices($conn) {
    global $cache;
    $cacheKey = "offices_all";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM offices ORDER BY is_main DESC, sort_order");
    $offices = [];
    
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
    
    $cache->set($cacheKey, $offices);
    return $offices;
}

function getMainOffice($conn) {
    global $cache;
    $cacheKey = "office_main";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM offices WHERE is_main = 1 LIMIT 1");
    $data = $result->fetch_assoc();
    
    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}



function getContactIcon($icon) {
    $icons = [
        'location' => '📍',
        'email' => '✉️',
        'support' => '🛠️',
        'accounting' => '💰',
        'company' => '🏢',
        'phone' => '📞',
        'phone-free' => '📞🎯',
        'mobile' => '📱'
    ];
    
    return $icons[$icon] ?? '📌';
}

function renderContacts($contacts) {
    echo '<div class="contacts-list">';
    
    foreach ($contacts as $contact) {
        $icon = getContactIcon($contact['icon']);
        $value = htmlspecialchars($contact['value']);
        
        echo '<div class="contact-item" onclick="copyToClipboard(\'' . htmlspecialchars($contact['value'], ENT_QUOTES) . '\', this)">';
        echo '<div class="contact-icon">' . $icon . '</div>';
        echo '<div class="contact-content">';
        echo '<h4 class="contact-title">' . htmlspecialchars($contact['title']) . '</h4>';
        
        if ($contact['contact_type'] === 'email') {
            echo '<a href="mailto:' . htmlspecialchars($contact['value']) . '" class="contact-value" onclick="event.stopPropagation();">';
            echo $value;
            echo '</a>';
        } elseif ($contact['contact_type'] === 'phone') {
            $phoneNumber = preg_replace('/[^0-9+]/', '', $contact['value']);
            echo '<a href="tel:' . $phoneNumber . '" class="contact-value" onclick="event.stopPropagation();">';
            echo $value;
            echo '</a>';
        } else {
            echo '<p class="contact-value">' . $value . '</p>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
}

function renderOffices($offices) {
    echo '<div class="offices-grid">';
    
    foreach ($offices as $office) {
        $isMain = $office['is_main'] ? ' main-office' : '';
        
        echo '<div class="office-card' . $isMain . '">';
        echo '<div class="office-header">';
        echo '<h3 class="office-city">' . htmlspecialchars($office['city']) . '</h3>';
        if ($office['is_main']) {
            echo '<span class="office-badge">Главный офис</span>';
        }
        echo '</div>';
        
        echo '<div class="office-content">';
        echo '<p class="office-address">📍 ' . htmlspecialchars($office['address']) . '</p>';
        
        if (!empty($office['phone'])) {
            $phoneNumber = preg_replace('/[^0-9+]/', '', $office['phone']);
            echo '<p class="office-phone">📞 <a href="tel:' . $phoneNumber . '">' . htmlspecialchars($office['phone']) . '</a></p>';
        }
        
        if (!empty($office['email'])) {
            echo '<p class="office-email">✉️ <a href="mailto:' . htmlspecialchars($office['email']) . '">' . htmlspecialchars($office['email']) . '</a></p>';
        }
        
        if (!empty($office['work_hours'])) {
            echo '<p class="office-hours">🕐 ' . htmlspecialchars($office['work_hours']) . '</p>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
}

function renderFAQ($faqItems, $collapsible = true) {
    echo '<div class="faq-list">';
    
    foreach ($faqItems as $index => $item) {
        $itemId = 'faq-item-' . $index;
        
        echo '<div class="faq-item">';
        
        if ($collapsible) {
            echo '<input type="checkbox" id="' . $itemId . '" class="faq-toggle">';
            echo '<label for="' . $itemId . '" class="faq-question">';
            echo '<span class="faq-icon">❓</span>';
            echo htmlspecialchars($item['question']);
            echo '<span class="faq-arrow">▼</span>';
            echo '</label>';
            
            echo '<div class="faq-answer">';
            echo '<div class="faq-answer-content">';
            echo $item['answer'];
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="faq-question-static">';
            echo '<span class="faq-icon">❓</span>';
            echo '<strong>' . htmlspecialchars($item['question']) . '</strong>';
            echo '</div>';
            
            echo '<div class="faq-answer-static">';
            echo $item['answer'];
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
}

function saveFeedback($conn, $name, $email, $phone, $subject, $message) {
    
    $stmt = $conn->prepare("INSERT INTO feedback (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}
?>