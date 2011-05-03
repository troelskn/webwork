Webwork
==

What is Webwork?
--

I guess you can call it a framework. Although it doesn't really have many of the traditional constraints that people have come to expect from a framework. Webwork is more a collection of handy tools that you might use as a foundation for a php website.

In a nutshell, webwork gives you:

* An abstraction of http request and response
* A routing mechanism
* A directory structure
* A way to organise configuration, migration and deployment

Other than that, it tries hard to get out of your way. Instead of imposing paradigms from other platforms on you, Webwork stays true to PHP. There is no inherent distinction between controllers and views (Although you're free to make one if you so wish). There is no template engine. There are no deep stacktraces and complicated dispatch mechanisms.

If you know basic PHP, you can comprehend all of Webwork in less than an hour.

Unlike most frameworks, there is no built-in concept of controllers and views. The dispatcher delegates control to a handler - which is just a fancy word for a flat php-file that gets included. This means that the handlers are almost in complete control of the application flow, allowing you to drop plain old procedural php code in there. Or even delegate control to another framework, if you need to. This makes it easy to progressively grow into - or out of - webwork.

Requirements
--

* Webwork is tested with PHP 5.2, but should work on any PHP 5.X installation.

* Webwork is only tested with Apache, but it should be simple to configure it for other web servers.

Your first application
--

To get started with webwork, make a clone of the repository.

    $ git clone git://github.com/troelskn/webwork.git myapp

Now configure Apache to serve `myapp/public` as the web root. Make sure that `mod_rewrite` is enabled, and that your site is configured with `AllowOverride All`.

Then create a log file (and make sure it has the correct permissions:

    $ mkdir log
    $ touch log/debug.log

Check that everything is working by opening your site, eg. browse to `http://localhost/`. You should see a welcome message. This page is being rendered from `handlers/root.php` which is simply a plain php file. If you go open it in a text editor, you will see a couple of function calls. These are core webwork hooks. Let's take them one by one:

    document()->setTitle("It Works");

As the name suggests, this sets a variable for the html-documents `<title>` tag.

    document()->addStylesheet('/res/main.css');

This adds an external style sheet reference to the document header.

    ... <?php e(__FILE__); ?>

The `e` function is shorthand for escape-and-echo. It simply outputs a string in an HTML context. Use this every time you want to output something in HTML.

If you pick "View Source" in your browser, you'll notice that there are some additional markup in the output. Specifically the main `<head>` and `<body>` boilerplate stuff. This is generated from a *layout*  file. In this case, the default layout is used. See in `handlers/layouts/default.php`. If you don't want a layout to be rendered, you can disable it by calling:

    document()->setLayout(false);

Or if you want a different layout, you can do so with:

    document()->setLayout('funky');

In which case the file `handlers/layouts/funky.php` will be used instead.

As you may have guessed, there are a couple more helpers for putting stuff in the document layout. The function `addScript` adds an external javascript file reference, and `addOnload` adds a piece of inline javascript code to be executed on document load.

A final, useful function to use in rendering output, is the `render` function. Calling it will execute (include) a handler and return the output. This is used for small snippets of reusable code.

Input/Output wrappers
---

Webwork has an abstraction that wraps access to the HTTP request and response. While you can still use PHP's native interfaces, it is recommended to use the wrappers instead. Not only do they provide a richer and more uniform API, but using them also ensures that your code can be stubbed out for tests etc.

Webwork comes with the following wrappers for HTTP:

* `request()`
* `response()`
* `cookie()`
* `session()`

See `vendor/webwork/docs/web.inc.php` for details about each of these.

Routing & URLs
---

Webwork has a simple routing mechanism which is used to map incoming requests to the proper handler. You can see how it work in the function `resolve_route` and in `public/index.php`. Basically, the global variable `$GLOBALS['ROUTES']` is a hash of `regular-expression => handler`. The request uri is matched against each of these in turn and the first match is rendered.

Routes are defined in the file `config/routes.inc.php`. See this file for some more examples.

To see a list of all routes, you can run the script in `scripts/routes` from the console.

HTML helpers
---

Instead of hardcoding URL's throughout your application, we recommend that you create helper-function to generate URLs. These should be placed in the `routes.inc.php` file. Such function should be postfixed with `_url` - Eg. `root_url()` or `users_url()`.

A common pattern is to have a url helper for model classes. If, for example, your application has a class `User`, you should also have a helper called `user_url($user)`. If you follow this recipe, you can use the higher level helper `url_for()`, which will take any object and return the proper link for it.

In addition to url helpers, webwork comes with a collection of html-helper functions that can be used for rendering common html form elements. All of these are prefixed with `html_`. For example `html_link()` that generates an `a`-tag. You can see a description of each of the core html helpers in: `vendor/webwork/docs/html_helpers.inc.php`.


TODO
--

A few more features of the framework, that are - for now - undocumented. You can some info out of looking at the annotated sources under `vendor/webwork/docs/`

* Configuration/environments
* Deployment
* Database and Migrations
* Plugins
