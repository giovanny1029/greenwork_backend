<?php
// filepath: c:\dev\giova\backend\src\FormDataHandler.php

/**
 * Clase para procesar solicitudes multipart/form-data
 * Especialmente útil para solicitudes PUT que pueden tener problemas con la forma estándar
 * de procesamiento de Slim
 */
class FormDataHandler {
    
    /**
     * Procesa el contenido de una solicitud multipart/form-data
     * 
     * @param \Slim\Http\Request $request La solicitud original
     * @return array Un array con los archivos procesados
     */
    public static function processFormData($request) {
        $contentType = $request->getHeaderLine('Content-Type');
        
        // Solo procesar si es multipart/form-data
        if (strpos($contentType, 'multipart/form-data') === false) {
            return [];
        }
        
        // Utilizar la super global $_FILES que puede contener los datos incluso si Slim no los procesó
        if (!empty($_FILES)) {
            $files = [];
            foreach ($_FILES as $key => $file) {
                // Crear un objeto similar a UploadedFile de Slim
                $files[$key] = [
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => $file['size'],
                    'tmp_name' => $file['tmp_name'],
                    'error' => $file['error'],
                    'raw' => $file
                ];
            }
            return $files;
        }
        
        return [];
    }
    
    /**
     * Obtiene el contenido de un archivo subido
     * 
     * @param array $file El archivo procesado
     * @return string|null El contenido del archivo o null si hay error
     */
    public static function getFileContents($file) {
        if (!$file || !isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return null;
        }
        
        return file_get_contents($file['tmp_name']);
    }
}
