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
      request()->setParams($reg);
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
  $GLOBALS['RESPONSE_DOCUMENT']['scripts'][] = $script;
}

function add_stylesheet($stylesheet) {
  $GLOBALS['RESPONSE_DOCUMENT']['stylesheets'][] = $stylesheet;
}

function add_onload($onload) {
  $GLOBALS['RESPONSE_DOCUMENT']['onload'][] = $onload;
}

/**
 * Returns the global http request wrapper
 */
function request() {
  if (!isset($GLOBALS['request_instance'])) {
    $GLOBALS['request_instance'] = new http_Request();
  }
  return $GLOBALS['request_instance'];
}

/**
 * Returns the global cookie access wrapper
 */
function cookie() {
  if (!isset($GLOBALS['cookie_instance'])) {
    $GLOBALS['cookie_instance'] = new http_DefaultCookieAccess();
  }
  return $GLOBALS['cookie_instance'];
}

/**
 * Returns the global session access wrapper
 */
function session() {
  if (!isset($GLOBALS['session_instance'])) {
    $GLOBALS['session_instance'] = new http_DefaultSessionAccess(cookie());
  }
  return $GLOBALS['session_instance'];
}

class http_Exception extends Exception {}
class http_NotModified extends http_Exception {}
class http_MethodNotAllowed extends http_Exception {}
class http_NotFound extends http_Exception {}
class http_SeeOther extends http_Exception {}

/**
 * Wraps incoming http request
 */
class http_Request {
  protected $body;
  protected $headers;
  protected $params;
  protected $files;
  function __construct($file_access = null) {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded' && empty($_POST)) {
      parse_str(file_get_contents('php://input'), $buffer);
      $this->body = $buffer;
    } else {
      $this->body = $_POST;
    }
    $this->headers = array();
    foreach (apache_request_headers() as $k => $v) {
      $this->headers[strtolower($k)] = $v;
    }
    $file_access = $file_access ? $file_access : new http_DefaultUploadedFileAccess();
    $this->files = array();
    foreach ($_FILES as $key => $file) {
      if (isset($file['tmp_name']) && is_array($file['tmp_name'])) {
        $file_info = array();
        for ($i = 0; $i < count($file['tmp_name']); $i++) {
          $file_info[$i] = array();
          $file_info[$i]['tmp_name'] = $file['tmp_name'][$i];
          $file_info[$i]['name'] = $file['name'][$i];
          $file_info[$i]['type'] = $file['type'][$i];
          $file_info[$i]['size'] = $file['size'][$i];
          $file_info[$i]['error'] = $file['error'][$i];
        }
        if (array_key_exists('name', $file_info)) {
          $this->files[$key] = new http_UploadedFile($file_info, $key, $file_access);
        } else {
          $this->files[$key] = array();
          foreach ($file_info as $file_info_struct) {
            $this->files[$key][] = new http_UploadedFile($file_info_struct, $key, $file_access);
          }
        }
      }
    }
  }

  function setParams($params) {
    $this->params = $params;
  }

  function param($key = null, $default = null) {
    if ($key === null) {
      return $this->params;
    }
    return isset($this->params[$key]) ? $this->params[$key] : $default;
  }

  function query($key = null, $default = null) {
    if ($key === null) {
      return $_GET;
    }
    return isset($_GET[$key]) ? $_GET[$key] : $default;
  }

  function body($key = null, $default = null) {
    if ($key === null) {
      return $this->body;
    }
    return isset($this->body[$key]) ? $this->body[$key] : $default;
  }

  function header($key = null, $default = null) {
    if ($key === null) {
      return $this->headers;
    }
    $key = strtolower($key);
    return isset($this->headers[$key]) ? $this->headers[$key] : $default;
  }

  function file($key = null) {
    if ($key === null) {
      return $this->files;
    }
    return isset($this->files[$key]) ? $this->files[$key] : $default;
  }

  function path() {
    return rtrim(preg_replace('~[?].*$~', '', $this->uri()), '/');
  }

  function uri() {
    return $_SERVER['REQUEST_URI'];
  }

  function subview() {
    if (preg_match('~[?]([^=&]+)(&|$)~', $this->uri(), $reg)) {
      return $reg[1];
    }
  }

  function method() {
    $real_http_method = strtolower($_SERVER['REQUEST_METHOD']);
    return $real_http_method === 'post' ? $this->body('_method', 'post') : $real_http_method;
  }
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

