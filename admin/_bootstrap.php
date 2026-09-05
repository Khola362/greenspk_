<?php
session_start();

const ADMIN_EMAIL = 'admin@gmail.pk';
const ADMIN_PASSWORD = 'admin123'; // Change this before launching publicly.
const PRODUCTS_FILE = __DIR__ . '/../products.json';

function is_admin(): bool { return !empty($_SESSION['admin_logged_in']); }
function require_admin(): void {
    if (!is_admin()) { header('Location: login.php'); exit; }
}
function products(): array {
    if (!file_exists(PRODUCTS_FILE)) return [];
    $items = json_decode(file_get_contents(PRODUCTS_FILE), true);
    return is_array($items) ? $items : [];
}
function save_products(array $items): bool {
    return file_put_contents(PRODUCTS_FILE, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}
function clean(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
