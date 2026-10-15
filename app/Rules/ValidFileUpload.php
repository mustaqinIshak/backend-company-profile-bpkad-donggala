<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidFileUpload implements ValidationRule
{
    private $errorMessage = '';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || !is_uploaded_file($value->getRealPath())) {
            $this->errorMessage = 'File must be a valid uploaded file.';
            $fail($this->errorMessage);
            return;
        }

        $maxSize = 20480 * 1024;
        if ($value->getSize() > $maxSize) {
            $this->errorMessage = 'File size must not exceed 20MB.';
            $fail($this->errorMessage);
            return;
        }

        $allowedMimes = ['application/pdf', 'application/msword', 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation'];

        $mimeType = $value->getMimeType();
        if (!in_array($mimeType, $allowedMimes)) {
            $this->errorMessage = "File type '$mimeType' is not allowed. Allowed types: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX.";
            $fail($this->errorMessage);
            return;
        }

        $filename = $value->getClientOriginalName();
        if (preg_match('/[<>:"|?*]/', $filename)) {
            $this->errorMessage = 'Filename contains invalid characters.';
            $fail($this->errorMessage);
            return;
        }
    }
}
