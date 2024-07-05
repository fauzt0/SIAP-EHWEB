<?php
namespace App\Models\Usuarios;
use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuario';
    protected $primaryKey       = 'id_usuario';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['nombre','apellido_paterno','apellido_materno','email','password','telefono','movil','estatus','avatar'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'creado_en';
    protected $updatedField  = 'actualizado_en';
    protected $deletedField  = 'eliminado_en';

    // Validation

    // Callbacks
    protected $allowCallbacks       = true;
    protected $beforeInsert         = ["beforeInsert"];
    
    //funciones callbacks
    protected function beforeInsert(array $params)
    {
        $params = $this->passwordHash($params);        
        return $params;
    }

  /**
   * Recursively hashes the password in the given data array.
   *
   * @param array $data The data array to hash the password in.
   * @return array The modified data array with the password hashed.
   */
    protected function passwordHash(array $data)
    {
      if(isset($data['data']['password'])){ //hash password
        $data['data']['password'] = $this->passwordHash($data['data']['password']);        
      }
      return $data;
    }

    
    


}