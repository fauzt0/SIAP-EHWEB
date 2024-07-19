<?php
/*
 * Filtro de validación de token CSRF y respuesta con regenerador de token
 */
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CsrfTokenFilter implements FilterInterface
{
 
    public function before(RequestInterface $request, $arguments = null)
    {
        //
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
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {        
      $method           = strtoupper($request->getMethod());
      $methodsProtected = ["POST", "PUT", "DELETE", "PATCH"];
        
      if (in_array($method, $methodsProtected, true)) {        
        $response->setHeader(csrf_header(), csrf_hash()); // regeneramos el token CSRF y lo guardamos en el header de la respuesta
      }
    }
}
