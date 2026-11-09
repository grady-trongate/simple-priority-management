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
        .priority-moves { display: inline-flex; gap: 0.35rem; vertical-align: middle; }

        /* The arrows are real buttons, styled down to plain glyphs. */
        .priority-arrow {
            background: none;
            border: 0;
            border-radius: 0;
            margin: 0;
            padding: 0;
            min-width: 0;
            font-size: 0.8rem;
            line-height: 1;
            color: #000;
            cursor: pointer;
            transition: opacity 0.15s ease, transform 0.15s ease;
        }

        .priority-arrow:hover:not([disabled]),
        .priority-arrow:focus-visible { opacity: 0.6; transform: scale(1.25); }
        .priority-arrow:focus-visible { outline: 2px solid #000; outline-offset: 2px; }
        .priority-arrow[disabled] { opacity: 0.2; cursor: default; transform: none; }
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
