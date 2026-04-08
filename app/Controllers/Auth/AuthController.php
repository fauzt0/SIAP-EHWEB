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

        $remember = (bool) $this->request->getPost('remember');

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

        // Mapa de redirección por prioridad de rol
        $roleRoutes = [
            'superadmin' => '/admin/dashboard',
            'admin' => '/admin/dashboard',
            'finance' => '/finance/dashboard',
            'inventory' => '/inventory/dashboard',
            'sales' => '/sales/dashboard',
            'purchasing' => '/purchasing/dashboard',
            'hr' => '/hr/dashboard',
        ];

        foreach ($roleRoutes as $group => $route) {
            if ($user->inGroup($group)) {
                return redirect()->to($route);
            }
        }

        return redirect()->to('/dashboard');
    }
}