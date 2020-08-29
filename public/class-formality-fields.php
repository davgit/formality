<?php

/**
 * Fields rendering functions
 *
 * @link       https://formality.dev
 * @since      1.0.0
 *
 * @package    Formality
 * @subpackage Formality/public
 */

class Formality_Fields {

  private $formality;
  private $version;

  public function __construct( $formality, $version ) {
    $this->formality = $formality;
    $this->version = $version;
  }
  
  /**
   * Base field rendering
   *
   * @since    1.0.0
   */
  public function field($type, $options, $form_type, $index) {
    $defaults = array(
      "name" => __("Field name", "formality"),
      "label" => "",
      "exclude" => 0,
      "halfwidth" => false,
      "required" => false,
      "value" => "",
      "placeholder" => $this->default_placeholder($type),
      "rules" => []
    );    
    $options = $options + $defaults;
    $options["value"] = $this->prefill($options, $type);
    $class = $type == "message" || $type == "media" ? "formality__" . $type : ( "formality__field formality__field--" . $type);
    $input_wrap = $options["exclude"] ? "%s" : ($this->label($options) . '<div class="formality__input">%s</div>');
    $wrap = '<div class="' . $class . ($options["halfwidth"] ? " formality__field--half" : "" ) . ($options["required"] ? " formality__field--required" : "") . ($options["value"] ? " formality__field--filled" : "") . '"' . $this->conditional($options["rules"]) . ' data-type="' . $type . '">'.$input_wrap.'</div>';
    if(($type=="step")&&($index==1)) {
      $wrap = '<section class="formality__section formality__section--active">%s';
    } else if($index==1) {
      $wrap = '<section class="formality__section formality__section--active">'.$wrap;
    } else if($type=="step") {
      if($form_type=="conversational") {
        $wrap = '%s';
      } else {
        $wrap = '</section><section class="formality__section">%s';
      }
    }
    return sprintf($wrap, $this->$type($options));
  }

  /**
   * Get default placeholders
   *
   * @since    1.0.0
   */
  public function default_placeholder($type) {
    if($type=="select") {
      $placeholder = __("Select your choice", "formality"); 
    } else if($type=="multiple") {
      $placeholder = "";
    } else if($type=="rating") {
      $placeholder = "";
    } else if ($type=="switch") {
      $placeholder = __("Click to confirm", "formality");
    } else {
      $placeholder = __("Type your answer here", "formality");
    }
    return $placeholder;
  }

  /**
   * Get input name attribute
   *
   * @since    1.0.0
   */
  public function attr_name($uid, $index = 0) {
    return 'id="' . $uid . ( $index ? ("_" . $index) : "" ) . '" name="'.$uid.'"';
  }

  /**
   * Get input required attribute
   *
   * @since    1.0.0
   */  
  public function attr_required($print) {
    return ($print ? ' required=""' : '');
  }
  
  /**
   * Get input placeholder attribute
   *
   * @since    1.0.0
   */
  public function attr_placeholder($placeholder, $label_only = false) {
    return ($label_only ? $placeholder : ' placeholder="' . $placeholder . '"');
  }

  /**
   * Build select options
   *
   * @since    1.0.0
   */
  public function print_options($raw_options) {
    $initval = $raw_options['value'];
    $options = "";
    $options .= '<option disabled '. ( $initval ? "" : "selected" ) .' value="">' . $raw_options['placeholder'] . '</option>';
    foreach ($raw_options['options'] as $option){
      if(isset($option['value']) && $option['value']) {
        $options .= '<option value="'. $option['value'] .'"'. ( $option['value'] == $initval ? " selected" : "" ) .'>' . ( isset($option['label']) && $option['label'] ? $option['label'] : $option['value'] ) . '</option>';
      }
    };
    return $options;
  }

