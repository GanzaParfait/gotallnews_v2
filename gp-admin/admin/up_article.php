<?php
include 'php/header/top.php';
include 'php/includes/ImageProcessor.php';

$record_id = $_GET['r_id'];
$get_record = mysqli_query($con, "SELECT * FROM `article`
INNER JOIN category ON category.CategoryID = article.CategoryID
WHERE `ArticleID` = '$record_id'");
$get_record_row = mysqli_fetch_assoc($get_record);

if (isset($_POST['updatearticle'])) {
    $f_r_id = $_POST['f_r_id'];
    $category_id = $_POST['category_id'];
    $title = mysqli_real_escape_string($con, $_POST['title']);

    function generateSeoUrl($title)
    {
        // Convert title to lowercase
        $seo_url = strtolower($title);

        // Replace any non-alphanumeric characters (except spaces) with an empty string
        $seo_url = preg_replace('/[^a-z0-9\s]/', '', $seo_url);

        // Replace multiple spaces with a single space
        $seo_url = preg_replace('/\s+/', ' ', $seo_url);

        // Replace spaces with hyphens
        $seo_url = str_replace(' ', '-', $seo_url);

        return $seo_url;
    }

    $seo_url = generateSeoUrl($title);

    $date = $_POST['date'];
    $date = mysqli_real_escape_string($con, $_POST['date']);
    $description = mysqli_real_escape_string($con, $_POST['description']);

    $update = mysqli_query($con, "UPDATE `article` SET `CategoryID`='$category_id',`Title`='$title',
            `Article_link`='$seo_url',`Content`='$description', Created_at = NOW() WHERE `ArticleID`='$f_r_id'");

    if ($update) {
        echo "<script>alert('An article updated successfully!')</script>";
        echo "<script>window.location.href = 'view_article.php'</script>";
    } else {
        die('Denied' . mysqli_error($con));
    }

    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $file_name = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];

        $img_explode = explode('.', $file_name);
        $img_extension = strtolower(end($img_explode));

        $extensions = ['png', 'jpeg', 'jpg', 'gif', 'webp'];

        if (in_array($img_extension, $extensions) === true) {
            $time = time();

            try {
                // Initialize image processor
                $imageProcessor = new ImageProcessor('images/uploaded/', 80);

                // Process the image: convert to WebP, resize, and compress
                $processedImages = $imageProcessor->processImage($tmp_name, $file_name, $time);

                if ($processedImages && count($processedImages) > 0) {
                    // Get the large image as the main image
                    $main_image = isset($processedImages['large']) ? $processedImages['large'] : $processedImages['medium'];

                    // Update article with all image sizes
                    $update = mysqli_query($con, "UPDATE `article` SET 
                        `CategoryID`='$category_id',
                        `Title`='$title',
                        `Article_link`='$seo_url',
                        `Image`='$main_image',
                        `image_large`='" . ($processedImages['large'] ?? '') . "',
                        `image_medium`='" . ($processedImages['medium'] ?? '') . "',
                        `image_small`='" . ($processedImages['small'] ?? '') . "',
                        `image_thumbnail`='" . ($processedImages['thumbnail'] ?? '') . "',
                        `Content`='$description', 
                        `Last_updated` = NOW() 
                        WHERE `ArticleID`='$f_r_id'");

                    if ($update) {
                        echo "<script>alert('Article updated successfully with optimized WebP images!')</script>";
                        echo "<script>window.location.href = 'view_article.php'</script>";
                    } else {
                        // Clean up processed images if database update fails
                        $imageProcessor->cleanupOldImages($processedImages);
                        die('Database update failed: ' . mysqli_error($con));
                    }
                } else {
                    die('Image processing failed');
                }
            } catch (Exception $e) {
                die('Image processing error: ' . $e->getMessage());
            }
        } else {
            die('Invalid image format. Allowed formats: PNG, JPG, JPEG, GIF, WebP');
        }
    } else {
        // No new image uploaded, just update other fields
        $update = mysqli_query($con, "UPDATE `article` SET 
            `CategoryID`='$category_id',
            `Title`='$title',
            `Article_link`='$seo_url',
            `Content`='$description', 
            `Last_updated` = NOW() 
            WHERE `ArticleID`='$f_r_id'");

        if ($update) {
            echo "<script>alert('Article updated successfully!')</script>";
            echo "<script>window.location.href = 'view_article.php'</script>";
        } else {
            die('Update failed: ' . mysqli_error($con));
        }
    }
}

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
    include 'php/includes/header.php';
    ?>
    <?php include 'php/includes/sidebar.php'; ?>

    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="pd-20 card-box mb-30" style="border-radius: 2px;">
                <div>
                    <div class="page-header" style="background-color: <?= ($get_record_row['Published'] == 'published') ? '#0080004b' : '#0000ff28'; ?>;">
                        <div class="row">
                            <div class="col-md-6 col-sm-12">
                                <div class="title">
                                    <h4>Edit Article Records</h4>
                                </div>
                                <nav aria-label="breadcrumb" role="navigation">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a href="index.php">Home</a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">
                                            Article
                                        </li>
                                    </ol>
                                </nav>
                                <small class="text-muted weight-400">Carefully, cause this records will be published on the website.</small>
                            </div>
                            <div class="col-md-6 col-sm-12 text-right">

                                <?php

                                if ($get_record_row['Published'] == 'published') {
                                    ?>
                                    <button class="btn btn-success" disabled>Published</button>
                                <?php
                                } else {
                                    ?>
                                    <a onclick="return confirm('If OK, This article will be published on the website.')" href="php/publish_article.php?article_id=<?= $get_record_row['ArticleID']; ?>" class="btn btn-<?= ($get_record_row['Published'] == 'published') ? 'success' : 'primary'; ?>">Publish
                                    </a>
                                <?php
                                }

                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="msg"></div>
                <form action="up_article.php" method="post" enctype="multipart/form-data" id="newformdata">
                    <input type="hidden" name="f_r_id" value="<?= $get_record_row['ArticleID']; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category:</label>
                                <select class="custom-select2 form-control" required name="category_id"
                                    style="width: 100%; height: 38px">
                                    <optgroup label="Categories">
                                        <option value="<?= $get_record_row['CategoryID']; ?>"><?= $get_record_row['Category']; ?>
                                        </option>
                                        <?php
                                        $get_categories = mysqli_query($con, 'SELECT * FROM `category`');
                                        if (mysqli_num_rows($get_categories) > 0) {
                                            while ($row = mysqli_fetch_assoc($get_categories)) {
                                                ?>
                                                <option value="<?= $row['CategoryID']; ?>"><?= $row['Category']; ?>
                                                </option>
                                            <?php
                                            }
                                        } else {
                                            ?>
                                            <option value="noproduct" hidden selected>No category available.</option>
                                        <?php
                                        }
                                        ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Article Title:</label>
                                <input class="form-control" type="text" name="title" value="<?= $get_record_row['Title']; ?>"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="form-group col-md-6" style="border-radius: 5px;">
                                    <?php
                                    $profile = 'images/uploaded/' . $get_record_row['Image'] . '';
                                    $default_image_url = '<img src="php/defaultavatar/avatar.png" alt="avatar">';

                                    if (file_exists($profile)) {
                                        ?>
                                        <a href="images/uploaded/<?= $get_record_row['Image']; ?>" target="_blank">
                                            <img src="images/uploaded/<?= $get_record_row['Image']; ?>" alt="<?= $get_record_row['Title']; ?>" style="border-radius: 5px;">
                                        </a>
                                    <?php
                                    } else {
                                        echo $default_image_url;
                                    }
                                    ?>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Image(<span class="text-dark">Allowed format _PNG, JPG, JPEG & GIF_</span>):</label>
                                    <input class="form-control" type="file" name="image">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Published at:</label>
                                <input class="form-control" readonly type="date" name="date" value="<?= $get_record_row['Date']; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="html-editor pd-20 card-box mb-30">
                        <textarea
                            class="textarea_editor form-control border-radius-0" name="description"><?= nl2br($get_record_row['Content']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-10 mb-2">
                            <button type="submit" class="btn btn-outline-primary"
                                style="width: 100%;" id="formdatabtn" name="updatearticle">Update</button>
                        </div>
                        <div class="col-md-2">
                            <a href="view_article.php" class="btn btn-primary" style="width: 100%;">View</a>
                        </div>
                    </div>

                </form>

            </div>
            <?php
            include 'php/includes/footer.php';
            ?>
        </div>
    </div>
    <!-- js -->
    <!-- <script src="javascript/up_article.js"></script> -->
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