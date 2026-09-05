<?php
require_once __DIR__ . '/_bootstrap.php';
require_admin();
function admin_header(string $title, string $active = ''): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= clean($title) ?> | GreensPk Admin</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet"><link rel="stylesheet" href="admin.css"></head><body class="admin-page"><aside class="sidebar"><a class="admin-brand" href="dashboard.php">🥬 Green<span>Pk</span></a><p class="side-label">MARKET MANAGEMENT</p><nav><a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">▦ <span>Dashboard</span></a><a class="<?= $active === 'products' ? 'active' : '' ?>" href="products.php">♧ <span>Products</span></a><a class="disabled" href="#">◷ <span>Orders <em>Soon</em></span></a><a class="disabled" href="#">◎ <span>Customers <em>Soon</em></span></a></nav><div class="sidebar-foot"><span>Signed in as<br><b><?= clean($_SESSION['admin_name'] ?? 'Admin') ?></b></span><a href="logout.php">Sign out</a></div></aside><main class="admin-main"><header class="admin-header"><div><p class="kicker">GREENSPK / ADMIN</p><h1><?= clean($title) ?></h1>
</div>
<a class="view-store" href="../index.php" target="_blank">View storefront ↗
</a>
</header>
<?php }
function admin_footer(): void { echo '</main></body></html>'; }
