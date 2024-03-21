<?php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class ExcelRule implements Rule
{
    private $file;

    /**
     * @param  UploadedFile  $file
     */
    public function __construct(UploadedFile $file)
    {
        $this->file = $file;
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     *
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $extension = strtolower($this->file->getClientOriginalExtension());

        return $extension == 'csv';
    }

    public function message(): string
    {
        return 'The excel file must be a file of type: csv';
    }
}
