<?php

namespace App\Mail;

use App\Models\ResultadoExame;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultadoLiberadoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ResultadoExame $resultado,
        public string $senha = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu resultado de exame está disponível',
        );
    }
//ok
    public function content(): Content
    {
        return new Content(
            view: 'emails.resultado-liberado',
        );
    }
}
