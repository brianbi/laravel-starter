<?php

namespace App\Traits;

trait Exportable
{
    /**
     * Get the exportable fields definition.
     * Override this method in your model to define exportable fields.
     *
     * @return array<string, array{label: string, width?: int, formatter?: string}>
     */
    public function getExportableFields(): array
    {
        return [];
    }

    /**
     * Get the export resource name.
     * Override this method to customize the resource name.
     */
    public function getExportResourceName(): string
    {
        return $this->getTable();
    }

    /**
     * Get field labels for export header.
     *
     * @param array|null $selectedFields Fields to include, null for all
     * @return array<string, string>
     */
    public function getExportHeaders(?array $selectedFields = null): array
    {
        $fields = $this->getExportableFields();

        if ($selectedFields !== null) {
            $fields = array_filter(
                $fields,
                fn($key) => in_array($key, $selectedFields),
                ARRAY_FILTER_USE_KEY
            );
        }

        return array_map(fn($config) => $config['label'], $fields);
    }

    /**
     * Get field widths for Excel export.
     *
     * @param array|null $selectedFields Fields to include, null for all
     * @return array<string, int>
     */
    public function getExportWidths(?array $selectedFields = null): array
    {
        $fields = $this->getExportableFields();

        if ($selectedFields !== null) {
            $fields = array_filter(
                $fields,
                fn($key) => in_array($key, $selectedFields),
                ARRAY_FILTER_USE_KEY
            );
        }

        $widths = [];
        foreach ($fields as $field => $config) {
            $widths[$field] = $config['width'] ?? 15;
        }

        return $widths;
    }

    /**
     * Get a row of data for export.
     *
     * @param array|null $selectedFields Fields to include, null for all
     * @return array
     */
    public function toExportRow(?array $selectedFields = null): array
    {
        $fields = $this->getExportableFields();

        if ($selectedFields !== null) {
            $fields = array_filter(
                $fields,
                fn($key) => in_array($key, $selectedFields),
                ARRAY_FILTER_USE_KEY
            );
        }

        $row = [];
        foreach ($fields as $field => $config) {
            $value = $this->getNestedValue($field);

            // Apply formatter if specified
            if (isset($config['formatter']) && method_exists($this, $config['formatter'])) {
                $value = $this->{$config['formatter']}($value);
            }

            $row[$field] = $value;
        }

        return $row;
    }

    /**
     * Get a nested attribute value using dot notation.
     *
     * @param string $key
     * @return mixed
     */
    protected function getNestedValue(string $key): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->getAttribute($key);
        }

        $parts = explode('.', $key);
        $value = $this;

        foreach ($parts as $part) {
            if ($value === null) {
                return null;
            }

            if (is_object($value)) {
                $value = $value->$part ?? ($value->getAttribute($part) ?? null);
            } elseif (is_array($value)) {
                $value = $value[$part] ?? null;
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Get all exportable field keys.
     *
     * @return array
     */
    public function getExportableFieldKeys(): array
    {
        return array_keys($this->getExportableFields());
    }

    /**
     * Get exportable fields as options for frontend.
     *
     * @return array
     */
    public function getExportFieldOptions(): array
    {
        $options = [];
        foreach ($this->getExportableFields() as $field => $config) {
            $options[] = [
                'value' => $field,
                'label' => $config['label'],
            ];
        }

        return $options;
    }
}
