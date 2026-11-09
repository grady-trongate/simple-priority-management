<?php
/**
 * Create form.
 *
 * Like manage.php, this is a bare page so the demo has no template
 * dependency. The priority is not editable: it is assigned on insert.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate.css">
    <title>Create New Article</title>
</head>
<body>
<div class="container">
    <h1>Create New Article</h1>

    <p>Once saved, this article will be added to the bottom of the list at
        priority <strong><?= out((string) $next_priority) ?></strong>.</p>

    <?= validation_errors() ?>

    <?= form_open($form_location) ?>
        <label for="title">Title</label>
        <?= form_input('title', $title_value, ['id' => 'title', 'placeholder' => 'Enter a title']) ?>
        <button type="submit" name="submit" value="Submit">Submit</button>
    <?= anchor('articles/manage', 'Cancel', ['class' => 'button alt']) ?>
    <?= form_close() ?>
</div>
</body>
</html>
