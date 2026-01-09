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
        // onlywhentable存ino clock才executeindex操as
        if (Schema::hasTable('delightful_chat_sequences')) {
            // checkandcreate idx_object_type_id_refer_message_id index
            $this->createIndexIfNotExists(
                'delightful_chat_sequences',
                'idx_object_type_id_refer_message_id',
                'CREATE INDEX idx_object_type_id_refer_message_id 
                ON `delightful_chat_sequences` (object_type, object_id, refer_message_id, seq_id DESC)'
            );

            // checkandcreate idx_object_type_id_seq_id index
            $this->createIndexIfNotExists(
                'delightful_chat_sequences',
                'idx_object_type_id_seq_id',
                'CREATE INDEX idx_object_type_id_seq_id
                ON `delightful_chat_sequences` (object_type, object_id, seq_id)'
            );

            // checkandcreate idx_conversation_id_seq_id index
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
            // deleteindex
            $this->dropIndexIfExists('delightful_chat_sequences', 'idx_object_type_id_refer_message_id');
            $this->dropIndexIfExists('delightful_chat_sequences', 'idx_object_type_id_seq_id');
            $this->dropIndexIfExists('delightful_chat_sequences', 'idx_conversation_id_seq_id');
        }
    }

    /**
     * checkindexwhether存in,ifnot存inthencreateindex.
     *
     * @param string $table table名
     * @param string $indexName indexname
     * @param string $createStatement createindexSQL语sentence
     */
    private function createIndexIfNotExists(string $table, string $indexName, string $createStatement): void
    {
        // checkindexwhether存in
        $indexExists = Db::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        // onlywhenindexnot存ino clock才create
        if (empty($indexExists)) {
            // createindex
            Db::statement($createStatement);
        }
    }

    /**
     * ifindex存inthendelete.
     *
     * @param string $table table名
     * @param string $indexName indexname
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        // checkindexwhether存in
        $indexExists = Db::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        if (! empty($indexExists)) {
            // delete现haveindex
            Db::statement("DROP INDEX `{$indexName}` ON `{$table}`");
        }
    }
};
