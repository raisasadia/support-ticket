<?php

namespace App\Modules\TicketManagement\Jobs;

use App\Modules\TicketManagement\Models\Ticket;
use App\Modules\TicketManagement\Services\SlaDeadlineCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ImportTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $path,
        public readonly int $userId,
        public readonly string $importId,
    ) {}

    public function handle(SlaDeadlineCalculator $calculator): void
    {
        $this->updateStatus(['status' => 'processing']);
        $stream = Storage::disk('local')->readStream($this->path);
        if ($stream === false) {
            throw new \RuntimeException('Unable to read the import file.');
        }

        $header = fgetcsv($stream);
        $required = ['subject', 'description', 'priority'];
        if (!$header || array_diff($required, $header)) {
            fclose($stream);
            throw new \InvalidArgumentException('CSV must contain subject, description, and priority columns.');
        }

        $total = 0;
        while (($row = fgetcsv($stream)) !== false) {
            $total++;
        }
        rewind($stream);
        fgetcsv($stream);
        $processed = 0;
        $imported = 0;
        $invalid = 0;
        $duplicates = 0;
        $errors = [];
        $seen = [];

        while (($row = fgetcsv($stream)) !== false) {
            $processed++;
            $data = array_combine($header, $row);
            $subject = trim((string) ($data['subject'] ?? ''));
            $description = trim((string) ($data['description'] ?? ''));
            $priority = strtolower(trim((string) ($data['priority'] ?? '')));
            $key = sha1($subject.'|'.$description);
            $error = null;

            if (!$data || count($row) !== count($header)) {
                $error = 'The row does not match the CSV column count.';
            } elseif ($subject === '') {
                $error = 'Subject is required.';
            } elseif ($description === '') {
                $error = 'Description is required.';
            } elseif (!in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
                $error = 'Priority must be low, medium, high, or critical.';
            } elseif (isset($seen[$key]) || Ticket::where('subject', $subject)->where('description', $description)->exists()) {
                $duplicates++;
                $errors[] = ['row' => $processed + 1, 'reason' => 'Duplicate ticket.'];
                continue;
            }

            if ($error !== null) {
                $invalid++;
                $errors[] = ['row' => $processed + 1, 'reason' => $error];
                continue;
            }
            $seen[$key] = true;

            DB::transaction(function () use ($data, $calculator, $subject, $description, $priority): void {
                $ticket = Ticket::create([
                    'ticket_number' => 'TMP-'.str()->random(20),
                    'subject' => $subject,
                    'description' => $description,
                    'priority' => $priority,
                    'category' => $data['category'] ?? null,
                    'created_by' => $this->userId,
                    'due_at' => $calculator->calculate($data['priority'], now()),
                ]);
                $ticket->update(['ticket_number' => 'TKT-'.$ticket->created_at->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
                $ticket->activities()->create(['user_id' => $this->userId, 'event' => 'created', 'description' => 'Imported from CSV.']);
            });
            $imported++;
            $this->updateStatus(compact('total', 'processed', 'imported', 'invalid', 'duplicates', 'errors'));
        }

        fclose($stream);
        Storage::disk('local')->delete($this->path);
        $this->updateStatus(compact('total', 'processed', 'imported', 'invalid', 'duplicates', 'errors') + ['status' => 'completed']);
    }

    public function failed(\Throwable $exception): void
    {
        $this->updateStatus([
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ]);
        Storage::disk('local')->delete($this->path);
    }

    private function updateStatus(array $changes): void
    {
        Cache::put('ticket-import:'.$this->importId, array_merge(
            Cache::get('ticket-import:'.$this->importId, []),
            $changes,
        ), now()->addDay());
    }
}
