<?php
$GLOBALS['HTTP_RESPONSE'] = array(
  'status' => 200,
  'etag' => null,
  'headers' => array(
    array('Content-Type', 'text/html; charset=UTF-8')));

$GLOBALS['RESPONSE_DOCUMENT'] = array(
  'render_layout' => 'default',
  'title' => 'No title',
  'scripts' => array(),
  'stylesheets' => array(),
  'onload' => array(),
);

function resolve_route($request_uri) {
  foreach ($GLOBALS['ROUTES'] as $pattern => $handler) {
    if (preg_match($pattern, $request_uri, $reg)) {
      array_shift($reg);
      $_SERVER['REQUEST_ARGS'] = $reg;
      return $handler;
    }
  }
  throw new http_NotFound();
}

function render($file_name, $params = array()) {
  ob_start();
  render_in_place($file_name, $params);
  return ob_get_clean();
}

function render_in_place($file_name, $params = array()) {
  extract($params);
  if (is_file($GLOBALS['APPLICATION_ROOT'].'/handlers/'.$file_name.'.php')) {
    include($GLOBALS['APPLICATION_ROOT'].'/handlers/'.$file_name.'.php');
    return;
  }
  foreach (scandir($GLOBALS['APPLICATION_ROOT'].'/vendor') as $plugin) {
    if (substr($plugin, 0, 1) !== '.') {
      if (is_file($GLOBALS['APPLICATION_ROOT'].'/vendor/'.$plugin.'/handlers/'.$file_name.'.php')) {
        include($GLOBALS['APPLICATION_ROOT'].'/vendor/'.$plugin.'/handlers/'.$file_name.'.php');
        return;
      }
    }
  }
  throw new Exception("Unable to render handler '$file_name'");
}

function set_layout($file_name) {
  $GLOBALS['RESPONSE_DOCUMENT']['render_layout'] = $file_name;
}

function set_title($title) {
  $GLOBALS['RESPONSE_DOCUMENT']['title'] = $title;
}

function add_script($script) {
  $GLOBALS['RESPONSE_DOCUMENT']['scripts'] = $script;
}

function add_stylesheet($stylesheet) {
  $GLOBALS['RESPONSE_DOCUMENT']['stylesheets'] = $stylesheet;
}

function add_onload($onload) {
  $GLOBALS['RESPONSE_DOCUMENT']['onload'] = $onload;
}

class http_Exception extends Exception {}
class http_NotModified extends http_Exception {}
class http_MethodNotAllowed extends http_Exception {}
class http_NotFound extends http_Exception {}
class http_SeeOther extends http_Exception {}

function request_param($key = null, $default = null) {
  if ($key === null) {
    return $_SERVER['REQUEST_PARAMS'];
  }
  return isset($_SERVER['REQUEST_PARAMS'][$key]) ? $_SERVER['REQUEST_PARAMS'][$key] : $default;
}

function request_query($key = null, $default = null) {
  if ($key === null) {
    return $_GET;
  }
  return isset($_GET[$key]) ? $_GET[$key] : $default;
}

function request_body($key = null, $default = null) {
  if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded' && empty($_POST)) {
    parse_str(file_get_contents('php://input'), $buffer);
    $_POST = $buffer;
  }
  if ($key === null) {
    return $_POST;
  }
  return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function request_header($key = null, $default = null) {
  if (!isset($_SERVER['REQUEST_HEADERS'])) {
    $_SERVER['REQUEST_HEADERS'] = array();
    foreach (apache_request_headers() as $k => $v) {
      $_SERVER['REQUEST_HEADERS'][strtolower($k)] = $v;
    }
  }
  if ($key === null) {
    return $_SERVER['REQUEST_HEADERS'];
  }
  $key = strtolower($key);
  return isset($_SERVER['REQUEST_HEADERS'][$key]) ? $_SERVER['REQUEST_HEADERS'][$key] : $default;
}

function request_path() {
  return rtrim(preg_replace('~[?].*$~', '', request_uri()), '/');
}

function request_uri() {
  return $_SERVER['REQUEST_URI'];
}

function request_subview() {
  if (preg_match('~[?]([^=&]+)(&|$)~', request_uri(), $reg)) {
    return $reg[1];
  }
}

function request_method() {
  $real_http_method = strtolower($_SERVER['REQUEST_METHOD']);
  return $real_http_method === 'post' ? request_body('_method', 'post') : $real_http_method;
}

/**
 * Sets the response ETag.
 * If the request specifies an ETag, then the request will end witha "Not Modified" response.
 * This is an efficient way to utilise http level caching in your application.
 *
 * Make sure that the ETag is a unique hash for the contents of your response.
 */
function cache_by_etag($etag) {
  $GLOBALS['HTTP_RESPONSE']['etag'] = $etag;
  if ($GLOBALS['HTTP_RESPONSE']['etag'] === request_header('If-Match')) {
    throw new http_NotModified(array('ETag: ' . $etag));
  }
}

