<?php
/*
 * Controlador Principal de la seccion de usuarios y administradores
 * Permite la gestion de usuarios y administradores mediante funciones CRUD
 *
 * Esta clase hereda de la clase RoleController, la cual permite la gestion de roles incluyendo las siguientes funciones y atributos: 
 * $this->usersProvider = auth()->getProvider(); //modelo de usuarios proporcionado por Shield
 *
 * Metodos disponibles: addUserPermissions($user, $permissions = [])
 *
 * 
 */
namespace App\Controllers\Users;

use App\Controllers\Users\RoleController; //
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User; //SHIELD user managing 
use App\Libraries\Breadcrumb;

/**
 * En Caso de no usera RoleController, sera necesario extender de la clase "BaseController"
 */
class UserController extends RoleController
{
  /**
   * En caso de no usar RoleController, sera necesario instanciar el modelo de usuarios descomentando la siguiente linea:
   * #protected $usersProvider;
   */
  protected $helpers = ['form', 'userdata', 'url'];
  protected $breadCrumb = []; //objeto global breadcrumb con las rutas por defecto 

  //creamos el init controller de la clase padre BaseController para inicializar variables globales
  public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
  {
    // Do Not Edit This Line
    parent::initController($request, $response, $logger);
    //Edit below this line

    //creamos el objeto breadcrumb con las rutas por defecto
    $this->breadcrumb = new Breadcrumb([
      'Inicio' => base_url(),
      'Usuarios' => route_to('users.list'),
    ]);

    $this->setViewNoContent('No se tiene contenido');
    $this->setPageTittleAhead('Usuarios', 'Gestión de Usuarios'); //seteamos titulo y cabecera

  /*Salidas del viewData personalizadas ()
   * 
   * Esta estructura se puede implementar en el constructor o en el método que muestre una salida, para establecer todos los datos de salida necesarios para la vista, sin embargo, se recomienda implementar los metodos de viewData disponibles de App:info. Se uttiliza array_merge() para unir los arrays en caso de que se tengan datos previamente establecidos en el constructor
   **/
  /*
   *$this->viewData = array_merge($this->viewData, [
   'success'     => true,     
   'statusCode'  => $isEmpty ? ResponseInterface::HTTP_NO_CONTENT : ResponseInterface::HTTP_OK,
   'message'     => $isEmpty ? 'No users found' : 'List of all users',            
   'error'       => $isEmpty ? 'No users found. Empty Data' : '',
   'response'    => [
   'breadcrumb'      => $breadCrumb,
   'responseMessage' => $isEmpty ? 'No se encontraron usuarios' : 'Listado de Usuarios:',
   'isEmpty'         => $isEmpty, //boolean
   'usersList'       => $users, //array  
   ],         
   ]);*/

  /*
   * La estructura outputData, permite enviar y devolver datos a otros métodos o a solicitudes HTTP, de esta manera se puede controlar la salida y la estructura de los datos que se retornan
   *
   */

  /**
   * $this->outputData['success']     = true;     
   * $this->outputData['statusCode']  = $isEmpty? ResponseInterface::HTTP_NO_CONTENT : ResponseInterface::HTTP_OK;
   * $this->outputData['message']     = $isEmpty? 'No users found' : 'List of all users';            
   * $this->outputData['error']       = $isEmpty? 'No users found. Empty Data' : '';
   * $this->outputData['response']    = []; //empty response
   */

  /*
   * En caso de no usar RoleController, sera necesario instanciar el modelo de usuarios    
   */
  //$this->usersProvider = auth()->getProvider(); //el modelo de usuarios de Shield se genera en RoleController
  }


  public function index(): string
  {
    $this->setViewSuccess('Bienvenido al panel de control');
    $this->setPageTittleAhead('Administración de Usuarios', 'Panel de Control - Administración de Usuarios');
    $this->viewData['response'] = ['responseMessage' => 'Administración de Usuarios'];
    $this->viewData['breadCrumb'] = $this->breadcrumb->getBreadCrumbHtml(
    [
      'Inicio' => route_to('dashboard.index'),
      'Usuarios' => route_to('users.list'),
    ]
    );

    return $this->renderLayout('Layouts/user_loggedin_layout', 'Users/main_users');
  }


