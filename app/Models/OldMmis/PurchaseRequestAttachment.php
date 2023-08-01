<?php

namespace App\Models\OldMMIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestAttachment extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'pr_attachments';
    protected $guarded = [];
}