  /**
   * Build radio/checkbox options
   *
   * @since    1.0.0
   */
  public function print_multiples($options) {
    $initval = $options['value'];
    $options['single'] = (isset($options['single']) && $options['single']) ? "radio" : "checkbox";
    $options['uid'] = $options['single']=="checkbox" ? ( $options['uid'] . "[]" ) : $options['uid'];
    $style = " formality__label--" . $options['single'];
    $index = 0;
    $multiples = "";
    foreach ($options['options'] as $multiple){
      if(isset($multiple['value']) && $multiple['value']) {
        $index++;
        $label_key = (isset($multiple['label']) && $multiple['label']) ? $multiple['label'] : $multiple['value'];
        $multiples .= '<input'. ( $multiple['value'] == $initval ? " checked" : "" ) .' type="'.$options['single'].'" ' . $this->attr_name($options['uid'], $index) . $this->attr_required($options['required']) .' value="'. $multiple['value'] .'" />' . $this->label($options, $label_key, "<i></i><span>", "</span>", $style, $index);        
      }
    };
    return $multiples;
  }

  /**
   * Prefill field
   *
   * @since    1.0.0
   */
  public function prefill($options, $type) {
    $value = $options['value'];
    if(isset($options['uid'])) {
      $uid = $options['uid'];
      $raw = isset($_GET[$uid]) && $_GET[$uid] ? $_GET[$uid] : '';
      if($raw) {
        switch($type) {
          case 'email':
            $value = sanitize_email($raw);
            break;
          case 'textarea':
            $value = sanitize_textarea_field($raw);
            break;
          case 'rating':
          case 'switch':
            $value = absint($raw);
            break;
          default:
            $value = sanitize_text_field($raw);
        }
      }
    }
    return $value;
  }

  /**
   * Build input conditional attribute
   *
   * @since    1.0.0
   */
  public function conditional($rules) {
    if($rules && isset($rules[0]['field'])) {
      $conditions = htmlspecialchars(json_encode($rules), ENT_QUOTES, get_bloginfo( 'charset' ));
      return ' data-conditional="'.esc_attr($conditions).'"';
    }
  }

  /**
   * Build input label
   *
   * @since    1.0.0
   */
  public function label($options, $label="", $before = "", $after = "", $class = "", $index = 0) {
    if(!$label) { $label = $options["name"]; }
    $label = '<label class="formality__label' . $class . '" for="' . $options['uid'] . ( $index ? ("_" . $index) : "" ) . '">' . $before . $label . $after . '</label>';
    return $label;
  }

  /**
   * Render form step
   *
   * @since    1.0.0
   */
  public function step($options) {
    $step = ($options["name"] ? ('<h4>'.$options["name"].'</h4>') : '' );
    $step .= ($options["description"] ? ('<p>'.$options["description"].'</p>') : '' );
    if($step) { $step = '<div class="formality__section__header">'.$step.'</div>'; }
    return $step;
  }

  /**
   * Render text field
   *
   * @since    1.0.0
   */  
  public function text($options) {
    $field = '<input type="text" ' . $this->attr_name($options['uid']) . $this->attr_required($options['required']) . $this->attr_placeholder($options['placeholder']) .' value="'. $options["value"] .'" />';
    return $field;
  }

  /**
   * Render email field
   *
   * @since    1.0.0
   */
  public function email($options) {
    $field = '<input type="email" ' . $this->attr_name($options['uid']) . $this->attr_required($options['required']) . $this->attr_placeholder($options['placeholder']) .' value="'. $options["value"] .'" />';
    return $field;
  }

  /**
   * Render textarea field
   *
   * @since    1.0.0
   */
  public function textarea($options) {
    $field = '<textarea ' . $this->attr_name($options['uid']) . $this->attr_required($options['required']) . $this->attr_placeholder($options['placeholder']) .' rows="'. (isset($options["rows"]) ? $options["rows"] : 3) .'" maxlength="'. (isset($options["max_length"]) ? $options["max_length"] : 500 ) .'">'. $options["value"] .'</textarea>';
    return $field;
  }

