<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     *  EHWEB EDIT
     * Create the public method to return the validation rules
     * @var string[]
     */     
    public $registration = [];


    /**
     * Class constructor.
     *
     * Will call the parent constructor and then load the ConfigFiles
     * and call the configure() method to set up the validation rules.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->configureRegistrationRules();
    }
     


    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var string[]
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    
    private function configureRegistrationRules(): void
    {
      $env = getenv('CI_ENVIRONMENT');
      $this->registration = [
        'username' => [
          'label' => 'Auth.username',
          'rules' => [
            'required',
            'max_length[30]',
            'min_length[3]',
            'regex_match[/\A[a-zA-Z0-9\.]+\z/]',
            'is_unique[users.username]',
          ],
         'errors' => [
          'is_unique' => '*El Alias se ha registrado previamente',
          'regex_match' => '*El Alias solo puede contener letras y números',
          'min_length' => '*El Alias debe tener al menos 3 caracteres',
          'max_length' => '*El Alias no puede tener más de 30 caracteres',
          'required' => '*El Alias de usuario es obligatorio'        
          ]
        ],    
        'email' => [
          'label' => 'Auth.email',
          'rules' => [
            'required',
            'max_length[254]',
            'valid_email',
            'is_unique[auth_identities.secret]',
          ],
          'errors' => [
            'is_unique' => '*El Email se ha registrado previamente',
            'valid_email' => '*El Email no es válido',
            'max_length' => '*El Email no puede tener más de 254 caracteres',
            'required' => '*El Email es obligatorio'
          ]
        ],
        'password' => [
          'label' => 'Auth.password',
          'rules' => [
            'required',
            'max_byte[72]',
            'strong_password[]',
          ],
          'errors' => [
            'max_byte' => 'La contraseña no puede tener más de 72 caracteres',
            'required' => '*La contraseña es obligatoria',            
            'strong_password' => '*La contraseña debe tener 8 caracteres con al menos una mayúscula, una minúscula, un número y un carácter especial',
          ]
        ],
        'password_confirm' => [
          'label' => 'Auth.passwordConfirm',
          'rules' => 'required|matches[password]',
          'errors' => [
            'matches' => '*Las contraseñas no coinciden',
            'required' => '*La confirmación de contraseña es obligatoria'
          ]
        ],        
      ];
      //verificamos si estamos en desarrollo, caso contrario, solicitamos todos los datos
      if($env !== 'development') {
        //agregamos reglas de usuario al $this->registration
        $this->registration ['first_name'] = [
          'label' => 'Nombre(s)',
          'rules' => [
            'required',
            'min_length[3]',
            'max_length[50]',
            'regex_match[/^[/\A[a-zA-Z0-9\.]+\z/]',
          ],
          'errors' => [
            'regex_match' => '*El nombre solo puede contener letras y números',
            'min_length' => '*El nombre debe tener al menos 3 caracteres',
            'max_length' => '*El nombre no puede tener más de 50 caracteres',
            'required' => '*El nombre del usuario es obligatorio'
          ]
        ];
        $this->registration ['last_name'] = [
          'label' => 'Apellidos',
          'rules' => [
            'required',
            'min_length[3]',
            'max_length[50]',
            'regex_match[/^[/\A[a-zA-Z0-9\.]+\z/]',
          ],
          'errors' => [
            'regex_match' => '*El apellido solo puede contener letras y números',
            'min_length' => '*El apellido debe tener al menos 3 caracteres',
            'max_length' => '*El apellido no puede tener más de 50 caracteres',
            'required' => '*El apellido del usuario es obligatorio'
          ]
        ];                               
      }//end if      
    }
    
  /*
  public $registration = [
    'username' => [
      'label' => 'Usuario',
      'rules' => [
        'required',
        'max_length[30]',
        'min_length[3]',
        'regex_match[/\A[a-zA-Z0-9\.]+\z/]',
        'is_unique[user.username]',
      ],
      'errors' => [
        'is_unique' => '*Nombre de usuario ya registrado',
        'regex_match' => '*El nombre de usuario solo puede contener letras y números',
        'min_length' => '*El nombre de usuario debe tener al menos 3 caracteres',
        'max_length' => '*El nombre de usuario no puede tener más de 30 caracteres',
        'required' => '*El nombre de usuario es obligatorio'        
      ]
    ],    
    'email' => [
      'label' => 'Auth.email',
      'rules' => [
        'required',
        'max_length[254]',
        'valid_email',
        'is_unique[auth_identities.secret]',
      ],
      'errors' => [
        'is_unique' => '*El correo electrónico ya está registrado',
        'valid_email' => '*El correo electrónico no es válido',
        'max_length' => '*El correo electrónico no puede tener más de 254 caracteres',
        'required' => '*El correo electrónico es obligatorio'
      ]
    ],
    'password' => [
      'label' => 'Auth.password',
      'rules' => [
        'required',
        'max_byte[72]',
        'strong_password[]',
      ],
      'errors' => [
        'max_byte' => 'Auth.errorPasswordTooLongBytes',
      ]
    ],
    'password_confirm' => [
      'label' => 'Auth.passwordConfirm',
      'rules' => 'required|matches[password]',
    ],       
    'first_name' => [
      'label' => 'Nombre(s)',
      'rules' => [
        'required',
        'min_length[3]',
        'max_length[50]',
        'regex_match[/^[/\A[a-zA-Z0-9\.]+\z/]',
      ],
      'errors' => [
        'regex_match' => '*El nombre solo puede contener letras y números',
        'min_length' => '*El nombre debe tener al menos 3 caracteres',
        'max_length' => '*El nombre no puede tener más de 50 caracteres',
        'required' => '*El nombre es obligatorio para el registro'
      ]
    ], 
  ];*/
  
}