  /**
   * list_ajax()
   * -------------------------------------------------------------------
   * Endpoint consumido exclusivamente por DataTables a través de AJAX.
   * Coordina con el modelo UserModel llamando a get_datatables() para obtener
   * solo los usuarios de la página actual, y convierte los datos crudos
   * de la base de datos en un arreglo formateado de HTML (badges, avatares)
   * devolviendo la estructura JSON estricta que exige la librería de DataTables.
   */
  public function list_ajax()
  {
    // Validación estricta de petición AJAX y metódo POST para prevenir accesos directos o falsificaciones
    if (!$this->request->isAJAX() || strtolower($this->request->getMethod()) !== 'post') {
      return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada. Método no válido o acceso no autorizado.']);
    }

    $postData = $this->request->getPost();
    $list = $this->usersProvider->get_datatables($postData);
    $data = array();
    $no = (int) $this->request->getPost('start');

    foreach ($list as $user) {
      $no++;
      $row = array();

      // 0. Avatar
      // Usamos el avatar genérico por ahora, más adelante se puede conectar al campo $user->avatar si lo manejas
      $avatarUrl = base_url('bootstrap/img/avatars/avatar.jpg');
      $row[] = '<img src="' . $avatarUrl . '" width="32" height="32" class="rounded-circle my-n1" alt="Avatar">';

      // 1. Name & Username
      $fullName = esc($user->first_name . ' ' . $user->last_name);
      $row[] = $fullName . '<br><small class="text-muted">' . esc($user->username) . '</small>';

      // 2. Role (Viene del join de auth_groups_users.group como "role")
      $role = $user->role ? ucfirst($user->role) : 'Indefinido';
      $row[] = esc($role);

      // 3. Email (Viene del join con auth_identities.secret como "email")
      $row[] = esc($user->email ?? 'N/A');

      // 4. Status Badge
      // Ignoramos $user->active (ya que Shield lo reserva para validar correos)
      // Basamos el estatus físico en si la cuenta fue borrada suavemente (Soft Deletes)
      $statusHtml = '';
      if (!empty($user->deleted_at)) {
        $statusHtml = '<span class="badge badge-subtle-danger">Eliminado</span>';
      } else {
        $statusHtml = '<span class="badge badge-subtle-success">Activo</span>';
      }
      $row[] = $statusHtml;

      $data[] = $row;
    }

    $output = array(
      "draw" => (int) $this->request->getPost('draw'),
      "recordsTotal" => $this->usersProvider->count_all(),
      "recordsFiltered" => $this->usersProvider->count_filtered($postData),
      "data" => $data,
    );

    return $this->response->setJSON($output);
  }



  // List all users and dashboard
  public function index_bak(): string
  {
    //$users = $this->usersProvider->findAll(); 
    $users = $this->listUsers();
    $isEmpty = empty($users);


    //establecemos los datos de la vista    
    if ($isEmpty) {
      $this->setViewNoResult('No se encontraron usuarios', 'Búsqueda sin resultados');
    }
    else {
      $this->setViewSuccess('Resultado del listado de usuarios');
    }

    $this->setPageTittleAhead('Usuarios', 'Gestión de Usuarios'); //seteamos titulo y cabecera
    $this->viewData['breadCrumb'] = $this->breadcrumb->getBreadCrumbHtml();
    $this->viewData['response'] = [
      'responseMessage' => $isEmpty ? 'No se encontraron usuarios en el sistema' : 'Listado de Usuarios:',
      'isEmpty' => $isEmpty, //boolean
      'usersList' => $users, //array  
      'userCount' => count($users), //variable para mostrar el numero de resultados 
    ];

    return view('Users/index', $this->viewData);
  }


  /**
   * Este metodo permite listar todos los usuarios. 
   * TODO(desarrollador): Aqui se puede implementar un metodo de busqueda personalizado, como por ejemplo, por nombre, email.
   * @return array  
   */
  private function listUsers(): array
  {
    $users = $this->usersProvider->findAll();
    return $users;
  }


