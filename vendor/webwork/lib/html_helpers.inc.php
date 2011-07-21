<?php

/**
 * Echoes a string, escaped for html.
 */
function e($strlike) {
  echo htmlspecialchars($strlike);
}

/**
 * Generates a link/anchortag
 */
function html_link($url, $title = null, $options = array()) {
  if ($title === null) {
    $title = $url;
  }
  $options['href'] = $url;
  $html = "<a";
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  $html .= ">".htmlspecialchars($title)."</a>";
  return $html;
}

/**
 * Generates an opening html `<form>` tag.
 */
function html_form_tag($method = 'post', $action = null, $options = array()) {
  $method = strtolower($method);
  $html = '<form';
  $options['action'] = $action ? $action : request()->uri();
  $options['method'] = $method === 'get' ? 'get' : 'post';
  $options['accept-charset'] = 'UTF-8';
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  $html .= ">\n";
  // http://railssnowman.info/
  if ($method != 'get') {
    $html .= '<input type="hidden" name="_utf8" value="&#9731;" />
';
  }
  if ($method !== 'get' && $method !== 'post') {
    $html .= '<input type="hidden" name="_method" value="' . htmlspecialchars($method) . '" />
';
  }
  return $html;
}

/**
 * Genereates an html form closing tag
 */
function html_form_tag_end() {
  return '</form>';
}

/**
 * Renders a html text input element.
 */
function html_text_field($name, $value = null, $options = array()) {
  $html = '<input';
  if (!isset($options['type'])) {
    $options['type'] = 'text';
  }
  $options['name'] = $name;
  $options['value'] = $value;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  return $html . " />\n";
}

/**
 * Renders a html password input element.
 */
function html_password_field($name, $options = array()) {
  $html = '<input type="password"';
  $options['name'] = $name;
  $options['value'] = null;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  return $html . " />\n";
}

/**
 * Renders a html hidden input element.
 */
function html_hidden_field($name, $value = null, $options = array()) {
  $html = '<input type="hidden"';
  $options['name'] = $name;
  $options['value'] = $value;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  return $html . " />\n";
}

/**
 * Renders a html `<textarea>` input element.
 */
function html_text_area($name, $value = null, $options = array()) {
  $html = '<textarea';
  $options['name'] = $name;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  return $html . ">" . htmlspecialchars($value) . "</textarea>\n";
}

/**
 * Renders a html radio input element.
 */
function html_radio($name, $value = null, $checked = false, $options = array()) {
  $html = "";
  if (isset($options['label'])) {
    $label = $options['label'];
    $options['label'] = null;
    $html .= '<label class="radio-button">';
  }
  $html .= '<input type="radio"';
  $options['name'] = $name;
  $options['value'] = $value;
  $options['checked'] = $checked ? 'checked' : null;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  $html .= ' />';
  if (isset($label)) {
    $html .= htmlspecialchars($label) . '</label>';
  }
  return $html . "\n";
}

/**
 * Renders a html checkbox input element.
 */
function html_checkbox($name, $checked = false, $options = array()) {
  $html = "";
  $html .= html_hidden_field($name, 'off');
  if (isset($options['label'])) {
    $label = $options['label'];
    $options['label'] = null;
    $html .= '<label>';
  }
  $html .= '<input type="checkbox"';
  $options['name'] = $name;
  $options['value'] = 'on';
  $options['checked'] = $checked ? 'checked' : null;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  $html .= ' />';
  if (isset($label)) {
    $html .= " " . htmlspecialchars($label) . '</label>';
  }
  return $html . "\n";
}

/**
 * Renders a html `<select>` input element.
 */
function html_select($name, $values = array(), $value = null, $options = array()) {
  $html = '<select';
  $options['name'] = $name;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
    }
  }
  return $html . ">" . html_options($values, $value, $options) . "</select>\n";
}

/**
 * Renders html `<option>` elements from an array.
 */
function html_options($values = array(), $value = null, $options = array()) {
  $associative = isset($options['associative']) && $options['associative'];
  $html = "";
  foreach ($values as $key => $v) {
    $html .= '<option';
    if (!is_integer($key) && !$associative) {
      $html .= ' value="' . htmlspecialchars($v) . '"';
    }
    if ($v == $value) {
      $html .= ' selected="selected"';
    }
    $html .= '>';
    if (is_integer($key) && !$associative) {
      $html .= htmlspecialchars($v);
    } else {
      $html .= htmlspecialchars($key);
    }
    $html .= "</option>\n";
  }
  return $html;
}
