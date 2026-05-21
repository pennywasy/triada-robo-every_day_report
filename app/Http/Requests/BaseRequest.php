<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationException;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseRequest
{
    protected array $data = [];
    protected array $errors = [];
    protected array $validated = [];

    private static array $reflectionCache = [];

    /**
     * @throws ValidationException
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->validate();

        if (!$this->isValid()) {
            throw new ValidationException($this->errors);
        }

        $this->mapProperties();
    }

    abstract protected function rules(): array;

    protected function messages(): array
    {
        return [];
    }

    protected function attributes(): array
    {
        return [];
    }

    protected function validate(): void
    {
        $compositeProperties = $this->getCompositeProperties();

        foreach ($compositeProperties as $field => $requestClass) {
            $value = $this->data[$field] ?? [];

            if (!is_array($value) && $value !== null) {
                $this->errors[$field][] = "Поле $field должно быть объектом";
                continue;
            }

            $isNullable = str_starts_with($requestClass, '?');

            if ($value === null && $isNullable) {
                $this->validated[$field] = null;
                continue;
            }

            $cleanClassName = trim($requestClass, '?');

            try {
                /** @var BaseRequest $request */
                $request = new $cleanClassName($value ?? []);

                if ($request->isValid()) {
                    // Сохраняем ОБЪЕКТ в validated
                    $this->validated[$field] = $request;

                    // Устанавливаем свойство сразу
                    if (property_exists($this, $field)) {
                        $this->$field = $request;
                    }
                } else {
                    foreach ($request->getErrors() as $nestedField => $errors) {
                        $this->errors["{$field}.{$nestedField}"] = $errors;
                    }
                }
            } catch (ValidationException $e) {
                foreach ($e->getErrors() as $nestedField => $errors) {
                    $this->errors["{$field}.{$nestedField}"] = $errors;
                }
            } catch (\Throwable $e) {
                $this->errors[$field][] = "Ошибка валидации поля $field: " . $e->getMessage();
            }
        }

        foreach ($this->rules() as $field => $rules) {
            if (isset($compositeProperties[$field])) {
                continue;
            }

            $value = $this->data[$field] ?? null;

            foreach ((array)$rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    protected function getCompositeProperties(): array
    {
        $className = static::class;

        if (!isset(self::$reflectionCache[$className])) {
            $reflection = new ReflectionClass($this);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            $compositeProperties = [];

            foreach ($properties as $property) {
                $type = $property->getType();

                if ($type && !$type->isBuiltin()) {
                    $typeName = $type->getName();

                    if (is_subclass_of($typeName, BaseRequest::class) || $typeName === BaseRequest::class) {
                        $compositeProperties[$property->getName()] = $typeName;
                    }
                }
            }

            self::$reflectionCache[$className] = $compositeProperties;
        }

        return self::$reflectionCache[$className];
    }

    protected function applyRule(string $field, $value, string $rule): void
    {
        [$ruleName, $ruleParams] = $this->parseRule($rule);

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    $this->addError($field, 'required');
                }
                break;

            case 'string':
                if (!is_string($value)) {
                    $this->addError($field, 'string');
                }
                break;

            case 'integer':
                if (!is_int($value) && !ctype_digit((string)$value)) {
                    $this->addError($field, 'integer');
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, 'numeric');
                }
                break;

            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
                    $this->addError($field, 'boolean');
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                }
                break;

            case 'min':
                if (is_string($value) && strlen($value) < $ruleParams) {
                    $this->addError($field, 'min.string', ['min' => $ruleParams]);
                } elseif (is_numeric($value) && $value < $ruleParams) {
                    $this->addError($field, 'min.numeric', ['min' => $ruleParams]);
                }
                break;

            case 'max':
                if (is_string($value) && strlen($value) > $ruleParams) {
                    $this->addError($field, 'max.string', ['max' => $ruleParams]);
                } elseif (is_numeric($value) && $value > $ruleParams) {
                    $this->addError($field, 'max.numeric', ['max' => $ruleParams]);
                }
                break;

            case 'array':
                if (!is_array($value)) {
                    $this->addError($field, 'array');
                }
                break;

            case 'in':
                if (!in_array($value, explode(',', $ruleParams), true)) {
                    $this->addError($field, 'in');
                }
                break;

            case 'confirmed':
                if ($value !== ($this->data[$field . '_confirmation'] ?? null)) {
                    $this->addError($field, 'confirmed');
                }
                break;

            case 'date':
                if (!is_string($value) || !strtotime($value)) {
                    $this->addError($field, 'date');
                }
                break;

            case 'nullable':
                break;
        }
    }

    protected function parseRule(string $rule): array
    {
        $parts = explode(':', $rule, 2);
        return [
            $parts[0],
            $parts[1] ?? null
        ];
    }

    protected function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->getMessage($field, $rule, $params);
        $this->errors[$field][] = $message;
    }

    protected function getMessage(string $field, string $rule, array $params): string
    {
        $customMessage = $this->messages()["{$field}.{$rule}"]
            ?? $this->messages()[$field]
            ?? null;

        if ($customMessage) {
            return $this->replacePlaceholders($customMessage, $params);
        }

        $attribute = $this->attributes()[$field] ?? $field;
        $defaultMessages = $this->getDefaultMessages();

        $messageTemplate = $defaultMessages[$rule]
            ?? "Поле {$attribute} содержит ошибку";

        $message = str_replace(':attribute', $attribute, $messageTemplate);
        return $this->replacePlaceholders($message, $params);
    }

    protected function getDefaultMessages(): array
    {
        return [
            'required' => 'Поле :attribute обязательно для заполнения',
            'string' => 'Поле :attribute должно быть строкой',
            'integer' => 'Поле :attribute должно быть целым числом',
            'numeric' => 'Поле :attribute должно быть числом',
            'boolean' => 'Поле :attribute должно быть булевым значением',
            'email' => 'Поле :attribute должно быть корректным email',
            'min.string' => 'Поле :attribute должно содержать минимум :min символов',
            'max.string' => 'Поле :attribute должно содержать максимум :max символов',
            'min.numeric' => 'Поле :attribute должно быть не меньше :min',
            'max.numeric' => 'Поле :attribute должно быть не больше :max',
            'array' => 'Поле :attribute должно быть массивом',
            'in' => 'Поле :attribute должно быть одним из допустимых значений',
            'confirmed' => 'Поле :attribute не совпадает с подтверждением',
            'date' => 'Поле :attribute должно быть корректной датой',
        ];
    }

    protected function replacePlaceholders(string $message, array $params): string
    {
        foreach ($params as $key => $value) {
            $message = str_replace(":{$key}", $value, $message);
        }
        return $message;
    }

    protected function mapProperties(): void
    {
        // mapProperties уже установил объекты в свойства в методе validate()
        // Здесь устанавливаем только простые значения
        foreach ($this->validated as $key => $value) {
            if (property_exists($this, $key) && !$value instanceof BaseRequest) {
                $this->$key = $value;
            }
        }
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        $result = [];
        foreach ($this->validated as $key => $value) {
            if ($value instanceof BaseRequest) {
                $result[$key] = $value->validated();
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return $this->validated[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->validated();
    }

    public function __isset(string $name): bool
    {
        return property_exists($this, $name) || isset($this->validated[$name]);
    }
}