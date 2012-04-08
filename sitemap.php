<?php
include_once "include/cm.CommonFunctions.php";
require_once $CONFIG['includes']."cm.SitemapGenerator.php";



if(!isset($_GET['OutputFormat']) || $_GET['OutputFormat'] != "xml") {
header("Content-Type: text/html;charset=UTF-8");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<link rel="stylesheet" href="<?php echo $CONFIG['relurl'];?>css/sitemap.css" />
		<title>SiteMap UI [<?php echo $CONFIG['sitename'];?>]</title>
		<link rel="stylesheet" href="<?php echo $CONFIG['relurl'];?>css/standard.css" />
	</head>
	<body class="ui">
		<div id="headWrap">
			<div class="logo-bg graphicsLayer">
				<img src="gfx/logo.png" />
				<div class="logo-shadow"></div>
			</div>
		</div><div id="bodyWrap" class="contents">
		<?php echo getSitemap("html", $_GET['cat']);?>
		</div>
	</body>
</html>

<?php 
} else { 
	header("Content-Type: text/xml;charset=UTF-8");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

	<?php echo getSitemap("xml", $_GET['cat']); ?>

</urlset>
<?php
}
?>