  public function create()
  {
    // Si no tenemos un post, generamos un error 404
    if (!$this->request->is('post')) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("No se ha enviado un metodo POST válido");
    }

    $rules = [
      'username' => [
        'label' => 'Nombre de usuario',
        'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[30]|is_unique[users.username]',
        'errors' => [
          'required' => 'El nombre de usuario es obligatorio.',
          'alpha_numeric_space' => 'El nombre de usuario solo puede contener letras, números y espacios.',
          'min_length' => 'El nombre de usuario debe tener al menos 3 caracteres.',
          'max_length' => 'El nombre de usuario no puede superar los 30 caracteres.',
          'is_unique' => 'Este nombre de usuario ya está registrado.'
        ]
      ],
      'email' => [
        'label' => 'Correo electrónico',
        'rules' => 'required|valid_email|is_unique[auth_identities.secret]',
        'errors' => [
          'required' => 'El correo electrónico es obligatorio.',
          'valid_email' => 'Debe proporcionar un correo electrónico válido.',
          'is_unique' => 'Este correo electrónico ya está registrado.'
        ]
      ],
      'password' => [
        'label' => 'Contraseña',
        'rules' => 'required|min_length[8]',
        'errors' => [
          'required' => 'La contraseña es obligatoria.',
          'min_length' => 'La contraseña debe tener al menos 8 caracteres.'
        ]
      ],
      'password_confirm' => [
        'label' => 'Confirmar contraseña',
        'rules' => 'required|matches[password]',
        'errors' => [
          'required' => 'Debe confirmar su contraseña.',
          'matches' => 'Las contraseñas no coinciden.'
        ]
      ],
      'first_name' => [
        'label' => 'Nombre',
        'rules' => 'required',
        'errors' => [
          'required' => 'El nombre es obligatorio.'
        ]
      ],
      'last_name' => [
        'label' => 'Apellido',
        'rules' => 'required',
        'errors' => [
          'required' => 'El apellido es obligatorio.'
        ]
      ],
      'role' => [
        'label' => 'Rol inicial',
        'rules' => 'required',
        'errors' => [
          'required' => 'Debe seleccionar un rol para el usuario.'
        ]
      ],
    ];

    if (!$this->validate($rules)) {
      $this->setOutputError('Error de validación', $this->validator->getErrors());
      return $this->response->setJSON($this->outputData);
    }

    // 1. Instanciamos la Entidad User de Shield (ya importada arriba)
    $user = new User([
      'username' => $this->request->getPost('username'),
      'email' => $this->request->getPost('email'),
      'password' => $this->request->getPost('password'),
      // Campos personalizados en la tabla users
      'first_name' => $this->request->getPost('first_name'),
      'last_name' => $this->request->getPost('last_name'),
    ]);

    // 2. Guardamos mediante el provider de Shield
    $result = $this->usersProvider->save($user);

    if (!$result) {
      $this->setOutputError('No se pudo crear el usuario', $this->usersProvider->errors());
      return $this->response->setJSON($this->outputData);
    }

    // 3. Obtenemos el ID del usuario recién insertado
    $userId = $this->usersProvider->getInsertID();

    // 4. Buscamos el objeto recién creado para asignarle el Rol (Group) en Shield
    $savedUser = $this->usersProvider->findById($userId);
    if ($savedUser) {
      // En Shield, el AuthGroups usa el método addGroup() de la Entidad HasGroups
      $savedUser->addGroup($this->request->getPost('role'));
    }

    // Devolvemos los datos del usuario creado  
    $this->setOutputSuccess('Usuario creado correctamente', ResponseInterface::HTTP_CREATED);
    $this->outputData['response'] = [
      'userId' => $userId,
      'username' => $user->username,
      'email' => $user->email,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'role' => $this->request->getPost('role')
    ];

