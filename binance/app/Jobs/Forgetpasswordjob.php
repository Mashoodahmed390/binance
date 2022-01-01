<?php

namespace App\Jobs;

use App\Mail\ForgetPasswordEmail;
use App\Mail\Sendemail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
class Forgetpasswordjob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $toemail;
    public $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email,$user)
    {
        $this->toemail = $email;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = new ForgetPasswordEmail($this->user);
        Mail::to($this->toemail)->send($data);
    }
}
