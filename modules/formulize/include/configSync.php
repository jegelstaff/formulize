<?php

class FormulizeConfigSync {
    private $db;
    private $configPath;
    private $changes = [];
    private $diffLog = [];

    // Configuration types and their corresponding table mappings
    private $configTypes = [
        'forms' => [
            'table' => 'formulize_id',
            'primaryKey' => 'id_form',
            'jsonFile' => 'forms.json',
            'identifierField' => 'form_handle'
        ],
        // 'elements' => [
        //     'table' => 'formulize',
        //     'primaryKey' => 'ele_id',
        //     'jsonFile' => 'elements.json',
        //     'identifierField' => 'ele_handle'
        // ],
        // 'relationships' => [
        //     'table' => 'formulize_relationships',
        //     'primaryKey' => 'rel_id',
        //     'jsonFile' => 'relationships.json',
        //     'identifierField' => 'rel_handle'
        // ]
        // Add other configuration types as needed
    ];

    public function __construct(string $configPath) {
        $this->configPath = rtrim($configPath, '/');
        $this->initializeDatabase();
    }

    private function initializeDatabase() {
        try {
            $this->db = new \PDO(
                'mysql:host=' . XOOPS_DB_HOST . ';dbname=' . XOOPS_DB_NAME,
                XOOPS_DB_USER,
                XOOPS_DB_PASS
            );
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->query("SET NAMES utf8mb4");
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Compare JSON configurations with database state
     *
     * @return array Array of differences found
     */
    public function compareConfigurations(): array {
        $this->changes = [];
        $this->diffLog = [];

        foreach ($this->configTypes as $type => $config) {
            $jsonConfig = $this->loadJsonConfig($config['jsonFile']);
            if (!$jsonConfig) {
                continue;
            }

            $dbConfig = $this->loadDatabaseConfig($config['table']);
            $this->compareConfigType($type, $jsonConfig, $dbConfig);
        }

        return [
            'changes' => $this->changes,
            'log' => $this->diffLog
        ];
    }

    /**
     * Load configuration from JSON file
     */
    private function loadJsonConfig(string $filename): array {
        $filepath = XOOPS_ROOT_PATH . '/modules/formulize/' . $this->configPath . '/' . $filename;
        if (!file_exists($filepath)) {
            $this->diffLog[] = "Warning: Configuration file not found: {$filepath}";
            return [];
        }

        $content = file_get_contents($filepath);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in {$filename}: " . json_last_error_msg());
        }

        return $config;
    }

    /**
     * Load configuration from database
     */
    private function loadDatabaseConfig(string $table): array {
        $table = $this->prefixTable($table);
        $stmt = $this->db->prepare("SELECT * FROM {$table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Compare specific configuration type
     */
    private function compareConfigType(string $type, array $jsonConfig, array $dbConfig): void {
        $typeConfig = $this->configTypes[$type];
        $identifierField = $typeConfig['identifierField'];

        // Index database config by identifier field for easier comparison
        $dbConfigIndexed = [];
        foreach ($dbConfig as $item) {
            $dbConfigIndexed[$item[$identifierField]] = $item;
        }

        // Compare each JSON config item with database
        foreach ($jsonConfig[$type] as $item) {
            $identifier = $item[$identifierField];

            if (!isset($dbConfigIndexed[$identifier])) {
                // Item exists in JSON but not in DB - needs to be created
                $this->addChange($type, 'create', $item);
                continue;
            }

            // Compare fields for differences
            $differences = $this->compareFields($item, $dbConfigIndexed[$identifier]);
            if (!empty($differences)) {
                $this->addChange($type, 'update', $item, $differences);
            }
        }

        // Check for items in DB that don't exist in JSON
        foreach ($dbConfigIndexed as $identifier => $dbItem) {
            $exists = false;
            foreach ($jsonConfig[$type] as $jsonItem) {
                if ($jsonItem[$identifierField] === $identifier) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $this->addChange($type, 'delete', $dbItem);
            }
        }
    }

    /**
     * Compare individual fields between JSON and DB configs
     */
    private function compareFields(array $jsonItem, array $dbItem): array {
        $differences = [];

        foreach ($jsonItem as $field => $value) {
            if (!isset($dbItem[$field])) {
                $differences[$field] = [
                    'type' => 'missing_field',
                    'json_value' => $value,
                    'db_value' => null
                ];
                continue;
            }

            if ($this->normalizeValue($value) !== $this->normalizeValue($dbItem[$field])) {
                $differences[$field] = [
                    'type' => 'value_mismatch',
                    'json_value' => $value,
                    'db_value' => $dbItem[$field]
                ];
            }
        }

        return $differences;
    }

    /**
     * Add a change to the change log
     */
    private function addChange(string $type, string $operation, array $data, array $differences = []): void {
        $this->changes[] = [
            'type' => $type,
            'operation' => $operation,
            'data' => $data,
            'differences' => $differences
        ];

        $identifier = $data[$this->configTypes[$type]['identifierField']];
        $this->diffLog[] = sprintf(
            "%s: %s %s '%s'",
            ucfirst($operation),
            $type,
            $this->configTypes[$type]['identifierField'],
            $identifier
        );
    }

    /**
     * Apply changes to the database
     */
    public function applyChanges(): array {
        $results = [
            'success' => [],
            'failure' => []
        ];

        try {
            $this->db->beginTransaction();

            foreach ($this->changes as $change) {
                try {
                    $this->applyChange($change);
                    $results['success'][] = $change;
                } catch (\Exception $e) {
                    $results['failure'][] = [
                        'change' => $change,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception("Failed to apply changes: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Apply a single change
     */
    private function applyChange(array $change): void {
        $typeConfig = $this->configTypes[$change['type']];
        $table = $this->prefixTable($typeConfig['table']);

        switch ($change['operation']) {
            case 'create':
                $this->insertRecord($table, $change['data']);
                break;

            case 'update':
                $this->updateRecord($table, $change['data'], $typeConfig);
                break;

            case 'delete':
                $this->deleteRecord($table, $change['data'], $typeConfig);
                break;
        }
    }

    /**
     * Insert a new record
     */
    private function insertRecord(string $table, array $data): void {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            '`' . implode('`, `', $fields) . '`',
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
    }

    /**
     * Update an existing record
     */
    private function updateRecord(string $table, array $data, array $typeConfig): void {
        $fields = array_keys($data);
        $sets = array_map(function($field) {
            return "`{$field}` = ?";
        }, $fields);

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $table,
            implode(', ', $sets),
            $typeConfig['primaryKey']
        );

        $values = array_values($data);
        $values[] = $data[$typeConfig['primaryKey']];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
    }

    /**
     * Delete a record
     */
    private function deleteRecord(string $table, array $data, array $typeConfig): void {
        $sql = sprintf(
            "DELETE FROM %s WHERE %s = ?",
            $table,
            $typeConfig['primaryKey']
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data[$typeConfig['primaryKey']]]);
    }

    /**
     * Normalize value for comparison
     */
    private function normalizeValue($value) {
        if (is_array($value)) {
            return json_encode($value, JSON_SORT_KEYS);
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        return (string) $value;
    }

    /**
     * Prefix table name with database prefix
     */
    private function prefixTable(string $table): string {
        return XOOPS_DB_PREFIX . '_' . trim($table, '_');
    }
}
