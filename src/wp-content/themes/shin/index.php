<?php get_header(); ?>
	<div class="container">
		<?php 
		if( appyn_options('home_description_top') ) {
			echo '<div class="hdt-t">'.wpautop( appyn_options('home_description_top') ).'</div>'; 
		} 
		?>
		<div class="sections">
			<?php do_action( 'do_home' ); ?>
		</div>
		<?php 
		if( appyn_options('home_description_bottom') ) {
			echo '<div class="hdt-b">'.wpautop( appyn_options('home_description_bottom') ).'</div>'; 
		}
        if( appyn_options( 'og_sidebar' ) ) {
            get_sidebar( 'general' ); 
        } ?>
	</div>
<?php get_footer(); ?>