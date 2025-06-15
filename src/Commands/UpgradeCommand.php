<?php

namespace ElliottLawson\Converse\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class UpgradeCommand extends Command
{
    protected $signature = 'converse:upgrade';

    protected $description = 'Upgrade Converse database schema to the latest version';

    public function handle(): int
    {
        $this->info('Checking Converse database schema...');

        $messagesTable = config('converse.tables.messages');

        // Check if messages table exists
        if (! Schema::hasTable($messagesTable)) {
            $this->error("Messages table '{$messagesTable}' does not exist. Please run migrations first.");

            return self::FAILURE;
        }

        // Check if uuid column already exists
        if (Schema::hasColumn($messagesTable, 'uuid')) {
            $this->info('✓ UUID column already exists in messages table.');

            return self::SUCCESS;
        }

        $this->info('Adding UUID column to messages table...');

        try {
            Schema::table($messagesTable, function ($table) {
                $table->uuid()->unique()->after('id');
            });

            $this->info('✓ UUID column added successfully.');
            $this->info('');
            $this->info('Note: Existing messages will not have UUIDs. New messages will automatically generate UUIDs.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to add UUID column: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
