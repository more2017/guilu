
<div class="cart">
<?php if ($_SESSION['cart']->count_contents() > 0) {?>
  <div class="animBoxCartLink" colspan="2"><a href="<?php echo zen_href_link(FILENAME_SHOPPING_CART,'','SSL')?>">&raquo; View Shopping Cart</a></div>
<?php $products = $_SESSION['cart']->get_products();
      for ($i=0, $n=sizeof($products); $i<$n; $i++) {?>
  <div class="item">
    <div class="animBoxCartImage" style="width:"><a href="<?php echo zen_href_link(zen_get_info_page($products[$i]['id']),'cPath='.zen_get_product_path($products[$i]['id']).'&products_id='.zen_get_prid($products[$i]['id']));?>"><?php echo zen_image(DIR_WS_IMAGES . $products[$i]['image'],$products[$i]['name'],IMAGE_SHOPPING_CART_WIDTH,IMAGE_SHOPPING_CART_HEIGHT);?></a></div>
    <div class="animBoxCartContent">
      <div class="animBoxCartName"><?php echo $products[$i]['name'];?>
        <?php
			  if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
				  echo '<ul class="animBoxCartAttributes">';
			   if (PRODUCTS_OPTIONS_SORT_ORDER=='0') {
				 $options_order_by= ' ORDER BY LPAD(popt.products_options_sort_order,11,"0")';
			   } else {
				 $options_order_by= ' ORDER BY popt.products_options_name';
			   }
			  foreach ($products[$i]['attributes'] as $option => $value) {
				  $attributes = "SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
								 FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
								 WHERE pa.products_id = :productsID
								 AND pa.options_id = :optionsID
								 AND pa.options_id = popt.products_options_id
								 AND pa.options_values_id = :optionsValuesID
								 AND pa.options_values_id = poval.products_options_values_id
								 AND popt.language_id = :languageID
								 AND poval.language_id = :languageID " . $options_order_by;
			
				  $attributes = $db->bindVars($attributes, ':productsID', $products[$i]['id'], 'integer');
				  $attributes = $db->bindVars($attributes, ':optionsID', $option, 'integer');
				  $attributes = $db->bindVars($attributes, ':optionsValuesID', $value, 'integer');
				  $attributes = $db->bindVars($attributes, ':languageID', $_SESSION['languages_id'], 'integer');
				  $attributes_values = $db->Execute($attributes);
				  if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID) {
					$attr_value = htmlspecialchars($products[$i]['attributes_values'][$option], ENT_COMPAT, CHARSET, TRUE);
				  } else {
					$attr_value = $attributes_values->fields['products_options_values_name'];
				  }
				  echo '<li> - '.$attributes_values->fields['products_options_name'].' : '.$attr_value.'</li>'; 
				}
			  echo '</ul>';
			  }
		?>
      </div>
      <div class="animBoxCartQty"> Qty: <?php echo $products[$i]['quantity'];?></div>
      <div class="animBoxCartPrice"><?php echo ' Price: '.$currencies->display_price($products[$i]['final_price'], zen_get_tax_rate($products[$i]['tax_class_id']), $products[$i]['quantity']) . ($products[$i]['onetime_charges'] != 0 ? '<br />' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');?></div>
      <div class="animBoxCartMoreLink"><a href="<?php echo zen_href_link(zen_get_info_page($products[$i]['id']),'cPath='.zen_get_product_path($products[$i]['id']).'&products_id='.zen_get_prid($products[$i]['id']));?>">&raquo; More Info</a></div>
    </div>
  </div>
  <br class="clearBoth" />
  <?php }?>
  <div class="animBoxCartTotal"><?php echo $currencies->format($_SESSION['cart']->show_total());?></div>
  <?php } else{?>
  <div class="animBoxCartNotice">Your Shopping Cart is empty.</div>
  <?php }?>
</div>