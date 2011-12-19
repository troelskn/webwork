<?php
// This file is the main entrypoint of the web application
// Start timer as the first thing
$__benchmark_start = microtime(true);
// Include framework code
require_once '../bootstrap.inc.php';
require_once 'web.inc.php';
require_once 'html_helpers.inc.php';
// Open a buffer so we can access headers during rendering.
ob_start();
try {
  if ($GLOBALS['WEBWORK_LOGGING']['request']) {
    debug(
      array(
        'method' => request()->method(),
        'uri' => request()->uri(),
        'param' => request()->param(),
        'query' => request()->query(),
        'body' => request()->body()));
  }
  // Main dispatch mechanism. `resolve_route` maps the request uri to a handler name, then it gets rendered.
  $handler = resolve_route(request(), $GLOBALS['ROUTES']);
  if ($GLOBALS['WEBWORK_LOGGING']['dispatch']) {
    debug("Dispatching $handler");
  }
  render_in_place($handler);
  if (!headers_sent()) {
    // The main content is wrapped in a layout file, if any exists
    if (document()->layout()) {
      $content = ob_get_clean();
      ob_start();
      render_in_place('layouts/'.document()->layout(), array_merge(document()->exportVariables(), array('content' => $content)));
    }
    // Output to client
    header("HTTP/1.1 ".response()->status());
    foreach (response()->headers() as $header) {
      header($header[0].": ".$header[1]);
    }
    header("X-Processing-Time: " . number_format(microtime(true) - $__benchmark_start, 4));
    ob_end_flush();
    if (response()->stream()) {
      call_user_func(response()->stream());
    }
  }
} catch (http_MovedPermanently $ex) {
  // Processing was halted with a redirect
  ob_end_clean();
  header("HTTP/1.1 301 Moved Permanently");
  foreach ($ex->headers() as $header) {
    header($header);
  }
} catch (http_SeeOther $ex) {
  // Processing was halted with a redirect
  ob_end_clean();
  header("HTTP/1.1 303 See Other");
  foreach ($ex->headers() as $header) {
    header($header);
  }
} catch (http_NotModified $ex) {
  // Processing was halted with a "not modified"
  ob_end_clean();
  header("HTTP/1.1 304 Not Modified");
  foreach ($ex->headers() as $header) {
    header($header);
  }
} catch (http_BadRequest $ex) {
  // Processing was halted with a "bad request"
  ob_end_clean();
  header("HTTP/1.1 400 Bad Request");
  foreach ($ex->headers() as $header) {
    header($header);
  }
} catch (http_Unauthorized $ex) {
  // Processing was halted with an "unauthorized"
  ob_end_clean();
  header("HTTP/1.1 401 Unauthorized");
  foreach ($ex->headers() as $header) {
    header($header);
  }
} catch (http_Forbidden $ex) {
  // Processing was halted with a "forbidden"
  ob_end_clean();
  header("HTTP/1.1 403 Forbidden");
  foreach ($ex->headers() as $header) {
    header($header);
  }
} catch (http_NotFound $ex) {
  // Processing was halted with a "not found"
  ob_end_clean();
  header("HTTP/1.1 404 Not Found");
  header('Content-Type: text/plain');
  echo "HTTP/1.1 404 Not Found.\n\n";
  echo "The requested page could not be found.\n";
  echo request()->method(), " ", request()->uri(), "\n\n";
  echo str_repeat(" ", 512);
} catch (http_Gone $ex) {
  // Processing was halted with a "gone"
  ob_end_clean();
  header("HTTP/1.1 410 Gone");
  header('Content-Type: text/plain');
  echo "HTTP/1.1 410 Gone.\n\n";
  echo "The requested resource is no longer available.\n";
  echo request()->method(), " ", request()->uri(), "\n\n";
  echo str_repeat(" ", 512);
} catch (Exception $ex) {
  // Something went haywire. If in development mode, dump to screen.
  // Otherwise allow the current exception handler (if any) to log the incident
  // In any case, present the user with a 500 message (You may want to pimp this part up a bit)
  ob_end_clean();
  header("HTTP/1.1 500 Internal Server Error");
  header('Content-Type: text/plain');
  echo "HTTP/1.1 500 Internal Server Error.\n\n";
  echo "Error Resolving request\n\n";
  echo request()->method(), " ", request()->uri(), "\n\n";
  if ($GLOBALS['ENVIRONMENT'] === 'development') {
    echo $ex;
    echo str_repeat(" ", 512);
  } else {
    // find the current exceptions handler
    // this is just a dummy, since the only way to get the current handler is by assigning a new
    $handler = set_exception_handler('debug');
    if (is_callable($handler)) {
      // yep - there is one, so invoke it
      call_user_func($handler, $ex);
    }
    // clean up after us. probably unnecessary, since the execution is about to halt any way
    restore_exception_handler();
  }
}
if (isset($GLOBALS['WEBWORK_LOGGING']['processing_time'])) {
  error_log(
    request()->method() . " " . request()->uri() . " " . number_format(microtime(true) - $__benchmark_start, 4) ."\n",
    3, $GLOBALS['WEBWORK_LOGGING']['processing_time']);
}
if (function_exists('apache_note')) {
  apache_note('x-php-processing-time', number_format(microtime(true) - $__benchmark_start, 4));
  if (function_exists('memory_get_peak_usage')) {
    apache_note('x-php-memory-usage', memory_get_peak_usage());
  }
}
