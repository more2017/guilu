<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
// $Id: jscript_main.php 5444 2006-12-29 06:45:56Z drbyte $
//
?>
<script type="text/javascript"><!--//

(function($) {
$(document).ready(function() {

$('#contentMainWrapper').addClass('onerow-fluid');
 $('#mainWrapper').css({
     'max-width': '100%',
     'margin': 'auto'
 });
 $('#headerWrapper').css({
     'max-width': '100%',
     'margin': 'auto'
 });
 $('#navSuppWrapper').css({
     'max-width': '100%',
     'margin': 'auto'
 });


$('.leftBoxContainer').css('width', '');
$('.rightBoxContainer').css('width', '');
$('#mainWrapper').css('margin', 'auto');

$('a[href="#top"]').click(function(){
$('html, body').animate({scrollTop:0}, 'slow');
return false;
});

$(".categoryListBoxContents").click(function() {
window.location = $(this).find("a").attr("href"); 
return false;
});

$('.centeredContent').matchHeight();
$('.specialsListBoxContents').matchHeight();
$('.centerBoxContentsAlsoPurch').matchHeight();
$('.categoryListBoxContents').matchHeight();

$('.no-fouc').removeClass('no-fouc');
});

}) (jQuery);

//--></script>