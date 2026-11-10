<?php
/**
 * Create or synchronize tables from the WSP structure XML format.
 *
 * Expected format:
 * <structure><table name="..."><field name="..." type="int" size="11"
 * not_null="true" default="0" auto_increment="true" primary_key="true" />
 * </table></structure>
 */
if (!class_exists('dbTools')) {
    class dbTools
    {
        private $connection;
        private $lastError = '';

        public function __construct($connection)
        {
            $this->connection = $connection;
        }

        /**
         * Synchronize every table in the given XML. Returns success, actions,
         * and any SQL/schema errors; existing columns are never removed.
         */
        public function applyStructure($xml): array
        {
            $report = ['success' => false, 'actions' => [], 'errors' => []];
            if (!$this->connection instanceof mysqli) {
                $report['errors'][] = 'Keine aktive mysqli-Verbindung.';
                return $report;
            }

            if ($xml instanceof SimpleXMLElement) {
                $root = $xml;
            } else {
                libxml_use_internal_errors(true);
                $root = simplexml_load_string((string)$xml);
                if ($root === false) {
                    foreach (libxml_get_errors() as $error) {
                        $report['errors'][] = 'Ungültiges XML: ' . trim($error->message);
                    }
                    libxml_clear_errors();
                    return $report;
                }
            }

            $tables = [];
            if ($root->getName() === 'table') {
                $tables[] = $root;
            } else {
                foreach ($root->table as $table) $tables[] = $table;
            }
            if (!$tables) {
                $report['errors'][] = 'Das XML enthält keine <table>-Elemente.';
                return $report;
            }

            foreach ($tables as $table) {
                $name = (string)$table['name'];
                if (!$this->validIdentifier($name)) {
                    $report['errors'][] = "Ungültiger Tabellenname: {$name}";
                    continue;
                }
                $fields = [];
                foreach ($table->field as $field) {
                    $definition = $this->parseField($field, $name, $report['errors']);
                    if ($definition !== null) $fields[$definition['name']] = $definition;
                }
                if (!$fields) {
                    $report['errors'][] = "Tabelle {$name}: keine gültigen Felder definiert.";
                    continue;
                }
                $this->syncTable($name, $fields, $report);
            }
            $report['success'] = count($report['errors']) === 0;
            return $report;
        }

        /** Alias for callers that prefer a shorter function name. */
        public function sync($xml): array
        {
            return $this->applyStructure($xml);
        }

        /**
         * Return one table's schema as XML compatible with applyStructure().
         * Returns false on failure; getLastError() then contains the reason.
         */
        public function tableToXml(string $table)
        {
            $this->lastError = '';
            if (!$this->connection instanceof mysqli) {
                $this->lastError = 'Keine aktive mysqli-Verbindung.';
                return false;
            }
            if (!$this->validIdentifier($table)) {
                $this->lastError = "Ungültiger Tabellenname: {$table}";
                return false;
            }
            $exists = $this->connection->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = " . $this->sqlString($table));
            if (!$exists) {
                $this->lastError = "Tabelle {$table}: Prüfung fehlgeschlagen: " . $this->connection->error;
                return false;
            }
            $found = $exists->num_rows > 0;
            $exists->free();
            if (!$found) {
                $this->lastError = "Tabelle {$table} existiert nicht in der aktuellen Datenbank.";
                return false;
            }

            $result = $this->connection->query('SHOW FULL COLUMNS FROM ' . $this->quote($table));
            if (!$result) {
                $this->lastError = "Tabelle {$table}: Struktur konnte nicht gelesen werden: " . $this->connection->error;
                return false;
            }
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<structure>\n  <table name=\"" . $this->xmlEscape($table) . "\">\n";
            while ($column = $result->fetch_assoc()) {
                if (!preg_match('/^([a-z]+)(?:\((.*)\))?( unsigned)?$/i', $column['Type'], $matches)) {
                    $result->free();
                    $this->lastError = "Tabelle {$table}, Feld {$column['Field']}: Typ '{$column['Type']}' kann nicht in das Struktur-XML übertragen werden.";
                    return false;
                }
                $type = strtolower($matches[1]);
                $size = $matches[2] ?? '';
                $unsigned = !empty($matches[3]);
                $attributes = [
                    'name' => $column['Field'],
                    'type' => $type,
                ];
                if ($size !== '') $attributes['size'] = $size;
                if ($unsigned) $attributes['unsigned'] = 'true';
                $attributes['not_null'] = $column['Null'] === 'NO' ? 'true' : 'false';
                if ($column['Default'] !== null) $attributes['default'] = (string)$column['Default'];
                if (strpos(strtolower($column['Extra']), 'auto_increment') !== false) $attributes['auto_increment'] = 'true';
                if ($column['Key'] === 'PRI') $attributes['primary_key'] = 'true';
                $xml .= '    <field';
                foreach ($attributes as $key => $value) $xml .= ' ' . $key . '="' . $this->xmlEscape($value) . '"';
                $xml .= " />\n";
            }
            $result->free();
            return $xml . "  </table>\n</structure>\n";
        }

        public function getLastError(): string
        {
            return $this->lastError;
        }

        private function parseField(SimpleXMLElement $field, string $table, array &$errors): ?array
        {
            $name = (string)$field['name'];
            $type = strtolower((string)$field['type']);
            $size = (string)$field['size'];
            $allowed = ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'varchar', 'char', 'text', 'mediumtext', 'longtext', 'date', 'datetime', 'timestamp', 'float', 'double', 'decimal', 'blob', 'mediumblob', 'longblob'];
            if (!$this->validIdentifier($name) || !in_array($type, $allowed, true)) {
                $errors[] = "Tabelle {$table}: ungültige Felddefinition für '{$name}'.";
                return null;
            }
            if ($size !== '' && (!ctype_digit($size) || (int)$size < 1)) {
                $errors[] = "Tabelle {$table}, Feld {$name}: ungültige Größe '{$size}'.";
                return null;
            }
            $needsSize = in_array($type, ['int','tinyint','smallint','mediumint','bigint','varchar','char','decimal'], true);
            if ($needsSize && $size === '') {
                $errors[] = "Tabelle {$table}, Feld {$name}: Größe fehlt für {$type}.";
                return null;
            }
            $notNull = $this->boolAttr($field, 'not_null', false);
            $auto = $this->boolAttr($field, 'auto_increment', false);
            $primary = $this->boolAttr($field, 'primary_key', false);
            if ($auto && (!$notNull || !in_array($type, ['int','tinyint','smallint','mediumint','bigint'], true))) {
                $errors[] = "Tabelle {$table}, Feld {$name}: AUTO_INCREMENT erfordert einen ganzzahligen NOT NULL-Typ.";
                return null;
            }
            $unsigned = $this->boolAttr($field, 'unsigned', false);
            if ($unsigned && !in_array($type, ['int','tinyint','smallint','mediumint','bigint','float','double','decimal'], true)) {
                $errors[] = "Tabelle {$table}, Feld {$name}: UNSIGNED ist für {$type} nicht zulässig.";
                return null;
            }
            $sqlType = $type . ($size !== '' ? '(' . $size . ')' : '') . ($unsigned ? ' unsigned' : '');
            $defaultProvided = array_key_exists('default', $field->attributes()->asArray());
            $default = $defaultProvided ? (string)$field['default'] : null;
            return compact('name', 'type', 'size', 'sqlType', 'notNull', 'auto', 'primary', 'defaultProvided', 'default');
        }

        private function syncTable(string $table, array $fields, array &$report): void
        {
            $tableSql = $this->quote($table);
            $result = $this->connection->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = " . $this->sqlString($table));
            if (!$result) {
                $report['errors'][] = "Tabelle {$table}: Prüfung fehlgeschlagen: " . $this->connection->error;
                return;
            }
            $exists = $result->num_rows > 0;
            $result->free();

            if (!$exists) {
                $definitions = [];
                $primary = [];
                foreach ($fields as $field) {
                    $definitions[] = $this->columnSql($field);
                    if ($field['primary']) $primary[] = $this->quote($field['name']);
                }
                if ($primary) $definitions[] = 'PRIMARY KEY (' . implode(', ', $primary) . ')';
                $sql = 'CREATE TABLE ' . $tableSql . ' (' . implode(', ', $definitions) . ')';
                if ($this->run($sql, $table, $report)) $report['actions'][] = "Tabelle {$table} angelegt.";
                return;
            }

            $result = $this->connection->query('SHOW FULL COLUMNS FROM ' . $tableSql);
            if (!$result) {
                $report['errors'][] = "Tabelle {$table}: Spalten konnten nicht gelesen werden: " . $this->connection->error;
                return;
            }
            $current = [];
            $currentPrimary = [];
            while ($row = $result->fetch_assoc()) {
                $current[$row['Field']] = $row;
                if ($row['Key'] === 'PRI') $currentPrimary[] = $row['Field'];
            }
            $result->free();

            foreach ($fields as $name => $field) {
                if (!isset($current[$name])) {
                    if ($this->run('ALTER TABLE ' . $tableSql . ' ADD ' . $this->columnSql($field), $table, $report)) {
                        $report['actions'][] = "Tabelle {$table}: Feld {$name} ergänzt.";
                    }
                    continue;
                }
                $differences = $this->differences($current[$name], $field);
                if (!$differences) continue;
                if ($this->run('ALTER TABLE ' . $tableSql . ' MODIFY ' . $this->columnSql($field), $table, $report)) {
                    $report['actions'][] = "Tabelle {$table}: Feld {$name} angepasst (" . implode(', ', $differences) . ').";
                }
            }

            $expectedPrimary = [];
            foreach ($fields as $field) if ($field['primary']) $expectedPrimary[] = $field['name'];
            sort($expectedPrimary);
            sort($currentPrimary);
            if ($expectedPrimary !== $currentPrimary) {
                $report['errors'][] = "Tabelle {$table}: Primärschlüssel weicht ab (vorhanden: " . ($currentPrimary ? implode(', ', $currentPrimary) : 'keiner') . '; erwartet: ' . ($expectedPrimary ? implode(', ', $expectedPrimary) : 'keiner') . '). Die Schlüsseldefinition wird nicht automatisch geändert.';
            }
        }

        private function differences(array $actual, array $expected): array
        {
            $differences = [];
            $actualType = strtolower(preg_replace('/\s+/', '', $actual['Type']));
            $expectedType = strtolower($expected['sqlType']);
            if ($actualType !== $expectedType) $differences[] = 'Typ';
            if (($actual['Null'] === 'NO') !== $expected['notNull']) $differences[] = 'NULL-Eigenschaft';
            if ((strtoupper((string)$actual['Extra']) === 'AUTO_INCREMENT') !== $expected['auto']) $differences[] = 'AUTO_INCREMENT';
            if ($expected['defaultProvided']) {
                $actualDefault = $actual['Default'];
                $expectedDefault = strtoupper($expected['default']) === 'NULL' ? null : $expected['default'];
                if ((string)$actualDefault !== (string)$expectedDefault || (($actualDefault === null) !== ($expectedDefault === null))) $differences[] = 'DEFAULT';
            }
            return $differences;
        }

        private function columnSql(array $field): string
        {
            $sql = $this->quote($field['name']) . ' ' . $field['sqlType'] . ($field['notNull'] ? ' NOT NULL' : ' NULL');
            if ($field['defaultProvided']) {
                $sql .= strtoupper($field['default']) === 'NULL' ? ' DEFAULT NULL' : ' DEFAULT ' . $this->sqlString($field['default']);
            }
            if ($field['auto']) $sql .= ' AUTO_INCREMENT';
            return $sql;
        }

        private function run(string $sql, string $table, array &$report): bool
        {
            if ($this->connection->query($sql) === true) return true;
            $report['errors'][] = "Tabelle {$table}: SQL konnte nicht ausgeführt werden: " . $this->connection->error . " [{$sql}]";
            return false;
        }

        private function boolAttr(SimpleXMLElement $element, string $name, bool $default): bool
        {
            if (!isset($element[$name])) return $default;
            return in_array(strtolower((string)$element[$name]), ['1', 'true', 'yes'], true);
        }

        private function validIdentifier(string $identifier): bool
        {
            return preg_match('/^[A-Za-z0-9_$-]+$/', $identifier) === 1;
        }

        private function quote(string $identifier): string
        {
            return '`' . str_replace('`', '``', $identifier) . '`';
        }

        private function sqlString(string $value): string
        {
            return "'" . $this->connection->real_escape_string($value) . "'";
        }

        private function xmlEscape(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }
    }
}
