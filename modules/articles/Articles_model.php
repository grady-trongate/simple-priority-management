<?php
/**
 * Articles_model
 *
 * Model for the demo `articles` table.
 *
 * The ordering logic lives in the "Priority ordering core" block below. That
 * block references nothing except $table_name, the primary key (id) and the
 * priority column, so it can be copied into another model unchanged.
 */
class Articles_model extends Trongate {

    private string $table_name = 'articles';

    /* ---------------------------------------------------------------------
     | Priority ordering core
     |
     | Portable. Copy this whole block into your own model and change
     | $table_name. Do not rewrite the algorithm - the transaction and the
     | negative placeholder are what make it safe under a UNIQUE index.
     | ------------------------------------------------------------------- */

    /**
     * Read one page of records in priority order.
     *
     * id ASC is a tie-breaker only. It never matters while priority is
     * unique, but it keeps ordering stable if somebody later removes the
     * UNIQUE index.
     */
    public function fetch_records(int $limit, int $offset): array {
        $sql = 'SELECT * FROM '.$this->table_name
            .' ORDER BY priority ASC, id ASC LIMIT '.$limit.' OFFSET '.$offset;
        return $this->db->query($sql, 'object');
    }

    /**
     * Lowest and highest priority values currently in use.
     *
     * Used by the view to disable the up arrow on the highest-priority row
     * and the down arrow on the lowest-priority row, so the browser never
     * fires a request that cannot do anything.
     *
     * Returns min/max of 0 when the table is empty.
     */
    public function get_priority_bounds(): array {
        $sql = 'SELECT COALESCE(MIN(priority), 0) AS lowest,'
            .' COALESCE(MAX(priority), 0) AS highest FROM '.$this->table_name;
        $result = $this->db->query($sql, 'object');

        return [
            'min' => (int) ($result[0]->lowest ?? 0),
            'max' => (int) ($result[0]->highest ?? 0)
        ];
    }

    /**
     * The priority a brand-new record should receive: current highest + 1.
     *
     * New records are therefore appended to the bottom of the list.
     */
    public function get_next_priority(): int {
        $sql = 'SELECT COALESCE(MAX(priority), 0) + 1 AS next_priority FROM '.$this->table_name;
        $result = $this->db->query($sql, 'object');
        return (int) ($result[0]->next_priority ?? 1);
    }

    /**
     * Stored priority for a single record (0 when the record is missing).
     */
    public function get_priority(int $update_id): int {
        $sql = 'SELECT priority FROM '.$this->table_name.' WHERE id = :id';
        $result = $this->db->query_bind($sql, ['id' => $update_id], 'object');
        return (int) ($result[0]->priority ?? 0);
    }

    /**
     * Move a record one place up the order.
     *
     * @return bool False when the record is already at the top.
     */
    public function move_up(int $update_id): bool {
        return $this->move_record($update_id, 'up');
    }

    /**
     * Move a record one place down the order.
     *
     * @return bool False when the record is already at the bottom.
     */
    public function move_down(int $update_id): bool {
        return $this->move_record($update_id, 'down');
    }

    /**
     * Swap a record's priority with that of its nearest neighbour.
     *
     * A "move" is a swap of two values, never a renumbering of the whole
     * table: exactly two rows are touched regardless of list size.
     *
     * The swap runs in three statements inside a transaction. The first parks
     * the moving record on a negative value, which frees its slot; the
     * neighbour then takes that slot, and finally the moving record takes the
     * neighbour's old slot. The negative park is essential - under a UNIQUE
     * index, a direct two-way assignment would collide with itself halfway
     * through. Negative values can never collide because stored priorities
     * are always positive.
     */
    private function move_record(int $update_id, string $direction): bool {
        $record = $this->db->get_where($update_id, $this->table_name);

        if ($record === false) {
            return false;
        }

        $current_priority = (int) $record->priority;

        if ($direction === 'up') {
            $sql = 'SELECT id, priority FROM '.$this->table_name
                .' WHERE priority < :priority ORDER BY priority DESC LIMIT 1';
        } else {
            $sql = 'SELECT id, priority FROM '.$this->table_name
                .' WHERE priority > :priority ORDER BY priority ASC LIMIT 1';
        }

        $neighbour = $this->db->query_bind($sql, ['priority' => $current_priority], 'object');

        if (empty($neighbour)) {
            return false;
        }

        $neighbour_id = (int) $neighbour[0]->id;
        $neighbour_priority = (int) $neighbour[0]->priority;

        $this->db->query('START TRANSACTION');
        try {
            $this->db->update($update_id, ['priority' => -$current_priority], $this->table_name);
            $this->db->update($neighbour_id, ['priority' => $current_priority], $this->table_name);
            $this->db->update($update_id, ['priority' => $neighbour_priority], $this->table_name);
            $this->db->query('COMMIT');
        } catch (\Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }

        $this->ensure_sequential_priorities();

        return true;
    }

