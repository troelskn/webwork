<?php
function resolve_route($request_uri) {
  foreach ($GLOBALS['ROUTES'] as $pattern => $handler) {
    if ($pattern !== 'ROOT') {
      if (preg_match($pattern, $request_uri, $reg)) {
        array_shift($reg);
        $_SERVER['REQUEST_ARGS'] = $reg;
        return $handler;
      }
    }
  }
  return $GLOBALS['ROUTES']['ROOT'];
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
  $GLOBALS['RENDER_LAYOUT'] = $file_name;
}

function request() {
  if (!isset($GLOBALS['http_request'])) {
    $GLOBALS['http_request'] = new http_Request();
  }
  return $GLOBALS['http_request'];
}

function response() {
  if (!isset($GLOBALS['http_response'])) {
    $GLOBALS['http_response'] = new http_Response(request());
  }
  return $GLOBALS['http_response'];
}

class http_Exception extends Exception {}
class http_NotModified extends http_Exception {}
class http_MethodNotAllowed extends http_Exception {}
class http_NotFound extends http_Exception {}
class http_SeeOther extends http_Exception {}

class http_Request {
  function arg($key = null, $default = null) {
    if ($key === null) {
      return $_SERVER['REQUEST_ARGS'];
    }
    return isset($_SERVER['REQUEST_ARGS'][$key]) ? $_SERVER['REQUEST_ARGS'][$key] : $default;
  }
  function query($key = null, $default = null) {
    if ($key === null) {
      return $_GET;
    }
    return isset($_GET[$key]) ? $_GET[$key] : $default;
  }
  function body($key = null, $default = null) {
    if ($key === null) {
      return $_POST;
    }
    return isset($_POST[$key]) ? $_POST[$key] : $default;
  }
  function header($key = null, $default = null) {
    $HEADERS = apache_request_headers();
    if ($key === null) {
      return $HEADERS;
    }
    return isset($HEADERS[$key]) ? $HEADERS[$key] : $default;
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

class http_Response {
  protected $status = 200;
  protected $headers = array(
    array('Content-Type', 'text/html; charset=UTF-8'));
  protected $requestEtag;
  protected $etag;
  function __construct($request) {
    $this->requestEtag = $request->header('If-Match');
  }
  function status() {
    return $this->status;
  }
  function headers() {
    return $this->headers;
  }
  function cacheByEtag($etag) {
    $this->etag = $etag;
    if ($this->requestEtag && $etag === $this->requestEtag) {
      throw new HttpNotModified(array('ETag: ' . $etag));
    }
  }
  function setStatus($code) {
    $this->status = $code;
  }
  function setHeader($key, $value) {
    $headers = array();
    foreach ($this->headers as $h) {
      if (strtolower($h[0]) != strtolower($key)) {
        $headers[] = $h;
      }
    }
    $this->headers = $headers;
    $this->addHeader($key, $value);
  }
  function addHeader($key, $value) {
    $this->headers[] = array($key, $value);
  }
}
