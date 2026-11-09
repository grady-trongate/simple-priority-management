<?php
/**
 * Manage page.
 *
 * Deliberately a bare, self-contained HTML page: the demo then runs in any
 * Trongate v2 application, with or without admin templates. In a real module,
 * replace everything outside the content block with your own template call,
 * e.g. $this->templates->admin($data).
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate.css">
    <script src="js/trongate-mx.min.js"></script>
    <title>Articles</title>
    <style>
        .priority-cell { white-space: nowrap; }
        .priority-number { display: inline-block; min-width: 1.5em; font-weight: bold; }
        .priority-arrows { display: inline-flex; gap: 2px; vertical-align: middle; }

        /* The arrows are plain links holding nothing but a triangle. */
        .priority-arrow {
            display: inline-block;
            padding: 0 2px;
            font-size: 0.75em;
            font-weight: normal;
            line-height: 1;
            text-decoration: none;
            color: #000;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }

        .priority-arrow:hover,
        .priority-arrow:focus-visible { opacity: 0.6; }
        .priority-arrow:focus-visible { outline: 2px solid #000; outline-offset: 1px; }

        /* End of the list: drawn, but not a link. */
        .priority-arrow.is-off { opacity: 0.2; cursor: default; }
    </style>
</head>
<body>
<div class="container">
    <h1>Articles</h1>

    <?= flashdata() ?>

    <p><?= anchor('articles/create', 'Create New Article', ['class' => 'button']) ?></p>

    <?= $table_html ?>
</div>
</body>
</html>
