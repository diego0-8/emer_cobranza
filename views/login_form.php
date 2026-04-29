<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url(img/fondo1.png);
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }
        
        .container {
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgb(0 0 0 / 31%);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: #15a37500;
        }

        .container img{
            max-width: 100%;
            height: 64px;
            margin-bottom: 2px;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #060606;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: #003399;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: left;
        }
        
        .alert-danger {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        
        .alert-success {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        
        .alert-info {
            background-color: #eef;
            border: 1px solid #ccf;
            color: #33c;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.8rem;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
        }
        
        .demo-credentials h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .demo-credentials p {
            color: #6c757d;
            font-size: 0.8rem;
            margin-bottom: 5px;
        }
        
        .demo-credentials strong {
            color: #495057;
        }
        
        /* Media queries para responsividad */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 30px 20px;
                max-width: 100%;
                margin: 0 10px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .form-control {
                padding: 12px 12px 12px 40px;
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 12px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 25px 15px;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .form-control {
                padding: 10px 10px 10px 35px;
                font-size: 0.85rem;
            }
            
            .input-wrapper i {
                left: 12px;
                font-size: 1rem;
            }
        }
        
        /* Asegurar que la imagen de fondo funcione en todos los navegadores */
        @supports not (backdrop-filter: blur(10px)) {
            .container {
                background: rgba(255, 255, 255, 0.98);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="img/emer.png" alt="logo">
        </div>

        <?php if (!empty($debug_client_console) && is_array($debug_client_console)): ?>
            <script>
                // #region agent log
                (function () {
                    try {
                        console.info('[LOGIN_DEBUG]', <?php echo json_encode($debug_client_console, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
                    } catch (e) {}
                })();
                // #endregion
            </script>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="index.php" id="loginForm" autocomplete="on">
            <input type="hidden" name="action" value="process_login">
            <div class="form-group">
                <label for="usuario">Nombre de Usuario</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="usuario" 
                           name="usuario" 
                           class="form-control" 
                           placeholder="Ingresa tu usuario"
                           value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
                           required 
                           autocomplete="username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="contrasena" 
                           name="contrasena" 
                           class="form-control" 
                           placeholder="Ingresa tu contraseña"
                           required 
                           autocomplete="current-password">
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="footer">
            <p>EMERMEDICA COBRANZAS <br>  por Diego Alejandro Lara Guaquez <br> <br> &copy; 2025 prueba base de datos</p>
        </div>
        
        
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const usuarioInput = document.getElementById('usuario');
            const contrasenaInput = document.getElementById('contrasena');
            
            // Limpiar mensajes de error al escribir
            usuarioInput.addEventListener('input', function() {
                const errorAlert = document.querySelector('.alert-danger');
                if (errorAlert) {
                    errorAlert.remove();
                }
            });
            
            contrasenaInput.addEventListener('input', function() {
                const errorAlert = document.querySelector('.alert-danger');
                if (errorAlert) {
                    errorAlert.remove();
                }
            });
            
            // Validación del formulario
            form.addEventListener('submit', function(e) {
                const usuario = usuarioInput.value.trim();
                const contrasena = contrasenaInput.value.trim();
                
                if (!usuario || !contrasena) {
                    e.preventDefault();
                    alert('Por favor, completa todos los campos.');
                    return false;
                }
                
                // Mostrar indicador de carga
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
                submitBtn.disabled = true;
            });
            
            // Enfocar en el primer campo al cargar
            usuarioInput.focus();
        });
    </script>
</body>
</html>