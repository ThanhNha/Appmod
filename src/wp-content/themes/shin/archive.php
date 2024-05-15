<?php get_header(); ?>
	<div class="container">
        <div class="app-p">
            <div class="section">
                <div class="title-section">
                    <?php
                    if( is_year() ){
                        echo __( 'Apps', 'appyn' ).': '.get_the_time('Y');    
                    } 
                    if( is_month() ){
                        echo __( 'Apps', 'appyn' ).': '.get_the_time('F, Y');
                    } 
                    if( is_author() ){
                        echo __( 'Apps de', 'appyn' ).': '.get_the_author();
                    } else {
                        echo post_type_archive_title( '', false );
                    }
                    ?>
                </div>
                <?php
                if( have_posts() ):
                    $aprpc = appyn_options( 'apps_per_row_pc', 6 );
                ?> 
                <div class="baps" data-cols="<?php echo $aprpc; ?>">
                    <?php
                    while( have_posts() ) : the_post();
                        get_template_part( 'template-parts/loop/app' );
                    endwhile;
                    ?>
                </div>
                <?php paginador();
                else: 
                    echo '<div class="no-entries"><p>'.__( 'No hay entradas', 'appyn' ).'</p></div>';
                endif; ?>
            </div>
        </div>
        <?php 
        if( appyn_options( 'og_sidebar' ) ) {
            get_sidebar( 'general' ); 
        } ?>
   </div>
<?php get_footer(); ?>