<?php

namespace App\Mail;

use App\Models\Rota;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** HR's record that a rota was approved & published. */
class RotaPublishedHrMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Rota $rota, public string $by, public bool $first, public int $people, public int $emailed) {}

    public function build()
    {
        return $this->subject(($this->first ? 'Rota published: ' : 'Rota updated: ') . $this->rota->title . " (by {$this->by})")
            ->view('emails.rota-published-hr', ['url' => route('attendance.rota.print', $this->rota)]);
    }
}
