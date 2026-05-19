<?php

namespace App\Services\Attendance;

use App\Models\AttendanceTicket;
use DomainException;

class AttendanceStatusService
{
    public function assertCanCall(string $status): void
    {
        if ($status !== AttendanceTicket::STATUS_AGUARDANDO) {
            throw new DomainException('Senha não está aguardando atendimento.');
        }
    }

    public function assertCanStart(string $status): void
    {
        if (! in_array($status, [AttendanceTicket::STATUS_CHAMADA, AttendanceTicket::STATUS_EM_ATENDIMENTO], true)) {
            throw new DomainException('Senha não pode iniciar atendimento neste status.');
        }
    }

    public function assertCanFinish(string $status): void
    {
        if (! in_array($status, [AttendanceTicket::STATUS_CHAMADA, AttendanceTicket::STATUS_EM_ATENDIMENTO], true)) {
            throw new DomainException('Senha não pode ser finalizada neste status.');
        }
    }

    public function assertCanNoShow(string $status): void
    {
        if ($status !== AttendanceTicket::STATUS_CHAMADA) {
            throw new DomainException('Apenas senhas chamadas podem ser marcadas como não compareceu.');
        }
    }

    public function assertCanCancel(string $status): void
    {
        if (in_array($status, [AttendanceTicket::STATUS_FINALIZADA, AttendanceTicket::STATUS_NAO_COMPARECEU, AttendanceTicket::STATUS_CANCELADA], true)) {
            throw new DomainException('Senha não pode ser cancelada neste status.');
        }
    }
}
