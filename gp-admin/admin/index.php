<?php
include 'php/header/top.php';

$count_category = mysqli_query($con, 'SELECT * FROM `category`');
$print_count_category = mysqli_num_rows($count_category);

$count_articles = mysqli_query($con, 'SELECT * FROM `article`');
$print_count_articles = mysqli_num_rows($count_articles);

$count_msgs = mysqli_query($con, 'SELECT * FROM `message`');
$print_count_msgs = mysqli_num_rows($count_msgs);

$count_article_view = mysqli_query($con, 'SELECT * FROM `view_logs`');
$print_count_article_view = mysqli_num_rows($count_article_view);

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
	<div class="whole-content-container">
		<?php
		include 'php/includes/header.php';
		?>

		<?php include 'php/includes/sidebar.php'; ?>

		<div class="main-container">
			<div class="pd-ltr-20">
				<div class="title pb-20">
					<h2 class="h3 mb-0">Content Overview</h2>
				</div>
				<div class="row pb-10">
					<div class="col-xl-3 col-lg-3 col-md-6 mb-20">
						<div class="card-box height-100-p widget-style3">
							<div class="d-flex flex-wrap">
								<div class="widget-data">
									<div class="weight-700 font-24 text-dark">
										<div class="section-counter">
											<span class="countup"><?= $print_count_category; ?>
											</span>
										</div>
									</div>
									<div class="font-14 text-secondary weight-500">
										Categories
									</div>
								</div>
								<a href="view_category.php" class="widget-icon">
									<div class="icon" data-color="#00eccf"><i class="icon-copy fa fa-building-o"
											aria-hidden="true"></i>
									</div>
								</a>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-lg-3 col-md-6 mb-20">
						<div class="card-box height-100-p widget-style3">
							<div class="d-flex flex-wrap">
								<div class="widget-data">
									<div class="weight-700 section-counter font-24 text-dark">
										<span class="countup">
											<?= $print_count_articles; ?>
										</span>
									</div>
									<div class="font-14 text-secondary weight-500">
										Articles
									</div>
								</div>
								<a href="view_article.php" class="widget-icon">
									<div class="icon" data-color="#ff5b5b">
										<i class="icon-copy fa fa-address-book-o" aria-hidden="true"></i>
									</div>
								</a>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-lg-3 col-md-6 mb-20">
						<div class="card-box height-100-p widget-style3">
							<div class="d-flex flex-wrap">
								<div class="widget-data">
									<div class="weight-700 section-counter font-24 text-dark">
										<span class="countup">
										<?= $print_count_msgs; ?>
										</span>
									</div>
									<div class="font-14 text-secondary weight-500">
										Messages
									</div>
								</div>
								<a href="view_received_message.php" class="widget-icon">
									<div class="icon">
										<i class="icon-copy fa fa-envelope-open-o" aria-hidden="true"></i>
									</div>
								</a>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-lg-3 col-md-6 mb-20">
						<div class="card-box height-100-p widget-style3">
							<div class="d-flex flex-wrap">
								<div class="widget-data">
									<div class="weight-700 section-counter font-24 text-dark">
										<span class="countup">
										<?= $print_count_article_view; ?>
										</span>
									</div>
									<div class="font-14 text-secondary weight-500">Viewed Articles in <b><?= $print_count_articles; ?></b></div>
								</div>
								<a href="#top" class="widget-icon">
									<div class="icon" data-color="#09cc06">
										<i class="icon-copy fa fa-eye" aria-hidden="true"></i>
									</div>
								</a>
							</div>
						</div>
					</div>
				</div>
				<?php
				include 'php/includes/footer.php';
				?>
			</div>
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
	<script src="counter/aos.js"></script>
	<script src="counter/counter.js"></script>
	<!-- Datatable Setting js -->
	<script src="vendors/scripts/datatable-setting.js"></script>

	<!-- ===================== Counter JS ======================== -->
	<!-- <script src="counter/amcharts.js"></script>
	<script src="counter/bootstrap.min.js"></script>
	<script src="counter/jquery-2.2.4.min.js"></script>
	<script src="counter/lobipanel.min.js"></script>
	<script src="counter/serial.js"></script>
	<script src="counter/counterUp/jquery.counterup.min.js"></script>
	<script src="counter/waypoint/waypoints.min.js"></script> -->
	<!-- ===================== End Counter JS ======================== -->
	<script>
		// $(function () {
		// 	// Counter for dashboard stats
		// 	$('.counter').counterUp({
		// 		delay: 10,
		// 		time: 1000
		// 	});
		// });

		let content = document.querySelector('.whole-content-container');
		let preLoader = document.querySelector('.pre-loader');

		// $(window).on('load', function() {
		// 	content.fadeOut(1000);
		// 	preLoader.fadeIn(1000);
		// });

		// content.hide();
		// $('.content').hide();
	</script>
</body>

</html>