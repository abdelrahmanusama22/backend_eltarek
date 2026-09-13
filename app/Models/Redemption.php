<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redemption extends Model
{
    protected $fillable = ['user_id', 'reward_id', 'code', 'points_spent', 'valid_until', 'status', 'used_at', 'used_by'];

    protected $casts = ['valid_until' => 'date:Y-m-d', 'used_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function reward() { return $this->belongsTo(Reward::class); }
    public function usedBy() { return $this->belongsTo(User::class, 'used_by'); }
    public function getEffectiveStatusAttribute(): string { return $this->status === 'active' && $this->valid_until->isPast() ? 'expired' : $this->status; }
    public function toApi(): array { return ['id'=>$this->id,'code'=>$this->code,'points_spent'=>$this->points_spent,'valid_until'=>$this->valid_until->format('Y-m-d'),'status'=>$this->effective_status,'used_at'=>$this->used_at?->toIso8601String(),'reward'=>['id'=>$this->reward_id,'name'=>$this->reward?->name,'name_ar'=>$this->reward?->name_ar]]; }
}
