<?php
/**
 * SQL access for WSP.
 *
 * This class wraps the active mysqli connection and keeps the result format
 * used by the existing doSQL() function.
 */
if (!class_exists('doSQL')) {
    class doSQL {
        protected $connection;

        public function __construct($connection) {
            $this->connection = $connection;
        }

        /**
         * Escape a value for use in a SQL string.
         */
        public function escape($value) {
            if ($this->connection) {
                return mysqli_real_escape_string($this->connection, (string)$value);
            }

            return $value;
        }

        /**
         * Execute a statement and return the legacy doSQL() result structure.
         */
        public function query($statement) {
            $set = array(
                'res' => false,
                'aff' => 0,
                'num' => 0,
                'set' => array(),
                'sql' => $statement,
                'inf' => '',
                'err' => '',
                'mysqli' => false
            );

            if (!$this->connection) {
                $set['err'] = 'No active mysqli connection';
                return $set;
            }

            try {
                $result = $this->connection->query($statement);
            } catch (Exception $exception) {
                $set['err'] = $exception;
                if (function_exists('addWSPMsg')) {
                    addWSPMsg('errormsg', '<p>' . nl2br($statement) . '</p>');
                    addWSPMsg('errormsg', '<p>' . nl2br($exception->getMessage()) . '</p>');
                }
                return $set;
            }

            if ($result === true) {
                $set['res'] = true;
                $set['aff'] = $this->connection->affected_rows;
            } elseif ($result instanceof mysqli_result) {
                $set['res'] = true;
                $set['aff'] = $this->connection->affected_rows;
                $set['num'] = $result->num_rows;
                while ($row = $result->fetch_assoc()) {
                    $set['set'][] = $row;
                }
                $result->free();
            } else {
                $set['err'] = $this->connection->error_list;
            }

            if ($set['res']) {
                $set['inf'] = $this->connection->insert_id;
            }
            $set['mysqli'] = mysqli_get_server_info($this->connection);

            return $set;
        }
    }
}