class http_DefaultCookieAccess {
  /** @var string */
  protected $domain;
  /** @var string */
  protected $raw;
  /**
    * @param string
    * @param array
    * @return null
    */
  function __construct($domain, $raw) {
    $this->domain = $domain === 'localhost' ? false : $domain;
    $this->raw = $raw;
  }
  function has($key) {
    return isset($this->raw[$key]);
  }
  function get($key, $default = null) {
    if ($key === null) {
      return $this->raw;
    }
    return isset($this->raw[$key]) ? $this->raw[$key] : $default;
  }
  function set($key, $value, $expire = 0, $secure = false, $httponly = false) {
    if ($value === null) {
      setcookie($key, '', time() - 42000, '/');
      unset($this->raw[$key]);
    } else {
      setcookie($key, $value, $expire, '/', $this->domain, $secure, $httponly);
      $this->raw[$key] = $value;
    }
  }
  function all() {
    return $this->raw;
  }
}

class http_DefaultSessionAccess {
  /** @var CookieAccess */
  protected $cookie_access;
  /**
    * @param DefaultCookieAccess
    * @return null
    */
  function __construct($cookie_access) {
    $this->cookie_access = $cookie_access;
  }
  protected function autoStart() {
    if (!session_id()) {
      session_start();
    }
  }
  function has($key) {
    $this->autoStart();
    return isset($_SESSION[$key]);
  }
  function get($key, $default = null) {
    $this->autoStart();
    if ($key === null) {
      return $_SESSION;
    }
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
  }
  function set($key, $value) {
    $this->autoStart();
    $_SESSION[$key] = $value;
    return $value;
  }
  function close() {
    session_id() && session_write_close();
  }
  function destroy() {
    $this->autoStart();
    $_SESSION = array();
    if ($this->cookie_access->has(session_name())) {
      $this->cookie_access->set(session_name(), null);
    }
    session_destroy();
    $filename = realpath(session_save_path()) . DIRECTORY_SEPARATOR . session_id();
    if (is_file($filename) && is_writable($filename)) {
      unlink($filename);
    }
  }
  function sessionId() {
    $this->autoStart();
    return session_id();
  }
  function regenerateId() {
    return session_regenerate_id();
  }
}

class http_DefaultUploadedFileAccess {
  function copy($tmp_name, $path_destination) {
    $this->ensureDirectory(dirname($path_destination));
    if (is_uploaded_file($tmp_name)) {
      move_uploaded_file($tmp_name, $path_destination);
    } else {
      throw new Exception("Fileinfo is not a valid uploaded file");
    }
  }
  protected function mkdir($path) {
    mkdir($path);
  }
  protected function ensureDirectory($dir) {
    if (!is_dir($dir)) {
      $this->ensureDirectory(dirname($dir));
      $this->mkdir($dir);
    }
  }
}

/**
 * Wrapper around an uploaded file
 */
class http_UploadedFile {
  protected $key;
  protected $name;
  protected $tmp_name;
  protected $size;
  protected $type;
  protected $file_access;
  function __construct($file_data, $key, $file_access) {
    $this->key = $key;
    $this->name = $file_data['name'];
    $this->tmp_name = $file_data['tmp_name'];
    $this->size = $file_data['size'];
    $this->type = $file_data['type'];
    $this->file_access = $file_access;
  }
  function __serialize() {
    throw new Exception("Can't serialize an uploaded file. Copy file to a permanent storage.");
  }
  function key() {
    return $this->key;
  }
  function name() {
    return $this->name;
  }
  function type() {
    return $this->type;
  }
  function size() {
    return $this->size;
  }
  function writeTo($path_destination) {
    if ($this->size() === 0) {
      throw new Exception("Filesize is zero");
    }
    $this->file_access->copy($this->tmp_name, $path_destination);
  }
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