<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadImport extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'uploaded_by', 'file_name', 'path', 'status', 'total_rows', 'imported', 'updated', 'failed', 'options', 'errors', 'message', 'finished_at'];

    protected $casts = ['options' => 'array', 'errors' => 'array', 'finished_at' => 'datetime'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function successRate(): ?float
    {
        return $this->total_rows > 0 ? round(($this->imported + $this->updated) / $this->total_rows * 100, 1) : null;
    }
}
