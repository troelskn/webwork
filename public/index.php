<?php
require_once '../bootstrap.inc.php';
require_once 'web.inc.php';
ob_start();
try {
  render_in_place(resolve_route(request()->uri()));
  if (isset($GLOBALS['RENDER_LAYOUT'])) {
    $content = ob_get_clean();
    ob_start();
    render_in_place($GLOBALS['RENDER_LAYOUT'].'_layout', array('content' => $content));
  }
  header("HTTP/1.1 ".response()->status());
  foreach (response()->headers() as $header) {
    header($header[0].": ".$header[1]);
  }
  ob_end_flush();
} catch (HttpSeeOther $ex) {
  header("HTTP/1.1 303 See Other");
  header('Location: ' . $ex->getMessage());
} catch (HttpNotModified $ex) {
  header("HTTP/1.1 304 Not Modified");
  foreach ($ex->getMessage() as $header) {
    header($header);
  }
} catch (HttpNotFound $ex) {
  header("HTTP/1.1 404 Not Found");
  echo "The requested page could not be found.";
} catch (HttpResponse $ex) {
  header('Content-Type: text/plain');
  echo "Error Resolving request\n\n";
  echo request()->method(), " ", request()->uri(), "\n\n";
  echo $ex;
}
