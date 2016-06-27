<?php
/**
 * @author      Serban Ghita <serbanghita@gmail.com>
 * @license     MIT License https://github.com/serbanghita/Mobile-Detect/blob/master/LICENSE.txt
 *
 * @author ZCAdditions.com, ZCA Responsive Template Default
 */
  function zen_draw_form_fh($name, $action, $method = 'post', $parameters = '') {
    $form = '<form class="form-horizontal" name="' . zen_output_string($name) . '" action="' . zen_output_string($action) . '" method="' . zen_output_string($method) . '"';

    if (zen_not_null($parameters)) $form .= ' ' . $parameters;

    $form .= '>';
    if (strtolower($method) == 'post') $form .= '<input type="hidden" name="securityToken" value="' . $_SESSION['securityToken'] . '" />';
    return $form;
  }

  
    function zen_image_submit_bs($image, $alt = '', $parameters = '', $sec_class = '') {
    global $template, $current_page_base, $zco_notifier;
    if (strtolower(IMAGE_USE_CSS_BUTTONS) == 'yes' && strlen($alt)<30) return zenCssButton($image, $alt, 'submit', $sec_class, $parameters);
    $zco_notifier->notify('PAGE_OUTPUT_IMAGE_SUBMIT');

    $image_submit = '<button type="submit" class="btn btn-danger';
    if (zen_not_null($parameters)) $image_submit .= ' ' . $parameters;
	$image_submit .= '">';
    if (zen_not_null($alt)) $image_submit .= '  ' . zen_output_string($alt) . ' ';

   

    $image_submit .= '</button>';

    return $image_submit;
  }
  
     function zen_image_submit_bs_info($image, $alt = '', $parameters = '', $sec_class = '') {
    global $template, $current_page_base, $zco_notifier;
    if (strtolower(IMAGE_USE_CSS_BUTTONS) == 'yes' && strlen($alt)<30) return zenCssButton($image, $alt, 'submit', $sec_class, $parameters);
    $zco_notifier->notify('PAGE_OUTPUT_IMAGE_SUBMIT');

    $image_submit = '<button type="submit" class="btn btn-info';
    if (zen_not_null($parameters)) $image_submit .= ' ' . $parameters;
	$image_submit .= '">';
    if (zen_not_null($alt)) $image_submit .= '  ' . zen_output_string($alt) . ' ';

   

    $image_submit .= '</button>';

    return $image_submit;
  }
  
  
