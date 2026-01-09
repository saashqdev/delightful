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
        // delete ## 开头的行
        $content = preg_replace('/^##.*\n/', '', $content);
        // use正thentable达式匹配notin引号内的换行符
        return preg_replace('/(?<!")[\r\n]+(?!")/', "\n\n", $content);
    }

    /**
     * 将contentconvert为CSVformat.
     * @param string $content originalcontent
     * @return string convert后的CSVformatcontent
     */
    private function convertToCsv(string $content): string
    {
        // 将content按行split，but保留单元格内的换行符
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

            // if是空行，skip
            if (empty(trim($line))) {
                $result[] = '';
                continue;
            }

            // usefgetcsv的methodparseCSV行
            $row = str_getcsv($line);

            // if是第一行andnot是sheetmark，then作为title行
            if (empty($headers) && ! empty($line)) {
                $headers = $row;
                continue;
            }

            // processdata行
            $rowResult = [];
            foreach ($row as $index => $value) {
                if (isset($headers[$index])) {
                    $rowResult[] = $this->formatCsvCell($headers[$index] . ':' . $value);
                }
            }

            // useoriginal行的分隔符
            $originalSeparator = $this->detectSeparator($line);
            $result[] = implode($originalSeparator, $rowResult);
        }

        return implode("\n", $result);
    }

    /**
     * 检测CSV行的分隔符.
     * @param string $line CSV行content
     * @return string 检测to的分隔符
     */
    private function detectSeparator(string $line): string
    {
        // 常见的CSV分隔符
        $separators = [',', ';', '\t'];

        foreach ($separators as $separator) {
            if (str_contains($line, $separator)) {
                return $separator;
            }
        }

        // ifnothave找to分隔符，defaultuse逗号
        return ',';
    }

    /**
     * format化CSV单元格content，对特殊content添加引号.
     * @param string $value 单元格content
     * @return string format化后的单元格content
     */
    private function formatCsvCell(string $value): string
    {
        // if单元格content为空，直接return空string
        if ($value === '') {
            return '';
        }

        // if单元格contentcontainby下任意字符，needuse引号package围
        if (str_contains($value, ',')
            || str_contains($value, '"')
            || str_contains($value, "\n")
            || str_contains($value, "\r")
            || str_starts_with($value, ' ')
            || str_ends_with($value, ' ')) {
            // 转义双引号
            $value = str_replace('"', '""', $value);
            return '"' . $value . '"';
        }

        return $value;
    }
}
