<?php

namespace ElliottLawson\Converse\Commands;

use ElliottLawson\Converse\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

            $totalMessages = Message::whereNull('uuid')->count();

            if ($totalMessages > 0) {
                $bar = $this->output->createProgressBar($totalMessages);
                $bar->start();

                $processedCount = 0;

                Message::whereNull('uuid')->chunkById(100, function ($messages) use ($bar, &$processedCount) {
                    $updates = $messages->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'uuid' => Str::uuid()->toString(),
                        ];
                    })->toArray();

                    $processedCount += count($updates);

                    // Bulk update using upsert
                    Message::upsert($updates, ['id'], ['uuid']);

                    $bar->advance(count($updates));
                });

                $bar->finish();
                $this->newLine();
                $this->info("✓ Generated UUIDs for {$processedCount} existing messages.");
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
