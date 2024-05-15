<?php 
/*
Template name: Most viewed apps
*/
get_header(); ?>
	<div class="container">
   		<div class="app-p"<?php echo ( appyn_gpm( $post->ID, 'appyn_hidden_sidebar' ) ) ? ' style="margin-right:0;"' : ''; ?>>
            <div class="section">
                <div class="title-section">
                    <?php the_title(); ?>
                </div>
                <div class="ct_description"><?php the_content(); ?></div>
                <?php
                $aprpc = appyn_options( 'apps_per_row_pc', 6 );

                $paged = 0 == get_query_var('paged') ? 1 : get_query_var('paged');

                $args = array( 
                    'paged' => $paged, 
                    'meta_key' => 'px_views', 
                    'orderby' => 'meta_value_num',
                    'ignore_sticky_posts' => true,
                );

                if( appyn_options( 'versiones_mostrar_amc' ) ) {
                    $args['post_parent'] = 0;
                }
                
                $query = new WP_Query( $args );
                
                if( $query->have_posts() ) :
                ?> 
                <div class="baps" data-cols="<?php echo $aprpc; ?>">
                    <?php
                    while( $query->have_posts() ) : $query->the_post();
                        get_template_part( 'template-parts/loop/app' );
                    endwhile;
                    wp_reset_query();
                    ?>
                </div>
                <?php
                paginador( $query );
                else : 
                    echo '<div class="no-entries"><p>'.__( 'No hay entradas', 'appyn' ).'</p></div>';
                endif; 
                ?>
            </div>
        </div>
		<?php if( ! appyn_gpm( $post->ID, 'appyn_hidden_sidebar' ) ) get_sidebar(); ?>
   </div>
<?php get_footer(); ?>