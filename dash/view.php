<?php

use \Sosa\Provider\ProductProvider;

require "middleware.php";
require "../backend/provider/ProductProvider.php";

$attribute = "";
$query = "";

if (isset($_GET["attr"]) && isset($_GET["query"])) {
    $attribute = $_GET["attr"];
    $query = $_GET["query"];
}

function getLabelClassValue($quantityAmount) {
    $quantityAmount = intval($quantityAmount);
    $classValue = "label ";

    if ($quantityAmount >= 30) {
        $classValue .= "label-success";
    } else if ($quantityAmount >= 15) {
        $classValue .= "label-warning";
    } else if ($quantityAmount < 15) {
        $classValue .= "label-danger";
    }

    return $classValue;
}

function sanitizeHtml($string) {
    return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>S O S A | Dashboard</title>

    <link rel="stylesheet" href="../assests/css/bootstrap-3.3.7.min.css">
    <link rel="stylesheet" href="../assests/css/sidebar-nav.css">
    <link rel="stylesheet" href="../assests/css/laf.css">

    <script src="../assests/vendor/jquery-3.1.1.min.js"></script>
    <script src="../assests/vendor/bootstrap-3.3.7.min.js"></script>
    <script src="../assests/js/main.js"></script>
</head>

<body>

<input id="csrf_token" type="hidden" value="<?php echo $_SESSION["csrf_token"] ?>">

<div class="row">

    <?php require("../dash/include/sidenav.html") ?>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="side-body">
            <h3 class="page-title">S o s a | Console Panel</h3>
            <div class="panel panel-sosa">
                <div class="panel-heading">
                    <strong>MANAGE PRODUCTS</strong>
                </div>
                <div class="container-fluid">
                    <br>
                    <form action="../dash/view.php" class="form-inline">
                        <div class="form-group">
                            <label for="query-filter">Refine search by</label>
                            <select id="query-filter" name="attr" class="form-control">
                                <option <?php if ($attribute == "Id") echo "selected"; ?>>Id</option>
                                <option <?php if ($attribute == "Name") echo "selected"; ?>>Name</option>
                                <option <?php if ($attribute == "Price") echo "selected"; ?>>Price</option>
                                <option <?php if ($attribute == "Size") echo "selected"; ?>>Size</option>
                                <option <?php if ($attribute == "Type") echo "selected"; ?>>Type</option>
                                <option <?php if ($attribute == "Stock") echo "selected"; ?>>Stock</option>
                            </select>

                            <input name="query" type="text" class="form-control" placeholder="Search" value="<?php if ($query != "") echo sanitizeHtml($query); ?>">

                            <button id="btn-new-product" type="submit" class="btn btn-primary">
                                <span class="glyphicon glyphicon-search"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="container-fluid table-container" style="overflow-x:auto;">
                    <table class="table table-responsive table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Date Added</th>
                            <th>Action</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php
                        $products = array();
                        $productProvider = new ProductProvider();

                        if (empty($attribute) || empty($query)) {
                            $products = $productProvider->getAllProducts();
                        } else if (! empty($attribute) && ! empty($query)) {
                            $products = $productProvider->getProductsByAttribute($attribute, $query);
                        }

                        if ($products != null) {
                            foreach ($products as $product) {
                                ?>
                                <tr data-product-id="<?php echo $product["id"] ?>">
                                    <td> <?php echo $product["id"] ?> </td>
                                    <td> <?php echo sanitizeHtml($product["name"]); ?> </td>
                                    <td> <?php echo $product["type"] ?> </td>
                                    <td> <?php echo $product["size"] ?> </td>
                                    <td> <?php echo "£" . $product["price"] ?> </td>
                                    <td> <span class="<?php echo getLabelClassValue($product["stock"]) ?>"><?php echo $product["stock"] ?></span> </td>
                                    <td> <?php echo $product["created_at"] ?> </td>
                                    <td>
                                        <a class="btn btn-warning btn-edit" href="<?php echo "product.php?action=edit&id=" . $product["id"] ?>">Edit</a>
                                        <a class="btn btn-danger btn-delete" data-product-id="<?php echo $product["id"] ?>" data-product-name="<?php echo sanitizeHtml($product["name"]) ?>">Delete</a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>

                        </tbody>
                    </table>

                    <?php
                    if ($products == null) {
                        $filteredQuery = sanitizeHtml($query);
                        $filteredAttribute = sanitizeHtml($attribute);
                        $errorMsg = "Null";

                        if ($productProvider->getTotalProductsCount() == 0) {
                            $errorMsg = "Sorry, but there are currently no products in the database. Please create a new product in order for it to be listed here.";
                        } else {
                            $errorMsg = "Sorry, but no item with the $filteredAttribute <strong>\"$filteredQuery\"</strong> could be found in the database. Please refine your search query.";
                        }

                        echo  "<div class=\"alert alert-warning\">$errorMsg</div>";
                    }
                    ?>

                    <a class="btn btn-primary pull-right" href="product.php?action=add">Add product
                        <span class="glyphicon glyphicon-plus"></span>
                    </a>
                    <br>
                    <br>
                </div>
            </div>
            <div id="deleteAlert" class="alert hidden" role="alert"></div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Confirm delete</h4>
            </div>
            <div id="deleteModalBody" class="modal-body"> </div>
            <div class="modal-footer">
                <button id="modalDeleteBtn" type="button" data-loading-text="Loading..." class="btn btn-danger">Yes</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>