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
            // Add the UUID column
            Schema::table($messagesTable, function ($table) {
                $table->uuid()->unique()->after('id');
            });

            $this->info('✓ UUID column added successfully.');

            // Populate UUIDs for existing messages
            $this->info('Populating UUIDs for existing messages...');

            $messageModel = \ElliottLawson\Converse\Models\Message::class;
            $messages = $messageModel::whereNull('uuid')->get();

            if ($messages->count() > 0) {
                $bar = $this->output->createProgressBar($messages->count());
                $bar->start();

                foreach ($messages as $message) {
                    $message->uuid = (string) \Illuminate\Support\Str::uuid();
                    $message->save();
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("✓ Generated UUIDs for {$messages->count()} existing messages.");
            } else {
                $this->info('✓ No existing messages found without UUIDs.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to upgrade: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
