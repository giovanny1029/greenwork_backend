<?php
// filepath: c:\dev\giova\backend\models\token.php
use Illuminate\Database\Eloquent\Model;

class Token extends Model {
    public $timestamps = false;
    protected $table = 'tokens';
    protected $fillable = ['id', 'user_id', 'refresh_token', 'expires_at', 'is_revoked', 'created_at'];
    
    // Relationship with user
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    // Check if token is expired
    public function isExpired() {
        return strtotime($this->expires_at) < time();
    }
    
    // Check if token is valid
    public function isValid() {
        return !$this->is_revoked && !$this->isExpired();
    }
}