    return $this->response->setJSON($this->outputData);
  }


  // Show user details
  public function show(int $userId): string
  {
    //$this->usersProvider->find($userId)->addPermission('beta.access');
    //$this->usersProvider->find($userId)->removePermission('beta.access');
    //agregamos el permiso beta.access al usuario, con el metodo addPermission de la clase padre RoleController    
    //precargamos los datos de la funcion
    $user = $this->usersProvider->find($userId);
    $isEmpty = is_null($user);

    /*
     $token = $this->getCsrfToken();
     var_dump($token);
     die;
     */

    if ($isEmpty) {
      $this->setViewNoResult('No se encontró al usuario', 'Búsqueda sin resultados');
    }
    else {
      $this->setViewSuccess('Resultado de los detalles del usuario');
    }

    $this->setPageTittleAhead('Usuarios', 'Datos del usuario'); //seteamos titulo y cabecera
    $this->viewData['breadCrumb'] = $this->breadcrumb->getBreadCrumbHtml(['User Detail' => '']);
    $this->viewData['response'] = [
      'responseMessage' => $isEmpty ? 'No se encontró al usuario en el sistema' : 'Detalles del Usuario:',
      'isEmpty' => $isEmpty,
      'userDetails' => $user,
      'groups' => $this->authGroups->groups,
      'permissions' => $this->authGroups->permissions,
    ];

    return view('Users/show', $this->viewData);
  }


  public function getCsrfToken(): array
  {

    $security = service('security');

    //retornamos un array con los tokens CSRF 
    return [
      'name' => $security->getTokenName(),
      'hash' => $security->getHash(),
    ];

  /*
   return setJSON([
   'name' => $security->getTokenName(),
   'hash' => $security->getHash(),
   ]);*/

  }



  public function update(int $userId)
  {
    //si no tenemos un post, generamos un error 404
    if (!$this->request->is('post')) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("No se ha enviado un metodo POST válido");
    }
    //verificamos si el usuario existe en la base de datos
    $user = $this->usersProvider->find($userId);
    $isEmpty = is_null($user);


    if ($isEmpty) {
      $this->setOutputNoContent('No se encontró al usuario');
      return $this->response->setJSON($this->outputData);
    }

    //devolvemos los datos del usuario  
    $this->setOutputSuccess('Usuario actualizado correctamente', [
      'userId' => $userId,
      'username' => $user->username,
      'email' => $user->email,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
    ]);

    return $this->response->setJSON($this->outputData);

  }

  /**
   * Método público que actúa como wrapper para llamar al método protegido syncUserGroups
   */
  public function syncGroups(int $userId, array $groups): array
  {
    return $this->syncUserGroups($userId, $groups);
  }

/*
 // Código comentado anterior
 $user = $this->usersProvider->find($userId);
 $isEmpty = empty($user);
 $breadCrumb = $this->breadcrumb->getBreadCrumbHtml(['User Update' => '']);
 $userData = [
 'username' => $this->request->getPost('username'),
 'first_name' => $this->request->getPost('first_name'),
 'last_name' => $this->request->getPost('last_name'),
 'type' => $this->request->getPost('type'),
 ];
 
 //recibimos los datos del usuario para editar en el modelo por medio de post
 if(!$isEmpty){
 $user->name = $this->request->getPost('name');
 $user->email = $this->request->getPost('email');
 $user->role = $this->request->getPost('role');
 $user->save();
 }
 
 $this->viewData = array_merge($this->viewData,   [
 'statusCode'  => $isEmpty ? ResponseInterface::HTTP_NO_CONTENT : ResponseInterface::HTTP_OK,
 'message'     => $isEmpty ? 'No user found' : 'Edit user',
 'error'       => $isEmpty ? 'No user found. Empty Data' : '',
 'response'    => [
 'breadcrumb'      => $breadCrumb,
 'responseMessage' => $isEmpty ? 'No se encontro al usuario en el sistema' : 'Detalles del Usuario:',
 'isEmpty'         => $isEmpty,
 'userDetails'     => $user,        
 'groups'          => $this->authGroups->groups,
 'permissions'     => $this->authGroups->permissions,        
 ],
 ]);
 
 }
 */



//creamos una funcion para actualizar los roles, que se extienda de RoleController




}