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
        // use正thentable达type匹配notin引numberinside换line符
        return preg_replace('/(?<!")[\r\n]+(?!")/', "\n\n", $content);
    }

    /**
     * willcontentconvertforCSVformat.
     * @param string $content originalcontent
     * @return string convertbackCSVformatcontent
     */
    private function convertToCsv(string $content): string
    {
        // willcontent按linesplit，but保留单yuan格inside换line符
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

            // ifis空line，skip
            if (empty(trim($line))) {
                $result[] = '';
                continue;
            }

            // usefgetcsvmethodparseCSVline
            $row = str_getcsv($line);

            // ifisthe一lineandnotissheetmark，thenasfortitleline
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

            // useoriginallineminute隔符
            $originalSeparator = $this->detectSeparator($line);
            $result[] = implode($originalSeparator, $rowResult);
        }

        return implode("\n", $result);
    }

    /**
     * 检测CSVlineminute隔符.
     * @param string $line CSVlinecontent
     * @return string 检测tominute隔符
     */
    private function detectSeparator(string $line): string
    {
        // 常见CSVminute隔符
        $separators = [',', ';', '\t'];

        foreach ($separators as $separator) {
            if (str_contains($line, $separator)) {
                return $separator;
            }
        }

        // ifnothave找tominute隔符，defaultuse逗number
        return ',';
    }

    /**
     * format化CSV单yuan格content，to特殊contentadd引number.
     * @param string $value 单yuan格content
     * @return string format化back单yuan格content
     */
    private function formatCsvCell(string $value): string
    {
        // if单yuan格contentfor空，直接return空string
        if ($value === '') {
            return '';
        }

        // if单yuan格contentcontainbydown任意character，needuse引numberpackage围
        if (str_contains($value, ',')
            || str_contains($value, '"')
            || str_contains($value, "\n")
            || str_contains($value, "\r")
            || str_starts_with($value, ' ')
            || str_ends_with($value, ' ')) {
            // 转义双引number
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }

        return $value;
    }
}
