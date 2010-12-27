<?php
function resolve_route($request, $routes) {
  $request_uri = $request->uri();
  $request_method = strtoupper($request->method());
  foreach ($routes as $pattern => $handler) {
    if (preg_match($pattern, $request_method . $request_uri, $reg) || preg_match($pattern, $request_uri, $reg)) {
      array_shift($reg);
      $request->setParams($reg);
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

/**
 * Returns the global response document wrapper
 *
 * @returns http_ResponseDocument
 */
function document() {
  if (!isset($GLOBALS['document_instance'])) {
    $GLOBALS['document_instance'] = new http_ResponseDocument();
  }
  return $GLOBALS['document_instance'];
}

/**
 * Returns the global http response wrapper
 *
 * @returns http_Response
 */
function response() {
  if (!isset($GLOBALS['response_instance'])) {
    $GLOBALS['response_instance'] = new http_Response();
  }
  return $GLOBALS['response_instance'];
}

/**
 * Returns the global http request wrapper
 *
 * @returns http_Request
 */
function request() {
  if (!isset($GLOBALS['request_instance'])) {
    $GLOBALS['request_instance'] = new http_Request();
  }
  return $GLOBALS['request_instance'];
}

/**
 * Returns the global cookie access wrapper
 *
 * @returns http_CookieAccess
 */
function cookie() {
  if (!isset($GLOBALS['cookie_instance'])) {
    $GLOBALS['cookie_instance'] = new http_CookieAccess(request()->serverName(), $_COOKIE);
  }
  return $GLOBALS['cookie_instance'];
}

/**
 * Returns the global session access wrapper
 *
 * @returns http_SessionAccess
 */
function session() {
  if (!isset($GLOBALS['session_instance'])) {
    $GLOBALS['session_instance'] = new http_SessionAccess(cookie());
  }
  return $GLOBALS['session_instance'];
}

class http_Exception extends Exception {
  function __construct($headers = array()) {
    $this->headers = $headers;
  }
  function headers() {
    return $this->headers;
  }
}
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
    $file_access = $file_access ? $file_access : new http_UploadedFileAccess();
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

  function serverName() {
    return $_SERVER['SERVER_NAME'];
  }

  function method() {
    $real_http_method = strtolower($_SERVER['REQUEST_METHOD']);
    return $real_http_method === 'post' ? $this->body('_method', 'post') : $real_http_method;
  }

  function isGet() {
    return $this->method() == 'get';
  }

  function isPost() {
    return $this->method() == 'post';
  }

  function isPut() {
    return $this->method() == 'put';
  }

  function isDelete() {
    return $this->method() == 'delete';
  }

  function isXhr() {
    throw new Exception("TODO");
  }

}

/**
 * Wraps access to the response document.
 */
class http_ResponseDocument {
  protected $render_layout = 'default';
  protected $title = 'No title';
  protected $scripts = array();
  protected $stylesheets = array();
  protected $onload = array();
  /**
   * Sets the document layout. Set to NULL to not have a layout rendered.
   */
  function setLayout($file_name) {
    $this->render_layout = $file_name;
  }

  function layout() {
    return $this->render_layout;
  }

  /**
   * Sets the document title.
   */
  function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Adds an external javascript file to the head of the document.
   */
  function addScript($script) {
    $this->scripts[] = $script;
  }

  /**
   * Adds an external style sheet file to the head of the document.
   */
  function addStylesheet($stylesheet) {
    $this->stylesheets[] = $stylesheet;
  }

  /**
   * Adds a piece of inline javascript code to execute on page load.
   */
  function addOnload($onload) {
    $this->onload[] = $onload;
  }

  /**
   * Returns the variables set on the document.
   */
  function exportVariables() {
    return get_object_vars($this);
  }
}

class http_Response {
  protected $status = 200;
  protected $headers = array(
    array('Content-Type', 'text/html; charset=UTF-8'));

  /**
   * Sets the response ETag.
   * If the request specifies an ETag, then the request will end witha "Not Modified" response.
   * This is an efficient way to utilise http level caching in your application.
   *
   * Make sure that the ETag is a unique hash for the contents of your response.
   */
  function cacheByEtag($etag) {
    if ($etag === request()->header('If-Match')) {
      throw new http_NotModified(array('ETag: ' . $etag));
    }
    $this->replaceHeader('ETag', $etag);
  }

  /**
   * Redirects browser to another location, with a "303 See Other" status.
   * This should be used after POST to redirect to a valid GET url.
   */
  function seeOther($location) {
    throw new http_SeeOther(array('Location: ' . $location));
  }

  /**
   * Sets the http status code of the response.
   */
  function setStatus($code) {
    $this->status = $code;
  }

  function status() {
    return $this->status;
  }

  /**
   * Replaces a http response header.
   *
   * Use instead of `header`
   */
  function replaceHeader($key, $value) {
    $headers = array();
    foreach ($this->headers as $h) {
      if (strtolower($h[0]) != strtolower($key)) {
        $headers[] = $h;
      }
    }
    $this->headers = $headers;
    $this->headers[] = array($key, $value);
  }

  /**
   * Adds a http response header.
   *
   * Use instead of `header`
   */
  function addHeader($key, $value) {
    $this->headers[] = array($key, $value);
  }

  function headers() {
    return $this->headers;
  }
}

class http_CookieAccess {
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

class http_SessionAccess {
  /** @var CookieAccess */
  protected $cookie_access;
  /**
    * @param http_CookieAccess
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

class http_UploadedFileAccess {
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