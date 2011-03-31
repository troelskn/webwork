<!DOCTYPE html>
<html>
  <head>
    <title><?php e($title); ?></title>
<?php foreach ($stylesheets as $style): ?>
    <link rel="stylesheet" href="<?php e($style); ?>" />
<?php endforeach; ?>
<?php foreach ($scripts as $script): ?>
    <script type="text/javascript" src="<?php e($script); ?>"></script>
<?php endforeach; ?>
  </head>
  <body>
    <?php foreach (get_flash_messages() as $flash): ?>
    <p class="flash-<?php e($flash['type']); ?>"><?php e($flash['message']); ?></p>
    <?php endforeach; ?>
    <?php echo $content; ?>
  </body>
<?php foreach ($onload as $javascript): ?>
    <script type="text/javascript">
      <?php echo $javascript; ?>
    </script>
<?php endforeach; ?>
</html>
