<?php

if( ! defined( 'ABSPATH' ) ) die ( '✋' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table_ModApps extends WP_List_Table {

	var $total_posts;
	var $total_posts_import;
	var $total_posts_noimport;
	var $response;
	var $error = false;
	var $posts;
	var $has_title = false;
	var $list_ids;
	var $is_search = false;
	var $status;
	var $response_body;

	public function __construct() {

		parent::__construct( [
			'singular' => 'app_to_import',
			'plural'   => 'apps_to_import',
			'ajax'     => false
		] );
		
		$s = ( isset($_GET['s']) ) ? $_GET['s'] : '';
		if( $s ) {
			$url = API_URL.'/v2/mod/search/'.urlencode($s);
			$this->is_search = true;
		} else {
			$url = API_URL.'/v2/mod/latestapps';
		}
	
		$this->status = ( isset($_GET['status']) ) ? $_GET['status'] : null;

		$response = function() use ($url) {
			return wp_remote_post( $url, array(
				'method'      => 'POST',
				'timeout'     => 30,
				'blocking'    => true,
				'sslverify'   => false,
				'headers'     => array(
					'Referer' 		=> get_site_url(),
					'Cache-Control' => 'max-age=0',
					'Expect' 		=> '',
				),
				'body' => array( 
					'apikey' 	=> appyn_options( 'apikey', true ), 
					'website'	=> get_site_url(),
				),
			) );
		};

		if( ! $this->is_search ) {
			if( ! get_transient( 'appyn_results_latest_mod_apps' ) ) {
				$response = $response();
				set_transient( 'appyn_results_latest_mod_apps', $response, 60 * MINUTE_IN_SECONDS );
			} else {
				$response = get_transient( 'appyn_results_latest_mod_apps' );

				if( count($response) == 0 ) {
					$response = $response();
					set_transient( 'appyn_results_latest_mod_apps', $response, 60 * MINUTE_IN_SECONDS );
				}
			}
		} else {
			$response = $response();
		}

		global $post;
		$query = new WP_Query( array( 'posts_per_page' => -1, 'post_parent' => 0, 'suppress_filters' => true, 'cache_results'  => false, 'meta_key' => 'mod_app_id' ) );

		$this->list_ids = array();

		if( $query->have_posts() ) :
			while( $query->have_posts() ) : $query->the_post();
				$mod_app_id = get_post_meta( $post->ID, 'mod_app_id', true );
				$this->list_ids[$mod_app_id] = $post->ID;
			endwhile;
		endif;

		if ( is_wp_error( $response ) ) {
			$response = $response->get_error_message();
		}
		
		$this->response = $response;
		$this->response_body = json_decode( $this->response['body'], true );

		if( $this->response_body['status'] == 'error' ) {
			$this->response = $this->response_body['response'];
			return;
		}
		
		$this->total_posts = $this->response_body['total_results'];

		if( $this->total_posts == 0 ) {
			$this->response = false;
		}

		if( ! $this->is_search ) {
			set_transient( 'total_posts_latest', $this->total_posts, 24 * HOUR_IN_SECONDS );
		}

		$this->apps_by_status( $this->response_body['results'] );
	}

	private function clean_result_search( $results ) {
		$nr = array();
		foreach( $results as $res ) {
			foreach( $res as $re ) {
				$nr[] = $re;
			}
		}
		return $nr;
	}

	public function get_apps_to_import() {
		if( !isset($this->response_body['results']) ) return;

		if( $this->is_search ) {
			$r = array();
			
			$results = $this->clean_result_search( $this->response_body['results'] );
			
			if( $this->status == 'imported' ) {
				foreach( $results as $res ) {
					if( array_key_exists( $res['u'], $this->list_ids ) ) {
						$r[] = $res;
					}
				}
				if( empty($r) ) {
					$this->response = false;
				}
				return $r;
			} elseif( $this->status == 'no-imported' ) {
				foreach( $results as $res ) {
					if( ! array_key_exists( $res['u'], $this->list_ids ) ) {
						$r[] = $res;
					}
				}
				if( empty($r) ) {
					$this->response = false;
				}
				return $r;
			}
			return $results;
		} else {
			$r = array();

			if( $this->status == 'imported' ) {
				foreach( $this->response_body['results'] as $res ) {
					if( array_key_exists( $res['u'], $this->list_ids ) ) {
						$r[] = $res;
					}
				}
				if( empty($r) ) {
					$this->response = false;
				}
				return $r;
			} elseif( $this->status == 'no-imported' ) {
				foreach( $this->response_body['results'] as $res ) {
					if( ! array_key_exists( $res['u'], $this->list_ids ) ) {
						$r[] = $res;
					}
				}
				if( empty($r) ) {
					$this->response = false;
				}
				return $r;
			}
			return $this->response_body['results'];
		}
	}

	private function apps_by_status( $results ) {

		if( $this->is_search ) {
			$results = $this->clean_result_search( $results );
		}

		$a = 0;
		foreach( $results as $res ) {
			if( array_key_exists( $res['u'], $this->list_ids ) ) {
				$a++;
			}
		}
		$this->total_posts_import = $a;
		
		$b = 0;
		foreach( $results as $res ) {
			if( ! array_key_exists( $res['u'], $this->list_ids ) ) {
				$b++;
			}
		}
		$this->total_posts_noimport = $b;
	}

	function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />', 'apps_to_import',  $item['u']
        );
    }
	public function no_items() {
		echo ( $this->response ) ? $this->response : __( 'No hay apps modificadas', 'appyn' );
	}

	public function column_default( $item, $column_name ) {
        $impt = '';
		$chck = ( array_key_exists( $item['u'], $this->list_ids ) ) ? true : false;
		if( $chck ) {
			$impt = '<a href="'.get_edit_post_link( $this->list_ids[$item['u']] ).'" target="_blank" title="'.__('View post').'"><i class="fas fa-file-import"></i></a>';
		}
		switch ( $column_name ) {
			case 'post_title':
				return '<div><span><img src="'.$item['img'].'" width="50" height="50"></span><span>' .$item['title'].'</span> '.$impt.'</div>';
				break;
			
			case 'version':
				return $item[$column_name];
				break;

			case 'import':
				return '<button type="button" class="button mod_app_import" data-u="'.$item['u'].'" '.( ( $chck ) ? 'data-post-id="'.$this->list_ids[$item['u']].'" data-import-type="reimport"' : '').'>'.( ( $chck ) ? __( 'Re-importar', 'appyn' ) : __( 'Importar', 'appyn' ) ).'</button>';
				break;

			default:
				return '';
		}
	}

	public function get_columns() {
		$columns = [
            'cb' => '<input type="checkbox" />',
			'post_title' => __( 'App', 'appyn' ).( ( isset($this->response_body['id']) ) ? '<input type="hidden" id="result_id" value="'.$this->response_body['id'].'">' : ''),
			'version' => __( 'Versión', 'appyn' ),
			'import' => '',
		];

		return $columns;
	}

    public function get_bulk_actions() {
        return array(
			'import' => __( 'Importar', 'appyn' ),
        );
    }

	public function search_box( $text, $input_id ) {
 
        $input_id = $input_id . '-search-input';
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
				<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
        <?php
    }

	public function prepare_items() {
        $columns = $this->get_columns(); 
        $hidden   = array( 'id' );
        $this->_column_headers = array( $columns, $hidden ); 

		$per_page     = $this->get_items_per_page( 'apps_to_import_per_page', 100 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->total_posts ? $this->total_posts : 0;

		if( ! $this->status ) {
			$this->set_pagination_args( [
				'total_items' => $total_items,
				'per_page'    => $per_page,
			] );
		}

		$tpl = ( get_transient( 'total_posts_latest' ) ) ? get_transient( 'total_posts_latest' ) : 0;
		$tpi = ( $this->total_posts_import ) ? $this->total_posts_import : 0;
		$tpni = ( $this->total_posts_noimport ) ? $this->total_posts_noimport : 0;

		echo '<ul class="subsubsub">';
			if( $this->is_search ) {
				echo '<li class="all"><a href="'.remove_query_arg( array('status', 's') ).'">'.__( 'Últimas apps modificadas', 'appyn' ).' <span class="count">('.$tpl.')</span></a></li> ';
			}
		echo '<li class="all"><a href="'.remove_query_arg( 'status' ).'"'.( ( ! $this->status ) ? ' class="current"' : '').'>'.__( 'Todos', 'appyn' ).' <span class="count">('.$total_items.')</span></a></li>
			<li class="all"><a href="'.add_query_arg( 'status', 'imported' ).'" '.( ( $this->status == 'imported' ) ? ' class="current"' : '').'>'.__( 'Importados', 'appyn' ).' <span class="count">('.$tpi.')</span></a></li>
			<li class="all"><a href="'.add_query_arg( 'status', 'no-imported' ).'" '.( ( $this->status == 'no-imported' ) ? ' class="current"' : '').'>'.__( 'No importados', 'appyn' ).' <span class="count">('.$tpni.')</span></a></li>
		</ul>';
		
		$this->items = self::get_apps_to_import( $per_page, $current_page );
	}

}