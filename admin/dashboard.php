<?php
require_once __DIR__ . '/_layout.php';
$items = products();
$active = count(array_filter($items, fn($p) => !empty($p['in_stock'])));
admin_header('Good morning, Admin', 'dashboard');
?>
<section class="welcome"><div><p>Here’s what is fresh in your market today.</p><a class="primary" href="products.php?action=new">+ Add a product</a></div><div class="date-card">📅 <span><?= date('l') ?><b><?= date('j F Y') ?></b></span></div></section>
<section class="stats"><article><span class="stat-icon green">♧</span><div><b><?= count($items) ?></b><small>Total products</small></div></article><article><span class="stat-icon lime">✓</span><div><b><?= $active ?></b><small>Live on store</small></div></article><article><span class="stat-icon orange">◷</span><div><b>0</b><small>Orders today</small></div></article></section>
<section class="panel"><div class="panel-head"><div><h2>Product catalogue</h2><p>Your most recently added items.</p></div><a href="products.php">Manage products →</a></div><?php if (!$items): ?><div class="blank-state"><span>🥕</span><h3>Your market is empty</h3><p>Add your first vegetable to get started.</p><a class="primary" href="products.php?action=new">Add first product</a></div><?php else: ?><div class="mini-products"><?php foreach (array_slice(array_reverse($items), 0, 5) as $p): ?><div><span class="product-emoji"><?= !empty($p['img']) ? '<img src="' . clean($p['img']) . '" alt="">' : '🥬' ?></span><b><?= clean($p['name']) ?></b><small><?= clean((string)$p['qty']) ?> <?= clean($p['uom']) ?> · Rs. <?= number_format((float)$p['greenspk_price']) ?></small><em class="status <?= !empty($p['in_stock']) ? 'live' : 'hidden' ?>"><?= !empty($p['in_stock']) ? 'In stock' : 'Out of stock' ?></em></div><?php endforeach; ?></div><?php endif; ?></section>
<?php admin_footer(); ?>