/*
 * The HTML image wrapper function
 */
  function zen_image_bs($src, $alt = '', $width = '', $height = '', $parameters = '') {
    global $template_dir, $zco_notifier;

    // soft clean the alt tag
    $alt = zen_clean_html($alt);

    // use old method on template images
    if (strstr($src, 'includes/templates') or strstr($src, 'includes/languages') or PROPORTIONAL_IMAGES_STATUS == '0') {
      return zen_image_OLD($src, $alt, $width, $height, $parameters);
    }

//auto replace with defined missing image
    if ($src == DIR_WS_IMAGES and PRODUCTS_IMAGE_NO_IMAGE_STATUS == '1') {
      $src = DIR_WS_IMAGES . PRODUCTS_IMAGE_NO_IMAGE;
    }

    if ( (empty($src) || ($src == DIR_WS_IMAGES)) && (IMAGE_REQUIRED == 'false') ) {
      return false;
    }

    // if not in current template switch to template_default
    if (!file_exists($src)) {
      $src = str_replace(DIR_WS_TEMPLATES . $template_dir, DIR_WS_TEMPLATES . 'template_default', $src);
    }

    // hook for handle_image() function such as Image Handler etc
    if (function_exists('handle_image')) {
      $newimg = handle_image($src, $alt, $width, $height, $parameters);
      list($src, $alt, $width, $height, $parameters) = $newimg;
      $zco_notifier->notify('NOTIFY_HANDLE_IMAGE', array($newimg));
    }

    // Convert width/height to int for proper validation.
    // intval() used to support compatibility with plugins like image-handler
    $width = empty($width) ? $width : intval($width);
    $height = empty($height) ? $height : intval($height);

// alt is added to the img tag even if it is null to prevent browsers from outputting
// the image filename as default
    $image = '<img src="' . zen_output_string($src) . '" alt="' . zen_output_string($alt) . '"';

    if (zen_not_null($alt)) {
      $image .= ' title=" ' . zen_output_string($alt) . ' "';
    }

    if ( ((CONFIG_CALCULATE_IMAGE_SIZE == 'true') && (empty($width) || empty($height))) ) {
      if ($image_size = @getimagesize($src)) {
        if (empty($width) && zen_not_null($height)) {
          $ratio = $height / $image_size[1];
          $width = $image_size[0] * $ratio;
        } elseif (zen_not_null($width) && empty($height)) {
          $ratio = $width / $image_size[0];
          $height = $image_size[1] * $ratio;
        } elseif (empty($width) && empty($height)) {
          $width = $image_size[0];
          $height = $image_size[1];
        }
      } elseif (IMAGE_REQUIRED == 'false') {
        return false;
      }
    }


    if (zen_not_null($width) && zen_not_null($height) and file_exists($src)) {
//      $image .= ' width="' . zen_output_string($width) . '" height="' . zen_output_string($height) . '"';
// proportional images
      $image_size = @getimagesize($src);
      // fix division by zero error
      $ratio = ($image_size[0] != 0 ? $width / $image_size[0] : 1);
      if ($image_size[1]*$ratio > $height) {
        $ratio = $height / $image_size[1];
        $width = $image_size[0] * $ratio;
      } else {
        $height = $image_size[1] * $ratio;
      }
// only use proportional image when image is larger than proportional size
      if ($image_size[0] < $width and $image_size[1] < $height) {
        $image .= ' width="' . $image_size[0] . '" height="' . intval($image_size[1]) . '"';
      } else {
        $image .= ' width="' . round($width) . '" height="' . round($height) . '"';
      }
    } else {
       // override on missing image to allow for proportional and required/not required
      if (IMAGE_REQUIRED == 'false') {
        return false;
      } else if (substr($src, 0, 4) != 'http') {
        $image .= ' width="' . intval(SMALL_IMAGE_WIDTH) . '" height="' . intval(SMALL_IMAGE_HEIGHT) . '"';
      }
    }

    // inject rollover class if one is defined. NOTE: This could end up with 2 "class" elements if $parameters contains "class" already.
    if (defined('IMAGE_ROLLOVER_CLASS') && IMAGE_ROLLOVER_CLASS != '') {
      $parameters .= (zen_not_null($parameters) ? ' ' : '') . 'class="rollover"';
    }
    // add $parameters to the tag output
    if (zen_not_null($parameters)) $image .= ' ' . $parameters;

    $image .= ' />';

    return $image;
  }

  
  
    function zen_image_button_bs($image, $alt = '', $parameters = '', $sec_class = '') {
    global $template, $current_page_base, $zco_notifier;

    // inject rollover class if one is defined. NOTE: This could end up with 2 "class" elements if $parameters contains "class" already.
    if (defined('IMAGE_ROLLOVER_CLASS') && IMAGE_ROLLOVER_CLASS != '') {
      $parameters .= (zen_not_null($parameters) ? ' ' : '') . 'class="rollover"';
    }

    $zco_notifier->notify('PAGE_OUTPUT_IMAGE_BUTTON');
    if (strtolower(IMAGE_USE_CSS_BUTTONS) == 'yes') return zenCssButton($image, $alt, 'button', $sec_class, $parameters);
    return zen_image_bs($template->get_template_dir($image, DIR_WS_TEMPLATE, $current_page_base, 'buttons/' . $_SESSION['language'] . '/') . $image, $alt, '', '', $parameters);
  }
  
  function zen_back_link_bs($link_only = false) {
    if (sizeof($_SESSION['navigation']->path)-2 >= 0) {
      $back = sizeof($_SESSION['navigation']->path)-2;
      $link = zen_href_link($_SESSION['navigation']->path[$back]['page'], zen_array_to_string($_SESSION['navigation']->path[$back]['get'], array('action')), $_SESSION['navigation']->path[$back]['mode']);
    } else {
      if (isset($_SERVER['HTTP_REFERER']) && preg_match("~^".HTTP_SERVER."~i", $_SERVER['HTTP_REFERER']) ) {
      //if (isset($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], str_replace(array('http://', 'https://'), '', HTTP_SERVER) ) ) {
        $link= $_SERVER['HTTP_REFERER'];
      } else {
        $link = zen_href_link(FILENAME_DEFAULT);
      }
      $_SESSION['navigation'] = new navigationHistory;
    }

    if ($link_only == true) {
      return $link;
    } else {
      return '<a class="btn btn-success" href="' . $link . '">';
    }
  }
  
    function zen_draw_textarea_field_bs($name, $width, $height, $text = '~*~*#', $parameters = '', $reinsert_value = true) {
    $field = '<textarea class="form-control" name="' . zen_output_string($name) . '" cols="' . zen_output_string($width) . '" rows="' . zen_output_string($height) . '"';

    if (zen_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>';

    if ($text == '~*~*#' && (isset($GLOBALS[$name]) && is_string($GLOBALS[$name])) && ($reinsert_value == true) ) {
      $field .= stripslashes($GLOBALS[$name]);
    } elseif ($text != '~*~*#' && zen_not_null($text)) {
      $field .= $text;
    }

    $field .= '</textarea>';

    return $field;
  }
  
   function zen_draw_pull_down_menu_bs($name, $values, $default = '', $parameters = '', $required = false) {
    $field = '<select class="form-control"';

    if (!strstr($parameters, 'id=')) $field .= ' id="select-'.zen_output_string($name).'"';

    $field .= ' name="' . zen_output_string($name) . '"';

    if (zen_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>' . "\n";

    if (empty($default) && isset($GLOBALS[$name]) && is_string($GLOBALS[$name]) ) $default = stripslashes($GLOBALS[$name]);

    for ($i=0, $n=sizeof($values); $i<$n; $i++) {
      $field .= '  <option value="' . zen_output_string($values[$i]['id']) . '"';
      if ($default == $values[$i]['id']) {
        $field .= ' selected="selected"';
      }

      $field .= '>' . zen_output_string($values[$i]['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>' . "\n";
    }
    $field .= '</select>' . "\n";

    if ($required == true) $field .= TEXT_FIELD_REQUIRED;

    return $field;
  }


  
  
////
// Display Price Retail
// Specials and Tax Included
  function zen_get_products_display_price_info($products_id) {
    global $db, $currencies;

    $free_tag = "";
    $call_tag = "";

// 0 = normal shopping
// 1 = Login to shop
// 2 = Can browse but no prices
    // verify display of prices
      switch (true) {
        case (CUSTOMERS_APPROVAL == '1' and $_SESSION['customer_id'] == ''):
        // customer must be logged in to browse
        return '';
        break;
        case (CUSTOMERS_APPROVAL == '2' and $_SESSION['customer_id'] == ''):
        // customer may browse but no prices
        return TEXT_LOGIN_FOR_PRICE_PRICE;
        break;
        case (CUSTOMERS_APPROVAL == '3' and TEXT_LOGIN_FOR_PRICE_PRICE_SHOWROOM != ''):
        // customer may browse but no prices
        return TEXT_LOGIN_FOR_PRICE_PRICE_SHOWROOM;
        break;
        case ((CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and CUSTOMERS_APPROVAL_AUTHORIZATION != '3') and $_SESSION['customer_id'] == ''):
        // customer must be logged in to browse
        return TEXT_AUTHORIZATION_PENDING_PRICE;
        break;
        case ((CUSTOMERS_APPROVAL_AUTHORIZATION != '0' and CUSTOMERS_APPROVAL_AUTHORIZATION != '3') and $_SESSION['customers_authorization'] > '0'):
        // customer must be logged in to browse
        return TEXT_AUTHORIZATION_PENDING_PRICE;
        break;
      case ((int)$_SESSION['customers_authorization'] == 2):
        // customer is logged in and was changed to must be approved to see prices
        return TEXT_AUTHORIZATION_PENDING_PRICE;
        break;
        default:
        // proceed normally
        break;
      }

// show case only
    if (STORE_STATUS != '0') {
      if (STORE_STATUS == '1') {
        return '';
      }
    }

    // $new_fields = ', product_is_free, product_is_call, product_is_showroom_only';
    $product_check = $db->Execute("select products_tax_class_id, products_price, products_priced_by_attribute, product_is_free, product_is_call, products_type from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'" . " limit 1");

    // no prices on Document General
    if ($product_check->fields['products_type'] == 3) {
      return '';
    }

    $show_display_price = '';
    $display_normal_price = zen_get_products_base_price($products_id);
    $display_special_price = zen_get_products_special_price($products_id, true);
    $display_sale_price = zen_get_products_special_price($products_id, false);

    $show_sale_discount = '';
    if (SHOW_SALE_DISCOUNT_STATUS == '1' and ($display_special_price != 0 or $display_sale_price != 0)) {
      if ($display_sale_price) {
        if (SHOW_SALE_DISCOUNT == 1) {
          if ($display_normal_price != 0) {
            $show_discount_amount = number_format(100 - (($display_sale_price / $display_normal_price) * 100),SHOW_SALE_DISCOUNT_DECIMALS);
          } else {
            $show_discount_amount = '';
          }
		  //$show_sale_discount = '1111<span class="productPriceDiscount">' . 'bbbbbbbb<br />' . PRODUCT_PRICE_DISCOUNT_PREFIX . $show_discount_amount . PRODUCT_PRICE_DISCOUNT_PERCENTAGE . '</span>';

          $show_sale_discount = PRODUCT_PRICE_DISCOUNT_PREFIX_INFO . $show_discount_amount . PRODUCT_PRICE_DISCOUNT_PERCENTAGE;

        } else {
          $show_sale_discount = '<span class="productPriceDiscount">' . '<br />' . PRODUCT_PRICE_DISCOUNT_PREFIX . $currencies->display_price(($display_normal_price - $display_sale_price), zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . PRODUCT_PRICE_DISCOUNT_AMOUNT . '</span>';
        }
      } else {
        if (SHOW_SALE_DISCOUNT == 1) {
          $show_sale_discount = '<span class="productPriceDiscount">' . '<br />' . PRODUCT_PRICE_DISCOUNT_PREFIX . number_format(100 - (($display_special_price / $display_normal_price) * 100),SHOW_SALE_DISCOUNT_DECIMALS) . PRODUCT_PRICE_DISCOUNT_PERCENTAGE . '</span>';
        } else {
          $show_sale_discount = '<span class="productPriceDiscount">' . '<br />' . PRODUCT_PRICE_DISCOUNT_PREFIX . $currencies->display_price(($display_normal_price - $display_special_price), zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . PRODUCT_PRICE_DISCOUNT_AMOUNT . '</span>';
        }
      }
    }

    if ($display_special_price) {
      //$show_normal_price = '2222<span class="normalprice">' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . ' </span>';
	  $show_normal_price = $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id']));
      if ($display_sale_price && $display_sale_price != $display_special_price) {
        $show_special_price = '&nbsp;' . '<span class="productSpecialPriceSale">' . $currencies->display_price($display_special_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
        if ($product_check->fields['product_is_free'] == '1') {
          $show_sale_price = '<br />' . '<span class="productSalePrice">' . PRODUCT_PRICE_SALE . '<s>' . $currencies->display_price($display_sale_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</s>' . '</span>';
        } else {
          $show_sale_price = '<br />' . '<span class="productSalePrice">' . PRODUCT_PRICE_SALE . $currencies->display_price($display_sale_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
        }
      } else {
        if ($product_check->fields['product_is_free'] == '1') {
          $show_special_price = '&nbsp;' . '<span class="productSpecialPrice">' . '<s>' . $currencies->display_price($display_special_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</s>' . '</span>';
        } else {
          //$show_special_price = '@@@@@@&nbsp;' . '<span class="productSpecialPrice">' . $currencies->display_price($display_special_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
		  $show_special_price = $currencies->display_price($display_special_price, zen_get_tax_rate($product_check->fields['products_tax_class_id']));
        }
        $show_sale_price = '';
      }
    } else {
      if ($display_sale_price) {
        $show_normal_price = '<span class="normalprice">' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . ' </span>';
        $show_special_price = '';
        $show_sale_price = '<br />' . '<span class="productSalePrice">' . PRODUCT_PRICE_SALE . $currencies->display_price($display_sale_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
      } else {
        if ($product_check->fields['product_is_free'] == '1') {
          $show_normal_price = '<span class="productFreePrice"><s>' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</s></span>';
        } else {
          $show_normal_price = '<span class="productBasePrice">' . $currencies->display_price($display_normal_price, zen_get_tax_rate($product_check->fields['products_tax_class_id'])) . '</span>';
        }
        $show_special_price = '';
        $show_sale_price = '';
      }
    }

    if ($display_normal_price == 0) {
      // don't show the $0.00
      $final_display_price = $show_special_price . $show_sale_price . $show_sale_discount;
    } else {
      //$final_display_price = $show_special_price . $show_normal_price . $show_sale_price . $show_sale_discount;
	  $final_display_price = '<span class="price salePrice">' . $show_special_price . '</span><span class="oldPrice"><span class="normalPriceInfo">'. $show_normal_price . '</span> &nbsp;&nbsp;&nbsp; ' . $show_sale_price . $show_sale_discount . '</span>';
    }

    // If Free, Show it
    if ($product_check->fields['product_is_free'] == '1') {
      if (OTHER_IMAGE_PRICE_IS_FREE_ON=='0') {
        $free_tag = '<br />' . PRODUCTS_PRICE_IS_FREE_TEXT;
      } else {
        $free_tag = '<br />' . zen_image(DIR_WS_TEMPLATE_IMAGES . OTHER_IMAGE_PRICE_IS_FREE, PRODUCTS_PRICE_IS_FREE_TEXT);
      }
    }

    // If Call for Price, Show it
    if ($product_check->fields['product_is_call']) {
      if (PRODUCTS_PRICE_IS_CALL_IMAGE_ON=='0') {
        $call_tag = '<br />' . PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT;
      } else {
        $call_tag = '<br />' . zen_image(DIR_WS_TEMPLATE_IMAGES . OTHER_IMAGE_CALL_FOR_PRICE, PRODUCTS_PRICE_IS_CALL_FOR_PRICE_TEXT);
      }
    }

    return $final_display_price . $free_tag . $call_tag;
  }
  
  
    function zen_draw_input_field_bs($name, $value = '', $parameters = '', $type = 'text', $reinsert_value = true) {
    $field = '<input class="form-control" type="' . zen_output_string($type) . '" name="' . zen_sanitize_string(zen_output_string($name)) . '"';
    if ( (isset($GLOBALS[$name]) && is_string($GLOBALS[$name])) && ($reinsert_value == true) ) {
      $field .= ' value="' . zen_output_string(stripslashes($GLOBALS[$name])) . '"';
    } elseif (zen_not_null($value)) {
      $field .= ' value="' . zen_output_string($value) . '"';
    }

    if (zen_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= ' />';

    return $field;
  }
  
    function zen_draw_form_info($name, $action, $method = 'post', $parameters = '') {
    $form = '<form class="form-inline" name="' . zen_output_string($name) . '" action="' . zen_output_string($action) . '" method="' . zen_output_string($method) . '"';

    if (zen_not_null($parameters)) $form .= ' ' . $parameters;

    $form .= '>';
    if (strtolower($method) == 'post') $form .= '<input type="hidden" name="securityToken" value="' . $_SESSION['securityToken'] . '" />';
    return $form;
  }
