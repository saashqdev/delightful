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
        // 转换为csv格式
        $content = $this->convertToCsv($content);
        // delete ## 开头的行
        $content = preg_replace('/^##.*\n/', '', $content);
        // use正则table达式匹配不在引号内的换行符
        return preg_replace('/(?<!")[\r\n]+(?!")/', "\n\n", $content);
    }

    /**
     * 将content转换为CSV格式.
     * @param string $content 原始content
     * @return string 转换后的CSV格式content
     */
    private function convertToCsv(string $content): string
    {
        // 将content按行分割，但保留单元格内的换行符
        $lines = preg_split('/(?<!")[\r\n]+(?!")/', $content);
        $result = [];
        $headers = [];

        foreach ($lines as $line) {
            // check是否是新的sheet
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

            // 如果是第一行且不是sheet标记，则作为标题行
            if (empty($headers) && ! empty($line)) {
                $headers = $row;
                continue;
            }

            // 处理数据行
            $rowResult = [];
            foreach ($row as $index => $value) {
                if (isset($headers[$index])) {
                    $rowResult[] = $this->formatCsvCell($headers[$index] . ':' . $value);
                }
            }

            // use原始行的分隔符
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

        // 如果没有找到分隔符，默认use逗号
        return ',';
    }

    /**
     * 格式化CSV单元格content，对特殊content添加引号.
     * @param string $value 单元格content
     * @return string 格式化后的单元格content
     */
    private function formatCsvCell(string $value): string
    {
        // 如果单元格content为空，直接return空string
        if ($value === '') {
            return '';
        }

        // 如果单元格content包含以下任意字符，需要用引号包围
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
