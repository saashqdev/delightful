<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // onlywhentable存ino clock才execute索引操as
        if (Schema::hasTable('delightful_chat_sequences')) {
            // checkandcreate idx_object_type_id_refer_message_id 索引
            $this->createIndexIfNotExists(
                'delightful_chat_sequences',
                'idx_object_type_id_refer_message_id',
                'CREATE INDEX idx_object_type_id_refer_message_id 
                ON `delightful_chat_sequences` (object_type, object_id, refer_message_id, seq_id DESC)'
            );

            // checkandcreate idx_object_type_id_seq_id 索引
            $this->createIndexIfNotExists(
                'delightful_chat_sequences',
                'idx_object_type_id_seq_id',
                'CREATE INDEX idx_object_type_id_seq_id
                ON `delightful_chat_sequences` (object_type, object_id, seq_id)'
            );

            // checkandcreate idx_conversation_id_seq_id 索引
            $this->createIndexIfNotExists(
                'delightful_chat_sequences',
                'idx_conversation_id_seq_id',
                'CREATE INDEX idx_conversation_id_seq_id
                ON `delightful_chat_sequences` (conversation_id, seq_id DESC)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('delightful_chat_sequences')) {
            // delete索引
            $this->dropIndexIfExists('delightful_chat_sequences', 'idx_object_type_id_refer_message_id');
            $this->dropIndexIfExists('delightful_chat_sequences', 'idx_object_type_id_seq_id');
            $this->dropIndexIfExists('delightful_chat_sequences', 'idx_conversation_id_seq_id');
        }
    }

    /**
     * check索引whether存in，ifnot存inthencreate索引.
     *
     * @param string $table table名
     * @param string $indexName 索引name
     * @param string $createStatement create索引SQL语sentence
     */
    private function createIndexIfNotExists(string $table, string $indexName, string $createStatement): void
    {
        // check索引whether存in
        $indexExists = Db::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        // onlywhen索引not存ino clock才create
        if (empty($indexExists)) {
            // create索引
            Db::statement($createStatement);
        }
    }

    /**
     * if索引存inthendelete.
     *
     * @param string $table table名
     * @param string $indexName 索引name
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        // check索引whether存in
        $indexExists = Db::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        if (! empty($indexExists)) {
            // delete现have索引
            Db::statement("DROP INDEX `{$indexName}` ON `{$table}`");
        }
    }
};
