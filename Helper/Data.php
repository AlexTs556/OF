<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use ReflectionClass;

class Data extends AbstractHelper
{
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Get fields from a model by analyzing its getter methods
     *
     * @param object|string $model Instance or class name
     * @return array
     */
    public function getFields($model): array
    {
        // Determine class name (object or string)
        $className = is_object($model) ? get_class($model) : $model;

        if (!class_exists($className)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($className);
            $methods = $reflection->getMethods();

            $fields = [];
            foreach ($methods as $method) {
                $name = $method->getName();

                // Check for getter methods
                if (str_starts_with($name, 'get') && strlen($name) > 3) {
                    $field = substr($name, 3);

                    // Convert CamelCase to snake_case
                    $snakeCase = $this->camelToSnake($field);

                    // Skip common system fields
                    if (!in_array($snakeCase, ['id', 'entity_id', 'created_at', 'updated_at'])) {
                        $fields[] = $snakeCase;
                    }
                }
            }

            return array_unique($fields);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get fields from model's data interface if available
     *
     * @param object|string $model Instance or class name
     * @return array
     */
    public function getFieldsFromInterface($model): array
    {
        $className = is_object($model) ? get_class($model) : $model;

        if (!class_exists($className)) {
            return [];
        }

        // Find data interface (usually ends with Interface)
        $interfaces = class_implements($className);
        if (empty($interfaces)) {
            return [];
        }

        // Look for data interface (usually contains 'Data' and ends with 'Interface')
        $dataInterface = null;
        foreach ($interfaces as $interface) {
            if (strpos($interface, 'Data') !== false && str_ends_with($interface, 'Interface')) {
                $dataInterface = $interface;
                break;
            }
        }

        if (!$dataInterface) {
            // Fallback to first interface
            $dataInterface = reset($interfaces);
        }

        try {
            $reflection = new ReflectionClass($dataInterface);
            $methods = $reflection->getMethods();

            $fields = [];
            foreach ($methods as $method) {
                $name = $method->getName();

                if (str_starts_with($name, 'get') && strlen($name) > 3) {
                    $field = substr($name, 3);
                    $snakeCase = $this->camelToSnake($field);

                    if (!in_array($snakeCase, ['id', 'entity_id'])) {
                        $fields[] = $snakeCase;
                    }
                }
            }

            return array_unique($fields);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all available fields (from model and interface)
     *
     * @param object|string $model Instance or class name
     * @return array
     */
    public function getAllFields($model): array
    {
        $modelFields = $this->getFields($model);
        $interfaceFields = $this->getFieldsFromInterface($model);

        return array_unique(array_merge($modelFields, $interfaceFields));
    }

    /**
     * Convert CamelCase to snake_case
     *
     * @param string $input
     * @return string
     */
    private function camelToSnake(string $input): string
    {
        // Add underscore before uppercase letters (except first)
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $input);

        // Convert to lowercase
        return strtolower($snake);
    }

    /**
     * Get model data as array with field names
     *
     * @param object $model
     * @return array
     */
    public function getModelData(object $model): array
    {
        $fields = $this->getAllFields($model);
        $data = [];

        foreach ($fields as $field) {
            $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));

            if (method_exists($model, $getter)) {
                $data[$field] = $model->$getter();
            }
        }

        return $data;
    }
}