/**
 * Sets the http status code of the response.
 */
function response_set_status($code) {
  $GLOBALS['HTTP_RESPONSE']['status'] = $code;
}

/**
 * Replaces a http response header.
 *
 * Use instead of `header`
 */
function response_replace_header($key, $value) {
  $headers = array();
  foreach ($GLOBALS['HTTP_RESPONSE']['headers'] as $h) {
    if (strtolower($h[0]) != strtolower($key)) {
      $headers[] = $h;
    }
  }
  $GLOBALS['HTTP_RESPONSE']['headers'] = $headers;
  response_add_header($key, $value);
}

/**
 * Adds a http response header.
 *
 * Use instead of `header`
 */
function response_add_header($key, $value) {
  $GLOBALS['HTTP_RESPONSE']['headers'][] = array($key, $value);
}

/**
 * Logs something to the debug log.
 *
 * Use it instead of `var_dump` and put a `tail -f` on the debug log.
 */
function debug($mixed) {
  static $process_id;
  if (!$process_id) {
    $process_id = substr(md5(microtime(true)), 0, 8);
  }
  $debug_backtrace = debug_backtrace();
  $msg = "*** ".$process_id." ".date("Y-m-d H:i:s")." ".$debug_backtrace[0]['file']." : ".$debug_backtrace[0]['line']."\n".json_encode_pretty($mixed)."\n";
  error_log($msg, 3, $GLOBALS['APPLICATION_ROOT'].'/log/debug.log');
}

/**
 * Input an object, returns a json-ized string of said object, pretty-printed
 *
 * @param mixed $obj The array or object to encode
 * @return string JSON formatted output
 */
function json_encode_pretty($obj, $indentation = 0) {
  switch (gettype($obj)) {
    case 'object':
      $obj = get_object_vars($obj);
    case 'array':
      if (!isset($obj[0])) {
        $arr_out = array();
        foreach ($obj as $key => $val) {
          $arr_out[] = '"' . addslashes($key) . '": ' . json_encode_pretty($val, $indentation + 1);
        }
        if (count($arr_out) < 2) {
          return '{' . implode(',', $arr_out) . '}';
        }
        return "{\n" . str_repeat("  ", $indentation + 1) . implode(",\n".str_repeat("  ", $indentation + 1), $arr_out) . "\n" . str_repeat("  ", $indentation) . "}";
      } else {
        $arr_out = array();
        $ct = count($obj);
        for ($j = 0; $j < $ct; $j++) {
          $arr_out[] = json_encode_pretty($obj[$j], $indentation + 1);
        }
        if (count($arr_out) < 2) {
          return '[' . implode(',', $arr_out) . ']';
        }
        return "[\n" . str_repeat("  ", $indentation + 1) . implode(",\n".str_repeat("  ", $indentation + 1), $arr_out) . "\n" . str_repeat("  ", $indentation) . "]";
      }
      break;
    case 'NULL':
      return 'null';
      break;
    case 'boolean':
      return $obj ? 'true' : 'false';
      break;
    case 'integer':
    case 'double':
      return $obj;
      break;
    case 'string':
    default:
      $obj = str_replace(array('\\','"',), array('\\\\','\"'), $obj);
      return '"' . $obj . '"';
      break;
  }
}

/**
 * Generates an URL
 */
function url($href, $params = array()) {
  if (is_array($href)) {
    $href = implode('/', array_map('rawurlencode', $href));
  }
  if (strpos($href, '#') !== false) {
    list($href, $fragment) = explode('#', $href, 2);
    $fragment = "#".$fragment;
  } else {
    $fragment = "";
  }
  if (strpos($href, '?') !== false) {
    list($href, $querystring) = explode('?', $href, 2);
    parse_str($querystring, $embedded_params);
    $params = array_merge($embedded_params, $params);
  }
  $assoc = array();
  $indexed = array();
  foreach ($params as $key => $value) {
    if (is_int($key)) {
      $indexed[] = rawurlencode($value);
    } else {
      $assoc[$key] = $value;
    }
  }
  $querystring = "";
  if (count($indexed) > 0) {
    $querystring = implode('&', $indexed);
  }
  $assoc_string = http_build_query($assoc);
  if ($assoc_string) {
    if ($querystring) {
      $querystring .= '&';
    }
    $querystring .= $assoc_string;
  }
  return $href . ($querystring ? ('?' . $querystring) : '') . $fragment;

}

/**
 * Returns a URL to an arbitrary object.
 * Requires that object's class to have a named url-helper
 *
 * See `config/routes.inc.php`
 */
function url_for($object, $params = array()) {
  $fn = get_class($object)."_url";
  if (!is_callable($fn)) {
    throw new Exception("Missing URL helper $fn");
  }
  $href = $fn($object);
  if (count($params) > 0) {
    return url($href, $params);
  }
  return $href;
}