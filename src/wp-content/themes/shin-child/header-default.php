<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<div class="wrapper-page">
		<div id="menu-mobile" style="display:none;">
			<?php px_nav_menu('mobile'); ?>
		</div>
		<div class="wrapper-inside">
			<header id="header">
				<div class="container">
					<div class="logo">
						<a href="<?php bloginfo('url'); ?>" style="display:flex; align-items: center;"><?php px_logo(); ?>
							<p class="head-top-title">HappyMod</p>
						</a>
					</div>
					<?php px_nav_menu(); ?>
					<div class="appie-btn-box text-right"><a class="main-btn ml-30 dlink" href="javascript:;">Get Started <i class="right-click"></i></a>
						<div class="toggle-btn ml-30 d-lg-none d-block"><i class="fa menu_click"></i></div>
					</div>
				</div>
			</header>
		</div>


		<?php
		if (!isset($args['ws'])) {
			do_action('subheader');
			echo px_ads('ads_header');
		} ?>
		<main id="main-site">
