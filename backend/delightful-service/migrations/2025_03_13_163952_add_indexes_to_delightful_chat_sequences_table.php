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
        // onlywhentableexistsino clockonlyexecuteindexoperationas
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
     * checkindexwhetherexistsin,ifnotexistsinthencreateindex.
     *
     * @param string $table tablename
     * @param string $indexName indexname
     * @param string $createStatement createindexSQLlanguagesentence
     */
    private function createIndexIfNotExists(string $table, string $indexName, string $createStatement): void
    {
        // checkindexwhetherexistsin
        $indexExists = Db::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        // onlywhenindexnotexistsino clockonlycreate
        if (empty($indexExists)) {
            // createindex
            Db::statement($createStatement);
        }
    }

    /**
     * ifindexexistsinthendelete.
     *
     * @param string $table tablename
     * @param string $indexName indexname
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        // checkindexwhetherexistsin
        $indexExists = Db::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        if (! empty($indexExists)) {
            // deleteshowhaveindex
            Db::statement("DROP INDEX `{$indexName}` ON `{$table}`");
        }
    }
};
