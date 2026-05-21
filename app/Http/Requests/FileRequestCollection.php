<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationException;

class FileRequestCollection extends BaseRequest
{
    /**
     * @var FileRequest[]
     */
    public array $files = [];

    protected function rules(): array
    {
        return [
            'files' => ['nullable', 'array'],
        ];
    }

    public function __construct(array $data)
    {
        $this->data = $data;

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $index => $fileData) {
                try {
                    $fileRequest = new FileRequest($fileData);
                    if ($fileRequest->isValid()) {
                        $this->files[] = $fileRequest;
                        $this->validated['files'][] = $fileRequest->validated();
                    } else {
                        foreach ($fileRequest->getErrors() as $errorField => $errors) {
                            $this->errors["files.$index.$errorField"] = $errors;
                        }
                    }
                } catch (ValidationException $e) {
                    foreach ($e->getErrors() as $errorField => $errors) {
                        $this->errors["files.$index.$errorField"] = $errors;
                    }
                }
            }
        } elseif (isset($data['files']) && $data['files'] !== null) {
            $this->errors['files'][] = 'Поле files должно быть массивом или null';
        }

        foreach ($this->rules() as $field => $rules) {
            if ($field === 'files') {
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

        if (!$this->isValid()) {
            throw new ValidationException($this->errors);
        }

        $this->mapProperties();
    }


    public function filterByType(array|string $types): array
    {
        return array_filter($this->files, function(FileRequest $file) use ($types) {
            return $file->isType($types);
        });
    }


    public function getImages(): array
    {
        return $this->filterByType(['image/']);
    }

    public function getPdfs(): array
    {
        return $this->filterByType('application/pdf');
    }


    public function getDocuments(): array
    {
        return $this->filterByType([
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/pdf',
        ]);
    }

    public function saveAllTo(string $directory, bool $useSafeNames = false): array
    {
        $savedPaths = [];

        foreach ($this->files as $index => $file) {
            $filename = $useSafeNames ? $file->generateSafeFilename() : $file->fileName;
            $path = $file->saveTo($directory, $filename);

            if ($path) {
                $savedPaths[] = [
                    'index' => $index,
                    'path' => $path,
                    'originalName' => $file->fileName,
                    'mimeType' => $file->fileType,
                    'size' => $file->size,
                ];
            }
        }

        return $savedPaths;
    }

    public function getTotalSize(): int
    {
        $total = 0;

        foreach ($this->files as $file) {
            $size = $file->size ?? strlen($file->getDecodedContent() ?: '');
            $total += (int)$size;
        }

        return $total;
    }

    public function hasFiles(): bool
    {
        return !empty($this->files);
    }

    public function count(): int
    {
        return count($this->files);
    }

    public function getFilenames(): array
    {
        return array_map(fn(FileRequest $file) => $file->fileName, $this->files);
    }

    public function getFile(int $index): ?FileRequest
    {
        return $this->files[$index] ?? null;
    }
}