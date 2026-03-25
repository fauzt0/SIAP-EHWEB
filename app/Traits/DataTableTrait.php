<?php
declare(strict_types=1);

namespace App\Traits;

/**
 * DataTableTrait
 * Proporciona métodos reutilizables para el procesamiento en servidor de DataTables
 * en cualquier Modelo de CodeIgniter 4.
 *
 * Requisitos:
 * 1. El modelo debe definir la propiedad $datatableConfig conteniendo 'column_order' y 'column_search'
 */
trait DataTableTrait
{
    /**
     * Aplica los filtros de búsqueda global generados por el cajón de "Buscar" del frontend
     * y el ordenamiento (ORDER BY) si el usuario hace clic en la cabecera de la tabla.
     * 
     * Puede ser invocado dentro de _get_datatables_query() u otro lado del constructor.
     */
    protected function _apply_datatables_filters($postData = [])
    {
        // Búsqueda
        $search = isset($postData['search']) ? $postData['search'] : null;
        if (isset($search['value']) && $search['value'] != '') {
            $searchValue = $search['value'];
            $this->builder()->groupStart();
            
            $i = 0;
            if (isset($this->datatableConfig['column_search']) && is_array($this->datatableConfig['column_search'])) {
                foreach ($this->datatableConfig['column_search'] as $item) {
                    if ($i === 0) {
                        $this->builder()->like($item, $searchValue);
                    } else {
                        $this->builder()->orLike($item, $searchValue);
                    }
                    $i++;
                }
            }
            $this->builder()->groupEnd();
        }

        // Ordenamiento
        $order = isset($postData['order']) ? $postData['order'] : null;
        if (isset($order)) {
            $column_index = $order[0]['column'];
            // Validamos que la columna exista en la conf
            if (isset($this->datatableConfig['column_order'][$column_index])) {
                $column_name = $this->datatableConfig['column_order'][$column_index];
                if ($column_name) {
                    // Prevenir SQL injection indirecto validando el flag de dirección
                    $dir = strtolower($order[0]['dir']) === 'asc' ? 'asc' : 'desc';
                    $this->builder()->orderBy($column_name, $dir);
                }
            }
        } elseif (isset($this->datatableConfig['order'])) {
            $configOrder = $this->datatableConfig['order'];
            $this->builder()->orderBy(key($configOrder), $configOrder[key($configOrder)]);
        }
    }

    /**
     * Método base que puede ser sobrescrito por el Modelo.
     * Si no se sobrescribe, aplica los filtros sobre la tabla del modelo estándar.
     */
    protected function _get_datatables_query($postData = [])
    {
        $this->_apply_datatables_filters($postData);
    }

    /**
     * get_datatables()
     * Obtiene el listado respetando LIMIT y OFFSET
     */
    public function get_datatables($postData = [])
    {
        $this->_get_datatables_query($postData);
        
        $length = isset($postData['length']) ? (int) $postData['length'] : -1;
        $start = isset($postData['start']) ? (int) $postData['start'] : 0;
        
        if ($length != -1) {
            $this->builder()->limit($length, $start);
        }
        
        return $this->builder()->get()->getResult();
    }

    /**
     * count_filtered()
     * Cuenta cuántos registros coinciden con el filtro de búsqueda actual (Para DataTables).
     */
    public function count_filtered($postData = [])
    {
        $this->_get_datatables_query($postData);
        return $this->builder()->countAllResults();
    }

    /**
     * count_all()
     * Cuenta absolutamente TODOS los registros en la base de datos sin
     * importar los filtros de búsqueda.
     */
    public function count_all($where = [])
    {
        $builder = $this->db->table($this->table);
        if (!empty($where)) {
            $builder->where($where);
        }
        return $builder->countAllResults();
    }
}
