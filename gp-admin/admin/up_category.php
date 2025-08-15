<?php
include "php/header/top.php";


if (isset($_POST['editcategory'])) {
    $f_r_id = $_POST['f_r_id'];
    $category = ucfirst($_POST['category']);
    $date = $_POST['date'];


    // Edit category to be related


    $edit = mysqli_query($con, "UPDATE `category` SET `Category`='$category',`Date`='$date' WHERE `CategoryID`='$f_r_id'");


    if ($edit) {
        header("Location: view_category.php?msg=Category updated successfully!");
    } else {
        header("Location: view_category.php?msg=Something went wrong in updating category!");
    }
}


$record_id = $_GET['r_id'];
$get_record = mysqli_query($con, "SELECT * FROM `category` WHERE `CategoryID` = '$record_id'");
$get_record_row = mysqli_fetch_assoc($get_record);



?>
<!DOCTYPE html>
<html>

<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8" />
    <title>CMS News _<?= $names; ?>_</title>

    <!-- Logo -->
    <link rel="icon" href="images/favicon-32x32.png">
    <!-- End Logo -->

    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />

    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/core.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="src/plugins/datatables/css/responsive.bootstrap4.min.css" />
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css" />
</head>

<body>
    <?php
    include "php/includes/header.php";
    ?>
	<?php include 'php/includes/sidebar.php'; ?>
    
    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="pd-20 card-box mb-30" style="border-radius: 2px;">
                <div class="">
                    <div class="page-header">
                        <div class="row">
                            <div class="col-md-6 col-sm-12">
                                <div class="title">
                                    <h4>Edit Category Records</h4>
                                </div>
                                <nav aria-label="breadcrumb" role="navigation">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="index.php">Home</a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">
                                            Category
                                        </li>
                                    </ol>
                                </nav>
                                <small class="text-muted weight-400">Carefully, cause this records will be published on the website.</small>
                            </div>
                            <div class="col-md-6 col-sm-12 text-right">
                                <a href="view_category.php" class="btn btn-light">
                                    <i class="icon-copy fa fa-table" aria-hidden="true"></i> Manage Categories
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                if (isset($_GET['msg'])) {
                ?>
                    <div class="alert alert-primary alert-dismissible fade show mt-3" role="alert">
                        <strong>Category!</strong>
                        <?= $_GET['msg']; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php
                } else {
                    echo "";
                }
                ?>
                <form action="up_category.php" method="post">
                    <input type="hidden" name="f_r_id" value="<?= $get_record_row['CategoryID']; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category:</label>
                                <input class="form-control" type="text" name="category" value="<?= $get_record_row['Category']; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date:</label>
                                <input class="form-control" type="date" name="date" value="<?= $get_record_row['Date']; ?>"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-10 mb-2">
                            <button type="submit" name="editcategory" class="btn btn-outline-primary"
                                style="width: 100%;">Update</button>
                        </div>
                        <div class="col-md-2">
                            <a href="view_category.php" class="btn btn-primary" style="width: 100%;">View</a>
                        </div>
                    </div>
                </form>

            </div>
            <?php
            include "php/includes/footer.php";
            ?>
        </div>
    </div>
    <!-- js -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
    <!-- buttons for Export datatable -->
    <script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.print.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.flash.min.js"></script>
    <script src="src/plugins/datatables/js/pdfmake.min.js"></script>
    <script src="src/plugins/datatables/js/vfs_fonts.js"></script>
    <!-- Datatable Setting js -->
    <script src="vendors/scripts/datatable-setting.js"></script>
    <!-- Page JS -->
    <script src="vendors/js/pages-account-settings-account.js"></script>
    <!-- add sweet alert js & css in footer -->
    <script src="src/plugins/sweetalert2/sweetalert2.all.js"></script>
    <script src="src/plugins/sweetalert2/sweet-alert.init.js"></script>
</body>

</html>