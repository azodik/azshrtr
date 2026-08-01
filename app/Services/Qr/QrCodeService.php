<?php

namespace App\Services\Qr;

use App\Enums\AuditAction;
use App\Models\Link;
use App\Models\Organization;
use App\Models\QrCode;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Usage\UsageTracker;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function __construct(
        private readonly UsageTracker $usage,
        private readonly AuditLogger $audit,
    ) {}

    public function generateForLink(
        Link $link,
        ?Organization $organization,
        ?User $user = null,
        int $size = 256,
        string $format = 'svg',
    ): QrCode {
        if ($organization !== null) {
            $this->usage->assertCanGenerateQr($organization);
        }

        $content = $link->shortUrl();
        $qr = QrCode::query()->create([
            'organization_id' => $organization?->id,
            'link_id' => $link->id,
            'content' => $content,
            'size' => $size,
            'format' => $format === 'png' ? 'svg' : $format,
        ]);

        if ($organization !== null) {
            $this->usage->incrementQrGenerated($organization);
            $this->audit->log(AuditAction::QrGenerated, $user, $organization, 'qr_code', $qr->id);
        }

        return $qr;
    }

    public function generateForContent(
        string $content,
        Organization $organization,
        ?User $user = null,
        int $size = 256,
        string $format = 'svg',
    ): QrCode {
        $this->usage->assertCanGenerateQr($organization);

        $qr = QrCode::query()->create([
            'organization_id' => $organization->id,
            'link_id' => null,
            'content' => $content,
            'size' => $size,
            'format' => $format === 'png' ? 'svg' : $format,
        ]);

        $this->usage->incrementQrGenerated($organization);
        $this->audit->log(AuditAction::QrGenerated, $user, $organization, 'qr_code', $qr->id);

        return $qr;
    }

    public function renderSvg(string $content, int $size = 256, int $margin = 1): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd,
        );
        $writer = new Writer($renderer);

        return $writer->writeString($content);
    }
}
