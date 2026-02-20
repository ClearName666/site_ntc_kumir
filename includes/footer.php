<?php 
// подключаемся к базе 
$conn = getDBConnection();
?>

<footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Быстрые ссылки</h3>
                    <ul class="footer-links">
                        <?php
                        $footerLinks = explode("\n", getContentBlock($conn, 'footer_links')['content']);
                        foreach ($footerLinks as $link) {
                            if (trim($link)) {
                                echo '<li><a href="#">' . trim($link) . '</a></li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Контакты</h3>
                    <div class="contact-info">
                        <p><?php echo getSetting($conn, 'company_address'); ?></p>
                        <p>Телефон: <?php echo getSetting($conn, 'phone'); ?></p>
                        <p>Email: <?php echo getSetting($conn, 'company_email'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p><?php echo getSetting($conn, 'copyright_text'); ?> | <?php echo getSetting($conn, 'developer_text'); ?></p>
            </div>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>