<?php

namespace IbnulHusainan\Arc\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TableSchema
{
    protected string $table;

    public function __construct(string &$table)
    {
        $this->table =& $table;
    }

    public function exists(): bool
    {
        if (Schema::hasTable($this->table)) {
            return true;
        } elseif (Schema::hasTable(Str::plural($this->table))) {
            $this->table = Str::plural($this->table);
            return true;
        }

        return false;
    }

    /** List of columns only */
    public function columns(): array
    {
        return Schema::getColumnListing($this->table);
    }

    /** Get raw column info from DB */
    private function rawColumns(): array
    {
        $driver = \DB::getDriverName();

        if ($driver === 'mysql') {
            return \DB::select("
                SELECT COLUMN_NAME, COLUMN_KEY, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH,
                       COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE, EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            ", [$this->table]);
        }
    
        if ($driver === 'sqlsrv') {
            return \DB::select("
                SELECT c.COLUMN_NAME,
                       c.DATA_TYPE,
                       c.CHARACTER_MAXIMUM_LENGTH,
                       c.COLUMN_DEFAULT,
                       c.IS_NULLABLE,
                       NULL AS COLUMN_TYPE,
                       CASE WHEN k.COLUMN_NAME IS NOT NULL THEN 'PRI' ELSE '' END AS COLUMN_KEY,
                       '' AS EXTRA,
                       COLUMNPROPERTY(OBJECT_ID(QUOTENAME(c.TABLE_SCHEMA) + '.' + QUOTENAME(c.TABLE_NAME)), c.COLUMN_NAME, 'IsIdentity') AS IS_IDENTITY
                FROM INFORMATION_SCHEMA.COLUMNS c
                LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
                    ON c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME
                    AND OBJECTPROPERTY(OBJECT_ID(k.CONSTRAINT_NAME), 'IsPrimaryKey') = 1
                WHERE c.TABLE_NAME = ?
            ", [$this->table]);
        }

        throw new \RuntimeException("Unsupported driver: $driver");
    }

    /** Full schema info */
    public function schemas(): array
    {
        $rawColumns = $this->rawColumns();

        $primaryKeyInfo = $this->getPrimaryKeyInfo($rawColumns);
        $timestamps     = $this->getTimestamps($rawColumns);
        $columns        = $this->getColumnsInfo($rawColumns, $primaryKeyInfo['name'], $timestamps);

        return [
            'table'        => $this->table,
            'primaryKey'   => $primaryKeyInfo['name'],
            'keyType'      => $primaryKeyInfo['type'],
            'incrementing' => $primaryKeyInfo['incrementing'],
            'timestamps'   => $timestamps ?: [],
            'columns'      => $columns,
        ];
    }

    /** Detect primary key info */
    private function getPrimaryKeyInfo(array $raw): array
    {
        $primaryKey   = 'id';
        $keyType      = 'int';
        $incrementing = false;

        foreach ($raw as $col) {
            if ($col->COLUMN_KEY === 'PRI') {
                $primaryKey = $col->COLUMN_NAME;
                $incrementing = isset($col->IS_IDENTITY)
                    ? ((bool) $col->IS_IDENTITY ?? false)
                    : str_contains($col->EXTRA, 'auto_increment');

                if (in_array($col->DATA_TYPE, ['char', 'varchar']) && (int) $col->CHARACTER_MAXIMUM_LENGTH === 36) {
                    $keyType = 'string';
                } elseif (in_array($col->DATA_TYPE, ['int', 'bigint', 'smallint'])) {
                    $keyType = 'int';
                } else {
                    $keyType = 'string';
                }
                break;
            }
        }

        return [
            'name'        => $primaryKey,
            'type'        => $keyType,
            'incrementing'=> $incrementing,
        ];
    }

    /** Detect timestamp columns */
    private function getTimestamps(array $raw): array
    {
        $timestamps = [];

        foreach ($raw as $col) {
            if (in_array($col->DATA_TYPE, ['datetime', 'timestamp', 'datetime2']) && preg_match('/(created|updated|deleted)/i', $col->COLUMN_NAME)) {
                $timestamps[] = $col->COLUMN_NAME;
            }
        }

        return $timestamps;
    }

    /** Build column info excluding primary key and timestamps */
    private function getColumnsInfo(array $raw, string $primaryKey, array $timestamps): array
    {
        $columns = [];

        foreach ($raw as $col) {
            $name = $col->COLUMN_NAME;

            if ($name === $primaryKey || in_array($name, $timestamps)) {
                continue;
            }

            $colInfo = [
                'type'      => $col->DATA_TYPE,
                'nullable'  => $col->IS_NULLABLE === 'YES',
                'default'   => $col->COLUMN_DEFAULT,
                'maxLength' => $col->CHARACTER_MAXIMUM_LENGTH,
            ];

            // Enum
            if (str_starts_with($col->COLUMN_TYPE, 'enum(')) {
                preg_match("/^enum\((.*)\)$/", $col->COLUMN_TYPE, $matches);
                if (!empty($matches[1])) {
                    $enumValues = array_map(fn($v) => trim($v, "'"), explode(',', $matches[1]));
                    $colInfo['enum'] = $enumValues;
                }
            }

            $columns[$name] = $colInfo;
        }

        return $columns;
    }

}
