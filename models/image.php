<?php
use Illuminate\Database\Eloquent\Model;
 
class Image extends Model {
    public $timestamps = false;
    protected $table = 'images';
    protected $primaryKey = 'id_image';
    protected $fillable = ['imagescol', 'name'];
    public $incrementing = true;
    
    // No guardar la imagen codificada en las respuestas JSON
    protected $hidden = ['imagescol'];
    
    /**
     * Método para asegurar que la imagen se guarde correctamente
     * Se llama automáticamente antes de guardar el modelo
     */
    public function save(array $options = []) {
        // Verificamos que tengamos datos en imagescol
        if (empty($this->imagescol)) {
            error_log('Advertencia: Intentando guardar imagen sin datos binarios (imagescol vacío)');
        }
        
        // Registramos información de depuración
        error_log('Guardando imagen: ID=' . $this->id_image . ', Nombre=' . $this->name . ', Tamaño=' . strlen($this->imagescol ?? ''));
        
        // Verificamos valores antes de guardar
        if ($this->id_image) {
            // Verificamos el estado actual en la BD
            $currentState = self::find($this->id_image);
            if ($currentState) {
                error_log('Estado actual en BD: ID=' . $currentState->id_image . 
                          ', Nombre=' . $currentState->name . 
                          ', Tamaño=' . strlen($currentState->imagescol ?? ''));
            }
        }
        
        // Intentamos guardar y devolvemos el resultado
        $result = parent::save($options);
        
        // Verificar que se guardó correctamente
        if ($result) {
            // Refrescar el modelo desde la base de datos
            $fresh = $this->fresh();
            if ($fresh && empty($fresh->imagescol)) {
                error_log('Advertencia: La imagen se guardó pero los datos binarios no se almacenaron correctamente');
            } else if ($fresh) {
                error_log('Imagen guardada correctamente: ID=' . $fresh->id_image . 
                          ', Nombre=' . $fresh->name . 
                          ', Tamaño=' . strlen($fresh->imagescol ?? ''));
            }
        } else {
            error_log('Error: No se pudo guardar la imagen');
        }
        
        return $result;
    }
    
    /**
     * Obtiene la URL para acceder a la imagen
     * @return string
     */
    public function getUrl() {
        return '/api/images/data/' . $this->id_image;
    }
    
    /**
     * Codifica la imagen en base64 para mostrarla en navegadores
     * @return string
     */
    public function getBase64DataUrl() {
        if (empty($this->imagescol)) {
            return null;
        }
          // Ya que ahora imagescol siempre debe ser una string base64,
        // simplemente verificamos si es una string válida
        $imageData = $this->imagescol;
        
        // Verificar si es una cadena válida en base64
        if (!$imageData || !is_string($imageData)) {
            return null;
        }
        
        // Asumimos que los datos ya están en base64
        // Extraer el tipo MIME de la imagen si es posible
        try {
            // Intentamos decodificar un poco de la imagen para determinar su tipo
            $decodedSample = base64_decode(substr($imageData, 0, 100), true);
            if ($decodedSample) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($finfo, $decodedSample);
                finfo_close($finfo);
                
                if (!$mime) {
                    $mime = 'image/jpeg'; // Tipo predeterminado
                }
            } else {
                $mime = 'image/jpeg'; // No pudimos decodificar, usar tipo predeterminado
            }
        } catch (\Exception $e) {
            $mime = 'image/jpeg'; // Error al procesar, usar tipo predeterminado
        }
        
        // Devolver el URL de datos con el tipo MIME
        return 'data:' . $mime . ';base64,' . $imageData;
    }
    
    /**
     * Relación: Una imagen puede ser usada como perfil por muchos usuarios
     */
    public function users() {
        return $this->hasMany('User', 'profile_image_id', 'id_image');
    }
}
