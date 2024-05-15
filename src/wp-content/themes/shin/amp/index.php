<?php get_header(); ?>
    <div class="container">
		<?php 
		if( appyn_options('home_description_top') ) {
			echo '<div class="hdt-t">'.wpautop( appyn_options('home_description_top') ).'</div>'; 
		} 
		?>
        <?php echo do_action( 'do_home' ); ?>
		<?php 
		if( appyn_options('home_description_bottom') ) {
			echo '<div class="hdt-b">'.wpautop( appyn_options('home_description_bottom') ).'</div>'; 
		}
        ?>
    </div>
<?php get_footer(); ?>