    /**
     * Renumber every record so priorities run 1..N with no gaps.
     *
     * Order is preserved (priority ASC, id ASC). Under a UNIQUE index the new
     * values cannot be written directly, so the work happens in two passes:
     * park everything on a unique negative value, then apply 1..N.
     *
     * The intended order is fetched first and ranked in PHP. Do not replace
     * this with SQL user variables or a window function that ranks inside the
     * UPDATE - the optimiser is free to reorder the rows it updates, which
     * silently shuffles the list.
     */
    public function resequence_priorities(): void {
        $sql = 'SELECT id FROM '.$this->table_name.' ORDER BY priority ASC, id ASC';
        $rows = $this->db->query($sql, 'object');

        if (empty($rows)) {
            return;
        }

        $this->db->query('START TRANSACTION');
        try {
            foreach ($rows as $index => $row) {
                $this->db->update((int) $row->id, ['priority' => -($index + 1)], $this->table_name);
            }

            foreach ($rows as $index => $row) {
                $this->db->update((int) $row->id, ['priority' => $index + 1], $this->table_name);
            }

            $this->db->query('COMMIT');
        } catch (\Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Resequence only when a gap actually exists.
     *
     * Cheap guard: a 1..N sequence always satisfies COUNT(*) === MAX(priority).
     */
    public function ensure_sequential_priorities(): void {
        $sql = 'SELECT COUNT(*) AS total, COALESCE(MAX(priority), 0) AS highest FROM '.$this->table_name;
        $result = $this->db->query($sql, 'object');
        $total = (int) ($result[0]->total ?? 0);
        $highest = (int) ($result[0]->highest ?? 0);

        if ($total > 0 && $highest !== $total) {
            $this->resequence_priorities();
        }
    }

    /* ---------------------------------------------------------------------
     | End of priority ordering core
     | ------------------------------------------------------------------- */

    /**
     * Create a record, assigning it the next available priority.
     *
     * The retry loop covers a race: two simultaneous inserts can read the
     * same "highest + 1". The UNIQUE index rejects the loser, and the loser
     * simply tries again with a freshly read value.
     */
    public function create_new_record(array $data): int {
        $this->ensure_sequential_priorities();

        $attempts = 5;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $data['priority'] = $this->get_next_priority();

            try {
                return $this->db->insert($data, $this->table_name);
            } catch (\Throwable $e) {
                // 23000 / 1062 = duplicate key: another insert claimed this priority.
                $is_duplicate = str_contains($e->getMessage(), '1062')
                    || str_contains($e->getMessage(), 'Duplicate entry');

                if (!$is_duplicate || $attempt === $attempts) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Unable to assign a unique priority.');
    }

    /**
     * Delete a record, then close the gap its priority leaves behind.
     */
    public function delete_record(int $update_id): void {
        $this->db->delete($update_id, $this->table_name);
        $this->resequence_priorities();
    }

    /**
     * Fetch a single record as a plain array (false when it does not exist).
     */
    public function get_data_from_db(int $update_id): array|false {
        $record = $this->db->get_where($update_id, $this->table_name);

        if ($record === false) {
            return false;
        }

        return (array) $record;
    }

    /**
     * Total number of records (used for pagination).
     */
    public function count_all(): int {
        return $this->db->count($this->table_name);
    }
}
