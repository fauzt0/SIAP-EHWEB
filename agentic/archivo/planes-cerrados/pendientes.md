* Administrador:
  - Alta edición de usuarios administradores
  - Asignación de roles (alta de roles con permisos especificos por rol)
  - Aisngación de permisos independientes (en caso de querer agregar o editar permisos que no esten en el rol)
  - Acceso y busqueda avanzada de bitacora de administrador (todos los movimientos y filtro avanzado). Todos los movimientos que haga cualquier usuario en el sistema deben reflejarse en la bitacora, desde inicios de sesion, edicion, alta o eliminacion de datos.
  - Edición y alta de datos de la empresa (perfil matriz y sucursales.
  - Cada sucual debe permitir el alta de logotipos y datos relacionados(pdf, imagenes, word,)
  - Verificar que el sistema de permisos funcione y proporcione o mediante el filtro desingado, impida o permita el acceso a ciertos modulos
  - Verificar que el sistema de permisos vaya de la mano con la visibilidad de accesos en el menu lateral. En caso de que un administrador no tenga acceso a cierta area, no deberia ser visible el acceso en el menu.
  
  Importante recordar que los administradores son usuarios gestionados por shield, al igual que los clientes por lo que es posible que tengan el uso de tablas similares en base de datos, pero con algunas variantes.
  
  Verificar que los permisos de este modulo se esten filtrando.
  Verificar que el sistema tenga alertas vinculadas a este modulo y los permisos del usuario correspondiente a este modulo.
  
  
 
 *Recursos humanos
  - Alta/edicion/eliminacion de trabajadores.
  - Vinculacion de trabajador con perfil de administrador (en caso de que el trabajador requiera acceso al sistema)
  - Gestión de trabajadores con datos legales de mexico. Complementar si es necesario (imss,  infonavit, vacaciones, horarios laborales, datos de contacto (son diferentes al de adminsitrador). Integrar al sistema de alertas y notificaciones, alertas por documentos faltantes como ine, comprobantes de domicilio, etc. Estas alertas solo deben salir para los administradores que tengan acceso al modulo correspondiente (ej:recursoshumanos/trabajadores)
  - Alta y gestión de departamentos que se puedan agregar en forma de listado desplegable desde el alta y edición del trabajador(es informativo, no cumple funciones del sistema mas que por puesto, los accesos al sistema se administran directamente en el modulo de administrador, en
  los roles y permisos)
  - Verificar sistema de contratos. El sistema debe permitir crear contratos a partir de plantillas, con el logotipo de la empresa o sucursal, ademas editar datos y generar el arbol historico de contratos(ya existe pero veriricar), ademas, debe generar un nuevo contrato por cada edición del trabajador como por ejemplo direccion, puesto, etc.
  - Agregar sistema de nominas. Verificar que el sistema tenga un sistema de nominas(ligado al sueldo del trabajador), por tablas, por nomina de trabajador, con monto, desglose y nomina mensual, quincenal, semanal, etc. El sistema debe ser capaz de crear nominas (manual o automaticas), que le permitan ver el monto total de la nomina de cierto periodo, pagos pendientes, metodos de pago (cuentas de trabajadores, tarjetas, bancos,etc) y diferentes cards informativas en el listado de nominas, indicando datos como total de nominas, pagos pendientes, pagadas en el mes, total pendiente del mes(nominas no pagadas. Las nominas deben generar un recibo con un logotipo, datos del trabajador, periodo de pago, estatus y si es pago total o parcial, deducciones, bonos, prestaciones, imss, etc. Integrar al sistema de alertas las notificaciones de nomina pendientes para administradores con acceso a este modulo. Ademas un modal con calendarizacion de nominas(planeador se adjuntan ejemplos en /agentic/nomina). 
  
  
  *Proveedores
  - Alta edición de proveedores con datos de contacto, categorias de proveedore (verificar que se tenga otro apartado que permita agregar o editar categorias con alertas en caso de eliminar, las categorias tienen softdelete para evitar problemas al ver historicos de proveedores)
  - Manejo de datos de proveedores como crm de datos, ordenes de compra, y revisar a detalle los servicios recurrentes con calendarizacion y alertas. 
  - Datos asociados a proveedores como contratos, acuerdos, etc, ines, en pdf.
  - Posibilidad de crear ordenes de compra internas, con datos de la compra y un recibo asi como la posibilidada de editar o agregar recibos o datos de proveedor. Las ordenes de compra tienen un dashboar con filtros y posibilidad de ver el estatus de ordenes (se adjuntan ejemplos en agentic/proveedores)
  - Entrada de productos fisicos asi como stock de esos productos (algunos son digitales y en lugar de stock debe ser disponibilidad).
  - Alertas y notificaciones en el sistema de alertas, para administradores con permiso de acceso a este modulo
  
  
  
  
  

