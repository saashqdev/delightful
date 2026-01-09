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
        // convert为csvformat
        $content = $this->convertToCsv($content);
        // delete ## 开head的line
        $content = preg_replace('/^##.*\n/', '', $content);
        // use正thentable达type匹配notin引numberinside的换line符
        return preg_replace('/(?<!")[\r\n]+(?!")/', "\n\n", $content);
    }

    /**
     * 将contentconvert为CSVformat.
     * @param string $content originalcontent
     * @return string convertback的CSVformatcontent
     */
    private function convertToCsv(string $content): string
    {
        // 将content按linesplit，but保留单yuan格inside的换line符
        $lines = preg_split('/(?<!")[\r\n]+(?!")/', $content);
        $result = [];
        $headers = [];

        foreach ($lines as $line) {
            // checkwhether是newsheet
            if (str_starts_with($line, '##')) {
                $result[] = $line;
                $headers = [];
                continue;
            }

            // if是空line，skip
            if (empty(trim($line))) {
                $result[] = '';
                continue;
            }

            // usefgetcsv的methodparseCSVline
            $row = str_getcsv($line);

            // if是the一lineandnot是sheetmark，then作为titleline
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

            // useoriginalline的minute隔符
            $originalSeparator = $this->detectSeparator($line);
            $result[] = implode($originalSeparator, $rowResult);
        }

        return implode("\n", $result);
    }

    /**
     * 检测CSVline的minute隔符.
     * @param string $line CSVlinecontent
     * @return string 检测to的minute隔符
     */
    private function detectSeparator(string $line): string
    {
        // 常见的CSVminute隔符
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
     * format化CSV单yuan格content，对特殊contentadd引number.
     * @param string $value 单yuan格content
     * @return string format化back的单yuan格content
     */
    private function formatCsvCell(string $value): string
    {
        // if单yuan格content为空，直接return空string
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
