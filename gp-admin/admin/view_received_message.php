<?php
include "php/header/top.php";

$count_messages = mysqli_query($con, "SELECT * FROM `message`");
$print_count_messages = mysqli_num_rows($count_messages);
?>
<!DOCTYPE html>
<html>

<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8" />
    <title>CMS News _<?= $names; ?>_</title>

    <!-- Logo -->
    <link rel="icon" href="images/favicon-16x16.png">
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

    <div class="left-side-bar">
        <div class="brand-logo">
            <a href="index.php">
                <!-- <img src="images/s1.png" width="200" style="height: 80px;margin: auto;" alt="logo"> -->
                <span style="color:#444;padding: 0 10px;">Logo</span>
            </a>
            <div class="close-sidebar" data-toggle="left-sidebar-close">
                <i class="ion-close-round"></i>
            </div>
        </div>
        <div class="menu-block customscroll">
            <div class="sidebar-menu">
                <ul id="accordion-menu">
                    <li>
                        <a href="index.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-house"></span><span class="mtext">Home</span>
                        </a>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon"><i class="icon-copy fa fa-newspaper-o" aria-hidden="true"></i></span><span
                                class="mtext">Article</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="new_article.php">New</a></li>
                            <li><a href="view_article.php">Manage</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon"><i class="icon-copy fa fa-object-ungroup" aria-hidden="true"></i></span><span
                                class="mtext">Category</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="new_category.php">New</a></li>
                            <li><a href="view_category.php">Manage</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="view_received_message.php" class="dropdown-toggle active no-arrow">
                            <span class="micon icon-copy fa fa-inbox"></span><span class="mtext">Messages</span>
                        </a>
                    </li>

                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon"><i class="icon-copy fa fa-cogs" aria-hidden="true"></i></span><span
                                class="mtext">Settings</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="profile.php">Profile</a></li>
                            <li><a href="php/extras/logout.php">Log Out</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:;" data-toggle="right-sidebar" class="dropdown-toggle no-arrow">
                            <span class="micon"><i class="icon-copy fa fa-map-o" aria-hidden="true"></i></span><span
                                class="mtext">Layout Setting</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <!-- Export Datatable start -->
            <div class="card-box mb-30" style="border-radius: 2px;">
                <div class="pd-20">
                    <h4 class="text-dark">Received Messages (<?= $print_count_messages; ?>)</h4>
                </div>
                <?php
                if (isset($_GET['msg'])) {
                ?>
                    <div class="p-2">
                        <div class="alert alert-primary alert-dismissible fade show mt-3" role="alert">
                            <strong>Content!</strong>
                            <?= $_GET['msg']; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                <?php
                } else {
                    echo "";
                }
                ?>
                <div class="pb-20 table-responsive">
                    <table class="table hover multiple-select-row nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Names</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>SentOn</th>
                                <th class="datatable-nosort action-column">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $c = 1;
                            $get_msg = mysqli_query($con, "SELECT * FROM `message` ORDER BY message.Id DESC");
                            if (mysqli_num_rows($get_msg) > 0) {
                                while ($row = mysqli_fetch_assoc($get_msg)) {
                            ?>
                                    <tr>
                                        <td><?= $c++; ?></td>
                                        <td>
                                            <?= $row['Names']; ?>
                                        </td>
                                        <td>
                                            <?= $row['Email']; ?>
                                        </td>
                                        <td>
                                            <?= $row['Subject']; ?>
                                        </td>
                                        <td>
                                            <?= $row['Message']; ?>
                                        </td>
                                        <td>
                                            <?= $row['StampedDate']; ?>
                                        </td>
                                        <td>
                                            <a style="color: red;" onclick="return confirm('Are you sure to delete this record.')"
                                                href="php/de_message.php?m_id=<?= $row['MesID']; ?>"><i
                                                    class="dw dw-delete-3"></i></a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                $msg[] = "No Messages found yet.";
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