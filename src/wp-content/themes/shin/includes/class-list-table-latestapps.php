<?php

if( ! defined( 'ABSPATH' ) ) die ( '✋' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table_LatestApps extends WP_List_Table {

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
			'singular' => 'gplay_app_to_import',
			'plural'   => 'gplay_apps_to_import',
			'ajax'     => false
		] );
		
		$url = API_URL.'/v2/gplay/latestapps';
	
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

		if( ! get_transient( 'appyn_results_latest_gplay_apps' ) ) {
			$response = $response();
			set_transient( 'appyn_results_latest_gplay_apps', $response, 60 * MINUTE_IN_SECONDS );
		} else {
			$response = get_transient( 'appyn_results_latest_gplay_apps' );

			if( count($response) == 0 ) {
				$response = $response();
				set_transient( 'appyn_results_latest_gplay_apps', $response, 60 * MINUTE_IN_SECONDS );
			}
		}

		global $post;
		$query = new WP_Query( array( 'posts_per_page' => -1, 'post_parent' => 0, 'suppress_filters' => true, 'cache_results'  => false, 'meta_key' => 'px_app_id' ) );

		$this->list_ids = array();

		if( $query->have_posts() ) :
			while( $query->have_posts() ) : $query->the_post();
				$px_app_id = get_post_meta( $post->ID, 'px_app_id', true );
				$this->list_ids[$px_app_id] = $post->ID;
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

		set_transient( 'total_posts_latest', $this->total_posts, 24 * HOUR_IN_SECONDS );

		$this->apps_by_status( $this->response_body['results'] );
	}

	public function get_gplay_apps_to_import() {
		
		if( !isset($this->response_body['results']) ) return;

		$r = array();

		if( $this->status == 'imported' ) {
			foreach( $this->response_body['results'] as $res ) {
				if( array_key_exists( $res['app_id'], $this->list_ids ) ) {
					$r[] = $res;
				}
			}

			if( empty($r) ) {
				$this->response = false;
			}
			return $r;
		} elseif( $this->status == 'no-imported' ) {
			foreach( $this->response_body['results'] as $res ) {
				if( ! array_key_exists( $res['app_id'], $this->list_ids ) ) {
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

	private function apps_by_status( $results ) {

		$a = 0;
		foreach( $results as $res ) {
			if( array_key_exists( $res['app_id'], $this->list_ids ) ) {
				$a++;
			}
		}
		$this->total_posts_import = $a;
		
		$b = 0;
		foreach( $results as $res ) {
			if( ! array_key_exists( $res['app_id'], $this->list_ids ) ) {
				$b++;
			}
		}
		$this->total_posts_noimport = $b;
	}

	function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />', 'apps_to_import',  $item['app_id']
        );
    }
	public function no_items() {
		echo ( $this->response ) ? $this->response : __( 'No hay apps', 'appyn' );
	}

	public function column_default( $item, $column_name ) {
        $impt = '';
		$chck = ( array_key_exists( $item['app_id'], $this->list_ids ) ) ? true : false;

		if( $chck ) {
			$impt = '<a href="'.get_edit_post_link( $this->list_ids[$item['app_id']] ).'" target="_blank" title="'.__('View post').'"><i class="fas fa-file-import"></i></a>';
		}
		switch ( $column_name ) {
			case 'post_title':
				return '<div><span><img src="'.$item['img'].'" width="50" height="50"></span><span>' .$item['title'].'</span> '.$impt.'<a href="https://play.google.com/store/apps/details?id='.$item['app_id'].'" target="_blank" title="'. __( 'Ver en Google Play', 'appyn' ) .'"><img alt="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAAQCAMAAAD+iNU2AAAAyVBMVEUAAAAAzf8A/i//2AAA/2L/Plj/MjMA4P8Ayf8AxP8A/yv/MTAA9EwA0v8A+ScA0v/+Q03/My3/1QD/0AD/0wD/3AAA/wD/CgMA/yn/MjMA/y7/MDAA/zf/JTH/1gAA/wD/CAP/0wAA/y7/MTEA/y0Azv8Ay/8A+EoAxf8A/wD/CwP/1gAA5/8A5f8A2v//Pkn/OkP/0QAAwf8A1/0A1fsA/yoA/0H/DTz/KTj/NzP/MzP/AA7/9wD/6QDx6QD02QD/ygD/xAD/swCt0I1nAAAAJXRSTlMA8f0t/v708fHx7OLe29rY1s7EurWtrKGShXZoWEpAKiMdFxAJTZezOAAAAItJREFUCNdNzlcWgyAQBdBJTO+990RMIIKY3qP7X5QoeGD+7jvToFlbglll5Aw2hjN75BxGO207DrIT7SS4VGapVXBtLFLLkR/vWMq2T3PfPyGkK42Ofj58c8L7wjHP3usefHpr0S+JH7e2Jfch9+ThZ3Wu7gkyXJiCqpJLGR7r/4uUDbeaUG+tDEEEzLgTqZH0pKcAAAAASUVORK5CYII=" alt="Google Play" style="height:10px;"></a></div>';
				break;
			
			case 'version':
				return $item[$column_name];
				break;

			case 'import':
				return '<div><button type="button" class="button app_import" data-uid="'.uniqid(true).'" data-app-id="'.$item['app_id'].'" '.( ( $chck ) ? 'data-post-id="'.$this->list_ids[$item['app_id']].'" data-import-type="reimport"' : '').'>'.( ( $chck ) ? __( 'Re-importar', 'appyn' ) : __( 'Importar', 'appyn' ) ).'</button></div>';
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

		$per_page     = $this->get_items_per_page( 'apps_to_import_per_page', 30 );
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
		echo '<li class="all"><a href="'.remove_query_arg( 'status' ).'"'.( ( ! $this->status ) ? ' class="current"' : '').'>'.__( 'Todos', 'appyn' ).' <span class="count">('.$total_items.')</span></a></li>
			<li class="all"><a href="'.add_query_arg( 'status', 'imported' ).'" '.( ( $this->status == 'imported' ) ? ' class="current"' : '').'>'.__( 'Importados', 'appyn' ).' <span class="count">('.$tpi.')</span></a></li>
			<li class="all"><a href="'.add_query_arg( 'status', 'no-imported' ).'" '.( ( $this->status == 'no-imported' ) ? ' class="current"' : '').'>'.__( 'No importados', 'appyn' ).' <span class="count">('.$tpni.')</span></a></li>
		</ul>';
		
		$this->items = self::get_gplay_apps_to_import( $per_page, $current_page );
	}

}