<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
session_start();
require_once('../model/admin-sesionModel.php');
require_once('../model/admin-usuarioModel.php');
require_once('../model/adminModel.php');

require_once ('../../vendor/autoload.php');
require_once ('../../vendor/phpmailer/phpmailer/src/Exception.php');
require_once ('../../vendor/phpmailer/phpmailer/src/PHPMailer.php');
require_once ('../../vendor/phpmailer/phpmailer/src/SMTP.php');
$tipo = $_GET['tipo'];

    //use PHPMailer\PHPMailer\PHPMailer;
    //use PHPMailer\PHPMailer\SMTP;
    //use PHPMailer\PHPMailer\Exception;

//instanciar la clase categoria model
$objSesion = new SessionModel();
$objUsuario = new UsuarioModel();
$objAdmin = new AdminModel();

//variables de sesion
$id_sesion = $_POST['sesion'];
$token = $_POST['token'];

if ($tipo == "listar_usuarios_ordenados_tabla") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        $pagina = $_POST['pagina'];
        $cantidad_mostrar = $_POST['cantidad_mostrar'];
        $busqueda_tabla_dni = $_POST['busqueda_tabla_dni'];
        $busqueda_tabla_nomap = $_POST['busqueda_tabla_nomap'];
        $busqueda_tabla_estado = $_POST['busqueda_tabla_estado'];
        //repuesta
        $arr_Respuesta = array('status' => false, 'contenido' => '');
        $busqueda_filtro = $objUsuario->buscarUsuariosOrderByApellidosNombres_tabla_filtro($busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado);
        $arr_Usuario = $objUsuario->buscarUsuariosOrderByApellidosNombres_tabla($pagina, $cantidad_mostrar, $busqueda_tabla_dni, $busqueda_tabla_nomap, $busqueda_tabla_estado);
        $arr_contenido = [];
        if (!empty($arr_Usuario)) {
            // recorremos el array para agregar las opciones de las categorias
            for ($i = 0; $i < count($arr_Usuario); $i++) {
                // definimos el elemento como objeto
                $arr_contenido[$i] = (object) [];
                // agregamos solo la informacion que se desea enviar a la vista
                $arr_contenido[$i]->id = $arr_Usuario[$i]->id;
                $arr_contenido[$i]->dni = $arr_Usuario[$i]->dni;
                $arr_contenido[$i]->nombres_apellidos = $arr_Usuario[$i]->nombres_apellidos;
                $arr_contenido[$i]->correo = $arr_Usuario[$i]->correo;
                $arr_contenido[$i]->telefono = $arr_Usuario[$i]->telefono;
                $arr_contenido[$i]->estado = $arr_Usuario[$i]->estado;
                $opciones = '<button type="button" title="Editar" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target=".modal_editar' . $arr_Usuario[$i]->id . '"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-info" title="Resetear Contraseña" onclick="reset_password(' . $arr_Usuario[$i]->id . ')"><i class="fa fa-key"></i></button>';
                $arr_contenido[$i]->options = $opciones;
            }
            $arr_Respuesta['total'] = count($busqueda_filtro);
            $arr_Respuesta['status'] = true;
            $arr_Respuesta['contenido'] = $arr_contenido;
        }
    }
    echo json_encode($arr_Respuesta);
}

if ($tipo == "registrar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        //repuesta
        if ($_POST) {
            $dni = $_POST['dni'];
            $apellidos_nombres = $_POST['apellidos_nombres'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $password = $_POST['password'];

            if ($dni == "" || $apellidos_nombres == "" || $correo == "" || $telefono == "" || $password == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objUsuario->buscarUsuarioByDni($dni);
                if ($arr_Usuario) {
                    $arr_Respuesta = array('status' => false, 'mensaje' => 'Registro Fallido, Usuario ya se encuentra registrado');
                } else {
                    $id_usuario = $objUsuario->registrarUsuario($dni, $apellidos_nombres, $correo, $telefono, $password);
                    if ($id_usuario > 0) {
                        // array con los id de los sistemas al que tendra el acceso con su rol registrado
                        // caso de administrador y director
                        $arr_Respuesta = array('status' => true, 'mensaje' => 'Registro Exitoso');
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al registrar producto');
                    }
                }
            }
        }
    }
    echo json_encode($arr_Respuesta);
}

