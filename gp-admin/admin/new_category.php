<?php
include "php/header/top.php";
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
                                    <h4>New Category Records</h4>
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
                <div id="msg"></div>
                <form action="#" id="newformdata">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category:</label>
                                <input class="form-control" type="text" name="category" placeholder="Category..."
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date:</label>
                                <input class="form-control" type="date" name="date"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-10 mb-2">
                            <button type="submit" class="btn btn-outline-primary"
                                style="width: 100%;" id="formdatabtn">Save</button>
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
    <script src="javascript/new_category.js"></script>
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