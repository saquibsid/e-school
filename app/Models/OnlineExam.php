<?php

namespace App\Models;

use App\Models\ClassSchool;
use App\Models\ClassSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OnlineExam extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function class_subject() {
        return $this->belongsTo(ClassSubject::class,'class_subject_id')->with('class.medium','subject');
    }

    public function question_choice(){
        return $this->hasMany(OnlineExamQuestionChoice::class,'online_exam_id');
    }

    public function student_attempt(){
        return $this->hasOne(StudentOnlineExamStatus::class,'online_exam_id');
    }

}
