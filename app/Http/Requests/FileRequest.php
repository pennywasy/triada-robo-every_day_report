<?php

namespace App\Http\Requests;

use App\Exceptions\ValidationException;

class FileRequest extends BaseRequest
{
    public string $fileName;
    public string $fileContent;
    public ?string $fileType;
    public ?string $size;

    protected function rules(): array
    {
        return [
            'fileName' => ['required', 'string'],
            'fileContent' => ['required', 'string'],
            'fileType' => ['nullable', 'string'],
            'size' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'fileName.required' => 'Имя файла обязательно',
            'fileName.string' => 'Имя файла должно быть строкой',
            'fileContent.required' => 'Содержимое файла обязательно',
            'fileContent.string' => 'Содержимое файла должно быть строкой в формате base64',
            'fileType.string' => 'Тип файла должен быть строкой',
            'size.string' => 'Размер файла должен быть строкой',
        ];
    }

    protected function attributes(): array
    {
        return [
            'fileName' => 'Имя файла',
            'fileContent' => 'Содержимое файла',
            'fileType' => 'Тип файла',
            'size' => 'Размер файла',
        ];
    }

    protected function afterValidation(): void
    {
        if ($this->fileContent && !$this->isValidBase64($this->fileContent)) {
            $this->errors['fileContent'][] = 'Некорректный формат base64';
        }

        if (!$this->fileType && $this->fileContent) {
            $this->fileType = $this->getMimeType();
        }

        if (!$this->size && $this->fileContent) {
            $decoded = base64_decode($this->fileContent, true);
            if ($decoded !== false) {
                $this->size = (string) strlen($decoded);
            }
        }
    }

    private function isValidBase64(string $string): bool
    {
        // Проверка base64
        $decoded = base64_decode($string, true);
        if ($decoded === false) {
            return false;
        }

        // Проверка, что после декодирования и обратного кодирования получается то же самое
        return base64_encode($decoded) === $string;
    }

    public function getDecodedContent(): ?string
    {
        if (!$this->fileContent) {
            return null;
        }

        $decoded = base64_decode($this->fileContent, true);
        return $decoded !== false ? $decoded : null;
    }

    public function getMimeType(): ?string
    {
        $content = $this->getDecodedContent();

        if (!$content) {
            return null;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $content);
            finfo_close($finfo);
            return $mime;
        }

        $extension = pathinfo($this->fileName, PATHINFO_EXTENSION);

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    public function getExtension(): string
    {
        return pathinfo($this->fileName, PATHINFO_EXTENSION);
    }

    public function getBaseName(): string
    {
        return pathinfo($this->fileName, PATHINFO_FILENAME);
    }

    public function isType(array|string $types): bool
    {
        $mimeType = $this->fileType ?: $this->getMimeType();

        if (!$mimeType) {
            return false;
        }

        $types = (array)$types;

        foreach ($types as $type) {
            if (strpos($mimeType, $type) === 0) {
                return true;
            }
        }

        return false;
    }

    public function isImage(): bool
    {
        return $this->isType(['image/']);
    }

    public function isPdf(): bool
    {
        return $this->isType('application/pdf');
    }

    public function isDocument(): bool
    {
        return $this->isType([
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/pdf',
        ]);
    }

    public function saveTo(string $directory, ?string $filename = null): ?string
    {
        $content = $this->getDecodedContent();

        if (!$content) {
            return null;
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $filename ?: $this->fileName;
        $path = rtrim($directory, '/') . '/' . $filename;

        if (file_put_contents($path, $content) !== false) {
            return $path;
        }

        return null;
    }

    public function generateSafeFilename(): string
    {
        $extension = $this->getExtension();
        $basename = $this->getBaseName();


        $safeBasename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $basename);
        $safeBasename = substr($safeBasename, 0, 50);


        $timestamp = time();

        return $safeBasename . '_' . $timestamp . ($extension ? '.' . $extension : '');
    }
}