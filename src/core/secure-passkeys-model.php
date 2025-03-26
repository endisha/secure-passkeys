<?php

namespace Secure_Passkeys\Core;

defined('ABSPATH') || exit;

abstract class Secure_Passkeys_Model
{
    protected \Wpdb $db;

    protected $table;

    protected $last_inserted_id = 0;

    protected $per_page = 20;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        global $wpdb;
        $table = $wpdb->base_prefix . $this->table;

        $this->db = $wpdb;
        $this->table = esc_sql($table);
    }

    /**
     * Get first
     */
    public function first(int $id)
    {
        return $this->db->get_row(
            $this->db->prepare("
                SELECT * FROM $this->table WHERE `id` = %d
            ", $id)
        );
    }

    /**
     * Update data
     */
    public function update(array $data, array $where = [])
    {
        return $this->db->update($this->table, $data, $where);
    }

    /**
     * Insert data
     */
    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        $last_inserted_id = $this->db->insert_id;
        $this->last_inserted_id = $last_inserted_id;
        return $last_inserted_id > 0;
    }

    /**
     * Get last inserted ID
     */
    public function get_last_inserted_id()
    {
        return $this->last_inserted_id;
    }

    /**
     * Delete data
     */
    public function delete(array $where = [])
    {
        return $this->db->delete($this->table, $where);
    }

    /**
     * Paginate data
     */
    public function get_all_paginate(array $columns = [], int $per_page = 10, array $filter_params = [])
    {
        $per_page = $per_page > 0 ? $per_page : $this->per_page;

        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

        $query = [];
        $query['params'] = [];
        // Count
        $query['sql']['count'][] = "SELECT count(*) as `count` FROM `{$this->table}`";
        // Columns
        $columns = !empty($columns) ? implode(',', $columns) : '*';
        $query['sql']['select'][] = "SELECT {$columns} FROM `{$this->table}`";
        // Apply Filters
        if (!empty($filter_params)) {
            $sub_queries = $this->apply_paginate_filters($filter_params);
            $query['params'] = $sub_queries['params'];
            $query['sql']['where'] = $sub_queries['where'];
        }
        // Where Query
        $where_query = '';
        if (isset($query['sql']['where']) && !empty($query['sql']['where'])) {
            foreach ($query['sql']['where'] as $c => $where_sql) {
                $where_query .= $c > 0 ? ' AND ' : ' WHERE ';
                $where_query .= $where_sql;
            }
        }
        // Count Query
        $count_query = implode(" ", $query['sql']['count']);
        $count_query .= $where_query;
        $count_params = $query['params'];
        if (!empty($count_params)) {
            $count_query = $this->db->prepare($count_query, $count_params);
        }
        $count = $this->db->get_var($count_query);
        $pages = ceil($count / $per_page);
        $query['sql']['order'][] = ' ORDER BY id DESC ';
        $query['sql']['limit'][] = ' LIMIT %d ';
        $query['sql']['offset'][] = 'OFFSET %d';
        $query['params'][] = $per_page;
        $query['params'][] = $per_page * ($page - 1);

        $sql = implode(" ", $query['sql']['select']);
        $sql .= $where_query;
        $sql .= implode(" ", $query['sql']['order']);
        $sql .= implode(" ", $query['sql']['limit']);
        $sql .= implode(" ", $query['sql']['offset']);

        $params = $query['params'];

        $records = $this->db->get_results(
            $this->db->prepare($sql, $params)
        );

        return [
            'records' => (array) $records,
            'count' => intval($count),
            'current' => intval($page),
            'pages' => intval($pages)
        ];
    }

    private function apply_paginate_filters(?array $filter_params = [])
    {
        $params = [];
        $where = [];

        if (method_exists($this, 'paginate_filters')) {
            $allowed_filters = call_user_func([$this, 'paginate_filters']);
            foreach ($allowed_filters as $key => $type) {
                if (isset($filter_params[$key]) && !empty($filter_params[$key])) {
                    $value = trim(sanitize_text_field($filter_params[$key] ?? ''));
                    if ($type === 'int' && is_numeric($value)) {
                        $where[] = "`$key` = %d";
                        $params[] = intval($value);
                    } elseif ($type === 'date_range' && !empty($value)) {
                        $where[] = "`$key` >= %s AND `$key` < %s + INTERVAL 1 DAY";
                        $params[] = $value;
                        $params[] = $value;
                    } elseif (is_callable([$this, 'paginate_filters_custom_' . $type])) {
                        $callback_query = call_user_func([$this, 'paginate_filters_custom_' . $type], $value);
                        if (!empty($callback_query) && is_array($callback_query) && count($callback_query) === 2) {
                            [$custom_where, $custom_params] = $callback_query;
                            if (!empty($custom_where)) {
                                $where[] = $custom_where;
                                if (!empty($custom_params)) {
                                    $params = array_merge($params, $custom_params);
                                }
                            }
                        }
                    } else {
                        $where[] = "`$key` = %s";
                        $params[] = $value;
                    }
                }
            }
        }

        return [
            'params' => $params,
            'where' => $where
        ];
    }

    public function delete_old_records(int $days)
    {
        return $this->db->query(
            $this->db->prepare(
                'DELETE FROM ' . $this->table . ' WHERE datediff(now(), `created_at`) >= %d',
                $days
            )
        );
    }
}
