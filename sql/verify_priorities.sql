-- Verification for a priority-managed list.
-- Replace `articles` with your table (and add the scope predicate if the
-- list is ordered within a group: `WHERE group_id = ?`).

-- 1. Sequence is sane. Expected: total = highest, lowest = 1.
--    (An empty table legitimately returns 0/0/0.)
SELECT COUNT(*) AS total,
       COALESCE(MIN(priority), 0) AS lowest,
       COALESCE(MAX(priority), 0) AS highest
FROM `articles`;

-- 2. No duplicates. Expected: no rows.
SELECT priority, COUNT(*) AS c
FROM `articles`
GROUP BY priority
HAVING c > 1;

-- 3. No non-positive values. Expected: no rows.
--    Negative values are only ever transient, inside a transaction.
SELECT id, priority FROM `articles` WHERE priority < 1;

-- 4. The list, in the order the administrator sees it.
SELECT id, priority, title FROM `articles` ORDER BY priority ASC, id ASC;

-- 5. Confirm the UNIQUE index exists. Expected: one row for uniq_priority.
SHOW INDEX FROM `articles` WHERE Key_name = 'uniq_priority';
