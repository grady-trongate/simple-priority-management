<?php
/**
 * Articles controller
 *
 * Demo module for Simple Priority Management. Shows the four pieces a
 * priority-ordering feature needs:
 *
 *   1. a manage page that lists records in priority order
 *   2. two MX endpoints (move_up / move_down) that perform the swap
 *   3. a partial table view that both the page and the endpoints render,
 *      so the swapped markup is identical to what the browser already has
 *   4. create and delete paths that keep the sequence 1..N
 *
 * There is no authentication in this demo. In a real application every
 * method below would sit behind your own login/role check.
 */
class Articles extends Trongate {

    private int $default_limit = 10;

    /**
     * Default entry point.
     */
    public function index(): void {
        redirect('articles/manage');
    }

    /**
     * Paginated list, ordered by priority.
     */
    public function manage(): void {
        $page = max(1, segment(3, 'int'));
        $table_data = $this->get_table_data($page);

        $data = [
            'table_html' => $this->view('table', $table_data, true)
        ];

        $this->view('manage', $data);
    }

    /**
     * MX endpoint: move one record up one place.
     */
    public function move_up(): void {
        $this->handle_move('up');
    }

    /**
     * MX endpoint: move one record down one place.
     */
    public function move_down(): void {
        $this->handle_move('down');
    }

    /**
     * Shared body of the two move endpoints.
     *
     * The record id is segment 3 and the current page number is segment 4.
     * The page number is carried in the URL so the response can re-render the
     * same slice of the list the user is looking at.
     *
     * The response body is the refreshed table partial - not a status code and
     * not JSON. Trongate MX swaps it into the element named by mx-target.
     */
    private function handle_move(string $direction): void {
        // Performs the CSRF check and kills the request if the token is bad.
        if (!$this->validation->run()) {
            return;
        }

        $update_id = segment(3, 'int');

        if ($update_id > 0) {
            if ($direction === 'up') {
                $this->model->move_up($update_id);
            } else {
                $this->model->move_down($update_id);
            }
        }

        $page = max(1, segment(4, 'int'));

        http_response_code(200);
        $this->view('table', $this->get_table_data($page));
    }

    /**
     * Everything the table partial needs, for one page of results.
     */
    private function get_table_data(int $page): array {
        $page = max(1, $page);
        $limit = $this->default_limit;
        $offset = ($page - 1) * $limit;

        $total_rows = $this->model->count_all();
        $rows = $this->model->fetch_records($limit, $offset);
        $bounds = $this->model->get_priority_bounds();

        return [
            'rows' => $rows,
            'total_rows' => $total_rows,
            'current_page' => $page,
            'lowest_priority' => $bounds['min'],
            'highest_priority' => $bounds['max'],
            'csrf_token' => $this->get_csrf_token(),
            'pagination_data' => [
                'total_rows' => $total_rows,
                'limit' => $limit,
                'pagination_root' => 'articles/manage',
                'pagination_query' => '',
                'record_name_plural' => 'articles',
                'include_showing_statement' => true
            ]
        ];
    }

    /**
     * The session CSRF token, for non-form triggers.
     *
     * Forms get a hidden csrf_token input automatically from form_close().
     * The arrow buttons are not inside a form, so the token is handed to them
     * through mx-vals instead. Same session key either way.
     */
    private function get_csrf_token(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    /**
     * Show the create form.
     */
    public function create(): void {
        $data = [
            'next_priority' => $this->model->get_next_priority(),
            'form_location' => 'articles/submit',
            // Repopulate after a failed submission.
            'title_value' => (string) post('title', true)
        ];

        $this->view('create', $data);
    }

    /**
     * Handle the create form.
     */
    public function submit(): void {
        $submit = post('submit', true);

        if ($submit !== 'Submit') {
            redirect('articles/manage');
            return;
        }

        $this->validation->set_rules('title', 'title', 'required|min_length[2]|max_length[255]');

        if ($this->validation->run()) {
            $title = (string) post('title', true);
            $this->model->create_new_record(['title' => $title]);
            set_flashdata('The article was added to the bottom of the list.');
            redirect('articles/manage');
        }

        // Validation failed: redisplay the form with errors and old input.
        $this->create();
    }

    /**
     * Show the delete confirmation page.
     */
    public function delete_conf(): void {
        $update_id = segment(3, 'int');
        $record = $this->model->get_data_from_db($update_id);

        if ($record === false) {
            redirect('articles/manage');
            return;
        }

        $data = [
            'update_id' => $update_id,
            'title' => $record['title'],
            'priority' => $record['priority'],
            'form_location' => 'articles/submit_delete/'.$update_id
        ];

        $this->view('delete_conf', $data);
    }

    /**
     * Handle delete confirmation.
     */
    public function submit_delete(): void {
        // CSRF check. Runs with no rules set, so nothing else is validated.
        if (!$this->validation->run()) {
            return;
        }

        $submit = post('submit', true);

        if ($submit === 'Yes - Delete Now') {
            $update_id = segment(3, 'int');

            if ($update_id > 0) {
                $this->model->delete_record($update_id);
                set_flashdata('The article was deleted and the list was renumbered.');
            }
        }

        redirect('articles/manage');
    }
}