  /**
   * Render number field
   *
   * @since    1.0.0
   */
  public function number($options) {
    $field = '<input type="number" ' . $this->attr_name($options['uid']) . $this->attr_required($options['required']) . $this->attr_placeholder($options['placeholder']) .' value="'. $options["value"] . '"' . (isset($options["value_min"]) ? ' min="' . $options["value_min"] . '"' : "") . (isset($options["value_max"]) ? ' max="' . $options["value_max"] . '"' : "") .' step="'. (isset($options["value_step"]) ? $options["value_step"] : "") .'" />';
    return $field;
  }

  /**
   * Render select field
   *
   * @since    1.0.0
   */
  public function select($options) {
    $field = '<select ' . $this->attr_name($options['uid']) . $this->attr_required($options['required']) . $this->attr_placeholder($options['placeholder']) .'>' . $this->print_options($options) . '</select>';
    return $field;
  }

  /**
   * Render switch field
   *
   * @since    1.0.0
   */
  public function switch($options) {
    $style = isset($options['style']) ? ( " formality__label--" . $options['style'] ) : "";
    $field = '<input'. (( isset($options['value']) && $options['value'] ) ? " checked" : "" ) .' type="checkbox" ' . $this->attr_name($options['uid']) . $this->attr_required($options['required']) .' value="1" />' . $this->label($options, $options["placeholder"], "<i></i><span>", "</span>", $style);
    return $field;
  }

  /**
   * Render multiple field
   *
   * @since    1.0.0
   */
  public function multiple($options) {
    $style = isset($options['style']) ? ( " formality__input__grid--" . $options['style'] ) : "";
    $field = '<div class="formality__note">' . $options['placeholder'] . '</div>';
    $field .= '<div class="formality__input__grid' . $style . ' formality__input__grid--' . ( isset($options['option_grid']) ? $options['option_grid'] : 2 ) . '">' . $this->print_multiples($options) . '</div>';
    return $field;
  }

  /**
   * Render rating field
   *
   * @since    1.0.0
   */
  public function rating($options) {
    $field = '<div class="formality__note">' . $options['placeholder'] . '</div>';
    $max = isset($options["value_max"]) ? $options["value_max"] : 10;
    $icon = isset($options["icon"]) ? $options["icon"] : 'star';
    $svg = wp_remote_get(plugin_dir_url(__DIR__) . "dist/images/public/" . $icon . ".svg");
    if(is_array( $svg ) && ! is_wp_error( $svg ) && $svg['response']['code'] !== '404' ) { $icon = $svg['body']; } else { $icon = ""; }
    for ($n = 1; $n <= $max; $n++) {
      $field .= '<input type="radio" ' . $this->attr_name($options['uid'], $n) . $this->attr_required($options['required']) .' value="' . $n . '" />' . $this->label($options, $n, $icon, "", "", $n);
    }
    return $field;
  }

  /**
   * Render message
   *
   * @since    1.0.0
   */
  public function message($options) {
    $field = isset($options['text']) ? '<p>' . $options['text'] . '<p>' : '';
    return $field;
  } 

  /**
   * Render media
   *
   * @since    1.0.0
   */
  public function media($options) {
    $field = "";
    if(isset($options['media'])) {
      if($options['media_type']=='video') {
        $field = '<video loop><source src="' . $options['media'] . '" type="video/mp4"></video>';
        $field .= '<a href="#"><svg width="24px" height="24px" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M7.77051563,5.42042187 L0.77053125,9.92042187 C0.6885,9.973625 0.593765625,10.0000156 0.500015625,10.0000156 C0.417984375,10.0000156 0.33496875,9.97948437 0.260765625,9.93898437 C0.099609375,9.85109375 0,9.68309375 0,9.5 L0,0.5 C0,0.31690625 0.099609375,0.14890625 0.260765625,0.061015625 C0.41896875,-0.025890625 0.617203125,-0.020546875 0.77053125,0.079578125 L7.77051562,4.57957812 C7.91310937,4.67135937 8.00001562,4.83007812 8.00001562,5 C8.00001562,5.16992187 7.91310938,5.32859375 7.77051563,5.42042187 Z" transform="translate(9.000000, 7.000000)"></path></svg></a>';
      } else {
        $field = wp_get_attachment_image($options['media_id'], 'full');     
      }
    }
    return $field;
  } 

}