if ($tipo == "actualizar") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        //repuesta
        if ($_POST) {
            $id = $_POST['data'];
            $dni = $_POST['dni'];
            $nombres_apellidos = $_POST['nombres_apellidos'];
            $correo = $_POST['correo'];
            $telefono = $_POST['telefono'];
            $estado = $_POST['estado'];

            if ($id == "" || $dni == "" || $nombres_apellidos == "" || $correo == "" || $telefono == "" || $estado == "") {
                //repuesta
                $arr_Respuesta = array('status' => false, 'mensaje' => 'Error, campos vacíos');
            } else {
                $arr_Usuario = $objUsuario->buscarUsuarioByDni($dni);
                if ($arr_Usuario) {
                    if ($arr_Usuario->id == $id) {
                        $consulta = $objUsuario->actualizarUsuario($id, $dni, $nombres_apellidos, $correo, $telefono, $estado);
                        if ($consulta) {
                            $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                        } else {
                            $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                        }
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'dni ya esta registrado');
                    }
                } else {
                    $consulta = $objUsuario->actualizarUsuario($id, $dni, $nombres_apellidos, $correo, $telefono, $estado);
                    if ($consulta) {
                        $arr_Respuesta = array('status' => true, 'mensaje' => 'Actualizado Correctamente');
                    } else {
                        $arr_Respuesta = array('status' => false, 'mensaje' => 'Error al actualizar registro');
                    }
                }
            }
        }
    }
    echo json_encode($arr_Respuesta);
}
if ($tipo == "reiniciar_password") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
        //print_r($_POST);
        $id_usuario = $_POST['id'];
        $password = $objAdmin->generar_llave(10);
        $pass_secure = password_hash($password, PASSWORD_DEFAULT);
        $actualizar = $objUsuario->actualizarPassword($id_usuario, $pass_secure);
        if ($actualizar) {
            $arr_Respuesta = array('status' => true, 'mensaje' => 'Contraseña actualizado correctamente a: ' . $password);
        } else {
            $arr_Respuesta = array('status' => false, 'mensaje' => 'Hubo un problema al actualizar la contraseña, intente nuevamente');
        }
    }
    echo json_encode($arr_Respuesta);
}

