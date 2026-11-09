<?php
/**
 * Delete confirmation.
 *
 * Deleting a record leaves a hole in the priority sequence, which the model
 * closes by resequencing. That is why the demo keeps a delete path at all.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate.css">
    <title>Delete Article</title>
</head>
<body>
<div class="container">
    <h1>Delete Article</h1>

    <p>You are about to delete <strong><?= out($title) ?></strong>
        (priority <?= out((string) $priority) ?>).</p>
    <p>The remaining articles will be renumbered so the sequence stays 1&hellip;N.</p>

    <?= form_open($form_location) ?>
        <button type="submit" name="submit" value="Yes - Delete Now" class="danger">Yes - Delete Now</button>
    <?= anchor('articles/manage', 'Cancel', ['class' => 'button alt']) ?>
    <?= form_close() ?>
</div>
</body>
</html>
