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
        // 转换为csvformat
        $content = $this->convertToCsv($content);
        // delete ## 开头的行
        $content = preg_replace('/^##.*\n/', '', $content);
        // use正则table达式匹配不在引号内的换行符
        return preg_replace('/(?<!")[\r\n]+(?!")/', "\n\n", $content);
    }

    /**
     * 将content转换为CSVformat.
     * @param string $content originalcontent
     * @return string 转换后的CSVformatcontent
     */
    private function convertToCsv(string $content): string
    {
        // 将content按行分割，但保留单元格内的换行符
        $lines = preg_split('/(?<!")[\r\n]+(?!")/', $content);
        $result = [];
        $headers = [];

        foreach ($lines as $line) {
            // check是否是newsheet
            if (str_starts_with($line, '##')) {
                $result[] = $line;
                $headers = [];
                continue;
            }

            // 如果是空行，跳过
            if (empty(trim($line))) {
                $result[] = '';
                continue;
            }

            // usefgetcsv的方式解析CSV行
            $row = str_getcsv($line);

            // 如果是第一行且不是sheetmark，则作为标题行
            if (empty($headers) && ! empty($line)) {
                $headers = $row;
                continue;
            }

            // process数据行
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
     * @return string 检测到的分隔符
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

        // 如果没有找到分隔符，defaultuse逗号
        return ',';
    }

    /**
     * format化CSV单元格content，对特殊content添加引号.
     * @param string $value 单元格content
     * @return string format化后的单元格content
     */
    private function formatCsvCell(string $value): string
    {
        // 如果单元格content为空，直接return空string
        if ($value === '') {
            return '';
        }

        // 如果单元格contentcontain以下任意字符，need用引号包围
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
