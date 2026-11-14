<?php

namespace App\Http\Requests;

class SendMessageChatRequest extends ChatParticipantRequest
{
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:'.config('chat.max_message_length')],
            'file' => [
                'nullable',
                'file',
                'max:'.$this->effectiveMaxAttachmentKb(),
                'mimes:'.implode(',', config('chat.allowed_extensions')),
                'mimetypes:'.implode(',', config('chat.allowed_mimes')),
            ],
        ];
    }

    public function messages(): array
    {
        $label = $this->formatAttachmentLimitLabel($this->effectiveMaxAttachmentKb());

        return [
            'file.uploaded' => 'O arquivo excede o limite permitido pelo servidor ('.$label.').',
            'file.max' => 'O arquivo excede o limite permitido de '.$label.'.',
            'file.mimes' => 'Envie apenas arquivos JPG, JPEG, PNG, WEBP, TXT ou PDF.',
            'file.mimetypes' => 'Envie apenas arquivos JPG, JPEG, PNG, WEBP, TXT ou PDF.',
        ];
    }

    private function effectiveMaxAttachmentKb(): int
    {
        $configuredKb = max(1, (int) config('chat.max_attachment_kb', 10240));
        $uploadKb = $this->iniSizeToKb(ini_get('upload_max_filesize'));
        $postKb = $this->iniSizeToKb(ini_get('post_max_size'));
        $limits = array_filter([$configuredKb, $uploadKb, $postKb], fn ($value) => (int) $value > 0);

        return (int) (empty($limits) ? $configuredKb : min($limits));
    }

    private function iniSizeToKb(string|false|null $value): int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        $unit = strtolower(substr($raw, -1));
        $number = (float) $raw;

        return match ($unit) {
            'g' => (int) round($number * 1024 * 1024),
            'm' => (int) round($number * 1024),
            'k' => (int) round($number),
            default => (int) round($number / 1024),
        };
    }

    private function formatAttachmentLimitLabel(int $maxKb): string
    {
        return $maxKb >= 1024
            ? number_format($maxKb / 1024, 0, ',', '.').' MB'
            : number_format($maxKb, 0, ',', '.').' KB';
    }
}
