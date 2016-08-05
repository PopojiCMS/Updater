<?php
include_once 'po-includes/core/core.php';
$porequest = new PoRequest();
if (!empty($_POST)) {
	$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
	function getFileInfo($url){
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_NOBODY, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
		curl_exec( $ch );
		$headerInfo = curl_getinfo( $ch );
		curl_close( $ch );
		return $headerInfo;
	}
	function fileDownload($url, $destination){
		$fp = fopen ($destination, 'w+');
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_exec( $ch );
		curl_close( $ch );
		fclose( $fp );
		if (filesize($destination) > 0) return true;
	}
	function insert_into_file($file_path, $insert_marker, $text, $action) {
		$contents = file_get_contents($file_path);
		if ($action == 'after') {
			$new_contents = preg_replace($insert_marker, '$0'.$text, $contents);
		} elseif ($action == 'before') {
			$new_contents = preg_replace($insert_marker, $text.'$0', $contents);
		} else {
			$new_contents = str_replace($insert_marker, $text, $contents);
		}
		return file_put_contents($file_path, $new_contents);
	}
	switch($_GET['action']) {
		case 'db':
			$data = $_POST['data'];
			if ($data == 'permalink') {
				$sql = "SELECT * FROM setting WHERE id_setting = '29' AND groups = 'config' AND options = 'permalink'";
				$rs = $conn->query($sql);
				if ($rs->num_rows > 0) {
					echo 'Permalink data is already exist';
				} else {
					$conn->query("INSERT INTO setting (`id_setting`, `groups`, `options`, `value`) VALUES (29, 'config', 'permalink', 'slug/post-title')");
					echo '1';
				}
			} elseif ($data == 'slug_permalink') {
				$sql = "SELECT * FROM setting WHERE id_setting = '30' AND groups = 'config' AND options = 'slug_permalink'";
				$rs = $conn->query($sql);
				if ($rs->num_rows > 0) {
					echo 'Slug permalink data is already exist';
				} else {
					$conn->query("INSERT INTO setting (`id_setting`, `groups`, `options`, `value`) VALUES (30, 'config', 'slug_permalink', 'detailpost')");
					echo '1';
				}
			} elseif ($data == 'country') {
				$sql = "SHOW columns FROM traffic like 'country'";
				$rs = $conn->query($sql);
				if ($rs->num_rows > 0) {
					echo 'Country column is already exist';
				} else {
					$conn->query("ALTER TABLE traffic ADD country varchar(255) NOT NULL");
					echo '1';
				}
			} elseif ($data == 'city') {
				$sql = "SHOW columns FROM traffic like 'city'";
				$rs = $conn->query($sql);
				if ($rs->num_rows > 0) {
					echo 'City column is already exist';
				} else {
					$conn->query("ALTER TABLE traffic ADD city varchar(255) NOT NULL");
					echo '1';
				}
			}
			$rs->free();
			$conn->close();
		break;

		case 'file':
			$data = $_POST['data'];
			$url = "https://raw.githubusercontent.com/PopojiCMS/PopojiCMS/master/".$data;
			if (strpos($data, 'po-admin') !== false) {
				$file_path = str_replace('po-admin', DIR_ADM, $data);
			} elseif (strpos($data, 'po-content') !== false) {
				$file_path = str_replace('po-content', DIR_CON, $data);
			} elseif (strpos($data, 'po-includes') !== false) {
				$file_path = str_replace('po-includes', DIR_INC, $data);
			} else {
				$file_path = $data;
			}
			$fileInfo = getFileInfo($url);
			if ($fileInfo['http_code'] == 200) {
				if (fileDownload($url, $file_path)) {
					echo '1';
				} else {
					echo 'File is already exist';
				}
			} else {
				echo 'File error to added';
			}
		break;

		case 'config':
			$file_path = $_POST['data'];
			if (file_exists($file_path)) {
				if (fopen($file_path, 'a')) {
					$sql = "SELECT * FROM setting WHERE id_setting = '16'";
					$rs = $conn->query($sql);
					$timezone = $rs->fetch_assoc()['value'];
					$handle = fopen($file_path, 'w');
					$new_data = <<<EOS
<?php

\$site['structure'] = 'PopojiCMS';
\$site['ver'] = '2.0';
\$site['build'] = '1';
\$site['release'] = '07 Agustus 2016';

define('CONF_STRUCTURE', \$site['structure']);
define('CONF_VER', \$site['ver']);
define('CONF_BUILD', \$site['build']);
define('CONF_RELEASE', \$site['release']);

\$site['url'] = "{$site['url']}";
\$site['adm'] = "{$site['adm']}";
\$site['con'] = "{$site['con']}";
\$site['inc'] = "{$site['inc']}";

define('WEB_URL', \$site['url']);
define('DIR_ADM', \$site['adm']);
define('DIR_CON', \$site['con']);
define('DIR_INC', \$site['inc']);

\$db['host'] = "{$db['host']}";
\$db['driver'] = "mysql";
\$db['sock'] = "{$db['sock']}";
\$db['port'] = "{$db['port']}";
\$db['user'] = "{$db['user']}";
\$db['passwd'] = "{$db['passwd']}";
\$db['db'] = "{$db['db']}";

define('DATABASE_HOST', \$db['host']);
define('DATABASE_DRIVER', \$db['driver']);
define('DATABASE_SOCK', \$db['sock']);
define('DATABASE_PORT', \$db['port']);
define('DATABASE_USER', \$db['user']);
define('DATABASE_PASS', \$db['passwd']);
define('DATABASE_NAME', \$db['db']);

\$site['vqmod'] = FALSE;
\$site['timezone'] = "{$timezone}";
\$site['permalink'] = "slug/post-title";
\$site['slug_permalink'] = "detailpost";

define('VQMOD', \$site['vqmod']);
define('TIMEZONE', \$site['timezone']);
define('PERMALINK', \$site['permalink']);
define('SLUG_PERMALINK', \$site['slug_permalink']);

?>
EOS;
					fwrite($handle, $new_data);
					fclose($handle);
					$rs->free();
					$conn->close();
					echo '1';
				} else {
					echo 'Error setting config.php';
				}
			} else {
				echo 'Error setting config.php';
			}
		break;
	}
} else {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="imagetoolbar" content="no" />
    <meta http-equiv="Copyright" content="PopojiCMS" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="PopojiCMS Engine Updater" />
    <meta name="generator" content="PopojiCMS 2.0.1" />
    <meta name="author" content="Dwira Survivor" />
    <meta name="language" content="Indonesia" />
    <meta name="revisit-after" content="7" />
    <meta name="webcrawlers" content="all" />
    <meta name="rating" content="general" />
    <meta name="spiders" content="all" />
	<title>PopojiCMS Engine Updater</title>
	<link rel="shortcut icon" href="po-includes/images/favicon.png" />

	<link type="text/css" rel="stylesheet" href="po-includes/css/bootstrap.min.css" />
	<link type="text/css" rel="stylesheet" href="po-includes/css/font-awesome.min.css" />

	<script type="text/javascript" src="po-includes/js/jquery/jquery-2.1.4.min.js"></script>
	<script type="text/javascript" src="po-includes/js/bootstrap/bootstrap.min.js"></script>

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

	<style type="text/css">
		.header,body{padding-bottom:20px}.header,.jumbotron{border-bottom:1px solid #e5e5e5}body{padding-top:20px}.footer,.header,.marketing{padding-right:15px;padding-left:15px}.header h3{margin-top:0;margin-bottom:0;line-height:40px}.footer{padding-top:19px;color:#777;border-top:1px solid #e5e5e5}@media (min-width:768px){.container{max-width:800px}}.container-narrow>hr{margin:30px 0}.jumbotron{text-align:center}.jumbotron h1{font-size:50px}.jumbotron .lead{font-size:20px}.jumbotron .btn{padding:14px 24px;font-size:21px}.marketing{margin:40px 0}.marketing p+h4{margin-top:28px}@media screen and (min-width:768px){.footer,.header,.marketing{padding-right:0;padding-left:0}.header{margin-bottom:30px}.jumbotron{border-bottom:0}}.btn-update{padding:2px;font-size:10px}.fixed{position:fixed;top:0;left:0;width:100%;z-index:9999;}
	</style>
</head>
<body>
	<div class="container" id="wrap">
		<div class="jumbotron">
			<img src="po-includes/images/logo.png" class="center-block img-responsive" style="width:100px;" />
			<h1 class="hidden-xs">PopojiCMS Engine Updater</h1>
			<h3 class="visible-xs">PopojiCMS Engine Updater</h3>
			<p class="lead hidden-xs">This is engine updater for update PopojiCMS v.2.0.0 to v.2.0.1<br />Please backup your files and database before start the updater.</p>
		</div>

		<div class="row marketing">
			<?php if (!$porequest->check_internet_connection()) { ?>
			<div class="alert alert-warning">
				<p class="text-center">Please connect your computer to internet connection for update process!</p>
			</div>
			<p>&nbsp;</p>
			<?php } ?>
			<div id="progress_box">
				<div class="progress" style="height: 40px;">
					<div id="progress_bar" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; line-height: 40px; font-size: 15px;"></div>
				</div>
			</div>
			<ul class="nav nav-tabs nav-justified" role="tablist">
				<li role="presentation" class="active"><a href="#update-database" aria-controls="update-database" role="tab" data-toggle="tab">Update Database</a></li>
				<li role="presentation"><a href="#add-file" aria-controls="add-file" role="tab" data-toggle="tab">Add File</a></li>
				<li role="presentation"><a href="#update-file" aria-controls="update-file" role="tab" data-toggle="tab">Update File</a></li>
			</ul>
			<div class="tab-content">
				<div role="tabpanel" class="tab-pane fade in active" id="update-database">
					<p>&nbsp;</p>
					<div class="panel panel-default">
						<div class="panel-heading">In Tabel 'setting'</div>
						<ul class="list-group">
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;Add data permalink = slug/post-title
								<a class="btn btn-sm btn-success btn-update pull-right" id="1" data-act="db" data-up="permalink" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;Add data slug_permalink = detailpost
								<a class="btn btn-sm btn-success btn-update pull-right" id="2" data-act="db" data-up="slug_permalink" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
						</ul>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading">In Tabel 'traffic'</div>
						<ul class="list-group">
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;Add field 'country' varchar(255)
								<a class="btn btn-sm btn-success btn-update pull-right" id="3" data-act="db" data-up="country" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;Add field 'city' varchar(255)
								<a class="btn btn-sm btn-success btn-update pull-right" id="4" data-act="db" data-up="city" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
						</ul>
					</div>
				</div>
				<div role="tabpanel" class="tab-pane fade" id="add-file">
					<p>&nbsp;</p>
					<div class="panel panel-default">
						<div class="panel-heading">Add New File</div>
						<ul class="list-group">
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/profile.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="5" data-act="file" data-up="po-content/themes/member/profile.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/images/bg-profile.jpg
								<a class="btn btn-sm btn-success btn-update pull-right" id="6" data-act="file" data-up="po-includes/images/bg-profile.jpg" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
						</ul>
					</div>
				</div>
				<div role="tabpanel" class="tab-pane fade" id="update-file">
					<p>&nbsp;</p>
					<div class="panel panel-default">
						<div class="panel-heading">Update Old File</div>
						<ul class="list-group">
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;index.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="7" data-act="file" data-up="index.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;maintenance.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="8" data-act="file" data-up="maintenance.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-admin/admin.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="9" data-act="file" data-up="po-admin/admin.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-admin/index.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="10" data-act="file" data-up="po-admin/index.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-admin/login.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="11" data-act="file" data-up="po-admin/login.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-admin/route.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="12" data-act="file" data-up="po-admin/route.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/category/category.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="13" data-act="file" data-up="po-content/component/category/category.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/comment/admin_comment.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="14" data-act="file" data-up="po-content/component/comment/admin_comment.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/comment/comment.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="15" data-act="file" data-up="po-content/component/comment/comment.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/contact/admin_contact.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="16" data-act="file" data-up="po-content/component/contact/admin_contact.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/home/admin_home.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="17" data-act="file" data-up="po-content/component/home/admin_home.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/home/admin_javascript.js
								<a class="btn btn-sm btn-success btn-update pull-right" id="18" data-act="file" data-up="po-content/component/home/admin_javascript.js" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/home/home.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="19" data-act="file" data-up="po-content/component/home/home.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/pages/admin_pages.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="20" data-act="file" data-up="po-content/component/pages/admin_pages.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/post/admin_javascript.js
								<a class="btn btn-sm btn-success btn-update pull-right" id="21" data-act="file" data-up="po-content/component/post/admin_javascript.js" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/post/admin_post.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="22" data-act="file" data-up="po-content/component/post/admin_post.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/post/post.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="23" data-act="file" data-up="po-content/component/post/post.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/setting/admin_javascript.js
								<a class="btn btn-sm btn-success btn-update pull-right" id="24" data-act="file" data-up="po-content/component/setting/admin_javascript.js" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/setting/admin_setting.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="25" data-act="file" data-up="po-content/component/setting/admin_setting.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/user/admin_user.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="26" data-act="file" data-up="po-content/component/user/admin_user.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/component/user/user.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="27" data-act="file" data-up="po-content/component/user/user.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/lang/home/gb.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="28" data-act="file" data-up="po-content/lang/home/gb.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/lang/home/id.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="29" data-act="file" data-up="po-content/lang/home/id.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/lang/setting/gb.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="30" data-act="file" data-up="po-content/lang/setting/gb.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/lang/setting/id.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="31" data-act="file" data-up="po-content/lang/setting/id.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/category.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="32" data-act="file" data-up="po-content/themes/chingsy/category.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/detailpost.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="33" data-act="file" data-up="po-content/themes/chingsy/detailpost.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/footer.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="34" data-act="file" data-up="po-content/themes/chingsy/footer.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/home.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="35" data-act="file" data-up="po-content/themes/chingsy/home.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/index.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="36" data-act="file" data-up="po-content/themes/chingsy/index.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/search.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="37" data-act="file" data-up="po-content/themes/chingsy/search.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/sidebar.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="38" data-act="file" data-up="po-content/themes/chingsy/sidebar.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/tag.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="39" data-act="file" data-up="po-content/themes/chingsy/tag.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/chingsy/css/style.css
								<a class="btn btn-sm btn-success btn-update pull-right" id="40" data-act="file" data-up="po-content/themes/chingsy/css/style.css" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/activation.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="41" data-act="file" data-up="po-content/themes/member/activation.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/addpost.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="42" data-act="file" data-up="po-content/themes/member/addpost.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/editpost.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="43" data-act="file" data-up="po-content/themes/member/editpost.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/forgot.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="44" data-act="file" data-up="po-content/themes/member/forgot.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/index.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="45" data-act="file" data-up="po-content/themes/member/index.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/login.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="46" data-act="file" data-up="po-content/themes/member/login.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/recover.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="47" data-act="file" data-up="po-content/themes/member/recover.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/themes/member/register.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="48" data-act="file" data-up="po-content/themes/member/register.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/widget/menu/menu.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="49" data-act="file" data-up="po-content/widget/menu/menu.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-content/widget/post/post.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="50" data-act="file" data-up="po-content/widget/post/post.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/core/core.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="51" data-act="file" data-up="po-includes/core/core.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/core/datetime.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="52" data-act="file" data-up="po-includes/core/datetime.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/core/string.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="53" data-act="file" data-up="po-includes/core/string.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/core/vendor/dynamicmenu/front_menu.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="54" data-act="file" data-up="po-includes/core/vendor/dynamicmenu/front_menu.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/core/vendor/plates/autoload.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="55" data-act="file" data-up="po-includes/core/vendor/plates/autoload.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/core/vendor/plates/Template/Template.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="56" data-act="file" data-up="po-includes/core/vendor/plates/Template/Template.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/css/member.css
								<a class="btn btn-sm btn-success btn-update pull-right" id="57" data-act="file" data-up="po-includes/css/member.css" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/js/filemanager/config/config.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="58" data-act="file" data-up="po-includes/js/filemanager/config/config.php" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/js/tinymce/skins/lightgray/skin.min.css
								<a class="btn btn-sm btn-success btn-update pull-right" id="59" data-act="file" data-up="po-includes/js/tinymce/skins/lightgray/skin.min.css" href="javascript:void(0)"><i class="fa fa-check"></i> Update Now</a>
							</li>
							<li class="list-group-item">
								<i class="fa fa-plus-square-o"></i>&nbsp;&nbsp;po-includes/core/config.php
								<a class="btn btn-sm btn-success btn-update pull-right" id="60" data-act="config" data-up="po-includes/core/config.php" href="javascript:void(0)"><i class="fa fa-check"></i> Setting Now</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12 text-center">
				<a href="./" class="btn btn-sm btn-info"><i class="fa fa-home"></i>&nbsp;&nbsp;Back To Main Site</a>
			</div>
			<p>&nbsp;</p>
		</div>

		<footer class="footer text-center">
			<p>&copy; 2013-2016 PopojiCMS. All Right Reserved</p>
		</footer>
	</div>

	<script type="text/javascript">
		var stickyOffset = $('#progress_box').offset().top;
		$(window).scroll(function(){
			var sticky = $('#progress_box'),
				scroll = $(window).scrollTop();
			if (scroll >= stickyOffset) sticky.addClass('fixed');
			else sticky.removeClass('fixed');
		});

		$(document).ready(function() {
			function progressbar_clear(){
				$('#progress_bar').removeClass('progress-bar-success');
				$('#progress_bar').removeClass('progress-bar-danger');
				$('#progress_bar').addClass('progress-bar-info');
				$('#progress_bar').attr('aria-valuenow', '0');
				$('#progress_bar').css('width', '0%');
				$('#progress_bar').html('');
			}

			function progressbar_success(){
				$('#progress_bar').removeClass('progress-bar-info');
				$('#progress_bar').removeClass('progress-bar-danger');
				$('#progress_bar').addClass('progress-bar-success');
				$('#progress_bar').attr('aria-valuenow', '100');
				$('#progress_bar').css('width', '100%');
				$('#progress_bar').html('Success');
			}

			function progressbar_error(res){
				$('#progress_bar').removeClass('progress-bar-info');
				$('#progress_bar').removeClass('progress-bar-success');
				$('#progress_bar').addClass('progress-bar-danger');
				$('#progress_bar').attr('aria-valuenow', '100');
				$('#progress_bar').css('width', '100%');
				$('#progress_bar').html(res);
			}

			$(".btn-update").click(function(){
				var element = $(this);
				var action = element.attr("data-act");
				var data = element.attr("data-up");
				$.ajax({
					type: "POST",
					url: "update.php?action="+action,
					data: 'data='+ data,
					cache: false,
					beforeSend: progressbar_clear(),
					xhr: function () {
						var xhr = new window.XMLHttpRequest();
						xhr.upload.addEventListener("progress", function(evt){
							if (evt.lengthComputable) {
								var percentComplete = evt.loaded / evt.total;
								$('#progress_bar').attr('aria-valuenow', percentComplete);
								$('#progress_bar').css('width', percentComplete+'%');
								$('#progress_bar').html(percentComplete+'%');
							}
						}, false);
						xhr.addEventListener("progress", function (evt) {
							if (evt.lengthComputable) {
								var percentComplete = evt.loaded / evt.total;
								$('#progress_bar').attr('aria-valuenow', percentComplete);
								$('#progress_bar').css('width', percentComplete+'%');
								$('#progress_bar').html(percentComplete+'%');
							}
						}, false);
						return xhr;
					},
					success: function(res){
						if (res == '1') {
							progressbar_success();
							element.removeClass('btn-success');
							element.removeClass('btn-danger');
							element.addClass('btn-info');
							element.html('<i class="fa fa-check"></i> Success');
						} else {
							progressbar_error(res);
							element.removeClass('btn-success');
							element.removeClass('btn-info');
							element.addClass('btn-danger');
							element.html('<i class="fa fa-refresh"></i> Retry');
						}
					}
				});
			});
		});
	</script>
</body>
</html>
<?php } ?>