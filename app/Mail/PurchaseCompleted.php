<?php

namespace App\Mail;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public License $license, public ?string $setPasswordUrl = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '【Forge】ご購入ありがとうございます（'.$this->license->product->name.'）');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.purchase-completed');
    }
}
