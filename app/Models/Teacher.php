<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Teacher extends Model
{
    use SoftDeletes;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function announcement() {
        return $this->morphMany(Announcement::class, 'modal');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function class_section() {
        return $this->hasOne(ClassSection::class, 'class_teacher_id');
    }

    public function subjects() {
        return $this->hasMany(SubjectTeacher::class, 'teacher_id');
    }

    public function classes() {
        return $this->hasMany(SubjectTeacher::class, 'teacher_id')->groupBy('class_section_id');
    }

    //Getter Attributes
    public function getImageAttribute($value) {
        return url(Storage::url($value));
    }

    public function scopeTeachers($query) {
        if (Auth::user()->hasRole('Teacher')) {
            return $query->where('user_id', Auth::user()->id);
        }
        return $query;
    }

}
