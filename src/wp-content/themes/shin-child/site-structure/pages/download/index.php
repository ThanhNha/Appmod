<div class="container">
  <div class="sections">
    <div class="section">

      <div class="entry homepage">

        <div class="app-icb data-app baps">
          <div class="heading-old-version">
            <h2>Old Version</h2>
          </div>

          <?php

          if (have_rows('old_version_list', 'option')) : ?>

            <div class="old-versions app-s">
              <?php
              // Loop through rows.
              while (have_rows('old_version_list', 'option')) : the_row();

                // Load sub field value.
                $link = get_sub_field('link');
                $version_name = get_sub_field('version_name');

              ?>
                <div class="da-s">
                  <b>Version</b>
                  <br>
                  <a href="<?php echo $link; ?>"><?php echo $version_name; ?></a>
                </div>
            <?php
              // Do something, but make sure you escape the value if outputting directly...

              // End loop.
              endwhile;

            // No value.
            else :
            // Do something...
            endif;
            ?>
            </div>



        </div>
        <?php the_content(); ?>

      </div>
      <div class="title-section">Apps</div>
      <?php
      $aprpc = appyn_options('apps_per_row_pc', 6);

      $paged = 0 == get_query_var('paged') ? 1 : get_query_var('paged');

      $args = array(
        'paged' => $paged,
        'meta_key' => 'px_views',
        'orderby' => 'meta_value_num',
        'ignore_sticky_posts' => true,
      );

      if (appyn_options('versiones_mostrar_amc')) {
        $args['post_parent'] = 0;
      }

      $query = new WP_Query($args);

      if ($query->have_posts()) :
      ?>
        <div class="baps" data-cols="<?php echo $aprpc; ?>">
          <?php
          while ($query->have_posts()) : $query->the_post();
            get_template_part('template-parts/loop/app');
          endwhile;
          wp_reset_query();
          ?>
        </div>
      <?php
      else :
        echo '<div class="no-entries"><p>' . __('No hay entradas', 'appyn') . '</p></div>';
      endif;
      ?>
    </div>
  </div>

</div>
