<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ContractEndingReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Collection $employees;
    public string $label;

    /**
     * @param  \Illuminate\Support\Collection|\App\Models\Employee[]  $employees
     */
    public function __construct($employees, string $label)
    {
        $this->employees = collect($employees);
        $this->label = $label;
    }

    public function build()
    {
        return $this
            ->subject('Contract Ending Reminder — ' . $this->label)
            ->view('emails.contract-reminder')
            ->with([
                'employees' => $this->employees,
                'label' => $this->label,
            ]);
    }
}
