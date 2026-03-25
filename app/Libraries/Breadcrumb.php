<?php 
/**
 * Clase para gestionar la barra de navegacion "BREADCRUMB"
 * Gúia de uso: 
 * Implementar la libreria con: use App\Libraries\Breadcrumb;
 * Crear el objeto de la clase $this->breadcrumb = new Breadcrumb(); con las rutas base como parametro. Se recomienda realizarlo en el constructor de la clase
 * En caso de crear el objeto en el constructor, es necesario crear una variable de la clase: protected $breadcrumb = $breadcrumb; para llamarla desde cualquier metodo   
 */
namespace App\Libraries;

class Breadcrumb
{
  
  public $baseListSet = []; //array con la lista de items de la barra de navegacion predefinidos
  public $breadcrumbList = []; //array con la lista de items de la barra de navegacion  
  


  public function __construct($baseListSet = [])
  {
    $this->baseListSet = $baseListSet;         
  }

  /**
   * Devuelve la lista de items definidos en el array base
   */
  public function getBaseUrlList(): array
  {
    return $this->baseListSet;    
  }

  /**
   * Agrega un nuevo item a la lista de items de la barra de navegacion
   * 
   * @param array $breadcumbLeves Associative array of title => URL pairs to add to the breadcrumb. Not HTML string or HTML elements.
   * 
   * */  
  private function getBreadCrumb(array $breadcrumbLevels = []): array
  {      
    //creamos un array nuevo con los items de $this->breadCrumbList y posteriormente le agregamos los items del array $breadcrumbLevels
    $levelsArray = $this->baseListSet;
    //verificamoq que sea un array asociativo y que no este vacio
    if(!empty($breadcrumbLevels)){
      //recorremos uno a uno los elementos del array levels y los agregamos al array $this->breadcrumbList
      foreach ($breadcrumbLevels as $key => $value) {
        $levelsArray[$key] = $value;        
      }
    }
       
    return $levelsArray;
  }

  /**
   * Crea la cadena HTML de la barra de navegacion con la lista de items pasados como parametro.
   *
   * @param array $breadcrumbItems Optional associative array of title => URL pairs to add to the breadcrumb. The last pair will be the current page.
   * @return string HTML for the breadcrumb
   */
  public function getBreadCrumbHtml(array $breadcrumbItems = []): string
  { 
    $breadCrumbString = ""; //devolvera una cadena con el HTML de la barra de navegacion
    $html = "";
    $allItems = $this->getBreadCrumb($breadcrumbItems);     
    
    // Generate the HTML for the breadcrumb
    $html = '<ol class="breadcrumb">';
    foreach ($allItems as $key => $item) {

      //verificamos si item no es vacio o es diferente de ''
      if (!empty($item)) {
        $html .= sprintf('<li class="breadcrumb-item"><a href="%s">%s</a></li>', $item, $key);
      } else {
        $html .= sprintf('<li class="breadcrumb-item">%s</li>', $key);
      }

      
    }
    $html .= '</ol>';
    
    return $html;
  }  







}

