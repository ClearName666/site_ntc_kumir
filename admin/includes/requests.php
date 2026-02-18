<?php
// Этот файл содержит только CSS код
?>

<style>
    .admin-container { padding: 20px; font-family: sans-serif; }
    .requests-table-container { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .admin-table th, .admin-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
    .admin-table th { background: #f8f9fa; color: #333; font-weight: 600; }

    .contact-link { color: #3498db; text-decoration: none; font-weight: bold; }
    .message-bubble { font-size: 0.9rem; color: #666; max-width: 250px; white-space: normal; }

    .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
    .badge-new { background: #e74c3c; color: white; }
    .badge-processed { background: #2ecc71; color: white; }

    tr.status-new { background-color: #fff9f9; }

    @media (max-width: 768px) {
        .admin-table th, .admin-table td { padding: 10px; font-size: 0.85rem; }
    }
</style>
