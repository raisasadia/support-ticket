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
use Illuminate\Support\Facades\Storage;

class ImportTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $path, public readonly int $userId) {}

    public function handle(SlaDeadlineCalculator $calculator): void
    {
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

        while (($row = fgetcsv($stream)) !== false) {
            $data = array_combine($header, $row);
            if (!$data || !in_array($data['priority'], ['low', 'medium', 'high', 'critical'], true) || trim($data['subject']) === '') {
                continue;
            }

            DB::transaction(function () use ($data, $calculator): void {
                $ticket = Ticket::create([
                    'ticket_number' => 'TMP-'.str()->random(20),
                    'subject' => trim($data['subject']),
                    'description' => trim($data['description'] ?? ''),
                    'priority' => $data['priority'],
                    'category' => $data['category'] ?? null,
                    'created_by' => $this->userId,
                    'due_at' => $calculator->calculate($data['priority'], now()),
                ]);
                $ticket->update(['ticket_number' => 'TKT-'.$ticket->created_at->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
                $ticket->activities()->create(['user_id' => $this->userId, 'event' => 'created', 'description' => 'Imported from CSV.']);
            });
        }

        fclose($stream);
        Storage::disk('local')->delete($this->path);
    }
}
