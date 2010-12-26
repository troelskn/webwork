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

* You need to route all requests to `public/index.php`. The usual configuration would be with Apache - in which case you'll need to have `mod_rewrite` enabled. The `public/.htaccess` file will configure this for you.

Your first application
--

To get started with webwork, make a clone of the repository.

    $ git clone git://github.com/troelskn/webwork.git myapp

Now configure Apache to serve `myapp/public` as the web root. Make sure that `mod_rewrite` is enabled, and that your site is configured with `AllowOverride All`.

Check that everything is working by opening your site, eg. browse to `http://localhost/`. You should see a welcome message. This page is being rendered from `handlers/root.php` which is simply a plain php file. If you go open it in a text editor, you will see a couple of function calls. These are core webwork hooks. Let's take them one by one:

    set_title("It Works");

As the name suggests, this sets a variable for the html-documents `<title>` tag.

    add_stylesheet(url('/res/main.css'));

This adds an external style sheet reference to the document.

    ... <?php e(__FILE__); ?>

The `e` function is shorthand for escape-and-echo. It simply outputs a string in an HTML context. Use this every time you want to output something in HTML.

If you pick "View Source" in your browser, you'll notice that there are some additional markup in the output. Specifically the main `<head>` and `<body>` boilerplate stuff. This is generated from a *layout*  file. In this case, the default layout is used. See in `handlers/default_layout.php`. If you don't want a layout to be rendered, you can disable it by calling:

    set_layout(false);

Or if you want a different layout, you can do so with:

    set_layout('funky');

In which case the file `handlers/funky_layout.php` will be used instead.

As you may have guessed, there are a couple more helpers for putting stuff in the document layout. The function `add_script` adds an external javascript file reference, and `add_onload` adds a piece of inline javascript code to be executed on document load.

A final, useful function to use in rendering output, is the `render` function. Calling it will execute (include) a handler and return the output. This is used for small snippets of reusable code.

TODO
--

A few more features of the framework, that are - for now - undocumented. Have a look at the sources, if you are curious.

* Input/Output (HTTP interaction)
* Routing & URLs
* HTML helpers
* Configuration/environments
* Deployment
* Migrations
* Plugins
