<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;



class PermissionFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {

      $sections = ['usuario', 'seccion' ];
      // $section = $arguments[0];          
      //echo $permission = $arguments[1];
      
      /*array de secciones permitidas
      $sections = ['usuario', 'seccion' ];
      $section = $arguments[0];          
      $permission = $arguments[1];
      //if(!isset($section) || !is_numeric($section)){
        if(!isset($section) || !in_array($section, $sections) || !isset($permission)){
        throw new \Exception('Seccion no valida'); 
      }     

      //verificamos en la base de datos que el usuario tenga el permiso 
      echo $section;
      echo $permission;
      */

      
      

    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
