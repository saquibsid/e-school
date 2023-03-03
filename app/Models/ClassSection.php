<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ClassSection extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function class() {
        return $this->belongsTo(ClassSchool::class)->withTrashed();
    }

    public function section() {
        return $this->belongsTo(Section::class)->withTrashed();
    }

    public function teacher() {
        return $this->belongsTo(Teacher::class, 'class_teacher_id', 'id')->with('user');
    }

    public function announcement() {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function subject_teachers() {
        return $this->hasMany(SubjectTeacher::class);
    }

    public function scopeClassTeacher($query) {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacher = $user->teacher;
            return $query->where('class_teacher_id', $teacher->id);
        }
        return $query;
    }

    public function scopeSubjectTeacher($query) {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $class_section_ids = $user->teacher->subjects()->pluck('class_section_id');
            return $query->whereIn('id', $class_section_ids);
        }
        return $query;
    }
}
