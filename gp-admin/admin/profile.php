<?php
include "php/header/top.php";


if (isset($_POST['changeprofile'])) {
	$uid = mysqli_real_escape_string($con, $_POST['uid']);
	$fname = mysqli_real_escape_string($con, $_POST['fname']);
	$lname = mysqli_real_escape_string($con, $_POST['lname']);
	$gender = mysqli_real_escape_string($con, $_POST['gender']);
	$email = mysqli_real_escape_string($con, $_POST['email']);
	$chatlink = mysqli_real_escape_string($con, $_POST['chatlink']);
	$phonenumber = mysqli_real_escape_string($con, $_POST['phone']);


	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$update = mysqli_query($con, "UPDATE `admin` SET `FirstName`='$fname',`LastName`='$lname',`PhoneNumber`='$phonenumber',`Email`='$email',
		`Gender`='$gender',`ChatLink` = '$chatlink' WHERE `AdminId`='$uid'");
		if ($update) {
			header('Location: profile.php?msg=Your profile has been Changed successfully!');
		} else {
			header('Location: profile.php?msg=Sorry! Something went wrong in changing your profile.' . mysqli_error($con));
		}

		if (isset($_FILES['image'])) {
			$file_name = $_FILES['image']['name'];
			$tmp_name = $_FILES['image']['tmp_name'];

			$img_explode = explode('.', $file_name);
			$img_extension = strtolower(end($img_explode));

			$extensions = ['png', 'jpeg', 'jpg', 'gif'];

			if (in_array($img_extension, $extensions) === true) {
				$time = time();
				$new_file_name = $time . $file_name;

				if (move_uploaded_file($tmp_name, 'php/userimages/' . $new_file_name)) {
					$update_user_info = mysqli_query($con, "UPDATE `admin` SET `FirstName`='$fname',`LastName`='$lname',`PhoneNumber`='$phonenumber',`Email`='$email',
					`Gender`='$gender',`Profile`='$new_file_name',`ChatLink` = '$chatlink' WHERE `AdminId`='$uid'");
				} else {
					header('Location: profile.php?msg=Something Went wrong!');
				}
			}
		}
	} else {
		header('Location: profile.php?msg=' . $email . ' - This email is invalid!');
	}
}

if (isset($_POST['changepassword'])) {
	$uid = mysqli_real_escape_string($con, $_POST['uid']);
	$currentpass = mysqli_real_escape_string($con, $_POST['currentpass']);
	$pass1 = $user['Password'];
	$newpass = mysqli_real_escape_string($con, $_POST['newpass']);
	$confirmnewpass = mysqli_real_escape_string($con, $_POST['confirmnewpass']);

	if (password_verify($currentpass, $pass1)) {
		if ($newpass != $confirmnewpass) {
			header("Location: profile.php?msg=Password doesn't match.");
		} elseif (strlen($newpass) < 4) {
			header("Location: profile.php?msg=Password is too short at least 4 characters.");
		} else {
			$hpass = password_hash($newpass, PASSWORD_DEFAULT);
			$update = mysqli_query($con, "UPDATE `admin` SET `Password` = '$hpass' WHERE `AdminId` = '$uid'");

			if ($update) {
				header("Location: profile.php?msg=Password Changed Successfully.");
			} else {
				header("Location: profile.php?msg=Something went wrong in changing password.");
			}
		}
	} else {
		header("Location: profile.php?msg=Sorry, Incorrect Current Password. Try Again.");
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
	include "php/includes/header.php";
	?>

	<div class="left-side-bar">
		<div class="brand-logo">
			<a href="index.php">
				<img src="images/logo.png" width="200" alt="logo">
				<!-- <span style="color:#444;padding: 0 10px;">Logo</span> -->
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
						<a href="view_received_message.php" class="dropdown-toggle no-arrow">
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
			<div class="card mb-5">
				<div class="min-height-200px">
					<h5 class="p-3">Profile Details</h5>
					<!-- Account -->
					<div class="card-body">
						<div class="d-flex align-items-start align-items-sm-center gap-4">
							<div class="mr-3">
								<?php
								$profile = 'php/userimages/' . $user['Profile'] . '';
								$default_image_url = '<img src="php/defaultavatar/avatar.png" alt="user-avatar" class="d-block rounded"
								height="100" width="100" id="uploadedAvatar">';

								if (file_exists($profile)) {
									echo '<img src="' . $profile . '" alt="user-avatar" class="d-block rounded"
									height="100" width="100" id="uploadedAvatar">';
								} else {
									echo $default_image_url;
								}
								?>
							</div>

							<form action="profile.php" method="post" enctype="multipart/form-data">
								<div class="button-wrapper">
									<label for="upload" class="btn btn-primary me-2 mb-4" tabindex="0">
										<span class="d-none d-sm-block">Upload new photo</span>
										<i class="icon-copy fa fa-cloud-upload d-block d-sm-none"
											aria-hidden="true"></i>
										<input type="file" id="upload" name="image" class="account-file-input" hidden
											accept="image/png, image/jpeg" />
									</label>
									<button type="button" class="btn btn-outline-secondary account-image-reset mb-4">
										<i class="icon-copy fa fa-refresh d-block d-sm-none" aria-hidden="true"></i>
										<span class="d-none d-sm-block">Reset</span>
									</button>

									<p class="text-muted mb-0">Allowed JPG, GIF or PNG. Max size of 800K</p>
								</div>
						</div>
						<?php
						if (isset($_GET['msg'])) {
						?>
							<div class="alert alert-primary alert-dismissible fade show mt-3" role="alert">
								<strong>Profile Changing!</strong> <?= $_GET['msg']; ?>
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						<?php
						} else {
							echo "";
						}
						?>
					</div>
					<hr class="my-0" />
					<div class="p-3">

						<div class="row">
							<input type="hidden" name="uid" value="<?= $user['AdminId']; ?>">
							<div class="col-md-6">
								<div class="form-group">
									<label>FirstName:</label>
									<input class="form-control" type="text" name="fname"
										value="<?= $user['FirstName']; ?>" required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>LastName:</label>
									<input class="form-control" type="text" name="lname"
										value="<?= $user['LastName']; ?>" required>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Gender</label>
									<select name="gender" class="form-control color-picker" id="gender">
										<option value="<?= $user['Gender']; ?>" hidden><?= ucfirst($user['Gender']); ?>
										</option>
										<option value="male">Male</option>
										<option value="female">Female</option>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>PhoneNumber:</label>
									<div class="row">
										<div class="col-md-2 mb-2">
											<input type="text" readonly value="+250" class="form-control">
										</div>
										<div class="col-md-10">
											<input class="form-control" type="text" name="phone" maxlength="10"
												value="<?= $user['PhoneNumber']; ?>" required>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Email:</label>
									<input class="form-control" type="email" name="email" value="<?= $user['Email']; ?>"
										required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Chat Link<span class="text-muted">(Link where a reader from website can reach you easily.)</span>:</label>
									<input class="form-control" type="text" name="chatlink" value="<?= $user['ChatLink']; ?>"
										required>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-10 mb-2">
								<button type="submit" name="changeprofile" class="btn btn-outline-primary"
									style="width: 100%;">Change Profile</button>
							</div>
							<div class="col-md-2">
								<a href="change-password.php" class="btn btn-primary" style="width: 100%;"
									data-backdrop="static" data-toggle="modal" data-target="#login-modal">Password</a>
							</div>
						</div>
						</form>
					</div>
				</div>
				<!-- /Account -->
				<!-- Reset Password modal -->
				<div class="col-md-4 col-sm-12 mb-30">
					<div class="modal fade" id="login-modal" tabindex="-1" role="dialog"
						aria-labelledby="myLargeModalLabel" aria-hidden="true">
						<div class="modal-dialog modal-dialog-centered">
							<div class="modal-content">
								<div class="login-box bg-white box-shadow border-radius-10">
									<div class="login-title">
										<h2 class="text-center text-primary">Reset Password</h2>
									</div>
									<h6 class="mb-20">Enter your current password, new, confirm and submit.</h6>
									<form action="profile.php" method="post" autocomplete="off">
										<input type="hidden" name="uid" value="<?= $user['AdminId']; ?>">
										<div class="input-group custom">
											<input type="password" name="currentpass"
												class="form-control form-control-md" placeholder="Current Password"
												required />
											<div class="input-group-append custom">
												<span class="input-group-text"><i class="dw dw-padlock1"></i></span>
											</div>
										</div>
										<div class="input-group custom">
											<input type="password" name="newpass" class="form-control form-control-md"
												placeholder="New Password" required />
											<div class="input-group-append custom">
												<span class="input-group-text"><i class="dw dw-padlock1"></i></span>
											</div>
										</div>
										<div class="input-group custom">
											<input type="password" name="confirmnewpass"
												class="form-control form-control-md" placeholder="Confirm New Password"
												required />
											<div class="input-group-append custom">
												<span class="input-group-text"><i class="dw dw-padlock1"></i></span>
											</div>
										</div>
										<div class="row">
											<div class="col-md-6 mb-2">
												<button type="submit" class="btn btn-primary btn-lg btn-block"
													name="changepassword">Submit</button>

											</div>
											<div class="col-md-6">
												<button type="button" style="width: 100%;" class="btn btn-secondary"
													data-dismiss="modal">
													Close
												</button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
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