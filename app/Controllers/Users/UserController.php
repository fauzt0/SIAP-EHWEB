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
  protected $breadcrumb; // objeto global Breadcrumb

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
    $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
      'Inicio' => route_to('dashboard.index'),
      'Usuarios' => route_to('users.list'),
    ]);

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

    foreach ($list as $user) { //recorremos la lista de usuarios para construir el array de datos de respuesta
      $no++;
      $row = array();

      // 0. Avatar
      $avatarFileName = $user->avatar ?? null;
      if (!empty($avatarFileName) && file_exists(FCPATH . 'uploads/avatars/' . $avatarFileName)) {
        $avatarUrl = base_url('uploads/avatars/' . $avatarFileName);
      } else {
        // Fallback al avatar por defecto
        $avatarUrl = base_url('bootstrap/img/avatars/avatar.jpg');
      }
      $row[] = '<img src="' . $avatarUrl . '" width="32" height="32" class="rounded-circle my-n1" style="object-fit: cover;" alt="Avatar">';

      // 1. Name & UsernameBuena
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

      // 5. Botones de Acción
      $userId = $user->id;
      $actionsHtml = '<div class="d-flex gap-1">';
      if (auth()->user()->can('users.view')) {
          $actionsHtml .= '<button class="btn btn-sm btn-outline-primary btn-view-user" data-user-id="' . $userId . '" title="Ver detalles"><i class="fas fa-fw fa-eye"></i></button>';
      }
      if (auth()->user()->can('users.edit')) {
          $actionsHtml .= '<button class="btn btn-sm btn-outline-warning btn-edit-user" data-user-id="' . $userId . '" title="Editar"><i class="fas fa-fw fa-edit"></i></button>';
      }
      if (auth()->user()->can('users.delete')) {
          $actionsHtml .= '<button class="btn btn-sm btn-outline-danger btn-delete-user" data-user-id="' . $userId . '" title="Eliminar"><i class="fas fa-fw fa-trash"></i></button>';
      }
      $actionsHtml .= '</div>';
      $row[] = $actionsHtml;

      $data[] = $row;
    }

    $output = array(
      "draw" => (int) $this->request->getPost('draw'),
      // recordsTotal: total de registros activos (sin soft-delete) para el contador de DataTables
      "recordsTotal" => $this->usersProvider->count_all(['users.deleted_at' => null]),
      "recordsFiltered" => $this->usersProvider->count_filtered($postData),
      "data" => $data,
    );

    return $this->response->setJSON($output);
  }


  /**
   * show_ajax()
   * -------------------------------------------------------------------
   * Endpoint AJAX consumido por el Offcanvas del listado de usuarios.
   * Devuelve los datos completos de un usuario + sus últimos 5 movimientos
   * de la bitácora de actividad en formato JSON.
   */
  public function show_ajax(int $userId)
  {
    if (!$this->request->isAJAX()) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Acceso no permitido');
    }

    // withDeleted() permite encontrar también usuarios con soft-delete activo
    $user = $this->usersProvider->withDeleted()->find($userId);

    if (!$user) {
      $this->setOutputError('Usuario no encontrado', null, ResponseInterface::HTTP_NOT_FOUND);
      return $this->response->setJSON($this->outputData);
    }

    // Obtenemos el email desde las identidades de Shield
    $identities = $user->getEmailIdentity();
    $email = $identities ? $identities->secret : 'N/A';

    // Obtenemos el rol/grupo del usuario
    $groups = $user->getGroups();
    $role = !empty($groups) ? ucfirst(reset($groups)) : 'Sin rol';

    // Avatar
    $avatarFileName = $user->avatar ?? null;
    if (!empty($avatarFileName) && file_exists(FCPATH . 'uploads/avatars/' . $avatarFileName)) {
      $avatarUrl = base_url('uploads/avatars/' . $avatarFileName);
    } else {
      $avatarUrl = base_url('bootstrap/img/avatars/avatar.jpg');
    }

    // Estatus basado en soft delete
    $status = !empty($user->deleted_at) ? 'Eliminado' : 'Activo';
    $statusClass = !empty($user->deleted_at) ? 'danger' : 'success';

    // Últimos 5 movimientos de la bitácora
    $activityModel = new \App\Models\Users\UserActivityLogsModel();
    $recentActivity = $activityModel->getRecentByUser($userId, 5);

    // Formateamos la actividad para el frontend
    $activityFormatted = [];
    foreach ($recentActivity as $log) {
      // Si created_at es un objeto (Time) lo formateamos a texto
      $logDate = is_object($log->created_at) ? $log->created_at->format('d/m/Y H:i') : $log->created_at;
      $activityFormatted[] = [
        'type' => $log->type,
        'description' => $log->description,
        'created_at' => $logDate,
        'ip_address' => $log->ip_address,
      ];
    }

    // Contactos del usuario
    $contactModel = new \App\Models\Users\DataContactUserModel();
    $contacts = $contactModel->where('users_id', $userId)->findAll();

    $this->setOutputSuccess('Datos del usuario cargados');
    $this->outputData['response'] = [
      'id' => $user->id,
      'username' => $user->username,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'email' => $email,
      'role' => $role,
      'contacts' => $contacts,
      'roleKey' => !empty($groups) ? reset($groups) : '',
      'avatar' => $avatarUrl,
      'status' => $status,
      'statusClass' => $statusClass,
      'created_at' => is_object($user->created_at) ? $user->created_at->format('d/m/Y H:i A') : ($user->created_at ?: 'N/A'),
      'activity' => $activityFormatted,
    ];

    return $this->response->setJSON($this->outputData);
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
      'avatar' => [
        'label' => 'Fotografía de perfil',
        // permit_empty permite que el usuario no suba imagen, pero si la sube, se validan extensión, tipo y tamaño (máx 2MB)
        'rules' => 'permit_empty|is_image[avatar]|ext_in[avatar,png,jpg,jpeg,gif]|max_size[avatar,2048]',
        'errors' => [
          'is_image' => 'El archivo debe ser una imagen real.',
          'ext_in' => 'El formato debe ser PNG, JPG, JPEG o GIF.',
          'max_size' => 'La imagen no puede pesar más de 2MB.'
        ]
      ],
    ];

    if (!$this->validate($rules)) {
      $this->setOutputError('Error de validación', $this->validator->getErrors());
      return $this->response->setJSON($this->outputData);
    }

    // Verificamos si se envió un archivo de avatar y procesamos la subida
    $avatarName = null;
    $avatarFile = $this->request->getFile('avatar');

    if ($avatarFile && $avatarFile->isValid() && !$avatarFile->hasMoved()) {
      // Generamos un nombre cifrado único para evitar sobreescribir archivos con el mismo nombre
      $avatarName = $avatarFile->getRandomName();
      // Movemos el archivo a la carpeta pública de destino
      $avatarFile->move(FCPATH . 'uploads/avatars', $avatarName);
    }

    // 1. Instanciamos la Entidad User de Shield (ya importada arriba)
    $user = new User([
      'username' => $this->request->getPost('username'),
      'email' => $this->request->getPost('email'),
      'password' => $this->request->getPost('password'),
      // Campos personalizados en la tabla users
      'first_name' => $this->request->getPost('first_name'),
      'last_name' => $this->request->getPost('last_name'),
      'avatar' => $avatarName, // Guardamos el nombre del archivo en base de datos si existe
    ]);

    // 2. Guardamos mediante el provider de Shield
    $result = $this->usersProvider->save($user);

    if (!$result) {
      $this->setOutputError('No se pudo crear el usuario', $this->usersProvider->errors());
      return $this->response->setJSON($this->outputData);
    }

    // 3. Obtenemos el ID del usuario recién insertado
    $userId = $this->usersProvider->getInsertID();

    // -- Registro de Actividad --
    if ($userId) {
      $logModel = new \App\Models\Users\UserActivityLogsModel();
      // Guardamos que el usuario en sesión (automático) creó un nuevo usuario
      $logModel->logActivity('create_user', 'Alta del nuevo usuario: ' . $user->username . ' (ID: ' . $userId . ')');
    }

    // 4. Buscamos el objeto recién creado para asignarle el Rol (Group) en Shield
    $savedUser = $this->usersProvider->findById($userId);
    if ($savedUser) {
      // En Shield, el AuthGroups usa el método addGroup() de la Entidad HasGroups
      $savedUser->addGroup($this->request->getPost('role'));
    }

    // 5. Procesamos posibles Contactos Adicionales
    $contactsPost = $this->request->getPost('contacts');
    if (is_array($contactsPost) && !empty($contactsPost)) {
      $contactModel = new \App\Models\Users\DataContactUserModel();
      $contactsData = [];
      foreach ($contactsPost as $c) {
        if (!empty($c['source']) && !empty($c['value'])) {
          $contactsData[] = [
            'users_id'       => $userId,
            'contact_source' => $c['source'],
            'contact_value'  => $c['value']
          ];
        }
      }
      if (!empty($contactsData)) {
        $contactModel->insertBatch($contactsData);
      }
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
    //precargamos los datos de la funcion
    $user = $this->usersProvider->find($userId);
    $isEmpty = is_null($user);

    if ($isEmpty) {
      $this->setViewNoResult('No se encontró al usuario', 'Búsqueda sin resultados');
    } else {
      $this->setViewSuccess('Resultado de los detalles del usuario');
    }

    $this->setPageTittleAhead('Usuarios', 'Datos del usuario'); //seteamos titulo y cabecera
    $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml(['User Detail' => '']);
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

  }



  public function update(int $userId)
  {
    // Validación estricta de método POST
    if (!$this->request->is('post')) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("No se ha enviado un metodo POST válido");
    }

    // Verificamos si el usuario existe en la base de datos
    $user = $this->usersProvider->find($userId);
    if (is_null($user)) {
      $this->setOutputError('No se encontró al usuario', null, ResponseInterface::HTTP_NOT_FOUND);
      return $this->response->setJSON($this->outputData);
    }

    // Reglas de validación para la edición
    $rules = [
      'username' => [
        'label' => 'Nombre de usuario',
        'rules' => "required|alpha_numeric_space|min_length[3]|max_length[30]|is_unique[users.username,id,{$userId}]",
        'errors' => [
          'required' => 'El nombre de usuario es obligatorio.',
          'alpha_numeric_space' => 'El nombre de usuario solo puede contener letras, números y espacios.',
          'min_length' => 'El nombre de usuario debe tener al menos 3 caracteres.',
          'max_length' => 'El nombre de usuario no puede superar los 30 caracteres.',
          'is_unique' => 'Este nombre de usuario ya está registrado a otra cuenta.'
        ]
      ],
      'first_name' => [
        'label' => 'Nombre',
        'rules' => 'required',
        'errors' => ['required' => 'El nombre es obligatorio.']
      ],
      'last_name' => [
        'label' => 'Apellido',
        'rules' => 'required',
        'errors' => ['required' => 'El apellido es obligatorio.']
      ],
      'email' => [
        'label' => 'Correo electrónico',
        // Validamos que el email no exista en la tabla auth_identities (como secret) en un usuario distinto al actual
        'rules' => "required|valid_email|is_unique[auth_identities.secret,user_id,{$userId}]",
        'errors' => [
          'required' => 'El correo electrónico es obligatorio.',
          'valid_email' => 'Debe proporcionar un correo electrónico válido.',
          'is_unique' => 'Este correo electrónico ya está registrado a otra cuenta.'
        ]
      ],
      'role' => [
        'label' => 'Rol',
        'rules' => 'required',
        'errors' => ['required' => 'Debe seleccionar un rol para el usuario.']
      ],
    ];

    // Validación condicional de contraseña (solo si se envió)
    $newPassword = $this->request->getPost('password');
    if (!empty($newPassword)) {
      $rules['password'] = [
        'label' => 'Contraseña',
        'rules' => 'min_length[8]',
        'errors' => ['min_length' => 'La contraseña debe tener al menos 8 caracteres.']
      ];
      $rules['password_confirm'] = [
        'label' => 'Confirmar contraseña',
        'rules' => 'required|matches[password]',
        'errors' => [
          'required' => 'Debe confirmar la nueva contraseña.',
          'matches' => 'Las contraseñas no coinciden.'
        ]
      ];
    }

    if (!$this->validate($rules)) {
      $this->setOutputError('Error de validación', $this->validator->getErrors());
      return $this->response->setJSON($this->outputData);
    }

    // Actualizamos los campos del usuario
    $user->first_name = $this->request->getPost('first_name');
    $user->last_name = $this->request->getPost('last_name');
    $user->username = $this->request->getPost('username') ?? $user->username;

    // Si se cambió el password, lo actualizamos
    if (!empty($newPassword)) {
      $user->password = $newPassword;
    }

    $result = $this->usersProvider->save($user);

    if (!$result) {
      $this->setOutputError('No se pudo actualizar el usuario', $this->usersProvider->errors());
      return $this->response->setJSON($this->outputData);
    }

    // Actualizamos el email en la identidad de Shield
    $newEmail = $this->request->getPost('email');
    $identity = $user->getEmailIdentity();
    if ($identity && $identity->secret !== $newEmail) {
      $identity->secret = $newEmail;
      $identityModel = model('UserIdentityModel');
      $identityModel->save($identity);
    }

    // Sincronizamos el rol/grupo
    $newRole = $this->request->getPost('role');
    $user->syncGroups($newRole);

    // Procesamos posibles Contactos Adicionales
    $contactsPost = $this->request->getPost('contacts');
    if (is_array($contactsPost)) { // Even if empty, meaning they deleted all contacts
      $contactModel = new \App\Models\Users\DataContactUserModel();
      $contactModel->where('users_id', $userId)->delete();
      
      $contactsData = [];
      foreach ($contactsPost as $c) {
        if (!empty($c['source']) && !empty($c['value'])) {
          $contactsData[] = [
            'users_id'       => $userId,
            'contact_source' => $c['source'],
            'contact_value'  => $c['value']
          ];
        }
      }
      if (!empty($contactsData)) {
        $contactModel->insertBatch($contactsData);
      }
    }

    // -- Registro de Actividad --
    $logModel = new \App\Models\Users\UserActivityLogsModel();
    $logModel->logActivity('update_user', 'Editó al usuario: ' . $user->username . ' (ID: ' . $userId . ')');

    // Devolvemos respuesta exitosa
    $this->setOutputSuccess('Usuario actualizado correctamente');
    $this->outputData['response'] = [
      'userId' => $userId,
      'username' => $user->username,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'role' => $newRole,
    ];

    return $this->response->setJSON($this->outputData);
  }

  /**
   * Método público que actúa como wrapper para llamar al método protegido syncUserGroups
   */
  public function syncGroups(int $userId, array $groups): array
  {
    return $this->syncUserGroups($userId, $groups);
  }

  /**
   * delete()
   * -------------------------------------------------------------------
   * Ejecuta un Soft Delete sobre el usuario indicado.
   * El usuario no se elimina físicamente de la BD, solo se marca
   * el campo deleted_at con la fecha actual. Se registra en la bitácora.
   */
  public function delete(int $userId)
  {
    if (!$this->request->isAJAX() || !$this->request->is('post')) {
      return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
    }

    // withDeleted() necesario para poder encontrar usuarios ya eliminados
    $user = $this->usersProvider->withDeleted()->find($userId);
    if (!$user) {
      $this->setOutputError('Usuario no encontrado', null, ResponseInterface::HTTP_NOT_FOUND);
      return $this->response->setJSON($this->outputData);
    }

    // Soft Delete — Shield UserModel usa $useSoftDeletes = true
    $result = $this->usersProvider->delete($userId);

    if (!$result) {
      $this->setOutputError('No se pudo eliminar al usuario');
      return $this->response->setJSON($this->outputData);
    }

    // Registro de auditoría
    $logModel = new \App\Models\Users\UserActivityLogsModel();
    $logModel->logActivity('delete_user', 'Eliminó al usuario: ' . $user->username . ' (ID: ' . $userId . ')');

    $this->setOutputSuccess('Usuario eliminado correctamente');
    return $this->response->setJSON($this->outputData);
  }

  /**
   * restore()
   * -------------------------------------------------------------------
   * Revierte el Soft Delete de un usuario, limpiando el campo deleted_at.
   * Se registra en la bitácora de actividad.
   */
  public function restore(int $userId)
  {
    if (!$this->request->isAJAX() || !$this->request->is('post')) {
      return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada.']);
    }

    // withDeleted() es necesario para encontrar el registro con deleted_at
    $user = $this->usersProvider->withDeleted()->find($userId);
    if (!$user) {
      $this->setOutputError('Usuario no encontrado', null, ResponseInterface::HTTP_NOT_FOUND);
      return $this->response->setJSON($this->outputData);
    }

    if (empty($user->deleted_at)) {
      $this->setOutputError('El usuario no está eliminado.');
      return $this->response->setJSON($this->outputData);
    }

    // Restaurar: limpiar deleted_at directamente via DB
    $db = \Config\Database::connect();
    $result = $db->table('users')->where('id', $userId)->update(['deleted_at' => null]);

    if (!$result) {
      $this->setOutputError('No se pudo restaurar al usuario.');
      return $this->response->setJSON($this->outputData);
    }

    // Registro de auditoría
    $logModel = new \App\Models\Users\UserActivityLogsModel();
    $logModel->logActivity('restore_user', 'Restauró al usuario: ' . $user->username . ' (ID: ' . $userId . ')');

    $this->setOutputSuccess('Usuario restaurado correctamente.');
    return $this->response->setJSON($this->outputData);
  }

  /**
   * activity()
   * -------------------------------------------------------------------
   * Muestra la vista principal (con DataTables) del historial de un usuario.
   */
  public function activity(int $userId)
  {
    // withDeleted() por si el usuario está soft-deleted
    $user = $this->usersProvider->withDeleted()->find($userId);
    if (!$user) {
      return redirect()->back()->with('error', 'Usuario no encontrado.');
    }

    $this->setViewSuccess('Actividad del usuario cargada');
    $this->setPageTittleAhead('Actividad de ' . $user->username, 'Actividad del Usuario');

    $this->viewData['response'] = [
      'userId' => $user->id,
      'username' => $user->username,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
    ];

    $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
      'Inicio' => route_to('dashboard.index'),
      'Usuarios' => route_to('users.list'),
      'Actividad' => current_url(),
    ]);

    // Opcional: Obtener valores únicos de Type para el select
    $logModel = new \App\Models\Users\UserActivityLogsModel();
    $typesRaw = $logModel->select('type')->distinct()->findAll();
    $types = array_map(function($t) { return $t->type; }, $typesRaw);
    $this->viewData['activityTypes'] = $types;

    return $this->renderLayout('Layouts/user_loggedin_layout', 'Users/user_activity');
  }

  /**
   * activity_ajax()
   * -------------------------------------------------------------------
   * Método POST para rellenar el DataTables de actividad con filtros.
   */
  public function activity_ajax(int $userId)
  {
    if (!$this->request->isAJAX()) {
      return $this->response->setStatusCode(403)->setJSON(['error' => 'Petición denegada']);
    }

    $postData = $this->request->getPost();
    // Forzamos el ID de usuario en los parámetros de búsqueda
    $postData['users_id'] = $userId;

    $logModel = new \App\Models\Users\UserActivityLogsModel();
    
    $list = $logModel->get_datatables($postData);
    $data = array();
    $no = (int) $this->request->getPost('start');

    foreach ($list as $log) {
      $no++;
      $row = array();
      
      // Damos formato a la fecha
      $logDate = is_object($log->created_at) ? $log->created_at->format('d/m/Y H:i:s') : $log->created_at;

      // Colores por acción tipo badge
      $badgeColor = 'primary';
      switch($log->type) {
        case 'login': $badgeColor = 'success'; break;
        case 'create_user': $badgeColor = 'info'; break;
        case 'update_user': $badgeColor = 'warning'; break;
        case 'delete_user': $badgeColor = 'danger'; break;
        case 'restore_user': $badgeColor = 'secondary'; break;
        case 'logout': $badgeColor = 'dark'; break;
      }
      $typeHtml = '<span class="badge bg-' . $badgeColor . '">' . esc($log->type) . '</span>';

      $row[] = $no;
      $row[] = $typeHtml;
      $row[] = esc($log->description);
      $row[] = esc($log->ip_address);
      $row[] = $logDate;

      $data[] = $row;
    }

    $output = array(
      "draw" => (int) $this->request->getPost('draw'),
      "recordsTotal" => $logModel->where('users_id', $userId)->countAllResults(),
      "recordsFiltered" => $logModel->count_filtered($postData),
      "data" => $data,
    );

    return $this->response->setJSON($output);
  }

  /**
   * Muestra el formulario para editar los permisos adicionales de un usuario
   */
  public function permissions(int $userId)
  {
    $user = $this->usersProvider->withDeleted()->find($userId);
    if (!$user) {
      return redirect()->back()->with('error', 'Usuario no encontrado.');
    }

    $this->setViewSuccess('Permisos de usuario listos');
    $this->setPageTittleAhead('Permisos de ' . $user->username, 'Permisos de Usuario');

    $this->viewData['response'] = [
      'userId' => $user->id,
      'username' => $user->username,
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
    ];

    $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
      'Inicio' => route_to('dashboard.index'),
      'Usuarios' => route_to('users.list'),
      'Permisos' => current_url(),
    ]);

    // Extraer todos los permisos disponibles en el sistema (AuthGroups config)
    $allPermissions = setting('AuthGroups.permissions');
    
    // Calcular permisos otorgados por sus roles bases
    $userGroups = $user->getGroups();
    $groupPermissions = [];
    $matrix = setting('AuthGroups.matrix');

    foreach ($userGroups as $group) {
        if (isset($matrix[$group])) {
            foreach ($matrix[$group] as $perm) {
                // Si es un wildcard "admin.*", lo expandimos
                if (strpos($perm, '.*') !== false) {
                    $prefix = substr($perm, 0, strpos($perm, '.*'));
                    foreach ($allPermissions as $availablePerm => $desc) {
                        if (strpos($availablePerm, $prefix . '.') === 0) {
                            $groupPermissions[] = strtolower($availablePerm);
                        }
                    }
                } else {
                    $groupPermissions[] = strtolower($perm);
                }
            }
        }
    }
    $groupPermissions = array_unique($groupPermissions);

    // Mapear los roles a sus títulos
    $authGroupsConfig = setting('AuthGroups.groups');
    $userRolesTitles = array_map(function($role) use ($authGroupsConfig) {
      return isset($authGroupsConfig[$role]) ? $authGroupsConfig[$role]['title'] : ucfirst($role);
    }, $userGroups);

    $this->viewData['userRolesTitles'] = $userRolesTitles;

    // Obtener los permisos extra (asignados directamente)
    $personalPermissions = $user->getPermissions();

    // Agrupar los permisos para la vista
    $groupedPermissions = [];
    foreach ($allPermissions as $perm => $desc) {
        $parts = explode('.', $perm);
        $module = $parts[0];
        if (!isset($groupedPermissions[$module])) {
            $groupedPermissions[$module] = [];
        }
        $groupedPermissions[$module][] = [
            'key' => $perm,
            'description' => $desc,
            'is_group' => in_array($perm, $groupPermissions, true),
            'is_personal' => in_array($perm, $personalPermissions, true)
        ];
    }

    $this->viewData['groupedPermissions'] = $groupedPermissions;

    return $this->renderLayout('Layouts/user_loggedin_layout', 'Users/user_permissions');
  }

  /**
   * Guarda los permisos adicionales del usuario
   */
  public function permissions_save(int $userId)
  {
    $user = $this->usersProvider->withDeleted()->find($userId);
    if (!$user) {
      return redirect()->to(route_to('users.list'))->with('error', 'Usuario no encontrado.');
    }

    $selectedPermissions = $this->request->getPost('permissions') ?? [];
    
    // syncPermissions reemplaza completamente la base de personal permissions.
    // Solo debemos guardar los que recibimos de los checkboxes (que serán únicamente los extra).
    
    $userGroups = $user->getGroups();
    $matrix = setting('AuthGroups.matrix');
    $allPermissions = setting('AuthGroups.permissions');
    
    $groupPermissions = [];
    foreach ($userGroups as $group) {
        if (isset($matrix[$group])) {
            foreach ($matrix[$group] as $perm) {
                if (strpos($perm, '.*') !== false) {
                    $prefix = substr($perm, 0, strpos($perm, '.*'));
                    foreach ($allPermissions as $availablePerm => $desc) {
                        if (strpos($availablePerm, $prefix . '.') === 0) {
                            $groupPermissions[] = strtolower($availablePerm);
                        }
                    }
                } else {
                    $groupPermissions[] = strtolower($perm);
                }
            }
        }
    }
    
    // Filtramos para que solo queden los permisos que NO forman parte del grupo base
    // y que de verdad existen en authgroups.
    $finalPermissions = [];
    foreach ($selectedPermissions as $sp) {
        if (!in_array($sp, $groupPermissions, true) && array_key_exists($sp, $allPermissions)) {
            $finalPermissions[] = $sp;
        }
    }

    // Shield's Authorizable trait syncPermissions
    $user->syncPermissions(...$finalPermissions);

    // Registramos en bitácora
    $logModel = new \App\Models\Users\UserActivityLogsModel();
    $logModel->logActivity(
      'update_permissions',
      'Se actualizaron los permisos adicionales de ' . $user->username,
      $userId
    );

    return redirect()->to(route_to('user.permissions', $userId))->with('exito', 'Permisos actualizados correctamente.');
  }


  /**
   * Muestra el formulario de perfil del usuario en sesión
   */
  public function profile(): string
  {
    $user    = auth()->user();
    $groups  = $user->getGroups();
    $role    = !empty($groups) ? ucfirst(reset($groups)) : 'Sin rol';

    // Avatar
    $avatarFile = $user->avatar ?? null;
    $avatarUrl  = (!empty($avatarFile) && file_exists(FCPATH . 'uploads/avatars/' . $avatarFile))
                  ? base_url('uploads/avatars/' . $avatarFile)
                  : base_url('bootstrap/img/avatars/avatar.jpg');

    $contactModel = new \App\Models\Users\DataContactUserModel();
    $contacts     = $contactModel->where('users_id', $user->id)->findAll();

    $this->setPageTittleAhead('Mi Perfil', 'Configuración de Perfil');
    $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml(['Mi Perfil' => '']);
    $this->viewData['response'] = [
      'user'      => $user,
      'role'      => $role,
      'avatarUrl' => $avatarUrl,
      'contacts'  => $contacts,
    ];

    return $this->renderLayout('Layouts/user_loggedin_layout', 'Users/user_profile');
  }


  /**
   * Guarda los cambios del perfil del usuario en sesión
   */
  public function profile_save()
  {
    if (!$this->request->is('post')) {
      throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $user   = auth()->user();
    $userId = $user->id;

    $rules = [
      'first_name' => ['label' => 'Nombre',    'rules' => 'required|max_length[50]'],
      'last_name'  => ['label' => 'Apellido',   'rules' => 'required|max_length[50]'],
      'username'   => [
        'label' => 'Nombre de usuario',
        'rules' => "required|alpha_numeric_space|min_length[3]|max_length[30]|is_unique[users.username,id,{$userId}]",
        'errors' => ['is_unique' => 'Este nombre de usuario ya está en uso.'],
      ],
      'avatar' => [
        'label' => 'Fotografía',
        'rules' => 'permit_empty|is_image[avatar]|ext_in[avatar,png,jpg,jpeg,gif]|max_size[avatar,2048]',
        'errors' => [
          'is_image' => 'El archivo debe ser una imagen.',
          'ext_in'   => 'Formato permitido: PNG, JPG, JPEG, GIF.',
          'max_size' => 'La imagen no puede superar 2MB.',
        ],
      ],
    ];

    // Validar contraseña solo si se proporcionó
    if ($this->request->getPost('password')) {
      $rules['password']         = ['label' => 'Contraseña',         'rules' => 'min_length[8]'];
      $rules['password_confirm'] = ['label' => 'Confirmar contraseña', 'rules' => 'matches[password]'];
    }

    if (!$this->validate($rules)) {
      return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
    }

    // Procesar nueva foto de perfil si se subió
    $avatarName = $user->avatar; // Mantener el actual por defecto
    $avatarFile = $this->request->getFile('avatar');
    if ($avatarFile && $avatarFile->isValid() && !$avatarFile->hasMoved()) {
      // Eliminar el avatar anterior si existe
      if (!empty($avatarName) && file_exists(FCPATH . 'uploads/avatars/' . $avatarName)) {
        unlink(FCPATH . 'uploads/avatars/' . $avatarName);
      }
      $avatarName = $avatarFile->getRandomName();
      $avatarFile->move(FCPATH . 'uploads/avatars', $avatarName);
    }

    // Actualizar datos en el modelo de Shield
    $user->fill([
      'first_name' => $this->request->getPost('first_name'),
      'last_name'  => $this->request->getPost('last_name'),
      'username'   => $this->request->getPost('username'),
      'avatar'     => $avatarName,
    ]);

    if ($this->request->getPost('password')) {
      $user->password = $this->request->getPost('password');
    }

    $this->usersProvider->save($user);

    // Procesar los contactos
    $contactModel = new \App\Models\Users\DataContactUserModel();
    // Eliminar los anteriores para sincronizar fácil
    $contactModel->where('users_id', $user->id)->delete();
    
    $contactsPost = $this->request->getPost('contacts');
    if (is_array($contactsPost) && !empty($contactsPost)) {
      $contactsData = [];
      foreach ($contactsPost as $c) {
        if (!empty($c['source']) && !empty($c['value'])) {
          $contactsData[] = [
            'users_id'       => $user->id,
            'contact_source' => $c['source'],
            'contact_value'  => $c['value']
          ];
        }
      }
      if (!empty($contactsData)) {
        $contactModel->insertBatch($contactsData);
      }
    }

    // Log de actividad
    $logModel = new \App\Models\Users\UserActivityLogsModel();
    $logModel->logActivity('update_profile', 'El usuario actualizó su perfil');

    return redirect()->to(route_to('user.profile'))->with('exito', 'Perfil actualizado correctamente.');
  }

}