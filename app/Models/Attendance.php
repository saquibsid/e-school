<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $hidden = ["remark","deleted_at","created_at","updated_at"];

    public function student()
    {
        return $this->belongsTo(Students::class)->with('user');
    }
}
