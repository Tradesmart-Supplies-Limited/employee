<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/** Reads a worksheet as plain rows (row 1 = headings) for the employee importer. */
class RawSheetImport implements ToArray, WithCalculatedFormulas
{
    public function array(array $array): void
    {
        // Rows are returned by Excel::toArray(); nothing to do here.
    }
}
