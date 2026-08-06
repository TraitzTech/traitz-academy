<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A model can be attached to a Course, Cohort, or Program via a
 * polymorphic attachable_type/attachable_id pair.
 */
trait HasAttachable
{
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
