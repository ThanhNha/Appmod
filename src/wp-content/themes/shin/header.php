<?php 
var_dump(is_amp_px());
if( is_amp_px() ) { 
	get_template_part( 'amp/header' ); 
} else {
	get_header( 'default' ); 
}
