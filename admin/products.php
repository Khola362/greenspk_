<?php
require_once __DIR__ . '/_layout.php';

$items = products();
$message = '';
$editing = null;


function upload_product_image($file, $oldImage = '')
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldImage;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $oldImage;
    }

    // Maximum file size: 5 MB
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Image size must be less than 5 MB.');
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mime])) {
        throw new Exception('Invalid image format. Please upload JPG, PNG, GIF, or WebP.');
    }

    $extension = $allowedTypes[$mime];

    // Upload directory
    $uploadDir = __DIR__ . '/uploads/products/';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Unable to create image upload directory.');
        }
    }

    // Generate unique filename
    $filename = 'product_' . bin2hex(random_bytes(8)) . '.' . $extension;

    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to upload image.');
    }

    // Delete old image if it belongs to our uploads directory
    if (!empty($oldImage)) {
        $oldFile = __DIR__ . '/' . ltrim($oldImage, '/');

        if (
            file_exists($oldFile) &&
            strpos(realpath($oldFile) ?: '', realpath($uploadDir) ?: '') === 0
        ) {
            @unlink($oldFile);
        }
    }

    return 'uploads/products/' . $filename;
}


/*
|--------------------------------------------------------------------------
| POST Actions
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Save Product
    |--------------------------------------------------------------------------
    */
    if ($action === 'save') {

        $id = trim($_POST['id'] ?? '');

        if ($id === '') {
            $id = 'veg-' . bin2hex(random_bytes(4));
        }

        /*
        | Find existing product
        */
        $existingProduct = null;

        foreach ($items as $item) {
            if ($item['id'] === $id) {
                $existingProduct = $item;
                break;
            }
        }

        $oldImage = $existingProduct['img'] ?? '';

        /*
        | Upload image
        */
        try {

            $image = upload_product_image(
                $_FILES['img'] ?? null,
                $oldImage
            );

        } catch (Exception $e) {

            $message = $e->getMessage();

            $editing = [
                'id' => $id,
                'name' => trim($_POST['name'] ?? ''),
                'greenspk_price' => (float)($_POST['greenspk_price'] ?? 0),
                'govt_price' => (float)($_POST['govt_price'] ?? 0),
                'grocer_price' => (float)($_POST['grocer_price'] ?? 0),
                'qty' => (float)($_POST['qty'] ?? 0),
                'uom' => trim($_POST['uom'] ?? ''),
                'in_stock' => ($_POST['in_stock'] ?? 'no') === 'yes',
                'img' => $oldImage
            ];

            $image = $oldImage;
        }

        $entry = [
            'id' => $id,
            'name' => trim($_POST['name'] ?? ''),
            'greenspk_price' => (float)($_POST['greenspk_price'] ?? 0),
            'govt_price' => (float)($_POST['govt_price'] ?? 0),
            'grocer_price' => (float)($_POST['grocer_price'] ?? 0),
            'qty' => (float)($_POST['qty'] ?? 0),
            'uom' => trim($_POST['uom'] ?? ''),
            'in_stock' => ($_POST['in_stock'] ?? 'no') === 'yes',
            'img' => $image
        ];

        if (
            $entry['name'] === '' ||
            $entry['uom'] === '' ||
            $entry['greenspk_price'] <= 0 ||
            $entry['qty'] <= 0
        ) {

            $message = 'Please complete the name, GreenSPK price, quantity, and unit of measurement.';
            $editing = $entry;

        } elseif ($message === '') {

            /*
            | Add or update product
            */
            $updated = false;

            foreach ($items as $key => $item) {

                if ($item['id'] === $id) {

                    $items[$key] = $entry;
                    $updated = true;

                    break;
                }
            }

            if (!$updated) {
                $items[] = $entry;
            }

            save_products($items);

            header(
                'Location: products.php?notice=' .
                urlencode($updated ? 'Product updated.' : 'Product added.')
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Product
    |--------------------------------------------------------------------------
    */
    if ($action === 'delete') {

        $id = $_POST['id'] ?? '';

        /*
        | Find image before deleting product
        */
        $imageToDelete = '';

        foreach ($items as $item) {

            if ($item['id'] === $id) {
                $imageToDelete = $item['img'] ?? '';
                break;
            }
        }

        /*
        | Remove product
        */
        $items = array_values(
            array_filter(
                $items,
                fn($p) => $p['id'] !== $id
            )
        );

        /*
        | Delete uploaded image
        */
        if (!empty($imageToDelete)) {

            $uploadDir = __DIR__ . '/uploads/products/';
            $imageFile = __DIR__ . '/' . ltrim($imageToDelete, '/');

            if (
                file_exists($imageFile) &&
                strpos(
                    realpath($imageFile) ?: '',
                    realpath($uploadDir) ?: ''
                ) === 0
            ) {
                @unlink($imageFile);
            }
        }

        save_products($items);

        header(
            'Location: products.php?notice=' .
            urlencode('Product removed.')
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Edit Product
|--------------------------------------------------------------------------
*/
if (($_GET['action'] ?? '') === 'edit') {

    foreach ($items as $item) {

        if ($item['id'] === ($_GET['id'] ?? '')) {

            $editing = $item;

            break;
        }
    }
}


/*
|--------------------------------------------------------------------------
| New Product
|--------------------------------------------------------------------------
*/
$new = ($_GET['action'] ?? '') === 'new';


/*
|--------------------------------------------------------------------------
| Admin Header
|--------------------------------------------------------------------------
*/
admin_header(
    $editing
        ? 'Edit product'
        : ($new ? 'Add product' : 'Product management'),
    'products'
);
?>


<?php if (!empty($_GET['notice'])): ?>

    <div class="notice">
        ✓ <?= clean($_GET['notice']) ?>
    </div>

<?php endif; ?>


<?php if ($editing || $new): ?>

    <?php

    $p = $editing ?: [
        'id' => '',
        'name' => '',
        'greenspk_price' => '',
        'govt_price' => '',
        'grocer_price' => '',
        'qty' => '',
        'uom' => 'KG',
        'in_stock' => true,
        'img' => ''
    ];

    ?>


    <section class="form-panel">

        <div class="panel-head">

            <div>

                <a class="back" href="products.php">
                    ← All products
                </a>

                <h2>
                    <?= $editing ? 'Update your listing' : 'New vegetable listing' ?>
                </h2>

                <p>
                    Set your selling price alongside government and Grocer.pk rates.
                </p>

            </div>

        </div>


        <?php if ($message): ?>

            <div class="alert">
                <?= clean($message) ?>
            </div>

        <?php endif; ?>


        <form
            method="post"
            class="product-form"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="action"
                value="save"
            >

            <input
                type="hidden"
                name="id"
                value="<?= clean($p['id']) ?>"
            >


            <!-- Product Name -->

            <label>
                Product name

                <input
                    name="name"
                    required
                    value="<?= clean($p['name']) ?>"
                    placeholder="e.g. Potato (Plain)"
                >
            </label>


            <!-- Product Image -->

            <label>
                Product Image

                <input
                    name="img"
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                >

                <small>
                    JPG, PNG, GIF or WebP. Maximum size: 5 MB.
                </small>

                <?php if (!empty($p['img'])): ?>

                    <div style="margin-top:10px;">

                        <img
                            src="<?= clean($p['img']) ?>"
                            alt="<?= clean($p['name']) ?>"
                            style="
                                width:100px;
                                height:100px;
                                object-fit:cover;
                                border-radius:8px;
                                border:1px solid #ddd;
                            "
                        >

                        <small style="display:block;margin-top:5px;">
                            Current image
                        </small>

                    </div>

                <?php endif; ?>

            </label>


            <!-- GreenSPK Price -->

            <label>
                GreenSPK price (Rs.)

                <input
                    name="greenspk_price"
                    type="number"
                    min="1"
                    required
                    value="<?= clean((string)$p['greenspk_price']) ?>"
                >
            </label>


            <!-- Government Price -->

            <label>
                Government price (Rs.)

                <input
                    name="govt_price"
                    type="number"
                    min="0"
                    value="<?= clean((string)$p['govt_price']) ?>"
                >
            </label>


            <!-- Grocer.pk Price -->

            <label>
                Grocer.pk price (Rs.)

                <input
                    name="grocer_price"
                    type="number"
                    min="0"
                    value="<?= clean((string)$p['grocer_price']) ?>"
                >
            </label>


            <!-- Quantity -->

            <label>
                Quantity

                <input
                    name="qty"
                    type="number"
                    min="0.01"
                    step="0.01"
                    required
                    value="<?= clean((string)$p['qty']) ?>"
                    placeholder="1"
                >
            </label>


            <!-- Unit -->

            <label>
                Unit of measurement

                <select name="uom">

                    <option
                        <?= $p['uom'] === 'KG' ? 'selected' : '' ?>
                    >
                        KG
                    </option>

                    <option
                        <?= $p['uom'] === 'Gram' ? 'selected' : '' ?>
                    >
                        Gram
                    </option>

                    <option
                        <?= $p['uom'] === 'Bunch' ? 'selected' : '' ?>
                        >
                        Bunch
                    </option>

                    <option
                        <?= $p['uom'] === 'Piece' ? 'selected' : '' ?>
                    >
                        Piece
                    </option>

                    <option
                        <?= $p['uom'] === 'Packet' ? 'selected' : '' ?>
                    >
                        Packet
                    </option>

                </select>

            </label>


            <!-- Stock -->

            <label>
                In stock

                <select name="in_stock">

                    <option
                        value="yes"
                        <?= !empty($p['in_stock']) ? 'selected' : '' ?>
                    >
                        Yes
                    </option>

                    <option
                        value="no"
                        <?= empty($p['in_stock']) ? 'selected' : '' ?>
                    >
                        No
                    </option>

                </select>

            </label>


            <!-- Form Actions -->

            <div class="form-actions">

                <a href="products.php">
                    Cancel
                </a>

                <button
                    class="primary"
                    type="submit"
                >
                    <?= $editing ? 'Save changes' : 'Add product' ?>
                </button>

            </div>

        </form>

    </section>


<?php else: ?>


    <!-- Product Listing -->

    <section class="panel">

        <div class="panel-head">

            <div>

                <p class="kicker">
                    CATALOGUE
                </p>

                <h2>
                    All products
                    <span class="count">
                        <?= count($items) ?>
                    </span>
                </h2>

                <p>
                    Compare GreenSPK pricing with official and Grocer.pk rates.
                </p>

            </div>

            <a
                class="primary"
                href="products.php?action=new"
            >
                + Add product
            </a>

        </div>


        <?php if (!$items): ?>

            <div class="blank-state">

                <span>🌱</span>

                <h3>
                    No products yet
                </h3>

                <p>
                    Start building your vegetable catalogue.
                </p>

                <a
                    class="primary"
                    href="products.php?action=new"
                >
                    Add your first product
                </a>

            </div>


        <?php else: ?>


            <div class="product-table">

                <div class="table-row table-label">

                    <span>
                        Product
                    </span>

                    <span>
                        Price comparison
                    </span>

                    <span>
                        Qty / UOM
                    </span>

                    <span>
                        Stock
                    </span>

                    <span></span>

                </div>


                <?php foreach ($items as $p): ?>

                    <div class="table-row">

                        <!-- Product -->

                        <span class="table-product">

                            <i>

                                <?php if (!empty($p['img'])): ?>

                                    <img
                                        src="<?= clean($p['img']) ?>"
                                        alt="<?= clean($p['name']) ?>"
                                    >

                                <?php else: ?>

                                    🥬

                                <?php endif; ?>

                            </i>

                            <b>
                                <?= clean($p['name']) ?>
                            </b>

                            <small>
                                GreenSPK:
                                Rs.
                                <?= number_format((float)$p['greenspk_price']) ?>
                            </small>

                        </span>


                        <!-- Price Comparison -->

                        <span class="price-comparison">

                            <small>
                                Govt:
                                Rs.
                                <?= number_format((float)$p['govt_price']) ?>
                            </small>

                            <small>
                                Grocer.pk:
                                Rs.
                                <?= number_format((float)$p['grocer_price']) ?>
                            </small>

                        </span>


                        <!-- Quantity -->

                        <span>

                            <?= clean((string)$p['qty']) ?>

                            <?= clean($p['uom']) ?>

                        </span>


                        <!-- Stock -->

                        <span>

                            <em
                                class="status <?= !empty($p['in_stock']) ? 'live' : 'hidden' ?>"
                            >

                                <?= !empty($p['in_stock'])
                                    ? 'In stock'
                                    : 'Out of stock'
                                ?>

                            </em>

                        </span>


                        <!-- Actions -->

                        <span class="row-actions">

                            <a
                                href="products.php?action=edit&id=<?= urlencode($p['id']) ?>"
                            >
                                Edit
                            </a>

                            <form
                                method="post"
                                onsubmit="return confirm('Remove this product?')"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= clean($p['id']) ?>"
                                >

                                <button>
                                    Delete
                                </button>

                            </form>

                        </span>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php endif; ?>

    </section>


<?php endif; ?>


<?php admin_footer(); ?>