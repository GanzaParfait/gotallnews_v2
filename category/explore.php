<?php
                                include "../backend/php/config.php";
                                $category_name = basename($_SERVER["REQUEST_URI"]);
                                $get_category_id = mysqli_query($con, "SELECT * FROM `category` WHERE `Category` = '$category_name'");
                                $get_category_id_row = mysqli_fetch_assoc($get_category_id);
                                $gp_category = intval($get_category_id_row["CategoryID"]);
                                include "../fill_category.php";
                            ?>