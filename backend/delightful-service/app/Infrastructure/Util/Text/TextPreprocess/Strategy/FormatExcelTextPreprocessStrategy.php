<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess\Strategy;

class FormatExcelTextPreprocessStrategy extends AbstractTextPreprocessStrategy
{
    public function preprocess(string $content): string
    {
        // convertforcsvformat
        $content = $this->convertToCsv($content);
        // delete ## openheadline
        $content = preg_replace('/^##.*\n/', '', $content);
        // usejustthentable达typematchnotin引numberinside换line符
        return preg_replace('/(?<!")[\r\n]+(?!")/', "\n\n", $content);
    }

    /**
     * willcontentconvertforCSVformat.
     * @param string $content originalcontent
     * @return string convertbackCSVformatcontent
     */
    private function convertToCsv(string $content): string
    {
        // willcontent按linesplit,butretainsingleyuan格inside换line符
        $lines = preg_split('/(?<!")[\r\n]+(?!")/', $content);
        $result = [];
        $headers = [];

        foreach ($lines as $line) {
            // checkwhetherisnewsheet
            if (str_starts_with($line, '##')) {
                $result[] = $line;
                $headers = [];
                continue;
            }

            // ifisemptyline,skip
            if (empty(trim($line))) {
                $result[] = '';
                continue;
            }

            // usefgetcsvmethodparseCSVline
            $row = str_getcsv($line);

            // ifistheonelineandnotissheetmark,thenasfortitleline
            if (empty($headers) && ! empty($line)) {
                $headers = $row;
                continue;
            }

            // processdataline
            $rowResult = [];
            foreach ($row as $index => $value) {
                if (isset($headers[$index])) {
                    $rowResult[] = $this->formatCsvCell($headers[$index] . ':' . $value);
                }
            }

            // useoriginallineminuteseparator
            $originalSeparator = $this->detectSeparator($line);
            $result[] = implode($originalSeparator, $rowResult);
        }

        return implode("\n", $result);
    }

    /**
     * detectCSVlineminuteseparator.
     * @param string $line CSVlinecontent
     * @return string detecttominuteseparator
     */
    private function detectSeparator(string $line): string
    {
        // commonCSVminuteseparator
        $separators = [',', ';', '\t'];

        foreach ($separators as $separator) {
            if (str_contains($line, $separator)) {
                return $separator;
            }
        }

        // ifnothave找tominuteseparator,defaultuse逗number
        return ',';
    }

    /**
     * format化CSVsingleyuan格content,tospecialcontentadd引number.
     * @param string $value singleyuan格content
     * @return string format化backsingleyuan格content
     */
    private function formatCsvCell(string $value): string
    {
        // ifsingleyuan格contentforempty,directlyreturnemptystring
        if ($value === '') {
            return '';
        }

        // ifsingleyuan格contentcontainbydown任意character,needuse引numberpackage围
        if (str_contains($value, ',')
            || str_contains($value, '"')
            || str_contains($value, "\n")
            || str_contains($value, "\r")
            || str_starts_with($value, ' ')
            || str_ends_with($value, ' ')) {
            // escapedouble引number
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }

        return $value;
    }
}
