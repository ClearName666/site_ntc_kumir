<?php
// Этот файл содержит только CSS код
?>

<style>
.profile-avatar .avatar-large {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #3498db, #2c3e50);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: 600;
    margin: 0 auto;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-item i {
    width: 20px;
    margin-right: 10px;
    color: #3498db;
}

.stats-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 15px;
    text-align: center;
}

.stat-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}
</style>