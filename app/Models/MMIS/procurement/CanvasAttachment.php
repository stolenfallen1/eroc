<?php

namespace App\Models\MMIS\procurement;

use App\Models\MMIS\CanvasMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanvasAttachment extends Model
{
    use HasFactory;

    protected $table = 'canvasAttachment';

    protected $fillable = [
        'canvas_id', 'canvas_Supplier_id',
        'filename', 'filepath',
    ];

    public function canvas(){
        return $this->belongsTo(CanvasMaster::class, 'canvas_id');
    }

}
