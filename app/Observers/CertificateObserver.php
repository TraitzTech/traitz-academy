<?php

namespace App\Observers;

use App\Models\Certificate;
use App\Notifications\Lms\CertificateReadyNotification;

class CertificateObserver
{
    public function saved(Certificate $certificate): void
    {
        if (! $certificate->pdf_path) {
            return;
        }

        if (! $certificate->wasChanged('pdf_path')) {
            return;
        }

        $certificate->loadMissing('user');
        if (! $certificate->user) {
            return;
        }

        $certificate->user->notify(new CertificateReadyNotification($certificate));
    }
}
