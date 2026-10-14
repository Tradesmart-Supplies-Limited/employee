<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\Rota;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** "Your shifts for the week" — sent when a rota is approved & published (or changed). */
class RotaPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Rota $rota, public Employee $employee, public array $week, public bool $first = true, public ?array $old = null) {}

    public function build()
    {
        $range = $this->rota->week_start->format('d M') . ' – ' . $this->rota->week_end->format('d M');

        return $this->subject(($this->first ? 'Your shifts for ' : 'Rota updated: your shifts for ') . $range)
            ->view('emails.rota-published', ['url' => route('portal.shifts.index')]);
    }
}
