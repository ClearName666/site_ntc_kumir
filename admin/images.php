<?php
require_once __DIR__. '/includes/functions.php';
$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

// ОБРАБОТКА ЗАГРУЗКИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id'])) {
    $imageId = (int)$_POST['image_id'];
    
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['new_image']);
        
        if ($uploadResult['success']) {
            updateImageInTable($conn, $imageId, $uploadResult['path']);
            logAdminAction($conn, 'image_update', "Обновлено изображение ID: $imageId");
            redirectWithNotification('images.php', 'Изображение обновлено', 'success');
        } else {
            redirectWithNotification('images.php', 'Ошибка загрузки: ' . $uploadResult['error'], 'error');
        }
    }
}

$allImages = getAllImagesFromDB($conn);

require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
            <h1 class="header-title">Управление медиа-ресурсами</h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>

    <div class="content-container">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-images"></i> Системные изображения и фоны</h3>
                <p class="text-muted">Здесь можно заменить основные изображения сайта (фоны, логотипы, заставки)</p>
            </div>
            <div class="card-body">
                <div class="images-management-grid">
                    <?php foreach ($allImages as $img): ?>
                        <div class="image-manage-item card">
                            <div class="image-preview-box">
                                <?php 
                                    $ext = pathinfo($img['image_path'], PATHINFO_EXTENSION);
                                    if ($ext === 'svg'): 
                                ?>
                                    <img src="../<?php echo $img['image_path']; ?>" alt="Preview" style="width: 100px;">
                                <?php else: ?>
                                    <div class="img-bg-preview" style="background-image: url('../<?php echo $img['image_path']; ?>')"></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="image-info">
                                <strong><?php echo htmlspecialchars($img['alt_text']); ?></strong>
                                <code class="d-block small text-muted"><?php echo $img['image_key']; ?></code>
                                <span class="badge badge-info"><?php echo $img['category']; ?></span>
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                <div class="custom-file-upload">
                                    <label class="btn btn-sm btn-outline-primary btn-block">
                                        <i class="fas fa-upload"></i> Заменить
                                        <input type="file" name="new_image" onchange="this.form.submit()" style="display:none;" accept="image/*">
                                    </label>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.images-management-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.image-manage-item {
    padding: 15px;
    border: 1px solid #eee;
    text-align: center;
}
.image-preview-box {
    height: 150px;
    background: #f4f4f4;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.img-bg-preview {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
}
.image-info {
    margin-bottom: 10px;
}
.badge-info {
    background: #e1f5fe;
    color: #0288d1;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
}
</style>

<?php 
require_once __DIR__. '/includes/scripts.php';
require_once __DIR__. '/includes/footer.php'; 
?>