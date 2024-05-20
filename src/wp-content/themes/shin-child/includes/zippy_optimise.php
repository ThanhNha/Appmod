<?php
add_filter('admin_footer_text', 'shin_change_footer_text');

function shin_change_footer_text()
{
  echo "Core developed by <span ><a href='https://theshin.online' target='_blank'>Shin</a> or call me <a href='tel:0966514360'>0966514360</a></span> ";
}
