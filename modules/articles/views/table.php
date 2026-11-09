<?php
/**
 * Table partial - the MX swap target.
 *
 * This file is rendered twice: once inside manage.php, and again as the
 * entire response body of articles/move_up and articles/move_down.
 *
 * The wrapping #articles-table div is part of this file so that a swap with
 * mx-swap="outerHTML" replaces the whole block, arrows and pagination
 * included. If the wrapper lived in manage.php instead, an outerHTML swap
 * would delete it and the next click would have nowhere to write.
 *
 * Expected data: rows, total_rows, current_page, lowest_priority,
 * highest_priority, csrf_token, pagination_data.
 */

// Sent with every arrow click. Carrying the page number means the response
// re-renders the slice of the list the user is actually looking at.
$mx_vals = json_encode([
    'csrf_token' => $csrf_token,
    'page' => $current_page
]);
?>
<div id="articles-table">

<?php if (empty($rows)): ?>
    <p>There are no articles to display.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Priority</th>
                <th class="text-left">Title</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row):
            $row_priority = (int) $row->priority;

            // Shared attributes for both arrows. The arrows are plain links,
            // not buttons: the glyph is nothing but a triangle (&#9650; or
            // &#9660;), and MX needs no more than the mx-post attribute.
            $move_attr = [
                'class' => 'priority-arrow',
                'mx-target' => '#articles-table',
                'mx-swap' => 'outerHTML',
                'mx-vals' => $mx_vals
            ];

            $up_attr = $move_attr + [
                'title' => 'Move up',
                'aria-label' => 'Move '.$row->title.' up',
                'mx-post' => 'articles/move_up/'.$row->id.'/'.$current_page
            ];

            $down_attr = $move_attr + [
                'title' => 'Move down',
                'aria-label' => 'Move '.$row->title.' down',
                'mx-post' => 'articles/move_down/'.$row->id.'/'.$current_page
            ];

            // At the ends of the list the arrow is still drawn, but it is a
            // plain span: there is no request to make, so none is offered.
            $up_arrow = ($row_priority <= $lowest_priority)
                ? '<span class="priority-arrow is-off" aria-hidden="true">&#9650;</span>'
                : anchor('#', '&#9650;', $up_attr);

            $down_arrow = ($row_priority >= $highest_priority)
                ? '<span class="priority-arrow is-off" aria-hidden="true">&#9660;</span>'
                : anchor('#', '&#9660;', $down_attr);
            ?>
            <tr>
                <td class="priority-cell">
                    <span class="priority-number"><?= out((string) $row->priority) ?></span>
                    <span class="priority-arrows"><?= $up_arrow ?><?= $down_arrow ?></span>
                </td>
                <td class="text-left"><?= out($row->title) ?></td>
                <td><?= anchor('articles/delete_conf/'.$row->id, 'Delete', ['class' => 'button alt']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?= Modules::run('pagination/display', $pagination_data) ?>
</div>
