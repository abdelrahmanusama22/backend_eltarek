<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class GarageLinkRequest extends Model {
    protected $fillable = ['user_id','identifier','car_name','status','admin_notes','reviewed_by','reviewed_at'];
    protected $casts = ['reviewed_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function toApi(): array { return ['id'=>$this->id,'identifier'=>$this->identifier,'car_name'=>$this->car_name,'status'=>$this->status,'admin_notes'=>$this->admin_notes,'created_at'=>$this->created_at?->toIso8601String()]; }
}
