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
    <!-- <div class="pre-loader">
        <div class="pre-loader-box">
            <div class="loader-logo">
                <img src="vendors/images/deskapp-logo.svg" alt="" />
            </div>
            <div class="loader-progress" id="progress_div">
                <div class="bar" id="bar1"></div>
            </div>
            <div class="percent" id="percent1">0%</div>
            <div class="loading-text">Loading...</div>
        </div>
    </div> -->

    <?php
    include "php/includes/header.php";
    ?>
	<?php include 'php/includes/sidebar.php'; ?>

    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <!-- Export Datatable start -->
            <div class="card-box mb-30" style="border-radius: 20px;">
                <div class="pd-20">
                    <h4 class="text-dark">Article Records</h4>
                </div>
                <div class="pb-20 table-responsive">
                    <table class="table hover table-striped multiple-select-row nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>TrendingScore</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="datatable-nosort action-column">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $c = 1;
                            $get_article = mysqli_query($con, "SELECT * FROM `article`
                            INNER JOIN category ON category.CategoryID = article.CategoryID
                            ORDER BY `article`.`Created_at` ASC");

                            if (mysqli_num_rows($get_article) > 0) {
                                while ($row = mysqli_fetch_assoc($get_article)) {
                            ?>
                                    <tr>
                                        <td><?= $c++; ?></td>
                                        <td>
                                            <?= $row['Category']; ?>
                                        </td>
                                        <td>
                                            <?= $row['Title']; ?>
                                        </td>
                                        <td>
                                            <?= $row['Trending_score']; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= ($row['Published'] == 'published') ? 'success' : 'secondary' ?>"><?= ucfirst($row['Published']); ?></span>
                                        </td>
                                        <td>
                                            <?= $row['Date']; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle"
                                                    href="#" role="button" data-toggle="dropdown">
                                                    <i class="dw dw-more"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                                    <a class="dropdown-item"
                                                        href="up_article.php?r_id=<?= $row['ArticleID']; ?>"><i
                                                            class="dw dw-edit2"></i> Edit</a>
                                                    <a class="dropdown-item"
                                                        onclick="return confirm('Are you sure to delete this record.')"
                                                        href="php/de_article.php?r_id=<?= $row['ArticleID']; ?>"><i
                                                            class="dw dw-delete-3"></i> Delete</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                $msg[] = "No products found in stock yet.";
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php
                    if (isset($msg)) {
                        foreach ($msg as $printmsg) {
                    ?>
                            <p class="text-center">
                                <?php echo ($printmsg) ?>
                            </p>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <!-- Export Datatable End -->
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
</body>

</html>