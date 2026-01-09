<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Crontab;

use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\DbConnection\Db;

#[Crontab(rule: '0 3 * * *', name: 'DelightfulFlowKnowledgeFragmentClearCrontab', singleton: true, mutexExpires: 600, onOneServer: true, callback: 'execute', memo: 'schedule清理知识库')]
readonly class DelightfulFlowKnowledgeFragmentClearCrontab
{
    public function execute(): void
    {
        // schedule清理软删的知识库和片段 仅保留 1 天

        $this->clearKnowledge();
        $this->clearDocument();
        $this->clearFragment();
    }

    private function clearKnowledge(): void
    {
        $lastId = 0;
        while (true) {
            $ids = [];
            $data = Db::table('delightful_flow_knowledge')->where('id', '>', $lastId)->whereNotNull('deleted_at')->limit(200)->get();
            foreach ($data as $item) {
                $diff = time() - strtotime($item['deleted_at']);
                if ($diff > 86400) {
                    $ids[] = $item['id'];
                }
            }
            if (empty($ids)) {
                break;
            }
            Db::table('delightful_flow_knowledge')->whereIn('id', $ids)->delete();
            $lastId = end($ids);
        }
    }

    private function clearDocument(): void
    {
        $lastId = 0;
        while (true) {
            $ids = [];
            $data = Db::table('knowledge_base_documents')->where('id', '>', $lastId)->whereNotNull('deleted_at')->limit(200)->get();
            foreach ($data as $item) {
                $diff = time() - strtotime($item['deleted_at']);
                if ($diff > 86400) {
                    $ids[] = $item['id'];
                }
            }
            if (empty($ids)) {
                break;
            }
            Db::table('knowledge_base_documents')->whereIn('id', $ids)->delete();
            $lastId = end($ids);
        }
    }

    private function clearFragment(): void
    {
        $lastId = 0;
        while (true) {
            $ids = [];
            $data = Db::table('delightful_flow_knowledge_fragment')->where('id', '>', $lastId)->whereNotNull('deleted_at')->limit(200)->get();
            foreach ($data as $item) {
                $diff = time() - strtotime($item['deleted_at']);
                if ($diff > 86400) {
                    $ids[] = $item['id'];
                }
            }
            if (empty($ids)) {
                break;
            }
            Db::table('delightful_flow_knowledge_fragment')->whereIn('id', $ids)->delete();
            $lastId = end($ids);
        }
    }
}
