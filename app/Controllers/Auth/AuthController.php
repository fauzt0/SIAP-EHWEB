<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class AuthController extends BaseController
{
    protected $auth;
    protected $config;
    protected $userModel;

    public function __construct()
    {
        $this->auth = service('authentication');
        $this->config = config('Auth');
        $this->userModel = new UserModel();
        helper(['form', 'auth', 'userdata']);
    }

    /**
     * Muestra la página de inicio de sesión
     */
    public function login()
    {
        // Si el usuario ya está autenticado, redirigir según su rol
        if ($this->auth->check()) {
            return $this->redirectBasedOnRole();
        }

        return view('Auth/login');
    }

    /**
     * Procesa el formulario de inicio de sesión
     */
    public function attemptLogin()
    {
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Intentar autenticar al usuario
        $credentials = [
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        $remember = (bool)$this->request->getPost('remember');

        if (!$this->auth->attempt($credentials, $remember)) {
            return redirect()->back()->withInput()->with('error', $this->auth->error() ?? lang('Auth.badAttempt'));
        }

        // Si la autenticación fue exitosa pero el usuario está desactivado
        $user = $this->auth->user();
        if (!$user->active) {
            $this->auth->logout();
            return redirect()->back()->withInput()->with('error', lang('Auth.notActivated'));
        }

        // Redirigir según el rol del usuario
        return $this->redirectBasedOnRole();
    }

    /**
     * Muestra la página de registro
     */
    public function register()
    {
        // Si el usuario ya está autenticado, redirigir según su rol
        if ($this->auth->check()) {
            return $this->redirectBasedOnRole();
        }

        return view('Auth/register');
    }

    /**
     * Procesa el formulario de registro
     */
    public function attemptRegister()
    {
        $rules = [
            'username' => 'required|alpha_numeric_space|min_length[3]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[auth_identities.secret]',
            'password' => 'required|strong_password',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Crear el usuario
        $user = new User([
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'active' => true, // Por defecto, los usuarios están activos
        ]);

        // Guardar el usuario y asignarle el rol 'user'
        $this->userModel->save($user);
        $user = $this->userModel->findById($this->userModel->getInsertID());
        $user->addGroup('user');

        // Iniciar sesión automáticamente
        $this->auth->login($user);

        // Redirigir según el rol
        return $this->redirectBasedOnRole();
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        $this->auth->logout();
        return redirect()->to('/auth/login')->with('message', 'Has cerrado sesión correctamente.');
    }

    /**
     * Muestra la página de perfil del usuario
     */
    public function profile()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/auth/login')->with('error', 'Debes iniciar sesión para acceder a tu perfil.');
        }

        $user = $this->auth->user();
        return view('Auth/profile', ['user' => $user]);
    }

    /**
     * Actualiza el perfil del usuario
     */
    public function updateProfile()
    {
        if (!$this->auth->check()) {
            return redirect()->to('/auth/login')->with('error', 'Debes iniciar sesión para actualizar tu perfil.');
        }

        $user = $this->auth->user();
        $userId = $user->id;

        $rules = [
            'username' => "required|alpha_numeric_space|min_length[3]|is_unique[users.username,id,$userId]",
            'email' => "required|valid_email|is_unique[auth_identities.secret,user_id,$userId]",
        ];

        // Si se proporciona una nueva contraseña, validarla
        if ($this->request->getPost('password')) {
            $rules['password'] = 'required|strong_password';
            $rules['password_confirm'] = 'required|matches[password]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Actualizar datos del usuario
        $user->fill([
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
        ]);

        // Actualizar contraseña si se proporcionó
        if ($this->request->getPost('password')) {
            $user->password = $this->request->getPost('password');
        }

        $this->userModel->save($user);

        return redirect()->to('/auth/profile')->with('message', 'Perfil actualizado correctamente.');
    }

    /**
     * Redirige al usuario según su rol
     */
    protected function redirectBasedOnRole(): RedirectResponse
    {
        $user = $this->auth->user();
        
        if ($user->inGroup('superadmin')) {
            return redirect()->to('/admin/dashboard');
        } elseif ($user->inGroup('admin')) {
            return redirect()->to('/admin/dashboard');
        } elseif ($user->inGroup('finance')) {
            return redirect()->to('/finance/dashboard');
        } elseif ($user->inGroup('inventory')) {
            return redirect()->to('/inventory/dashboard');
        } elseif ($user->inGroup('sales')) {
            return redirect()->to('/sales/dashboard');
        } elseif ($user->inGroup('purchasing')) {
            return redirect()->to('/purchasing/dashboard');
        } elseif ($user->inGroup('hr')) {
            return redirect()->to('/hr/dashboard');
        } else {
            return redirect()->to('/dashboard');
        }
    }
}
use CodeIgniter\HTTP\RedirectResponse;

class AuthController extends BaseController
{
    protected $auth;

    public function __construct()
    {
        $this->auth = auth()->getAuthenticator();
    }

    /**
     * Muestra la página de inicio de sesión
     */
    public function login()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }

        return view('Auth/login');
    }

    /**
     * Procesa el intento de inicio de sesión
     */
    public function attemptLogin()
    {
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Obtener credenciales del formulario
        $credentials = [
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        // Intentar autenticar
        $result = $this->auth->attempt($credentials);

        if (!$result->isOK()) {
            return redirect()->back()->withInput()->with('error', $result->reason());
        }

        // Determinar la redirección basada en el rol del usuario
        return $this->redirectBasedOnRole();
    }

    /**
     * Redirige al usuario según su rol
     */
    protected function redirectBasedOnRole(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->inGroup('superadmin')) {
            return redirect()->to('/admin/dashboard');
        } elseif ($user->inGroup('admin')) {
            return redirect()->to('/admin/dashboard');
        } elseif ($user->inGroup('finance')) {
            return redirect()->to('/finance/dashboard');
        } elseif ($user->inGroup('inventory')) {
            return redirect()->to('/inventory/dashboard');
        } elseif ($user->inGroup('sales')) {
            return redirect()->to('/sales/dashboard');
        } elseif ($user->inGroup('purchasing')) {
            return redirect()->to('/purchasing/dashboard');
        } elseif ($user->inGroup('hr')) {
            return redirect()->to('/hr/dashboard');
        } else {
            return redirect()->to('/dashboard');
        }
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        $this->auth->logout();
        
        return redirect()->to('/login')->with('message', 'Sesión cerrada correctamente');
    }

    /**
     * Muestra la página de registro
     */
    public function register()
    {
        // Si el usuario ya está autenticado, redirigir al dashboard
        if (auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }

        return view('Auth/register');
    }

    /**
     * Procesa el registro de un nuevo usuario
     */
    public function attemptRegister()
    {
        $rules = [
            'firstname' => 'required|min_length[2]',
            'lastname' => 'required|min_length[2]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Crear el usuario
        $users = model(UserModel::class);

        $user = new \CodeIgniter\Shield\Entities\User([
            'username' => $this->request->getPost('email'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'firstname' => $this->request->getPost('firstname'),
            'lastname' => $this->request->getPost('lastname'),
        ]);

        // Guardar el usuario
        $users->save($user);

        // Asignar al grupo 'user' por defecto
        $user = $users->findById($users->getInsertID());
        $user->addGroup('user');

        // Iniciar sesión automáticamente
        $this->auth->login($user);

        return redirect()->to('/dashboard')->with('message', 'Registro completado correctamente');
    }

    /**
     * Muestra la página de perfil del usuario
     */
    public function profile()
    {
        if (!auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $data = [
            'user' => auth()->user()
        ];

        return view('Auth/profile', $data);
    }

    /**
     * Actualiza el perfil del usuario
     */
    public function updateProfile()
    {
        if (!auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $rules = [
            'firstname' => 'required|min_length[2]',
            'lastname' => 'required|min_length[2]',
            'email' => 'required|valid_email',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = auth()->user();
        $users = model(UserModel::class);

        // Actualizar datos del usuario
        $user->fill([
            'firstname' => $this->request->getPost('firstname'),
            'lastname' => $this->request->getPost('lastname'),
            'email' => $this->request->getPost('email'),
        ]);

        $users->save($user);

        return redirect()->to('/profile')->with('message', 'Perfil actualizado correctamente');
    }
}