<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campuscoin:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and generate due recurring income and expense transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Processing recurring transactions for date: {$today->toDateString()}");

        $recurringParents = Transaction::where('is_recurring', true)
            ->whereNotNull('recurring_frequency')
            ->where(function ($q) use ($today) {
                $q->whereNull('recurring_end_date')
                  ->orWhere('recurring_end_date', '>=', $today);
            })
            ->get();

        $generatedCount = 0;

        foreach ($recurringParents as $parent) {
            $frequency = strtolower($parent->recurring_frequency); // 'weekly' or 'monthly'
            $startDate = $parent->recurring_start_date ?? $parent->transaction_date;

            // Determine if a transaction has already been logged for this cycle
            // Check latest transaction matching this user, description and category
            $latest = Transaction::where('user_id', $parent->user_id)
                ->where('category_id', $parent->category_id)
                ->where('description', $parent->description)
                ->orderBy('transaction_date', 'desc')
                ->first();

            $lastDate = $latest ? Carbon::parse($latest->transaction_date) : Carbon::parse($startDate);
            $nextDate = null;

            if ($frequency === 'weekly') {
                $nextDate = $lastDate->copy()->addWeek();
            } elseif ($frequency === 'monthly') {
                $nextDate = $lastDate->copy()->addMonth();
            }

            if ($nextDate && $nextDate->lte($today)) {
                // Generate next occurrence
                Transaction::create([
                    'user_id' => $parent->user_id,
                    'category_id' => $parent->category_id,
                    'amount' => $parent->amount,
                    'type' => $parent->type,
                    'description' => $parent->description,
                    'transaction_date' => $nextDate->toDateString(),
                    'payment_method' => $parent->payment_method,
                    'note' => 'Auto-generated recurring transaction (' . ucfirst($frequency) . ')',
                    'is_recurring' => false,
                ]);

                $generatedCount++;
                $this->line("Generated {$frequency} transaction for User #{$parent->user_id}: {$parent->description} (Rs. {$parent->amount}) on {$nextDate->toDateString()}");
            }
        }

        $this->info("Completed recurring transactions processing. Total generated: {$generatedCount}");
        return Command::SUCCESS;
    }
}
