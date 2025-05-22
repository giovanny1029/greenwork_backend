<?php
use Illuminate\Database\Eloquent\Model;
 
class User extends Model{
    public $timestamps = false;
    protected $table = 'users';
    protected $fillable = ['id', 'first_name', 'last_name', 'email', 'password', 'role', 'preferred_language', 'profile_image_id'];
    
    // Relación con las compañías: un usuario puede tener muchas compañías
    public function companies() {
        return $this->hasMany('Company', 'user_id');
    }
    
    // Relación con la imagen de perfil: un usuario tiene una imagen de perfil
    public function profileImage() {
        return $this->belongsTo('Image', 'profile_image_id', 'id_image');
    }
}