if ($tipo == "sent_email_password") {
    $arr_Respuesta = array('status' => false, 'msg' => 'Error_Sesion');
    if ($objSesion->verificar_sesion_si_activa($id_sesion, $token)) {
       
    $datos_sesion = $objSesion->buscarSesionLoginById($id_sesion);
    $datos_usuario = $objUsuario->buscarUsuarioById($datos_sesion->id_usuario);
    $llave = $objAdmin->generar_llave(30);
    $token = password_hash($llave, PASSWORD_DEFAULT);
    $update = $objUsuario->uptdateResetPassword($datos_sesion->id_usuario, $llave, 1);
        if ($update) {

                        //Import PHPMailer classes into the global namespace
            //These must be at the top of your script, not inside a function
            //Load Composer's autoloader (created by composer, not included with PHPMailer)
            require '../../vendor/autoload.php';

            //Create an instance; passing `true` enables exceptions
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host       = 'mail.importecsolutions.com';                     //Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                $mail->Username   = 'inventario_diego@importecsolutions.com';                     //SMTP username
                $mail->Password   = 'inventariopass';                               //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                //Recipients
                $mail->setFrom('inventario_diego@importecsolutions.com', 'Mailer');
                $mail->addAddress($datos_usuario->correo, $datos_usuario->nombres_apellidos);     //Add a recipient
                /*$mail->addAddress('ellen@example.com');               //Name is optional
                $mail->addReplyTo('info@example.com', 'Information');
                $mail->addCC('cc@example.com');
                $mail->addBCC('bcc@example.com');

                //Attachments
                $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
                $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
                */
                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Cambio de Contraseña - Sist. Inventario Diego';
                $mail->Body    = <<<HTML
        <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Contraseña - EXTREME AI</title>
    <style>
        /* Reset básico compatible con email */
        table, td, div, h1, h2, h3, p, a {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, Helvetica, sans-serif !important;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
            width: 100% !important;
            min-width: 100% !important;
            line-height: 1.6 !important;
        }

        table {
            border-collapse: collapse !important;
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        .email-wrapper {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 40px 20px;
        }

        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .header-table {
            width: 100%;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 50%, #a93226 100%);
            position: relative;
        }

        .header-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 50px 50px, 30px 30px;
        }

        .header-content {
            position: relative;
            z-index: 2;
            padding: 50px 40px;
            text-align: center;
        }

        .brand-container {
            margin-bottom: 30px;
        }

        .logo-section {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            padding: 20px 30px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .logo-icon {
            display: inline-block;
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            text-align: center;
            line-height: 60px;
            font-size: 28px;
            margin-right: 20px;
            vertical-align: middle;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .brand-name {
            display: inline-block;
            font-size: 32px;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 3px;
            vertical-align: middle;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header-title {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin: 20px 0 8px 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .header-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            text-align: center;
            margin: 0;
            font-weight: 400;
        }

        .content-wrapper {
            padding: 50px 40px;
        }

        .greeting {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .message {
            color: #5a6c7d;
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .message strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .button-section {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            border: 1px solid #dee2e6;
        }

        .button-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 18px 45px;
            border-radius: 50px;
            font-size: 17px;
            font-weight: 700;
            border: none;
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.3);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .reset-button:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%) !important;
            box-shadow: 0 12px 35px rgba(231, 76, 60, 0.4);
            transform: translateY(-2px);
        }

        .stats-container {
            display: flex;
            justify-content: space-around;
            margin: 35px 0;
            background: #f8f9fa;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 900;
            color: #e74c3c;
            margin-bottom: 5px;
            display: block;
        }

        .stat-label {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .security-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 5px solid #f39c12;
            padding: 25px;
            margin: 30px 0;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.1);
        }

        .security-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .security-icon {
            color: #e67e22;
            font-size: 22px;
            font-weight: bold;
            margin-right: 12px;
            width: 30px;
            height: 30px;
            background: rgba(230, 126, 34, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .security-title {
            color: #d68910;
            font-size: 17px;
            font-weight: 700;
            margin: 0;
        }

        .security-text {
            color: #b7950b;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .info-icon {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }

        .info-title {
            color: #495057;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            color: #e74c3c;
            font-size: 18px;
            font-weight: 700;
        }

        .divider {
            height: 2px;
            background: linear-gradient(to right, transparent, #e9ecef, transparent);
            margin: 40px 0;
            border-radius: 2px;
        }

        .alternative-link {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            border: 1px solid #e9ecef;
        }

        .link-title {
            color: #495057;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .copy-link {
            color: #e74c3c;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            background: #ffffff;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 14px;
        }

        .footer-table {
            width: 100%;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #ecf0f1;
        }

        .footer-content {
            padding: 40px;
            text-align: center;
        }

        .footer-text {
            color: #bdc3c7;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .footer-link {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
        }

        .social-container {
            margin: 25px 0;
        }

        .social-link {
            display: inline-block;
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            text-align: center;
            line-height: 45px;
            margin: 0 8px;
            text-decoration: none;
            color: #bdc3c7;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .social-link:hover {
            background: #e74c3c;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
        }

        .copyright {
            color: #95a5a6;
            font-size: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* Tablets */
        @media only screen and (max-width: 768px) {
            .email-container {
                max-width: 90%;
            }

            .stats-container {
                flex-direction: row;
            }

            .stat-item {
                padding: 10px;
            }
        }

        /* Teléfonos */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 10px !important;
            }

            .email-container {
                border-radius: 12px !important;
            }

            .header-content {
                padding: 30px 20px !important;
            }

            .content-wrapper {
                padding: 30px 20px !important;
            }

            .footer-content {
                padding: 30px 20px !important;
            }

            .brand-name {
                font-size: 24px !important;
                display: block !important;
                margin-top: 15px !important;
                letter-spacing: 2px !important;
            }

            .logo-icon {
                display: block !important;
                margin: 0 auto 15px auto !important;
            }

            .header-title {
                font-size: 22px !important;
            }

            .reset-button {
                padding: 16px 35px !important;
                font-size: 16px !important;
            }

            .stats-container {
                flex-direction: column !important;
                gap: 20px !important;
            }

            .info-grid {
                grid-template-columns: 1fr !important;
                gap: 30px !important; /* Aumenta el espacio entre los elementos */
            }

            .stat-number {
                font-size: 28px !important;
            }

            .security-header {
                justify-content: center;
            }
        }

        /* Pantallas grandes */
        @media only screen and (min-width: 1200px) {
            .email-container {
                max-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td>
                    <div class="email-container">

                        <!-- Header -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" class="header-table">
                            <tr>
                                <td>
                                    <div class="header-pattern"></div>
                                    <div class="header-content">
                                        <div class="brand-container">
                                            <div class="logo-section">
                                                <span class="logo-icon">&#128274;</span>
                                                <span class="brand-name">EXTREME AI</span>
                                            </div>
                                        </div>
                                        <h1 class="header-title">Restablecer Contraseña</h1>
                                        <p class="header-subtitle">Solicitud de cambio de contraseña segura</p>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <!-- Content -->
                        <div class="content-wrapper">
                            <div class="greeting">
                                ¡Hola, María! &#128075;
                            </div>

                            <div class="message">
                                Hemos recibido una solicitud para <strong>restablecer la contraseña</strong> de tu cuenta EXTREME AI. Para garantizar la seguridad de tu información, hemos generado un enlace temporal y seguro.
                            </div>

                            <div class="button-section">
                                <div class="button-label">Haz clic en el botón para continuar</div>
                                <a href="#" class="reset-button">Cambiar Contraseña</a>
                            </div>

                            <div class="stats-container">
                                <div class="stat-item">
                                    <span class="stat-number">24</span>
                                    <span class="stat-label">Horas válido</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">1</span>
                                    <span class="stat-label">Solo uso</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">256</span>
                                    <span class="stat-label">Bits encriptación</span>
                                </div>
                            </div>

                            <div class="security-notice">
                                <div class="security-header">
                                    <div class="security-icon">&#9888;</div>
                                    <h3 class="security-title">Aviso Importante de Seguridad</h3>
                                </div>
                                <p class="security-text">
                                    Si <strong>NO solicitaste</strong> este cambio de contraseña, puedes ignorar este email de forma segura. Tu contraseña actual permanecerá activa y segura. Te recomendamos revisar la actividad reciente de tu cuenta.
                                </p>
                            </div>

                            <div class="info-grid">
                                <div class="info-card">
                                    <span class="info-icon">&#128337;</span>
                                    <div class="info-title">Tiempo límite</div>
                                    <div class="info-value">24:00 hrs</div>
                                </div>
                                <div class="info-card">
                                    <span class="info-icon">&#128510;</span>
                                    <div class="info-title">Nivel seguridad</div>
                                    <div class="info-value">Máximo</div>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <div class="message">
                                <strong>¿Necesitas ayuda?</strong> Nuestro equipo de soporte está disponible 24/7 para asistirte con cualquier consulta sobre tu cuenta o este proceso de restablecimiento.
                            </div>

                            <div class="alternative-link">
                                <div class="link-title">&#128279; Enlace alternativo (copia y pega en tu navegador):</div>
                                <div class="copy-link">https://extremeai.com/reset-password?token=XtRm3AI_s3cur3_t0k3n_2025</div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" class="footer-table">
                            <tr>
                                <td>
                                    <div class="footer-content">
                                        <p class="footer-text">
                                            Este email fue enviado desde un sistema automatizado seguro.<br>
                                            Para tu protección, no respondas a este mensaje.
                                        </p>
                                        <p class="footer-text">
                                            ¿Necesitas ayuda? Visita nuestro <a href="#" class="footer-link">Centro de Soporte 24/7</a>
                                        </p>

                                        <div class="social-container">
                                            <a href="#" class="social-link">@</a>
                                            <a href="#" class="social-link">in</a>
                                            <a href="#" class="social-link">f</a>
                                            <a href="#" class="social-link">ig</a>
                                            <a href="#" class="social-link">yt</a>
                                        </div>

                                        <p class="copyright">
                                            &copy; 2025 <strong>EXTREME AI</strong> - Inteligencia Artificial Avanzada<br>
                                            Torre Empresarial, Piso 25 | Ciudad Tech, País Digital 12345<br>
                                            Todos los derechos reservados | Política de Privacidad
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>

                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

HTML;


                $mail->send();
                echo 'Message has been sent';
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }else {
            echo "Error al actualizar";
        }
print_r($update);

    }
}