<?php
// This file is the main entrypoint of the web application
// Start timer as the first thing
$__benchmark_start = microtime(true);
// Include framework code
require_once '../bootstrap.inc.php';
require_once 'web.inc.php';
require_once 'html_helpers.inc.php';
// Open a buffer so we can accesse headers during rendering.
ob_start();
try {
  // Main dispatch mechanism. `resolve_route` maps the request uri to a handler name, then it gets rendered.
  render_in_place(resolve_route(request(), $GLOBALS['ROUTES']));
  if (!headers_sent()) {
    // The main content is wrapped in a layout file, if any exists
    if (document()->layout()) {
      $content = ob_get_clean();
      ob_start();
      render_in_place(document()->layout().'_layout', array_merge(document()->exportVariables(), array('content' => $content)));
    }
    // Output to client
    header("HTTP/1.1 ".response()->status());
    foreach (response()->headers() as $header) {
      header($header[0].": ".$header[1]);
    }
    header("X-Processing-Time: " . number_format(microtime(true) - $__benchmark_start, 4));
    ob_end_flush();
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
} catch (http_Unauthorized $ex) {
  // Processing was halted with an "unauthorized"
  ob_end_clean();
  header("HTTP/1.1 401 Unauthorized");
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
  ob_end_clean();
  header("HTTP/1.1 500 Internal Server Error");
  header('Content-Type: text/plain');
  echo "HTTP/1.1 500 Internal Server Error.\n\n";
  echo "Error Resolving request\n\n";
  echo request()->method(), " ", request()->uri(), "\n\n";
  if ($GLOBALS['ENVIRONMENT'] === 'development') {
    echo $ex;
    echo str_repeat(" ", 512);
  }
}
