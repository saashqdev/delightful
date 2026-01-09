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
        // usejustthentablereachtypematchnotinimportnumberinsideexchangelinesymbol
        return preg_replace('/(?<!")[\r\n]+(?!")/', "\n\n", $content);
    }

    /**
     * willcontentconvertforCSVformat.
     * @param string $content originalcontent
     * @return string convertbackCSVformatcontent
     */
    private function convertToCsv(string $content): string
    {
        // willcontentbylinesplit,butretainsingleyuanformatinsideexchangelinesymbol
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

        // ifnothavefindtominuteseparator,defaultuse逗number
        return ',';
    }

    /**
     * formatizationCSVsingleyuanformatcontent,tospecialcontentaddimportnumber.
     * @param string $value singleyuanformatcontent
     * @return string formatizationbacksingleyuanformatcontent
     */
    private function formatCsvCell(string $value): string
    {
        // ifsingleyuanformatcontentforempty,directlyreturnemptystring
        if ($value === '') {
            return '';
        }

        // ifsingleyuanformatcontentcontainbydownanycharacter,needuseimportnumberpackage围
        if (str_contains($value, ',')
            || str_contains($value, '"')
            || str_contains($value, "\n")
            || str_contains($value, "\r")
            || str_starts_with($value, ' ')
            || str_ends_with($value, ' ')) {
            // escapedoubleimportnumber
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }

        return $value;
    }
}
