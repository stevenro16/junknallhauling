<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryStatusHistory extends Model
{
    use HasUuids;

    protected $table = 'inquiry_status_history';

    public $timestamps = false;

    protected $guarded = [];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');
    }
}
