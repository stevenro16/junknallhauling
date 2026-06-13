<?php

/**
 * SQLite -> MySQL dump generator
 * --------------------------------------------------------------------------
 * Reads the local dev database (database/database.sqlite) and writes a
 * MySQL-compatible .sql file you can import into production through phpMyAdmin
 * (Import tab) or the mysql CLI.
 *
 * Usage:   php database/sqlite-to-mysql.php
 * Output:  database/sqlite-to-mysql.sql
 *
 * Notes:
 *   - The output is idempotent: each table is DROP-ed and recreated, so the
 *     file can be re-imported cleanly. It WILL overwrite matching tables in
 *     the target database.
 *   - Transient framework tables (cache, sessions, queue) get their schema
 *     created but their rows are NOT exported (see $skipData).
 *   - The generated .sql contains real customer data + the admin password
 *     hash. Keep it out of version control / don't share it publicly.
 */

$sqlitePath = __DIR__ . '/database.sqlite';
$outPath    = __DIR__ . '/sqlite-to-mysql.sql';

if (!file_exists($sqlitePath)) {
    fwrite(STDERR, "SQLite database not found at: $sqlitePath\n");
    exit(1);
}

$db = new PDO('sqlite:' . $sqlitePath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tables whose schema we recreate but whose (transient) rows we skip.
$skipData = [
    'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches',
    'failed_jobs', 'password_reset_tokens',
];

/** Quote an identifier for MySQL. */
function bq(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

/** Map a SQLite declared type to a MySQL column type. */
function mapType(string $sqliteType, bool $isAutoPk): string
{
    if ($isAutoPk) {
        return 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT';
    }

    $t = strtolower(trim($sqliteType));

    if ($t === '') {
        return 'LONGTEXT';
    }
    // tinyint(1) / boolean must be checked before the generic "int" match
    if (str_contains($t, 'tinyint(1)') || str_contains($t, 'bool')) {
        return 'TINYINT(1)';
    }
    if (str_contains($t, 'int')) {
        return 'BIGINT';
    }
    if (str_contains($t, 'char') || str_contains($t, 'varchar')) {
        return 'VARCHAR(255)';
    }
    if (str_contains($t, 'text') || str_contains($t, 'clob')) {
        return 'LONGTEXT';
    }
    if (str_contains($t, 'numeric') || str_contains($t, 'decimal') || str_contains($t, 'dec')) {
        return 'DECIMAL(15,2)';
    }
    if (str_contains($t, 'doub') || str_contains($t, 'real') || str_contains($t, 'floa')) {
        return 'DOUBLE';
    }
    if (str_contains($t, 'datetime')) {
        return 'DATETIME';
    }
    if (str_contains($t, 'date')) {
        return 'DATE';
    }
    if (str_contains($t, 'time')) {
        return 'TIME';
    }
    if (str_contains($t, 'blob')) {
        return 'LONGBLOB';
    }
    return 'VARCHAR(255)';
}

/** MySQL string literal for a value (NULL-safe, escaped). */
function val($v): string
{
    if ($v === null) {
        return 'NULL';
    }
    $s = (string) $v;
    $s = str_replace('\\', '\\\\', $s);
    $s = str_replace("'", "\\'", $s);
    // escape the bytes that would otherwise break a SQL import stream
    $s = str_replace(["\0", "\032"], ['\\0', '\\Z'], $s);
    return "'" . $s . "'";
}

$tables = $db->query(
    "SELECT name, sql FROM sqlite_master
      WHERE type='table' AND name NOT LIKE 'sqlite_%'
      ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$out = [];
$out[] = '-- ---------------------------------------------------------------------------';
$out[] = '-- MySQL import generated from SQLite (database/database.sqlite)';
$out[] = '-- Generator: database/sqlite-to-mysql.php';
$out[] = '-- Import via phpMyAdmin > Import, or:  mysql -u USER -p DBNAME < this-file.sql';
$out[] = '-- ---------------------------------------------------------------------------';
$out[] = '';
$out[] = 'SET NAMES utf8mb4;';
$out[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
$out[] = "SET time_zone = '+00:00';";
$out[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$out[] = '';

foreach ($tables as $tbl) {
    $table     = $tbl['name'];
    $createSql = strtolower($tbl['sql']);
    $hasAuto   = str_contains($createSql, 'autoincrement');

    $columns = $db->query('PRAGMA table_info(' . bq($table) . ')')->fetchAll(PDO::FETCH_ASSOC);

    $defs    = [];
    $pkCols  = [];
    foreach ($columns as $col) {
        $name      = $col['name'];
        $isAutoPk  = $hasAuto && (int) $col['pk'] === 1 && str_contains(strtolower($col['type']), 'int');
        $mysqlType = mapType($col['type'], $isAutoPk);

        $def = bq($name) . ' ' . $mysqlType;

        if (!$isAutoPk) {
            $def .= ((int) $col['notnull'] === 1) ? ' NOT NULL' : ' NULL';

            // DEFAULTs — MySQL forbids literal defaults on TEXT/BLOB columns,
            // so skip those (the data INSERT still carries the real value).
            $isLob = str_contains($mysqlType, 'TEXT') || str_contains($mysqlType, 'BLOB');
            if ($col['dflt_value'] !== null && !$isLob) {
                $default = trim($col['dflt_value']);
                if (strtoupper($default) === 'CURRENT_TIMESTAMP') {
                    $def .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $def .= ' DEFAULT ' . $default; // already quoted by SQLite for strings
                }
            }
        }

        $defs[] = '  ' . $def;
        if ((int) $col['pk'] > 0) {
            $pkCols[(int) $col['pk']] = $name;
        }
    }

    if ($pkCols) {
        ksort($pkCols);
        $defs[] = '  PRIMARY KEY (' . implode(', ', array_map('bq', $pkCols)) . ')';
    }

    // Secondary indexes (skip the implicit primary-key index).
    $indexList = $db->query('PRAGMA index_list(' . bq($table) . ')')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($indexList as $idx) {
        if (($idx['origin'] ?? '') === 'pk') {
            continue;
        }
        $idxCols = $db->query('PRAGMA index_info(' . bq($idx['name']) . ')')->fetchAll(PDO::FETCH_ASSOC);
        $cols    = array_map(fn ($c) => bq($c['name']), $idxCols);
        $kind    = ((int) $idx['unique'] === 1) ? 'UNIQUE KEY' : 'KEY';
        $defs[]  = '  ' . $kind . ' ' . bq($idx['name']) . ' (' . implode(', ', $cols) . ')';
    }

    // Foreign keys.
    $fks = $db->query('PRAGMA foreign_key_list(' . bq($table) . ')')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fks as $fk) {
        $constraint = 'fk_' . $table . '_' . $fk['from'];
        $clause = '  CONSTRAINT ' . bq($constraint)
            . ' FOREIGN KEY (' . bq($fk['from']) . ')'
            . ' REFERENCES ' . bq($fk['table']) . ' (' . bq($fk['to']) . ')';
        if (!empty($fk['on_delete']) && strtoupper($fk['on_delete']) !== 'NO ACTION') {
            $clause .= ' ON DELETE ' . strtoupper($fk['on_delete']);
        }
        if (!empty($fk['on_update']) && strtoupper($fk['on_update']) !== 'NO ACTION') {
            $clause .= ' ON UPDATE ' . strtoupper($fk['on_update']);
        }
        $defs[] = $clause;
    }

    $out[] = '-- -------------------- table: ' . $table . ' --------------------';
    $out[] = 'DROP TABLE IF EXISTS ' . bq($table) . ';';
    $out[] = 'CREATE TABLE ' . bq($table) . " (\n" . implode(",\n", $defs)
        . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $out[] = '';

    // ---- data ----
    if (in_array($table, $skipData, true)) {
        $out[] = '-- (transient table — schema only, rows skipped)';
        $out[] = '';
        continue;
    }

    $rows = $db->query('SELECT * FROM ' . bq($table))->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        $out[] = '-- (no rows)';
        $out[] = '';
        continue;
    }

    $colNames  = array_keys($rows[0]);
    $colList   = implode(', ', array_map('bq', $colNames));
    $batchSize = 100;

    for ($i = 0; $i < count($rows); $i += $batchSize) {
        $batch = array_slice($rows, $i, $batchSize);
        $tuples = [];
        foreach ($batch as $row) {
            $vals = array_map(fn ($c) => val($row[$c]), $colNames);
            $tuples[] = '(' . implode(', ', $vals) . ')';
        }
        $out[] = 'INSERT INTO ' . bq($table) . ' (' . $colList . ") VALUES\n"
            . implode(",\n", $tuples) . ';';
    }
    $out[] = '';
}

$out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$out[] = '';

file_put_contents($outPath, implode("\n", $out));

$bytes = filesize($outPath);
fwrite(STDOUT, "Wrote $outPath (" . number_format($bytes) . " bytes, " . count($tables) . " tables)\n");
