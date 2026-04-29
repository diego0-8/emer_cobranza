<?php
// Archivo: views/gestionar_cliente.php
// Sistema de tipificaciones inteligente para asesores
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php require_once 'shared_styles.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/softphone-web.css">
    <style>
        .gestion-container {
            background: #f8fafc;
            min-height: 100vh;
            padding: 20px;
        }
        
        .cliente-info-card {
    background: white;
    border-radius: 12px;
    padding: 0px 33px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    }
        
        .cliente-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .cliente-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        .cliente-details h2 {
            margin: 0;
            color: #1f2937;
            font-size: 1.5rem;
        }
        
        .cliente-meta {
            color: #6b7280;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .cliente-cedula {
            display: flex;
            align-items: center;
        }
        
        .cliente-email {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .cliente-email #email-value {
            font-weight: normal;
        }
        
        .cliente-email .email-registrado {
            color: #1976d2;
            font-weight: 600;
        }
        
        .cliente-email .email-no-registrado {
            color: #6c757d;
            font-style: italic;
        }
        
        .cliente-telefonos {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            max-width: 100%;
            overflow: hidden;
        }
        
        .telefono-seleccionado {
            color: #3b82f6;
            font-size: 0.9rem;
            font-weight: 500;
            user-select: all;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            background-color: #eff6ff;
            border: 2px solid #3b82f6;
            min-width: 100px;
            max-width: 200px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .telefono-seleccionado:hover {
            background-color: #3b82f6;
            color: white;
            border-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        .telefono-seleccionado:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(59, 130, 246, 0.2);
        }
        
        .telefono-dropdown {
            background: #f8fafc;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.9rem;
            color: #374151;
            min-width: 120px;
            transition: all 0.2s ease;
        }
        
        .telefono-dropdown:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        
        .telefono-dropdown:hover {
            border-color: #9ca3af;
        }
        
        /* Estilos para el desplegable de teléfonos */
        .telefono-dropdown-container {
            display: inline-block;
            margin-left: 10px;
            position: relative;
        }
        
        .telefono-dropdown {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.9rem;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
            text-align: center;
        }
        
        .telefono-dropdown:hover {
            background: #e5e7eb;
            border-color: #3b82f6;
        }
        
        .telefono-dropdown:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .telefono-dropdown option {
            padding: 8px 12px;
            background: white;
            color: #374151;
        }
        
        .telefono-dropdown option:hover {
            background: #f3f4f6;
        }
        
        .telefono-info {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
        }
        
        .telefono-copy-btn {
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .telefono-copy-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .telefono-copy-btn:active {
            transform: translateY(0);
        }
        
        /* Estilos para la información del cliente desde base de datos */
        .cliente-info-db {
            margin-top: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            transition: all 0.3s ease;
        }
        
        .cliente-info-db:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            padding: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }
        
        .info-item strong {
            color: #6b7280;
            font-size: 0.85rem;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item span {
            color: #1f2937;
            font-weight: 600;
            font-size: 1rem;
        }
        
        /* Responsive para la información del cliente */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .info-item {
                padding: 8px;
            }
        }
        
        /* ========================================
           CLASES CSS REUTILIZABLES
           ======================================== */
        
        /* Clases base para tarjetas */
        .card-base {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .card-base:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        
        .card-blue-border {
            border-left: 4px solid #007bff;
        }
        
        .card-blue-bg {
            border-left: 4px solid #3b82f6;
        }
        
        /* Clases para items de información */
        .info-item-base {
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .info-item-base:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }
        
        /* Clases para grid responsivo */
        .grid-responsive {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .grid-responsive-mobile {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        /* Clases para títulos y textos repetitivos */
        .title-base {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 1rem;
        }
        
        .title-large {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .text-label {
            color: #6b7280;
            font-size: 0.85rem;
            display: block;
            margin-bottom: 5px;
        }
        
        .text-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .text-value-dark {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .text-value-red {
            color: #dc2626;
            font-weight: 600;
            font-size: 1rem;
        }
        
        /* Estilos para la información simple del cliente en tipificaciones */
        .cliente-info-simple {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        
        /* Responsive para información simple */
        @media (max-width: 768px) {
            .grid-responsive {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }
            
            .info-item-base {
                padding: 6px;
            }
        }
        
        .tipificacion-card {
            width: 100% !important;
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .tipificacion-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tipificacion-principal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Estilos para el diseño de tipificaciones */
        .columna-tipificaciones {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }
        
        .columna-observaciones {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }
        
        .tipificaciones-especificas {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .tipificacion-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .tipificacion-option:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.15);
        }
        
        .tipificacion-option.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .tipificacion-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .tipificacion-icon.contactado {
            background: #dcfce7;
            color: #166534;
        }
        
        .tipificacion-icon.no-contactado {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .tipificacion-text {
            flex: 1;
        }
        
        .tipificacion-text h3 {
            margin: 0 0 5px 0;
            color: #1f2937;
            font-size: 1.1rem;
        }
        
        .tipificacion-text p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .sub-tipificaciones {
            display: none;
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .sub-option input[type="radio"]:checked + label {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .sub-option input[type="radio"]:checked + label::before {
            content: "✓ ";
            font-weight: bold;
        }
        
        .sub-tipificaciones.show {
            display: block;
        }
        
        .sub-tipificaciones h4 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .sub-options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .sub-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .sub-option:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .sub-option input[type="radio"] {
            margin: 0;
        }
        
        .sub-option label {
            cursor: pointer;
            margin: 0;
            flex: 1;
            color: #374151;
            font-weight: 500;
        }
        
        .acciones-especificas {
            display: none;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .acciones-especificas.show {
            display: block;
        }
        
        .acciones-especificas h4 {
            color: #0369a1;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .form-section {
            width: 100% !important;
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .form-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-prefix {
            position: absolute;
            left: 12px;
            color: #6b7280;
            font-weight: 500;
            z-index: 10;
        }
        
        .input-group .form-input {
            padding-left: 30px;
        }
        
        .form-help {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .form-select, .form-input, .form-textarea {
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        /* Estilos para Canales Autorizados */
        .canales-autorizados-section {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .canales-title {
            color: #495057;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .canales-checkboxes {
            display: flex;
            justify-content: center;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            max-width: 800px;
            width: 100%;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .checkbox-label:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,123,255,0.15);
        }
        
        .canal-checkbox {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            accent-color: #007bff;
            cursor: pointer;
        }
        
        .checkbox-text {
            color: #495057;
            user-select: none;
        }
        
        .checkbox-label:has(.canal-checkbox:checked) {
            border-color: #007bff;
            background-color: #e7f3ff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.2);
        }
        
        .checkbox-label:has(.canal-checkbox:checked) .checkbox-text {
            color: #0056b3;
            font-weight: 600;
        }
        
        
        
        .canales-seleccionados {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .canal-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(40,167,69,0.2);
        }
        
        .comentarios-detalle {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .btn-info {
            background: #06b6d4;
            color: white;
        }
        
        .btn-info:hover {
            background: #0891b2;
            transform: translateY(-1px);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-group .btn {
            flex: 1;
            min-width: 200px;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .btn-lg {
            padding: 15px 30px;
            font-size: 1.1rem;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .alert-info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        
        .info-adicional {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .info-adicional h4 {
            color: #92400e;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .info-adicional.show {
            display: block;
        }
        
        .info-adicional.hide {
            display: none;
        }

        /* Estilos para el historial de gestiones - Diseño empresarial */
        .historial-section {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .historial-title {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.4em;
            font-weight: 600;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .historial-item {
            background: white;
            padding: 0;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #e1e8ed;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .historial-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-1px);
        }
        
        .historial-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .historial-fecha {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95em;
        }
        
        
        .historial-content {
            padding: 20px;
        }
        
        .historial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .historial-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .historial-field-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .historial-field-value {
            color: #2c3e50;
            font-size: 0.95em;
            font-weight: 500;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #3498db;
            min-height: 20px;
        }
        
        .historial-field-value.canal-contacto {
            border-left-color: #e74c3c;
        }
        
        .historial-field-value.tipificacion {
            border-left-color: #f39c12;
        }
        
        .historial-field-value.razon-especifica {
            border-left-color: #3498db;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8f9fa 100%);
            font-weight: 600;
        }
        
        .historial-field-value.asesor {
            border-left-color: #9b59b6;
        }
        
        .historial-field-value.obligacion {
            border-left-color: #e67e22;
        }
        
        .historial-field-value.canales-autorizados {
            border-left-color: #27ae60;
        }
        
        .historial-field-value.observaciones {
            border-left-color: #34495e;
        }
        
        .historial-observaciones {
            grid-column: 1 / -1;
        }
        
        .historial-observaciones .historial-field-value {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .historial-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .historial-field-label {
                font-size: 0.8em;
            }
            
            .historial-field-value {
                font-size: 0.9em;
                padding: 6px 10px;
            }
            
            .historial-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
        }
        
        @media (max-width: 480px) {
            .historial-section {
                padding: 15px;
            }
            
            .historial-content {
                padding: 15px;
            }
            
            .historial-title {
                font-size: 1.2em;
            }
        }
        
        
        .historial-fecha {
            font-weight: bold;
            color: #6c757d;
        }
        
        .historial-tipo {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        .historial-resultado {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: inline-block;
            font-weight: bold;
        }
        
        /* Estilos para detalles de tipificación */
        .historial-detalles {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
        }
        
        .historial-detalles h5 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .detalles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detalle-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .detalle-label {
            font-weight: bold;
            color: #6c757d;
        }
        
        .detalle-valor {
            color: #495057;
            font-weight: 500;
        }
        
        /* Estilos para próxima acción */
        .historial-proxima {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #ffeaa7;
        }
        
        .historial-proxima h5 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .proxima-accion, .proxima-fecha {
            margin-bottom: 8px;
            color: #856404;
        }

         /* Estilos para información del cliente */
         .cliente-info-display {
             background: #f8fafc;
             border-radius: 8px;
             padding: 20px;
             border: 1px solid #e2e8f0;
         }
         
         .info-value {
             background: white;
             padding: 8px 12px;
             border-radius: 6px;
             border: 1px solid #e2e8f0;
             color: #374151;
             font-weight: 500;
         }
         
         /* Estilos para la información del cliente CSV */
         .info-cliente-csv {
             background: #f8fafc;
             border-radius: 12px;
             padding: 25px;
             border: 1px solid #e2e8f0;
         }
         
         .info-seccion {
             background: white;
             border-radius: 8px;
             padding: 20px;
             margin-bottom: 20px;
             border: 1px solid #e2e8f0;
             box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
         }
         
         .info-seccion:last-child {
             margin-bottom: 0;
         }
         
         .info-seccion h4 {
             color: #1f2937;
             margin-bottom: 15px;
             font-size: 1.1rem;
             font-weight: 600;
             display: flex;
             align-items: center;
             gap: 8px;
             border-bottom: 2px solid #e2e8f0;
             padding-bottom: 10px;
         }
         
         .info-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
             gap: 15px;
         }
         
         .info-item {
             display: flex;
             flex-direction: column;
             gap: 5px;
             padding: 12px;
             background: #f8fafc;
             border-radius: 6px;
             border: 1px solid #e2e8f0;
         }
         
         .info-label {
             font-weight: 600;
             color: #6b7280;
             font-size: 0.9rem;
             text-transform: uppercase;
             letter-spacing: 0.5px;
         }
         
         .info-value {
             color: #1f2937;
             font-size: 1rem;
             font-weight: 500;
             word-break: break-word;
         }
         
         /* Estilos para indicadores de mora */
         .mora-baja {
             color: #059669;
             background: #d1fae5;
             padding: 4px 8px;
             border-radius: 4px;
             font-weight: 600;
         }
         
         .mora-media {
             color: #d97706;
             background: #fef3c7;
             padding: 4px 8px;
             border-radius: 4px;
             font-weight: 600;
         }
         
         .mora-alta {
             color: #dc2626;
             background: #fee2e2;
             padding: 4px 8px;
             border-radius: 4px;
             font-weight: 600;
         }
         
        /* Responsive para la información del cliente */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .info-item {
                padding: 10px;
            }
        }
        
        /* Estilos para el layout de Bootstrap */
        .row {
            margin: 0;
            display: flex;
            flex-wrap: wrap;
        }
        
        .col-lg-3, .col-lg-6, .col-lg-4, .col-lg-8, .col-md-12 {
            padding: 0 15px;
            box-sizing: border-box;
        }
        
        .col-lg-3 {
            flex: 0 0 25%;
            max-width: 25%;
        }
        
        .col-lg-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        
        .col-lg-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
        }
        
        .col-lg-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
        
        @media (max-width: 991.98px) {
            .col-lg-3, .col-lg-6, .col-lg-8, .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        
        /* Ajustar el grid de información del cliente para la columna de 3 */
        .col-lg-3 .info-grid {
            grid-template-columns: 1fr;
            gap: 5px;
        }
        
        .col-lg-4 .info-item {
            padding: 5px 8px;
            margin-bottom: 3px;
            font-size: 0.9rem;
        }
        
        .col-lg-4 .info-seccion {
            margin-bottom: 10px;
        }
        
        .col-lg-4 .info-seccion h4 {
            font-size: 0.9rem;
            margin-bottom: 8px;
            padding: 5px 8px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .col-lg-3 .info-label,
        .col-lg-4 .info-label {
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .col-lg-4 .info-value {
            font-size: 0.85rem;
        }
        
        /* Estilos para la lista compacta de datos CSV */
        .datos-csv-lista {
            padding: 10px 0;
        }
        
        .dato-item {
            padding: 6px 8px;
            margin-bottom: 4px;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            border-radius: 3px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
        }
        
        .dato-item:hover {
            background: #e9ecef;
            border-left-color: #0056b3;
        }
        
        .dato-item.mora-baja {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .dato-item.mora-media {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .dato-item.mora-alta {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        /* Estilos para la sección de facturas - removido max-height y overflow-y para evitar doble scroll */
        
        .factura-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .factura-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
            transform: translateY(-1px);
        }
        
        .datos-factura-lista {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 11px;
        }
        
        .dato-item {
            padding: 6px 8px;
            margin-bottom: 4px;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            border-radius: 3px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .dato-item:hover {
            background: #e9ecef;
            border-left-color: #0056b3;
        }
        
        .dato-item.mora-baja {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .dato-item.mora-media {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .dato-item.mora-alta {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        /* Estilos específicos para el campo franja */
        .dato-item.franja-bloqueado {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .dato-item.franja-espera {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .dato-item.franja-activo {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .dato-item.franja-suspendido {
            border-left-color: #6c757d;
            background: #e2e3e5;
        }
        
        .franja-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .estadisticas-facturas {
            background: #e8f4f8;
            border-radius: 6px;
            border-left: 4px solid #3498db;
            padding: 10px;
            margin-top: 15px;
        }
        
        .estadisticas-facturas h6 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .estadisticas-facturas div {
            font-size: 11px;
            color: #555;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        /* Estilos para el desplegable de facturas */
        #factura_gestionar {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            color: #2d3748;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        #factura_gestionar:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        #factura_gestionar:hover {
            border-color: #a0aec0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        #factura_gestionar option {
            padding: 8px 12px;
            background: white;
            color: #2d3748;
        }
        
        #factura_gestionar option:hover {
            background: #f7fafc;
        }
        
        /* Asegurar que Sistema de Tipificaciones y Observaciones tengan el mismo ancho */
        
        /* Asegurar que ambas secciones tengan el mismo estilo visual */
        .tipificacion-card .tipificacion-title,
        .form-section .form-title {
            color: #2d3748;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        /* Ajustar canales autorizados para que se vean en columna */
        .canales-checkboxes .checkbox-group {
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
        }
        
        .canales-checkboxes .checkbox-label {
            width: 100% !important;
            margin-bottom: 5px !important;
        }
         
         /* Estilos para el modal */
         .modal-overlay {
             display: none;
             position: fixed;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             background: rgba(0, 0, 0, 0.5);
             z-index: 1000;
             backdrop-filter: blur(4px);
         }
         
         .modal-content {
             position: absolute;
             top: 50%;
             left: 50%;
             transform: translate(-50%, -50%);
             background: white;
             border-radius: 12px;
             width: 90%;
             max-width: 600px;
             max-height: 90vh;
             overflow-y: auto;
             box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
         }
         
         .modal-header {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 20px 25px;
             border-bottom: 1px solid #e2e8f0;
             background: #f8fafc;
             border-radius: 12px 12px 0 0;
         }
         
         .modal-header h3 {
             margin: 0;
             color: #1f2937;
             font-size: 1.2rem;
         }
         
        .modal-close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .modal-close:hover {
            opacity: 0.7;
        }
        
        .close {
            color: #dc2626;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .close:hover {
            opacity: 0.7;
        }
         
         .modal-body {
             padding: 25px;
         }
         
         .modal-body p {
             color: #6b7280;
             margin-bottom: 20px;
         }
         
         .modal-actions {
             display: flex;
             gap: 15px;
             justify-content: flex-end;
             margin-top: 25px;
             padding-top: 20px;
             border-top: 1px solid #e2e8f0;
         }
         
         .d-flex {
             display: flex;
         }
         
         .justify-content-between {
             justify-content: space-between;
         }
         
         .align-items-center {
             align-items: center;
         }
         
        @media (max-width: 768px) {
            .tipificacion-columnas {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .columna-tipificaciones,
            .columna-observaciones {
                padding: 20px;
            }
             
             .sub-options-grid {
                 grid-template-columns: 1fr;
             }
             
             .form-row {
                 grid-template-columns: 1fr;
             }
             
             .btn-container {
                 flex-direction: column;
                 align-items: center;
             }
             
             .modal-content {
                 width: 95%;
                 margin: 20px;
             }
             
             .modal-actions {
                 flex-direction: column;
         }
     }
     
     /* Botón de Teléfono Flotante */
     .telefono-fab {
         position: fixed;
         bottom: 30px;
         left: 30px;
         width: 60px;
         height: 60px;
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         color: white;
         font-size: 24px;
         cursor: pointer;
         box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
         z-index: 1000;
         transition: all 0.3s ease;
     }
     
     .telefono-fab:hover {
         transform: scale(1.1);
         box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
     }
     
     .telefono-fab:active {
         transform: scale(0.95);
     }
     
     /* Estilos para números clickeables */
     .numero-telefono {
         color: #667eea;
         cursor: pointer;
         text-decoration: underline;
         transition: all 0.3s ease;
         padding: 2px 4px;
         border-radius: 4px;
         display: inline-block;
     }
     .numero-telefono:hover {
         background-color: #667eea;
         color: white;
         transform: scale(1.05);
         box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
     }

        /* Estilos para el buscador de clientes en la primera columna */
        .buscador-cliente-container {
            position: relative;
        }
        
        .resultados-buscador {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            margin-top: 5px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .resultado-buscador-item {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .resultado-buscador-item:hover {
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
        }
        
        .resultado-buscador-item:last-child {
            border-bottom: none;
        }
        
        .resultado-buscador-nombre {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }
        
        .resultado-buscador-info {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .resultado-buscador-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .sin-resultados-buscador {
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }
        
        .loading-buscador {
            padding: 20px;
            text-align: center;
            color: #3b82f6;
        }
        
        /* Estilos para el modal de búsqueda */
        .resultados-busqueda {
            border-top: 1px solid #e0e0e0;
            padding-top: 15px;
        }
     
     .lista-resultados {
         max-height: none;
         overflow-y: visible;
         padding-right: 0;
     }
     
     .resultado-cliente {
         border: 1px solid #e0e0e0;
         border-radius: 8px;
         padding: 15px;
         margin-bottom: 12px;
         background: #f8f9fa;
         transition: all 0.2s ease;
         cursor: pointer;
         min-height: 80px;
     }
     
     .resultado-cliente:hover {
         background: #e9ecef;
         border-color: #007bff;
         transform: translateY(-2px);
         box-shadow: 0 4px 8px rgba(0,0,0,0.1);
     }
     
     .resultado-cliente h5 {
         margin: 0 0 8px 0;
         color: #333;
         font-size: 16px;
     }
     
     .resultado-cliente .cliente-info {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 10px;
         font-size: 14px;
         color: #666;
     }
     
     .resultado-cliente .cliente-info span {
         display: flex;
         align-items: center;
         gap: 5px;
     }
     
     .resultado-cliente .cliente-acciones {
         margin-top: 10px;
         display: flex;
         gap: 10px;
     }
     
     .btn-seleccionar-cliente {
         background: #28a745;
         color: white;
         border: none;
         padding: 8px 16px;
         border-radius: 4px;
         cursor: pointer;
         font-size: 14px;
         transition: background 0.2s ease;
     }
     
     .btn-seleccionar-cliente:hover {
         background: #218838;
     }
     
     .sin-resultados {
         text-align: center;
         padding: 20px;
         color: #666;
         font-style: italic;
     }
     
     .info-resultados {
         background: #e3f2fd;
         border: 1px solid #bbdefb;
         border-radius: 6px;
         padding: 10px 15px;
         margin-bottom: 15px;
         color: #1976d2;
         font-size: 14px;
         display: flex;
         align-items: center;
         gap: 8px;
     }
     
     .resultado-header {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 10px;
     }
     
     .resultado-numero {
         background: #007bff;
         color: white;
         padding: 4px 8px;
         border-radius: 12px;
         font-size: 12px;
         font-weight: bold;
     }
     
     /* Estilos específicos para el modal de búsqueda */
     .modal-busqueda {
         overflow: hidden !important;
     }
     
     .modal-body-scrollable {
         flex: 1;
         overflow-y: auto !important;
         padding: 20px;
         max-height: calc(85vh - 120px);
         min-height: 200px;
     }
     
     .modal-body-scrollable::-webkit-scrollbar {
         width: 10px;
     }
     
     .modal-body-scrollable::-webkit-scrollbar-track {
         background: #f1f1f1;
         border-radius: 5px;
     }
     
     .modal-body-scrollable::-webkit-scrollbar-thumb {
         background: #888;
         border-radius: 5px;
     }
     
     .modal-body-scrollable::-webkit-scrollbar-thumb:hover {
         background: #555;
     }
     
     /* Estilos para loading de cliente */
     .loading-cliente {
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: center;
         padding: 40px;
         text-align: center;
     }
     
     .loading-cliente .spinner-border {
         width: 3rem;
         height: 3rem;
         border-width: 0.3em;
     }
     
     .loading-cliente p {
         margin-top: 15px;
         color: #666;
         font-size: 16px;
     }
     
     /* Estilos para la gestión de productos */
     .products-management-container {
         margin-top: 20px;
     }
     
     .client-info-header {
         border-left: 4px solid #28a745;
     }
     
     .productos-lista {
         max-height: 400px;
         overflow-y: auto;
     }
     
     .producto-item {
         background: white;
         border: 1px solid #e2e8f0;
         border-radius: 8px;
         padding: 15px;
         margin-bottom: 10px;
         cursor: pointer;
         transition: all 0.3s ease;
     }
     
     .producto-item:hover {
         border-color: #3498db;
         box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
     }
     
     .producto-item.selected {
         border-color: #3498db;
         background: #e3f2fd;
     }
     
     .producto-header {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 8px;
     }
     
     .producto-nombre {
         font-weight: 600;
         color: #2c3e50;
         margin: 0;
     }
     
     .producto-estado {
         padding: 4px 8px;
         border-radius: 12px;
         font-size: 12px;
         font-weight: 500;
     }
     
     .estado-activa {
         background: #d1ecf1;
         color: #0c5460;
     }
     
     .estado-pagada {
         background: #d4edda;
         color: #155724;
     }
     
     .estado-cancelada {
         background: #f8d7da;
         color: #721c24;
     }
     
     .estado-refinanciada {
         background: #fff3cd;
         color: #856404;
     }
     
     .producto-monto {
         color: #6c757d;
         font-size: 14px;
         margin: 0;
     }
     
     .producto-fecha {
         color: #adb5bd;
         font-size: 12px;
         margin: 5px 0 0 0;
     }
     
     .canales-checkboxes {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 10px;
         margin-top: 10px;
     }
     
     .checkbox-label {
         display: flex;
         align-items: center;
         cursor: pointer;
         padding: 8px;
         border: 1px solid #e2e8f0;
         border-radius: 6px;
         transition: all 0.3s ease;
     }
     
     .checkbox-label:hover {
         background: #f8f9fa;
     }
     
     .checkbox-label input[type="checkbox"] {
         margin-right: 8px;
     }
     
     .form-actions {
         display: flex;
         gap: 10px;
         margin-top: 20px;
     }
     
     .product-actions {
         display: flex;
         gap: 10px;
         flex-wrap: wrap;
     }
     
     .selected-product-info {
         border-left: 4px solid #3498db;
     }

     @media (max-width: 768px) {
         .canales-checkboxes {
             grid-template-columns: 1fr;
         }
         
         .form-actions {
             flex-direction: column;
         }
         
         .product-actions {
             flex-direction: column;
         }
     }
     
    </style>
</head>
<body>
    <?php 
    require_once 'shared_navbar.php';
    echo getNavbar('Gestión de Cliente', $_SESSION['user_role'] ?? '');
    ?>
    
    <div class="gestion-container">

        <!-- Layout principal con Bootstrap - 3 Columnas -->
        <div class="row">
            <!-- Información del Cliente (3 columnas) - IZQUIERDA -->
            <div class="col-lg-3 col-md-12">
                <!-- Buscador de Clientes -->
                <div class="cliente-info-card" style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 15px; color: #1f2937; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-search"></i> Buscar Cliente
                    </h4>
                    <div class="buscador-cliente-container">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <input type="text" 
                                   id="buscadorClienteInput" 
                                   class="form-input" 
                                   placeholder="Buscar por nombre, cédula o teléfono..."
                                   autocomplete="off"
                                   style="width: 100%; padding: 10px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem;">
                        </div>
                        <div id="resultadosBuscador" class="resultados-buscador" style="display: none; max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: white; margin-top: 5px;">
                            <!-- Los resultados se mostrarán aquí -->
                        </div>
                    </div>
                </div>
                
                <?php if (isset($cliente) && $cliente): ?>
                <!-- Información Básica del Cliente -->
                <div class="cliente-info-card" style="margin-bottom: 20px;">
                    <div class="cliente-header">
                        <div class="cliente-avatar">
                            <?php echo strtoupper(substr($cliente['nombre'] ?? 'C', 0, 1)); ?>
                        </div>
                        <div class="cliente-details">
                            <h2 id="clienteNombre"><?php echo htmlspecialchars($cliente['nombre'] ?? ''); ?></h2>
                            <div class="cliente-meta">
                                <div class="cliente-cedula">
                                    <strong>Cédula:</strong> <span id="clienteCedula"><?php echo htmlspecialchars($cliente['cedula'] ?? ''); ?></span>
                                </div>
                                <div class="cliente-email" id="cliente-email-display">
                                    <strong>Email:</strong> 
                                    <span id="email-value" class="<?php echo !empty($cliente['email']) ? 'email-registrado' : 'email-no-registrado'; ?>">
                                        <?php 
                                        if (!empty($cliente['email'])) {
                                            echo htmlspecialchars($cliente['email']);
                                        } else {
                                            echo 'No registrado';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="cliente-ciudad" id="cliente-ciudad-display">
                                    <strong>Ciudad:</strong> <span id="ciudad-value"><?php echo htmlspecialchars($cliente['ciudad'] ?? 'No registrada'); ?></span>
                                </div>
                                <div class="cliente-direccion" id="cliente-direccion-display">
                                    <strong>Dirección:</strong> <span id="direccion-value"><?php echo htmlspecialchars($cliente['direccion'] ?? 'No registrada'); ?></span>
                                </div>
                                <div class="cliente-telefonos" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <strong>Teléfono:</strong>
                                    <select class="telefono-dropdown" id="telefonoDropdown">
                                        <?php 
                                        // Recopilar todos los teléfonos únicos del cliente
                                        $telefonos = [];
                                        
                                        // Campo principal (telefono)
                                        if (!empty($cliente['telefono']) && trim($cliente['telefono']) !== '') {
                                            $telefono = trim($cliente['telefono']);
                                            if (!in_array($telefono, $telefonos)) {
                                                $telefonos[] = $telefono;
                                            }
                                        }
                                        
                                        // Campos adicionales (celular2, cel3, cel4, etc. hasta cel11)
                                        $columnasTelefono = ['celular2', 'cel3', 'cel4', 'cel5', 'cel6', 'cel7', 'cel8', 'cel9', 'cel10', 'cel11'];
                                        foreach ($columnasTelefono as $columna) {
                                            // Verificar si la columna existe y tiene valor
                                            if (isset($cliente[$columna]) && !empty(trim((string)$cliente[$columna]))) {
                                                $telefono = trim((string)$cliente[$columna]);
                                                if (!in_array($telefono, $telefonos)) {
                                                    $telefonos[] = $telefono;
                                                }
                                            }
                                        }
                                        
                                        // Agregar teléfonos de las facturas (si existen)
                                        if (!empty($facturas)) {
                                            foreach ($facturas as $factura) {
                                                if (!empty($factura['telefono2']) && !in_array($factura['telefono2'], $telefonos)) {
                                                    $telefonos[] = $factura['telefono2'];
                                                }
                                                if (!empty($factura['telefono3']) && !in_array($factura['telefono3'], $telefonos)) {
                                                    $telefonos[] = $factura['telefono3'];
                                                }
                                            }
                                        }
                                        
                                        // Mostrar opciones
                                        if (!empty($telefonos)) {
                                            foreach ($telefonos as $telefono) {
                                                echo '<option value="' . htmlspecialchars($telefono) . '">' . htmlspecialchars($telefono) . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">N/A</option>';
                                        }
                                        ?>
                                    </select>
                                    <span class="telefono-seleccionado" id="telefonoSeleccionado" title="Clic para llamar desde el softphone"><?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información de Facturas -->
                <div class="cliente-info-card">
                    <h3 class="tipificacion-title">
                        <i class="fas fa-file-invoice-dollar"></i> Facturas (<?php echo count($facturas ?? []); ?>) - Total: $<?php 
                        $totalFacturas = 0;
                        if (!empty($facturas)) {
                            foreach ($facturas as $factura) {
                                $totalFacturas += $factura['saldo'] ?? 0;
                            }
                        }
                        echo number_format($totalFacturas, 0, ',', '.');
                        ?>
                    </h3>
                    
                    <div class="info-cliente-csv" style="max-height: 400px; overflow-y: auto; overflow-x: hidden;">
                        <?php if (!empty($facturas) && count($facturas) > 0): ?>
                            <!-- Mostrar todas las facturas del cliente -->
                            <div class="facturas-container" id="facturasListaPanel" style="overflow: visible;">
                                <?php foreach ($facturas as $index => $factura): ?>
                                <div class="factura-item" style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; margin-bottom: 10px; background: #f8f9fa;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <h5 style="margin: 0; color: #2c3e50; font-size: 13px;">
                                            <i class="fas fa-file-invoice"></i> Factura #<?php echo $index + 1; ?>
                                        </h5>
                                        <span class="badge badge-<?php echo $factura['estado_factura'] === 'pendiente' ? 'success' : ($factura['estado_factura'] === 'pagada' ? 'info' : 'warning'); ?>" style="font-size: 10px; padding: 4px 8px; border-radius: 12px; background: <?php echo $factura['estado_factura'] === 'pendiente' ? '#d4edda' : ($factura['estado_factura'] === 'pagada' ? '#d1ecf1' : '#fff3cd'); ?>; color: <?php echo $factura['estado_factura'] === 'pendiente' ? '#155724' : ($factura['estado_factura'] === 'pagada' ? '#0c5460' : '#856404'); ?>;">
                                            <?php echo ucfirst($factura['estado_factura'] ?? 'pendiente'); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="datos-factura-lista" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 11px;">
                                        <!-- Información básica de la factura -->
                                        <div class="dato-item">
                                            <strong>Número:</strong><br>
                                            <?php echo htmlspecialchars($factura['numero_factura']); ?>
                                        </div>
                                        
                                        <div class="dato-item">
                                            <strong>Saldo:</strong><br>
                                            $<?php 
                                            $saldo = $factura['saldo'] ?? 0;
                                            echo number_format($saldo, 0, ',', '.'); 
                                            ?>
                                        </div>
                                        
                                        
                                        <!-- Información de mora -->
                                        <?php if (!empty($factura['dias_mora'])): ?>
                                        <div class="dato-item mora-<?php echo $factura['dias_mora'] > 30 ? 'alta' : ($factura['dias_mora'] > 15 ? 'media' : 'baja'); ?>">
                                            <strong>Días en Mora:</strong><br>
                                            <?php echo $factura['dias_mora']; ?> días
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Información del contrato -->
                                        <?php if (!empty($factura['numero_contrato'])): ?>
                                        <div class="dato-item">
                                            <strong>Número Contrato:</strong><br>
                                            <?php echo htmlspecialchars($factura['numero_contrato']); ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Información del RMT -->
                                        <?php if (!empty($factura['rmt'])): ?>
                                        <div class="dato-item">
                                            <strong>RMT:</strong><br>
                                            <?php echo htmlspecialchars($factura['rmt']); ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Información de la Franja -->
                                        <?php if (!empty($factura['franja'])): ?>
                                        <div class="dato-item franja-<?php echo strtolower(str_replace(' ', '-', $factura['franja'])); ?>">
                                            <strong>Franja:</strong><br>
                                            <span class="franja-badge" style="
                                                display: inline-block;
                                                padding: 2px 6px;
                                                border-radius: 4px;
                                                font-size: 10px;
                                                font-weight: 600;
                                                text-transform: uppercase;
                                                background: <?php 
                                                    $franja = strtoupper($factura['franja']);
                                                    if ($franja === 'BLOQUEADO') echo '#dc3545';
                                                    elseif ($franja === 'ESPERA') echo '#ffc107';
                                                    elseif ($franja === 'ACTIVO') echo '#28a745';
                                                    elseif ($franja === 'SUSPENDIDO') echo '#6c757d';
                                                    else echo '#17a2b8';
                                                ?>;
                                                color: white;
                                            ">
                                                <?php echo htmlspecialchars($factura['franja']); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Fechas -->
                                        <?php if (!empty($factura['fecha_creacion'])): ?>
                                        <div class="dato-item">
                                            <strong>Fecha Creación:</strong><br>
                                            <?php echo date('d/m/Y H:i', strtotime($factura['fecha_creacion'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <!-- Estadísticas de facturas -->
                                <?php if (!empty($estadisticasObligaciones)): ?>
                                <div class="estadisticas-facturas" style="margin-top: 15px; padding: 10px; background: #e8f4f8; border-radius: 6px; border-left: 4px solid #3498db;">
                                    <h6 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 12px;">
                                        <i class="fas fa-chart-bar"></i> Resumen de Facturas
                                    </h6>
                                    <div style="font-size: 11px; color: #555; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <div><strong>Total Facturas:</strong> <?php echo $estadisticasFacturas['total_facturas']; ?></div>
                                        <div><strong>Activas:</strong> <?php echo $estadisticasFacturas['facturas_activas'] ?? 0; ?></div>
                                        <div><strong>Pagadas:</strong> <?php echo $estadisticasFacturas['facturas_pagadas'] ?? 0; ?></div>
                                        <div><strong>Canceladas:</strong> <?php echo $estadisticasFacturas['facturas_canceladas'] ?? 0; ?></div>
                                        <?php if (!empty($estadisticasFacturas['saldo_total'])): ?>
                                        <div><strong>Saldo Total:</strong> $<?php echo number_format($estadisticasFacturas['saldo_total'], 0, ',', '.'); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($estadisticasFacturas['mora_promedio'])): ?>
                                        <div><strong>Mora Promedio:</strong> <?php echo round($estadisticasFacturas['mora_promedio']); ?> días</div>
                                        <?php endif; ?>
                                        <?php if (!empty($estadisticasFacturas['facturas_mora_alta'])): ?>
                                        <div><strong>Con Mora Alta:</strong> <?php echo $estadisticasFacturas['facturas_mora_alta']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Mensaje cuando no hay facturas -->
                            <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                                <i class="fas fa-file-invoice" style="font-size: 24px; margin-bottom: 10px;"></i>
                                <p style="margin: 0; font-size: 12px;">No se encontraron facturas para este cliente.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Botón para agregar más información -->
                    <div class="text-center" style="margin: 15px 0;">
                        <button type="button" class="btn btn-success btn-sm" onclick="mostrarModalAgregarInfo()">
                            <i class="fas fa-plus-circle"></i> Agregar más información
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sistema de Tipificaciones (6 columnas) - CENTRO -->
            <div class="col-lg-6 col-md-12">
                <div class="tipificacion-card">
                    <h3 class="tipificacion-title">
                        📞 Sistema de Tipificaciones de Llamadas
                    </h3>
                    
                    <form method="POST" id="tipificacionForm" action="index.php?action=guardar_tipificacion">
                        <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                        <input type="hidden" name="tipificacion" id="tipificacion_principal" value="">
                        <input type="hidden" name="sub_tipificacion" id="sub_tipificacion_hidden" value="">
                        
                        <!-- Árbol de Tipificaciones (una sola columna ahora) -->
                        <div class="columna-tipificaciones">
                            <h4>🎯 Tipificación de la Llamada</h4>
                                
                                <!-- Forma de contacto -->
                        <div class="form-group">
                            <label for="forma_contacto" class="form-label">Forma de Contacto:</label>
                            <select name="forma_contacto" id="forma_contacto" class="form-select" required>
                                <option value="">Selecciona una opción</option>
                                <option value="llamada">📞 Llamada</option>
                                <option value="whatsapp">📱 WhatsApp</option>
                                <option value="email">📧 Email/Correo Electrónico</option>
                            </select>
                        </div>
                        
                        <!-- Factura a gestionar -->
                        <div class="form-group">
                            <label for="factura_gestionar" class="form-label">Factura a Gestionar:</label>
                            <select name="factura_gestionar" id="factura_gestionar" class="form-select" required>
                                <option value="">Selecciona una factura</option>
                                <option value="ninguna">❌ Ninguna (Cliente no quiere pagar)</option>
                                <?php if (!empty($facturas) && count($facturas) > 0): ?>
                                    <?php 
                                    // Calcular total de facturas pendientes
                                    $facturasPendientes = array_filter($facturas, function($factura) {
                                        return ($factura['estado_factura'] ?? 'pendiente') === 'pendiente';
                                    });
                                    $totalFacturasPendientes = count($facturasPendientes);
                                    $totalSaldoPendiente = 0;
                                    foreach ($facturasPendientes as $factura) {
                                        $totalSaldoPendiente += $factura['saldo'] ?? 0;
                                    }
                                    ?>
                                    
                                    <?php if ($totalFacturasPendientes >= 2): ?>
                                        <option value="todas_las_facturas" 
                                                data-numero="TODAS LAS FACTURAS"
                                                data-saldo="<?php echo $totalSaldoPendiente; ?>"
                                                data-estado="todas"
                                                data-facturas-ids="<?php echo implode(',', array_column($facturasPendientes, 'id')); ?>">
                                            💰 Todas las facturas (<?php echo $totalFacturasPendientes; ?> facturas) - Total: $<?php echo number_format($totalSaldoPendiente, 0, ',', '.'); ?>
                                        </option>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($facturas as $index => $factura): ?>
                                        <option value="<?php echo $factura['id']; ?>" 
                                                data-numero="<?php echo htmlspecialchars($factura['numero_factura']); ?>"
                                                data-saldo="<?php echo $factura['saldo'] ?? 0; ?>"
                                                data-estado="<?php echo $factura['estado_factura'] ?? 'pendiente'; ?>">
                                            📄 Factura #<?php echo $index + 1; ?> - <?php echo htmlspecialchars($factura['numero_factura']); ?> 
                                            (Saldo: $<?php echo number_format($factura['saldo'] ?? 0, 0, ',', '.'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Primer dropdown: Tipo de contacto -->
                        <div class="form-group">
                            <label for="tipo_contacto" class="form-label">Tipo de Contacto:</label>
                            <script>
// #region debug d200d9 tipificaciones bootstrap (pre-onchange)
(function(){
  try{
    // Captura de errores JS que impiden definir funciones más abajo.
    window.addEventListener('error', function (ev) {
      fetch('http://127.0.0.1:7559/ingest/0bcc0192-fe61-4fb0-b109-b4792228bcf7',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'b7eaa7'},body:JSON.stringify({sessionId:'b7eaa7',runId:'pre',hypothesisId:'TIPBOOT1',location:'views/gestionar_cliente.php:pre-onchange:window.error',message:'error',data:{msg:String(ev.message||''),file:String(ev.filename||''),line:Number(ev.lineno||0),col:Number(ev.colno||0)},timestamp:Date.now()})}).catch(()=>{});
    }, { once: true });
    window.addEventListener('unhandledrejection', function (ev) {
      fetch('http://127.0.0.1:7559/ingest/0bcc0192-fe61-4fb0-b109-b4792228bcf7',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'b7eaa7'},body:JSON.stringify({sessionId:'b7eaa7',runId:'pre',hypothesisId:'TIPBOOT1',location:'views/gestionar_cliente.php:pre-onchange:unhandledrejection',message:'rejection',data:{reason:String((ev&&ev.reason&&ev.reason.message)?ev.reason.message:(ev&&ev.reason)||'')},timestamp:Date.now()})}).catch(()=>{});
    }, { once: true });

    // Stub global para evitar ReferenceError desde el onchange inline.
    if (typeof window.mostrarTipificacionesEspecificas !== 'function') {
      window.mostrarTipificacionesEspecificas = function(tipo){
        fetch('http://127.0.0.1:7559/ingest/0bcc0192-fe61-4fb0-b109-b4792228bcf7',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'b7eaa7'},body:JSON.stringify({sessionId:'b7eaa7',runId:'pre',hypothesisId:'TIPBOOT2',location:'views/gestionar_cliente.php:pre-onchange:stub',message:'called',data:{tipo:String(tipo||'')},timestamp:Date.now()})}).catch(()=>{});
        // Si el script real cargó después, delegar.
        if (typeof window.__realMostrarTipificacionesEspecificas === 'function') {
          try { return window.__realMostrarTipificacionesEspecificas(tipo); } catch(e){}
        }
      };
    }

    fetch('http://127.0.0.1:7559/ingest/0bcc0192-fe61-4fb0-b109-b4792228bcf7',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'b7eaa7'},body:JSON.stringify({sessionId:'b7eaa7',runId:'pre',hypothesisId:'TIPBOOT0',location:'views/gestionar_cliente.php:pre-onchange',message:'boot',data:{hasFn:typeof window.mostrarTipificacionesEspecificas==='function'},timestamp:Date.now()})}).catch(()=>{});
  }catch(e){}
})();
// #endregion
                            </script>
                            <select name="tipo_contacto" id="tipo_contacto" class="form-select" onchange="mostrarTipificacionesEspecificas(this.value)" required>
                                <option value="">Selecciona una opción</option>
                                <option value="contacto_exitoso">CONTACTO EXITOSO</option>
                                <option value="contacto_tercero">CONTACTO CON TERCERO</option>
                                <option value="sin_contacto">SIN CONTACTO</option>
                            </select>
                        </div>
                        
                        <!-- Opciones para CONTACTO EXITOSO -->
                        <div id="opciones_contacto_exitoso" class="form-group" style="display: none;">
                            <label for="opcion_contacto_exitoso" class="form-label">Resultado del Contacto:</label>
                            <select name="opcion_contacto_exitoso" id="opcion_contacto_exitoso" class="form-select" onchange="seleccionarOpcionContactoExitoso(this.value)" required>
                                <option value="">Selecciona el resultado</option>
                                <option value="acuerdo_pago">ACUERDO DE PAGO</option>
                                <option value="ya_pago">YA PAGO</option>
                                <option value="localizado_sin_acuerdo">LOCALIZADO SIN ACUERDO</option>
                                <option value="reclamo">RECLAMO</option>
                                <option value="volver_llamar">VOLVER A LLAMAR</option>
                                <option value="recordar_pago">RECORDAR PAGO</option>
                                <option value="venta_novedad">VENTA CON NOVEDAD</option>
                            </select>
                            
                            <!-- Sub-opciones para ACUERDO DE PAGO -->
                            <div id="sub_opciones_acuerdo_pago" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_acuerdo_pago" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_acuerdo_pago" id="sub_opcion_acuerdo_pago" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                    <option value="desempleo">DESEMPLEO</option>
                                    <option value="incremento_tarifa">INCREMENTO DE TARIFA</option>
                                    <option value="otras_prioridades_economicas">TIENE OTRAS PRIORIDADES ECONOMICAS</option>
                                    <option value="disminucion_ingresos">DISMINUCION DE INGRESOS</option>
                                    <option value="adquirio_otro_servicio_salud">ADQUIRIO OTRO SERVICIO DE SALUD</option>
                                    <option value="no_utiliza_beneficios">NO UTILIZA/NO BENEFICIOS DEL SERVICIO</option>
                                    <option value="sale_del_pais">SALE DEL PAIS</option>
                                    <option value="fallecido">FALLECIDO</option>
                                    <option value="humanizacion_servicio">HUMANIZACION DEL SERVICIO GENERAL</option>
                                    <option value="oportunidad_nunca_llegaron">OPORTUNIDAD/NUNCA LLEGARON</option>
                                    <option value="metodo_pago_errado">METODO DE PAGO ERRADO/DEBITO AUTOMATICO</option>
                                    <option value="no_realizan_debito_automatico">NO REALIZAN DEBITO AUTOMATICO</option>
                                    <option value="falsa_promesa_comercial">FALSA PROMESA COMERCIAL</option>
                                    <option value="fraude">FRAUDE</option>
                                    <option value="factura_no_corresponde">FACTURA NO CORRESPONDE</option>
                                    <option value="no_entrega_aviso_pago">NO ENTREGA DE AVISO DE PAGO/FACTURA</option>
                                    <option value="facturacion_errada">FACTURACION ERRADA</option>
                                    <option value="cambio_traslado_sin_cobertura">CAMBIO/TRASLADO SIN COBERTURA</option>
                                    <option value="cancelacion_no_aplicada">CANCELACION NO APLICADA</option>
                                    <option value="incumplimiento_ofercimientos">INCUMPLIMIENTO OFRECIMIENTOS REALIZADOS (LEALTAD)</option>
                                    <option value="inconformidad_pqr">INCONFORMIDAD PQR</option>
                                    <option value="informacion_errada">INFORMACION ERRADA</option>
                                    <option value="no_contestaron_sac">NO CONTESTARON EN LA LINEA DE SAC</option>
                                    <option value="reclamo_pendiente_respuesta">RECLAMO PENDIENTE DE RESPUESTA</option>
                                    <option value="pago_afiliacion_no_aplicado">PAGO DE AFILIACION NO APLICADO</option>
                                    <option value="pago_sin_aplicar">PAGO SIN APLICAR</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para YA PAGO -->
                            <div id="sub_opciones_ya_pago" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_ya_pago" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_ya_pago" id="sub_opcion_ya_pago" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="ya_pago">YA PAGO</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para LOCALIZADO SIN ACUERDO -->
                            <div id="sub_opciones_localizado_sin_acuerdo" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_localizado_sin_acuerdo" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_localizado_sin_acuerdo" id="sub_opcion_localizado_sin_acuerdo" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                    <option value="desempleo">DESEMPLEO</option>
                                    <option value="incremento_tarifa">INCREMENTO DE TARIFA</option>
                                    <option value="otras_prioridades_economicas">TIENE OTRAS PRIORIDADES ECONOMICAS</option>
                                    <option value="disminucion_ingresos">DISMINUCION DE INGRESOS</option>
                                    <option value="adquirio_otro_servicio_salud">ADQUIRIO OTRO SERVICIO DE SALUD</option>
                                    <option value="no_utiliza_beneficios">NO UTILIZA/NO BENEFICIOS DEL SERVICIO</option>
                                    <option value="sale_del_pais">SALE DEL PAIS</option>
                                    <option value="fallecido">FALLECIDO</option>
                                    <option value="humanizacion_servicio">HUMANIZACION DEL SERVICIO GENERAL</option>
                                    <option value="oportunidad_nunca_llegaron">OPORTUNIDAD/NUNCA LLEGARON</option>
                                    <option value="metodo_pago_errado">METODO DE PAGO ERRADO/DEBITO AUTOMATICO</option>
                                    <option value="no_realizan_debito_automatico">NO REALIZAN DEBITO AUTOMATICO</option>
                                    <option value="falsa_promesa_comercial">FALSA PROMESA COMERCIAL</option>
                                    <option value="fraude">FRAUDE</option>
                                    <option value="factura_no_corresponde">FACTURA NO CORRESPONDE</option>
                                    <option value="no_entrega_aviso_pago">NO ENTREGA DE AVISO DE PAGO/FACTURA</option>
                                    <option value="facturacion_errada">FACTURACION ERRADA</option>
                                    <option value="cambio_traslado_sin_cobertura">CAMBIO/TRASLADO SIN COBERTURA</option>
                                    <option value="cancelacion_no_aplicada">CANCELACION NO APLICADA</option>
                                    <option value="incumplimiento_ofercimientos">INCUMPLIMIENTO OFRECIMIENTOS REALIZADOS (LEALTAD)</option>
                                    <option value="inconformidad_pqr">INCONFORMIDAD PQR</option>
                                    <option value="informacion_errada">INFORMACION ERRADA</option>
                                    <option value="no_contestaron_sac">NO CONTESTARON EN LA LINEA DE SAC</option>
                                    <option value="reclamo_pendiente_respuesta">RECLAMO PENDIENTE DE RESPUESTA</option>
                                    <option value="pago_afiliacion_no_aplicado">PAGO DE AFILIACION NO APLICADO</option>
                                    <option value="pago_sin_aplicar">PAGO SIN APLICAR</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para RECLAMO -->
                            <div id="sub_opciones_reclamo" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_reclamo" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_reclamo" id="sub_opcion_reclamo" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="desempleo">DESEMPLEO</option>
                                    <option value="incremento_tarifa">INCREMENTO DE TARIFA</option>
                                    <option value="otras_prioridades_economicas">TIENE OTRAS PRIORIDADES ECONOMICAS</option>
                                    <option value="disminucion_ingresos">DISMINUCION DE INGRESOS</option>
                                    <option value="adquirio_otro_servicio_salud">ADQUIRIO OTRO SERVICIO DE SALUD</option>
                                    <option value="no_utiliza_beneficios">NO UTILIZA/NO BENEFICIOS DEL SERVICIO</option>
                                    <option value="sale_del_pais">SALE DEL PAIS</option>
                                    <option value="fallecido">FALLECIDO</option>
                                    <option value="humanizacion_servicio">HUMANIZACION DEL SERVICIO GENERAL</option>
                                    <option value="oportunidad_nunca_llegaron">OPORTUNIDAD/NUNCA LLEGARON</option>
                                    <option value="metodo_pago_errado">METODO DE PAGO ERRADO/DEBITO AUTOMATICO</option>
                                    <option value="no_realizan_debito_automatico">NO REALIZAN DEBITO AUTOMATICO</option>
                                    <option value="falsa_promesa_comercial">FALSA PROMESA COMERCIAL</option>
                                    <option value="fraude">FRAUDE</option>
                                    <option value="factura_no_corresponde">FACTURA NO CORRESPONDE</option>
                                    <option value="no_entrega_aviso_pago">NO ENTREGA DE AVISO DE PAGO/FACTURA</option>
                                    <option value="facturacion_errada">FACTURACION ERRADA</option>
                                    <option value="cambio_traslado_sin_cobertura">CAMBIO/TRASLADO SIN COBERTURA</option>
                                    <option value="cancelacion_no_aplicada">CANCELACION NO APLICADA</option>
                                    <option value="incumplimiento_ofercimientos">INCUMPLIMIENTO OFRECIMIENTOS REALIZADOS (LEALTAD)</option>
                                    <option value="inconformidad_pqr">INCONFORMIDAD PQR</option>
                                    <option value="informacion_errada">INFORMACION ERRADA</option>
                                    <option value="no_contestaron_sac">NO CONTESTARON EN LA LINEA DE SAC</option>
                                    <option value="reclamo_pendiente_respuesta">RECLAMO PENDIENTE DE RESPUESTA</option>
                                    <option value="pago_afiliacion_no_aplicado">PAGO DE AFILIACION NO APLICADO</option>
                                    <option value="pago_sin_aplicar">PAGO SIN APLICAR</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para VOLVER A LLAMAR -->
                            <div id="sub_opciones_volver_llamar" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_volver_llamar" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_volver_llamar" id="sub_opcion_volver_llamar" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="volver_llamar">VOLVER A LLAMAR</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para RECORDAR PAGO -->
                            <div id="sub_opciones_recordar_pago" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_recordar_pago" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_recordar_pago" id="sub_opcion_recordar_pago" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="recordar_pago">RECORDAR PAGO</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para VENTA CON NOVEDAD -->
                            <div id="sub_opciones_venta_novedad" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_venta_novedad" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_venta_novedad" id="sub_opcion_venta_novedad" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="venta_novedad">VENTA CON NOVEDAD</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Opciones para CONTACTO CON TERCERO -->
                        <div id="opciones_contacto_tercero" class="form-group" style="display: none;">
                            <label for="opcion_contacto_tercero" class="form-label">Resultado del Contacto con Tercero:</label>
                            <select name="opcion_contacto_tercero" id="opcion_contacto_tercero" class="form-select" onchange="seleccionarOpcionContactoTercero(this.value)" required>
                                <option value="">Selecciona el resultado</option>
                                <option value="aqui_no_vive">AQUÍ NO VIVE NO TRABAJA</option>
                                <option value="mensaje_tercero">MENSAJE CON TERCERO</option>
                                <option value="fallecido_otro">FALLECIDO/OTRO</option>
                            </select>
                            
                            <!-- Sub-opciones para AQUÍ NO VIVE NO TRABAJA -->
                            <div id="sub_opciones_aqui_no_vive" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_aqui_no_vive" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_aqui_no_vive" id="sub_opcion_aqui_no_vive" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para MENSAJE CON TERCERO -->
                            <div id="sub_opciones_mensaje_tercero" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_mensaje_tercero" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_mensaje_tercero" id="sub_opcion_mensaje_tercero" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para FALLECIDO/OTRO -->
                            <div id="sub_opciones_fallecido_otro" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_fallecido_otro" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_fallecido_otro" id="sub_opcion_fallecido_otro" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Opciones para SIN CONTACTO -->
                        <div id="opciones_sin_contacto" class="form-group" style="display: none;">
                            <label for="opcion_sin_contacto" class="form-label">Motivo de No Contacto:</label>
                            <select name="opcion_sin_contacto" id="opcion_sin_contacto" class="form-select" onchange="seleccionarOpcionSinContacto(this.value)" required>
                                <option value="">Selecciona el motivo</option>
                                <option value="no_contesta">NO CONTESTA</option>
                                <option value="buzon_mensajes">BUZÓN DE MENSAJES</option>
                                <option value="telefono_danado">TELÉFONO DAÑADO</option>
                                <option value="fallecido_otro">FALLECIDO/OTRO</option>
                                <option value="localizacion">LOCALIZACIÓN</option>
                                <option value="envio_estado_cuenta">ENVÍO ESTADO DE CUENTA</option>
                                <option value="venta_novedad_analisis">VENTA CON NOVEDAD ANÁLISIS DATA</option>
                            </select>
                            
                            <!-- Sub-opciones para NO CONTESTA -->
                            <div id="sub_opciones_no_contesta" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_no_contesta" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_no_contesta" id="sub_opcion_no_contesta" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                    <option value="contesta_cuelga">CONTESTA-CUELGA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para BUZÓN DE MENSAJES -->
                            <div id="sub_opciones_buzon_mensajes" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_buzon_mensajes" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_buzon_mensajes" id="sub_opcion_buzon_mensajes" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para TELÉFONO DAÑADO -->
                            <div id="sub_opciones_telefono_danado" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_telefono_danado" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_telefono_danado" id="sub_opcion_telefono_danado" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para FALLECIDO/OTRO -->
                            <div id="sub_opciones_fallecido_otro_sin_contacto" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_fallecido_otro_sin_contacto" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_fallecido_otro_sin_contacto" id="sub_opcion_fallecido_otro_sin_contacto" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para LOCALIZACIÓN -->
                            <div id="sub_opciones_localizacion" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_localizacion" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_localizacion" id="sub_opcion_localizacion" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para ENVÍO ESTADO DE CUENTA -->
                            <div id="sub_opciones_envio_estado_cuenta" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_envio_estado_cuenta" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_envio_estado_cuenta" id="sub_opcion_envio_estado_cuenta" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                            
                            <!-- Sub-opciones para VENTA CON NOVEDAD ANÁLISIS DATA -->
                            <div id="sub_opciones_venta_novedad_analisis" class="form-group" style="display: none; margin-top: 15px;">
                                <label for="sub_opcion_venta_novedad_analisis" class="form-label">Razón específica:</label>
                                <select name="sub_opcion_venta_novedad_analisis" id="sub_opcion_venta_novedad_analisis" class="form-select" onchange="seleccionarSubOpcion(this.value)">
                                    <option value="">Selecciona la razón</option>
                                    <option value="no_informa">NO INFORMA</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Campos adicionales para opciones específicas -->
                        <div id="campos_adicionales" class="form-group" style="display: none;">
                            <!-- Campos que aparecerán según la opción seleccionada -->
                        </div>
                        
                        <!-- Acciones Específicas -->
                        <div class="acciones-especificas" id="accionesEspecificas">
                            <h4 id="accionTitulo">Acción Específica</h4>
                            <div id="accionContenido">
                                <!-- El contenido se carga dinámicamente -->
                            </div>
                        </div>
                        </div>
                        
                        <!-- Observaciones y Comentarios - Al final de la segunda columna -->
                        <div class="columna-observaciones" style="margin-top: 30px;">
                            <h4>📝 Observaciones y Comentarios</h4>
                            <p><em>Documente las interacciones y seguimientos pertinentes</em></p>
                            
                            <!-- Campo de observaciones -->
                            <div class="form-group">
                                <label for="comentarios" class="form-label">Observaciones Detalladas:</label>
                                <textarea name="comentarios" id="comentarios" class="form-textarea" 
                                          placeholder="Describe detalladamente el resultado de la gestión, acuerdos, próximos pasos, objeciones del cliente, etc." 
                                          required></textarea>
                            </div>
                        </div>
                    
                    <!-- Botones de acción dinámicos -->
                    <div class="btn-container">
                        <!-- Botón principal que cambia según el estado -->
                        <button type="submit" id="btnGuardarPrincipal" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Gestión
                        </button>
                        
                            <!-- Botones de navegación (se muestran después de completar todas las facturas) -->
                        <div id="btnNavegacion" style="display: none;">
                            <button type="button" id="btnSiguienteCliente" class="btn btn-warning" onclick="irAlSiguienteCliente()">
                                <i class="fas fa-arrow-right"></i> Siguiente Cliente
                            </button>
                        </div>
                        
                        <!-- Botones de navegación estándar -->
                        <a href="index.php?action=mis_tareas" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Tareas
                        </a>
                        <a href="index.php?action=dashboard" class="btn btn-success">
                            <i class="fas fa-home"></i> Ir al Dashboard
                        </a>
                    </div>
                    </form>
                </div>
            </div>
            
            <!-- Softphone y Canales de Comunicación (3 columnas) - DERECHA -->
            <div class="col-lg-3 col-md-12">
                <?php if ($tieneTelefono && !empty($datosTelefono['extension_telefono'])): ?>
                <!-- Softphone WebRTC -->
                <div class="cliente-info-card" style="margin-bottom: 20px;">
                    <div class="columna-softphone">
                        <div class="softphone-container">
                            <div id="webrtc-softphone"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Canales de Comunicación Autorizados -->
                <div class="cliente-info-card">
                    <div class="canales-autorizados-section">
                        <h5 class="canales-title" style="margin-bottom: 15px;">
                            <i class="fas fa-broadcast-tower"></i> Canales de Comunicación Autorizados
                        </h5>
                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 15px;"><em>Seleccione los canales autorizados por la empresa para futuras comunicaciones</em></p>
                        
                        <div class="canales-checkboxes">
                            <div class="checkbox-group" style="display: flex; flex-direction: column; gap: 10px;">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="canales_autorizados[]" value="llamada" class="canal-checkbox">
                                    <span class="checkbox-text">📞 Llamada Telefónica</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="canales_autorizados[]" value="whatsapp" class="canal-checkbox">
                                    <span class="checkbox-text">📱 WhatsApp</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="canales_autorizados[]" value="correo_electronico" class="canal-checkbox">
                                    <span class="checkbox-text">📧 Correo Electrónico</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="canales_autorizados[]" value="sms" class="canal-checkbox">
                                    <span class="checkbox-text">💬 SMS</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="canales_autorizados[]" value="correo_fisico" class="canal-checkbox">
                                    <span class="checkbox-text">📮 Correo Físico</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="canales_autorizados[]" value="mensajeria_aplicaciones" class="canal-checkbox">
                                    <span class="checkbox-text">📱 Mensajería por Aplicaciones</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historial de Gestiones -->
         <?php if (isset($historial) && !empty($historial)): ?>
         <div class="historial-section">
             <h4 class="historial-title">
                 <i class="fas fa-history"></i> 
                 Historial de Interacciones (<?php echo count($historial); ?> registros)
             </h4>
             <div id="historialLlamadasLista">
             <?php foreach ($historial as $gestion): ?>
             <div class="historial-item">
                 <div class="historial-header">
                     <div class="historial-fecha">
                         <i class="fas fa-calendar-alt"></i>
                        <?php echo date('d/m/Y H:i', strtotime($gestion['fecha_gestion'])); ?>
                        <?php if (!empty($gestion['nombre_base'])): ?>
                            <span style="margin-left: 10px; color: #6b7280;">
                                <i class="fas fa-database"></i>
                                <?php echo htmlspecialchars($gestion['nombre_base']); ?>
                            </span>
                        <?php endif; ?>
                     </div>
                 </div>
                 
                 <div class="historial-content">
                     <div class="historial-grid">
                         <!-- Canal de Contacto -->
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-phone"></i> Canal de Contacto
                             </div>
                             <div class="historial-field-value canal-contacto">
                                 <?php 
                                 $canalContacto = $gestion['forma_contacto'] ?? 'llamada';
                                 $canalMap = [
                                     'llamada' => '📞 Llamada Telefónica',
                                     'whatsapp' => '📱 WhatsApp',
                                     'email' => '📧 Correo Electrónico',
                                     'correo_electronico' => '📧 Correo Electrónico',
                                     'chat' => '💬 Chat en Línea'
                                 ];
                                 echo $canalMap[$canalContacto] ?? ucfirst($canalContacto);
                                 ?>
                             </div>
                         </div>
                         
                         <!-- Número de contacto usado en la gestión -->
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-phone-square-alt"></i> Número de Contacto
                             </div>
                             <div class="historial-field-value">
                                 <?php
                                 $telefonoHist = trim((string)($gestion['telefono_contacto'] ?? ''));
                                 if ($telefonoHist === '' && !empty($cliente['telefono'])) {
                                     $telefonoHist = trim((string)$cliente['telefono']);
                                 }
                                 echo $telefonoHist !== '' 
                                     ? htmlspecialchars($telefonoHist) 
                                     : '<span style="color:#6c757d;font-style:italic;">No registrado</span>';
                                 ?>
                             </div>
                         </div>
                         
                         <!-- Tipo de contacto (1.er nivel del árbol de tipificación) -->
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-sitemap"></i> Tipo de Contacto
                             </div>
                             <div class="historial-field-value tipificacion">
                                <?php
                                $codTipoContacto = $gestion['tipo_contacto_arbol_codigo'] ?? ($gestion['tipo_contacto'] ?? ($gestion['tipo_gestion'] ?? null));
                                $tipoContactoMap = [
                                    'contacto_exitoso' => '✅ CONTACTO EXITOSO',
                                    'contacto_tercero' => '👥 CONTACTO CON TERCERO',
                                    'sin_contacto' => '❌ SIN CONTACTO',
                                ];
                                if ($codTipoContacto !== null && $codTipoContacto !== '') {
                                    echo htmlspecialchars($tipoContactoMap[$codTipoContacto] ?? ucfirst(str_replace('_', ' ', $codTipoContacto)));
                                } else {
                                    echo '<span style="color:#6c757d;font-style:italic;">No especificado (gestión anterior)</span>';
                                }
                                ?>
                             </div>
                         </div>
                         
                         <!-- Resultado del contacto (2.º nivel del árbol) -->
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-tags"></i> Resultado del Contacto
                             </div>
                            <div class="historial-field-value tipificacion">
                                <?php 
                                $tipificacionGeneral = $gestion['resultado_contacto_codigo'] ?? ($gestion['resultado_contacto'] ?? '');
                                if ($tipificacionGeneral === '' && !empty($gestion['tipo_gestion']) && strpos((string) $gestion['tipo_gestion'], '|') === false) {
                                    $tipificacionGeneral = (string) $gestion['tipo_gestion'];
                                }
                                if ($tipificacionGeneral === '') {
                                    $tipificacionGeneral = 'No especificada';
                                }
                                
                                // Mapear los valores para mostrar texto más legible
                                $tipificacionGeneralMap = [
                                    'contacto_exitoso' => '✅ CONTACTO EXITOSO',
                                    'contacto_tercero' => '👥 CONTACTO CON TERCERO',
                                    'sin_contacto' => '❌ SIN CONTACTO',
                                    'Llamada de Venta' => '📞 LLAMADA DE VENTA',
                                    'Llamada de Gestión' => '📞 LLAMADA DE GESTIÓN',
                                    'Cliente Interesado' => '💡 CLIENTE INTERESADO',
                                    'Venta Ingresada' => '💰 VENTA INGRESADA',
                                    // Valores del sistema de tipificación de 3 niveles (con guiones bajos)
                                    'acuerdo_pago' => '💰 ACUERDO DE PAGO',
                                    'ya_pago' => '✅ YA PAGO',
                                    'localizado_sin_acuerdo' => '📍 LOCALIZADO SIN ACUERDO',
                                    'reclamo' => '📋 RECLAMO',
                                    'volver_llamar' => '📞 VOLVER A LLAMAR',
                                    'recordar_pago' => '⏰ RECORDAR PAGO',
                                    'venta_novedad' => '🆕 VENTA CON NOVEDAD',
                                    'aqui_no_vive' => '🏠 AQUÍ NO VIVE NO TRABAJA',
                                    'mensaje_tercero' => '📝 MENSAJE CON TERCERO',
                                    'fallecido_otro' => '💀 FALLECIDO/OTRO',
                                    'no_contesta' => '📞 NO CONTESTA',
                                    'buzon_mensajes' => '📪 BUZÓN DE MENSAJES',
                                    'telefono_danado' => '📵 TELÉFONO DAÑADO',
                                    'localizacion' => '📍 LOCALIZACIÓN',
                                    'envio_estado_cuenta' => '📧 ENVÍO ESTADO DE CUENTA',
                                    'venta_novedad_analisis' => '🆕 VENTA CON NOVEDAD ANÁLISIS DATA',
                                    // Valores del sistema de tipificación de 3 niveles (sin guiones bajos)
                                    'ACUERDO DE PAGO' => '💰 ACUERDO DE PAGO',
                                    'YA PAGO' => '✅ YA PAGO',
                                    'LOCALIZADO SIN ACUERDO' => '📍 LOCALIZADO SIN ACUERDO',
                                    'RECLAMO' => '📋 RECLAMO',
                                    'VOLVER A LLAMAR' => '📞 VOLVER A LLAMAR',
                                    'RECORDAR PAGO' => '⏰ RECORDAR PAGO',
                                    'VENTA CON NOVEDAD' => '🆕 VENTA CON NOVEDAD'
                                ];
                                
                                $tipificacionGeneralTexto = $tipificacionGeneralMap[$tipificacionGeneral] ?? ucfirst(str_replace('_', ' ', $tipificacionGeneral));
                                echo htmlspecialchars($tipificacionGeneralTexto);
                                ?>
                            </div>
                         </div>
                         
                         <!-- Razón específica (3.er nivel — opción de “Razón específica” en el formulario) -->
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-list-alt"></i> Razón Específica
                             </div>
                            <div class="historial-field-value razon-especifica">
                                <?php 
                                $razonEspecifica = $gestion['razon_especifica'] ?? ($gestion['razon_especifica_codigo'] ?? ($gestion['resultado'] ?? ''));
                                if ($razonEspecifica === '') {
                                    $razonEspecifica = 'No especificada';
                                }
                                
                                // Mapear los valores para mostrar texto más legible
                                $razonEspecificaMap = [
                                    // Opciones de ACUERDO DE PAGO
                                    'acuerdo_pago' => '💰 ACUERDO DE PAGO',
                                    'ya_pago' => '✅ YA PAGO',
                                    'localizado_sin_acuerdo' => '📍 LOCALIZADO SIN ACUERDO',
                                    'reclamo' => '📋 RECLAMO',
                                    'volver_llamar' => '📞 VOLVER A LLAMAR',
                                    'recordar_pago' => '⏰ RECORDAR PAGO',
                                    'venta_novedad' => '🆕 VENTA CON NOVEDAD',
                                    
                                    // Opciones de RECLAMO (según la imagen)
                                    'desempleo' => '💼 DESEMPLEO',
                                    'incremento_tarifa' => '📈 INCREMENTO DE TARIFA',
                                    'otras_prioridades_economicas' => '💰 TIENE OTRAS PRIORIDADES ECONOMICAS',
                                    'disminucion_ingresos' => '📉 DISMINUCION DE INGRESOS',
                                    'adquirio_otro_servicio_salud' => '🏥 ADQUIRIO OTRO SERVICIO DE SALUD',
                                    'no_utiliza_beneficios' => '❌ NO UTILIZA/NO BENEFICIOS DEL SERVICIO',
                                    'sale_del_pais' => '✈️ SALE DEL PAIS',
                                    'fallecido' => '💀 FALLECIDO',
                                    'humanizacion_servicio' => '🤝 HUMANIZACION DEL SERVICIO GENERAL',
                                    'oportunidad_nunca_llegaron' => '⏰ OPORTUNIDAD/NUNCA LLEGARON',
                                    'metodo_pago_errado' => '💳 METODO DE PAGO ERRADO/DEBITO AUTOMATICO',
                                    'no_realizan_debito_automatico' => '🚫 NO REALIZAN DEBITO AUTOMATICO',
                                    'falsa_promesa_comercial' => '❌ FALSA PROMESA COMERCIAL',
                                    'fraude' => '🚨 FRAUDE',
                                    'factura_no_corresponde' => '📄 FACTURA NO CORRESPONDE',
                                    'no_entrega_aviso_pago' => '📬 NO ENTREGA DE AVISO DE PAGO/FACTURA',
                                    'facturacion_errada' => '📊 FACTURACION ERRADA',
                                    'cambio_traslado_sin_cobertura' => '🔄 CAMBIO/TRASLADO SIN COBERTURA',
                                    'cancelacion_no_aplicada' => '❌ CANCELACION NO APLICADA',
                                    
                                    // Otras opciones del sistema
                                    'incumplimiento_ofercimientos' => '🤝 INCUMPLIMIENTO OFRECIMIENTOS REALIZADOS (LEALTAD)',
                                    'inconformidad_pqr' => '📋 INCONFORMIDAD PQR',
                                    'informacion_errada' => 'ℹ️ INFORMACION ERRADA',
                                    'no_contestaron_sac' => '📞 NO CONTESTARON EN LA LINEA DE SAC',
                                    'reclamo_pendiente_respuesta' => '⏳ RECLAMO PENDIENTE DE RESPUESTA',
                                    'pago_afiliacion_no_aplicado' => '💳 PAGO DE AFILIACION NO APLICADO',
                                    'pago_sin_aplicar' => '💰 PAGO SIN APLICAR',
                                    'no_contesta' => '📞 NO CONTESTA',
                                    'mensaje_tercero' => '📝 MENSAJE CON TERCERO',
                                    'no_informa' => '❌ NO INFORMA',
                                    'contesta_cuelga' => '📞 CONTESTA-CUELGA',
                                    'aqui_no_vive' => '🏠 AQUÍ NO VIVE',
                                    'fallecido_otro' => '💀 FALLECIDO/OTRO',
                                    'localizacion' => '📍 LOCALIZACIÓN',
                                    'envio_estado_cuenta' => '📧 ENVÍO DE ESTADO DE CUENTA',
                                    'venta_novedad_analisis' => '🆕 VENTA CON NOVEDAD ANÁLISIS DATA',
                                    'informacion_adicional' => 'ℹ️ INFORMACIÓN ADICIONAL',
                                    
                                    // Valores legacy del sistema anterior
                                    'INTERESADO' => '💡 INTERESADO',
                                    'VENTA INGRESADA' => '💰 VENTA INGRESADA',
                                    'VOLVER A LLAMAR' => '📞 VOLVER A LLAMAR',
                                    'Número Equivocado' => '❌ NÚMERO EQUIVOCADO',
                                    'Venta Exitosa' => '✅ VENTA EXITOSA',
                                    'BUZÓN DE VOZ' => '📞 BUZÓN DE VOZ',
                                    'FALLECIDO' => '💀 FALLECIDO'
                                ];
                                
                                $razonEspecificaTexto = $razonEspecificaMap[$razonEspecifica] ?? ucfirst(str_replace('_', ' ', $razonEspecifica));
                                echo htmlspecialchars($razonEspecificaTexto);
                                ?>
                            </div>
                         </div>
                         
                         <!-- Asesor que lo tipificó -->
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-user"></i> Asesor Responsable
                             </div>
                             <div class="historial-field-value asesor">
                                 <?php echo htmlspecialchars($gestion['asesor_nombre'] ?? 'No asignado'); ?>
                             </div>
                         </div>
                         
                         <!-- Factura a Gestionar -->
                         <?php if (!empty($gestion['factura_gestionar'])): ?>
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-file-invoice"></i> Factura a Gestionar
                             </div>
                             <div class="historial-field-value factura">
                                 <?php if ($gestion['factura_gestionar'] === 'ninguna'): ?>
                                     <span style="color: #dc3545; font-weight: bold;">❌ Ninguna (Cliente no quiere pagar)</span>
                                 <?php elseif ($gestion['numero_obligacion'] === 'TODAS LAS FACTURAS'): ?>
                                     <span style="color: #17a2b8; font-weight: bold;">💰 Todas las facturas</span>
                                     <?php if (!empty($gestion['monto_obligacion'])): ?>
                                         <span style="color: #6c757d; font-size: 0.9em; font-family: 'Courier New', monospace;">- $<?php echo number_format($gestion['monto_obligacion'], 0, ',', '.'); ?> COP</span>
                                     <?php endif; ?>
                                 <?php else: ?>
                                     <span style="color: #28a745; font-weight: bold;">✅ Factura #<?php echo htmlspecialchars($gestion['numero_obligacion'] ?? 'N/A'); ?></span>
                                     <?php if (!empty($gestion['monto_obligacion'])): ?>
                                         <span style="color: #6c757d; font-size: 0.9em; font-family: 'Courier New', monospace;">- $<?php echo number_format($gestion['monto_obligacion'], 0, ',', '.'); ?> COP</span>
                                     <?php endif; ?>
                                 <?php endif; ?>
                             </div>
                         </div>
                         <?php endif; ?>
                         
                         <!-- Canales Autorizados -->
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-broadcast-tower"></i> Canales Autorizados
                             </div>
                             <div class="historial-field-value canales-autorizados">
                                 <?php if (!empty($gestion['canales_autorizados'])): ?>
                                     <?php 
                                    $canalesMap = [
                                        'llamada' => '📞 Llamada Telefónica',
                                        'whatsapp' => '📱 WhatsApp',
                                        'correo_electronico' => '📧 Correo Electrónico',
                                        'sms' => '💬 SMS',
                                        'correo_fisico' => '📮 Correo Físico',
                                        'mensajeria_aplicaciones' => '📱 Mensajería por Aplicaciones'
                                    ];
                                    // Normalizar origen (array o string CSV) y evitar duplicados
                                    $canalesOrigen = $gestion['canales_autorizados'];
                                    if (!is_array($canalesOrigen)) {
                                        $canalesOrigen = array_filter(array_map('trim', explode(',', (string)$canalesOrigen)));
                                    }
                                    // Normalizar a minúscula y sin espacios, y evitar duplicados
                                    $canalesOrigen = array_map(function($c){ return strtolower(trim($c)); }, $canalesOrigen);
                                    $canalesOrigen = array_values(array_unique($canalesOrigen));
                                    $canalesTexto = array_map(function($canal) use ($canalesMap) {
                                        return $canalesMap[$canal] ?? $canal;
                                    }, $canalesOrigen);
                                    echo implode(', ', $canalesTexto);
                                     ?>
                                 <?php else: ?>
                                     <span style="color: #6c757d; font-style: italic;">No especificados</span>
                                 <?php endif; ?>
                             </div>
                         </div>
                         
                         <!-- Fecha de Pago y Cuota (solo para acuerdo de pago) -->
                         <?php if (!empty($gestion['fecha_acuerdo']) && !empty($gestion['monto_acuerdo'])): ?>
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-calendar-check"></i> Fecha de la Cuota
                             </div>
                             <div class="historial-field-value">
                                 <?php echo date('d/m/Y', strtotime($gestion['fecha_acuerdo'])); ?>
                             </div>
                         </div>
                         
                         <div class="historial-field">
                             <div class="historial-field-label">
                                 <i class="fas fa-dollar-sign"></i> Cuota a Pagar
                             </div>
                             <div class="historial-field-value" style="font-family: 'Courier New', monospace; font-weight: bold; color: #28a745;">
                                 $<?php echo number_format($gestion['monto_acuerdo'], 0, ',', '.'); ?> COP
                             </div>
                         </div>
                         <?php endif; ?>
                         
                         <!-- Observaciones -->
                         <div class="historial-field historial-observaciones">
                             <div class="historial-field-label">
                                 <i class="fas fa-comments"></i> Observaciones
                             </div>
                             <div class="historial-field-value observaciones">
                                 <?php echo htmlspecialchars($gestion['comentarios'] ?? 'Sin observaciones'); ?>
                             </div>
                         </div>
                     </div>
                 </div>
                 
                 
                 <?php if (!empty($gestion['proxima_accion']) || !empty($gestion['proxima_fecha'])): ?>
                 <div class="historial-proxima">
                     <h5>📅 Próxima Acción:</h5>
                     <?php if (!empty($gestion['proxima_accion'])): ?>
                     <div class="proxima-accion">
                         <strong>Acción:</strong> <?php echo htmlspecialchars($gestion['proxima_accion'] ?? ''); ?>
                     </div>
                     <?php endif; ?>
                     <?php if (!empty($gestion['proxima_fecha'])): ?>
                     <div class="proxima-fecha">
                         <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($gestion['proxima_fecha'])); ?>
                     </div>
                     <?php endif; ?>
                 </div>
                 <?php endif; ?>
                 
             </div>
             <?php endforeach; ?>
             </div>
         </div>
         <?php else: ?>
         <div class="historial-section">
             <h4 class="historial-title">📋 Historial de Gestiones</h4>
             <div id="historialLlamadasLista">
                 <div class="alert alert-info">
                     <i class="fas fa-info-circle"></i>
                     <strong>Sin historial:</strong> Este cliente no tiene gestiones registradas aún.
                 </div>
             </div>
         </div>
         <?php endif; ?>
     </div>

    <script>
        let tipificacionSeleccionada = null;
        let subTipificacionSeleccionada = null;

        // #region debug d200d9 tipificaciones (vista)
        function dbglog_tip(location, message, data, hypothesisId, runId) {
            try {
                fetch('index.php?action=client_debug_log', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        runId: runId || 'pre-fix',
                        hypothesisId: hypothesisId || 'TIPV0',
                        location: location,
                        message: message,
                        data: data || {},
                        timestamp: Date.now()
                    })
                }).catch(function(){});
            } catch (e) {}
        }
        window.addEventListener('error', function (ev) {
            dbglog_tip('views/gestionar_cliente.php:window.error', 'error', {
                msg: String(ev.message || ''),
                file: String(ev.filename || ''),
                line: Number(ev.lineno || 0),
                col: Number(ev.colno || 0),
            }, 'TIPV1');
        });
        window.addEventListener('unhandledrejection', function (ev) {
            dbglog_tip('views/gestionar_cliente.php:window.unhandledrejection', 'promise_rejection', {
                reason: String((ev && ev.reason && ev.reason.message) ? ev.reason.message : (ev && ev.reason) || ''),
            }, 'TIPV1');
        });
        // #endregion
        
        // Mapeo centralizado de canales para evitar duplicación
        const CANALES_MAP = {
            'llamada': '📞 Llamada Telefónica',
            'whatsapp': '📱 WhatsApp',
            'email': '📧 Correo Electrónico',
            'correo_electronico': '📧 Correo Electrónico',
            'sms': '💬 SMS',
            'correo_fisico': '📮 Correo Físico',
            'mensajeria_aplicaciones': '📱 Mensajería por Aplicaciones',
            'chat': '💬 Chat en Línea'
        };

        function mostrarAccionesEspecificas(accion, subTipificacion) {
            const accionesDiv = document.getElementById('accionesEspecificas');
            const tituloDiv = document.getElementById('accionTitulo');
            const contenidoDiv = document.getElementById('accionContenido');
            
            if (!accionesDiv || !tituloDiv || !contenidoDiv) {
                console.error('Elementos no encontrados para mostrar acciones específicas');
                return;
            }
            
            accionesDiv.classList.add('show');
            
            if (accion === 'informacion_pago') {
                tituloDiv.innerHTML = '💰 Información de Pago - Detalles Adicionales (Opcional)';
                contenidoDiv.innerHTML = `
                    <div class="alert alert-info">
                        <strong>Información de Pago:</strong> Completa los detalles específicos de la situación de pago.
                        <br><small class="text-muted">Esta información es opcional y puede completarse más tarde.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_pago_esperada" class="form-label">Fecha Esperada de Pago (Opcional):</label>
                            <input type="date" name="fecha_pago_esperada" id="fecha_pago_esperada" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="monto_pendiente" class="form-label">Monto Pendiente (COP) (Opcional):</label>
                            <div class="input-group">
                                <span class="input-prefix">$</span>
                                <input type="text" name="monto_pendiente" id="monto_pendiente" class="form-input" placeholder="Ej: 150.000 (opcional)" oninput="formatearPesos(this)">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="detalles_pago" class="form-label">Detalles Específicos (Opcional):</label>
                        <textarea name="detalles_pago" id="detalles_pago" class="form-textarea" placeholder="Describe los detalles específicos de la situación de pago... (opcional)"></textarea>
                    </div>
                `;
            } else if (accion === 'programar_llamada') {
                tituloDiv.innerHTML = '📅 Programar Nueva Llamada';
                contenidoDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <strong>No Contactado:</strong> Programa una nueva llamada para este cliente.
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_nueva_llamada" class="form-label">Fecha y Hora para Nueva Llamada:</label>
                            <input type="datetime-local" name="fecha_nueva_llamada" id="fecha_nueva_llamada" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="motivo_nueva_llamada" class="form-label">Motivo de la Nueva Llamada:</label>
                            <input type="text" name="motivo_nueva_llamada" id="motivo_nueva_llamada" class="form-input" placeholder="Ej: Seguimiento de propuesta" required>
                        </div>
                    </div>
                `;
            } 
        }

        // Validación del formulario
        const tipificacionForm = document.getElementById('tipificacionForm');
        if (tipificacionForm) {
            tipificacionForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevenir envío normal del formulario
                
                // Remover el atributo required de todos los selects ocultos para evitar el error de validación
                const allSelects = document.querySelectorAll('select[required]');
                allSelects.forEach(select => {
                    if (select.style.display === 'none' || select.offsetParent === null) {
                        select.removeAttribute('required');
                    }
                });
                
                // Obtener valores de los nuevos dropdowns
                const formaContactoElement = document.getElementById('forma_contacto');
                const tipoContactoElement = document.getElementById('tipo_contacto');
                const facturaGestionarElement = document.getElementById('factura_gestionar');
                
                if (!tipoContactoElement) {
                    console.error('Elemento tipo_contacto no encontrado');
                    return;
                }
                
                const formaContacto = formaContactoElement ? formaContactoElement.value : '';
                const tipoContacto = tipoContactoElement.value;
                const facturaGestionar = facturaGestionarElement ? facturaGestionarElement.value : '';
                
                if (!formaContacto) {
                    alert('Por favor selecciona la forma de contacto (Llamada, WhatsApp o Email).');
                    return;
                }
                
                if (!tipoContacto) {
                    alert('Por favor selecciona el tipo de contacto.');
                    return;
                }
                
                if (!facturaGestionar) {
                    alert('Por favor selecciona una factura a gestionar o "Ninguna" si el cliente no quiere pagar.');
                    return;
                }
                
                // Obtener la sub-tipificación seleccionada
                let subTipificacionSeleccionada = '';
                if (tipoContacto === 'contacto_exitoso') {
                    const opcionElement = document.getElementById('opcion_contacto_exitoso');
                    subTipificacionSeleccionada = opcionElement ? opcionElement.value : '';
                } else if (tipoContacto === 'contacto_tercero') {
                    const opcionElement = document.getElementById('opcion_contacto_tercero');
                    subTipificacionSeleccionada = opcionElement ? opcionElement.value : '';
                } else if (tipoContacto === 'sin_contacto') {
                    const opcionElement = document.getElementById('opcion_sin_contacto');
                    subTipificacionSeleccionada = opcionElement ? opcionElement.value : '';
                }
                
                if (!subTipificacionSeleccionada) {
                    alert('Por favor selecciona una tipificación específica.');
                    return;
                }
                
                const comentariosElement = document.getElementById('comentarios');
                const comentarios = comentariosElement ? comentariosElement.value.trim() : '';
                if (comentarios.length < 10) {
                    alert('Las observaciones deben tener al menos 10 caracteres.');
                    return;
                }
                
                // Si pasa todas las validaciones, llamar a la función de guardar
                guardarTipificacion();
            });
        }

         // Inicialización principal cuando se carga la página
         document.addEventListener('DOMContentLoaded', function() {
             // La información del cliente se muestra por defecto
             
             // Inicializar gestión de facturas
             inicializarGestionFacturas();
             
             // Verificar si se acaba de guardar una gestión
             const urlParams = new URLSearchParams(window.location.search);
             if (urlParams.get('gestion_guardada') === '1') {
                 // Mostrar botones de navegación si es necesario
                 mostrarBotonesNavegacion();
             }
             
             // Limpiar formulario al cargar la página
             document.getElementById('tipificacionForm').reset();
             
            // Ocultar tipificaciones específicas al inicio y remover required

            // Resaltar información importante del cliente
            resaltarInformacionCliente();
            
            dbglog_tip('views/gestionar_cliente.php:DOMContentLoaded', 'boot', {
                hasTipoContacto: !!document.getElementById('tipo_contacto'),
                hasOpcionesExitoso: !!document.getElementById('opciones_contacto_exitoso'),
                hasOpcionesTercero: !!document.getElementById('opciones_contacto_tercero'),
                hasOpcionesSin: !!document.getElementById('opciones_sin_contacto'),
                hasTipifHidden: !!document.getElementById('tipificacion_principal'),
                hasSubHidden: !!document.getElementById('sub_tipificacion_hidden')
            }, 'TIPV2');

            console.log('Sistema de gestión de cliente inicializado correctamente');
         });

        // Función para mostrar tipificaciones específicas según el tipo de contacto
        function mostrarTipificacionesEspecificas(tipo) {
            console.log('mostrarTipificacionesEspecificas llamado con:', tipo);
            dbglog_tip('views/gestionar_cliente.php:mostrarTipificacionesEspecificas', 'enter', {
                tipo: String(tipo || ''),
                hasOpcionesExitoso: !!document.getElementById('opciones_contacto_exitoso'),
                hasOpcionesTercero: !!document.getElementById('opciones_contacto_tercero'),
                hasOpcionesSin: !!document.getElementById('opciones_sin_contacto'),
            }, 'TIPV3');
            
            // Ocultar todas las secciones
            const opcionesContactoExitoso = document.getElementById('opciones_contacto_exitoso');
            const opcionesContactoTercero = document.getElementById('opciones_contacto_tercero');
            const opcionesSinContacto = document.getElementById('opciones_sin_contacto');
            const camposAdicionales = document.getElementById('campos_adicionales');
            
            // Obtener los selects para manejar el atributo required
            const selectContactoExitoso = document.getElementById('opcion_contacto_exitoso');
            const selectContactoTercero = document.getElementById('opcion_contacto_tercero');
            const selectSinContacto = document.getElementById('opcion_sin_contacto');
            
            // Ocultar secciones y remover required de selects ocultos
            if (opcionesContactoExitoso) {
                opcionesContactoExitoso.style.display = 'none';
                if (selectContactoExitoso) selectContactoExitoso.removeAttribute('required');
            }
            if (opcionesContactoTercero) {
                opcionesContactoTercero.style.display = 'none';
                if (selectContactoTercero) selectContactoTercero.removeAttribute('required');
            }
            if (opcionesSinContacto) {
                opcionesSinContacto.style.display = 'none';
                if (selectSinContacto) selectSinContacto.removeAttribute('required');
            }
            if (camposAdicionales) camposAdicionales.style.display = 'none';
            
            // Mostrar la sección correspondiente y agregar required
            if (tipo === 'contacto_exitoso' && opcionesContactoExitoso) {
                opcionesContactoExitoso.style.display = 'block';
                if (selectContactoExitoso) selectContactoExitoso.setAttribute('required', 'required');
                console.log('Mostrando opciones de contacto exitoso');
            } else if (tipo === 'contacto_tercero' && opcionesContactoTercero) {
                opcionesContactoTercero.style.display = 'block';
                if (selectContactoTercero) selectContactoTercero.setAttribute('required', 'required');
                console.log('Mostrando opciones de contacto tercero');
            } else if (tipo === 'sin_contacto' && opcionesSinContacto) {
                opcionesSinContacto.style.display = 'block';
                if (selectSinContacto) selectSinContacto.setAttribute('required', 'required');
                console.log('Mostrando opciones de sin contacto');
            }
            
            // Limpiar selecciones anteriores
            if (selectContactoExitoso) selectContactoExitoso.value = '';
            if (selectContactoTercero) selectContactoTercero.value = '';
            if (selectSinContacto) selectSinContacto.value = '';
            
            // Actualizar la tipificación principal
            tipificacionSeleccionada = tipo;
            document.getElementById('tipificacion_principal').value = tipo;

            // Log de estado final de visibilidad
            const ex = document.getElementById('opciones_contacto_exitoso');
            const te = document.getElementById('opciones_contacto_tercero');
            const si = document.getElementById('opciones_sin_contacto');
            dbglog_tip('views/gestionar_cliente.php:mostrarTipificacionesEspecificas', 'after', {
                tipo: String(tipo || ''),
                displayExitoso: ex ? (getComputedStyle(ex).display + '|' + ex.style.display) : null,
                displayTercero: te ? (getComputedStyle(te).display + '|' + te.style.display) : null,
                displaySin: si ? (getComputedStyle(si).display + '|' + si.style.display) : null,
            }, 'TIPV3');
        }

        // Exponer implementación real para que el stub pueda delegar.
        window.__realMostrarTipificacionesEspecificas = mostrarTipificacionesEspecificas;
        window.mostrarTipificacionesEspecificas = mostrarTipificacionesEspecificas;

        // Asegurar funciones globales para handlers inline (onchange="...")
        window.mostrarTipificacionesEspecificas = mostrarTipificacionesEspecificas;
        window.seleccionarSubOpcion = seleccionarSubOpcion;
        window.seleccionarOpcionContactoExitoso = seleccionarOpcionContactoExitoso;
        window.seleccionarOpcionContactoTercero = seleccionarOpcionContactoTercero;
        window.seleccionarOpcionSinContacto = seleccionarOpcionSinContacto;

        document.addEventListener('DOMContentLoaded', function() {
            const tipoContactoEl = document.getElementById('tipo_contacto');
            if (tipoContactoEl) {
                tipoContactoEl.addEventListener('change', function() {
                    dbglog_tip('views/gestionar_cliente.php:tipo_contacto.change', 'change', {
                        value: String(tipoContactoEl.value || '')
                    }, 'TIPV5');
                });
            }
        });

        // Función para ir al siguiente cliente
        // Ahora siempre recarga la página
        async function irAlSiguienteCliente() {
            console.log('➡️ [Navegación] Obteniendo siguiente cliente...');

            // Mostrar loading en el botón
            const btnSiguiente = document.getElementById('btnSiguienteCliente');
            const textoOriginal = btnSiguiente ? btnSiguiente.innerHTML : '';
            if (btnSiguiente) {
                btnSiguiente.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
                btnSiguiente.disabled = true;
            }

            try {
                const response = await fetch('index.php?action=obtener_siguiente_cliente', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();
                console.log('✅ [Navegación] Respuesta del servidor:', data);

                if (data.success && data.siguiente_cliente) {
                    const clienteId = data.siguiente_cliente.id;
                    console.log('✅ [Navegación] Siguiente cliente encontrado:', clienteId);
                    console.log('🔄 [Navegación] Recargando página con nuevo cliente');

                    // Recargar la página con el nuevo cliente
                    window.location.href = `index.php?action=gestionar_cliente&id=${encodeURIComponent(clienteId)}`;
                } else {
                    alert(data.message || 'No hay más clientes disponibles');
                    // Restaurar botón
                    if (btnSiguiente) {
                        btnSiguiente.innerHTML = textoOriginal;
                        btnSiguiente.disabled = false;
                    }
                }
            } catch (error) {
                console.error('❌ [Navegación] Error al obtener siguiente cliente:', error);
                alert('Error al obtener el siguiente cliente: ' + error.message);
                // Restaurar botón
                if (btnSiguiente) {
                    btnSiguiente.innerHTML = textoOriginal;
                    btnSiguiente.disabled = false;
                }
            }
        }

         // Función para formatear pesos colombianos
         function formatearPesos(input) {
             // Remover todos los caracteres no numéricos
             let valor = input.value.replace(/\D/g, '');
             
             // Si no hay valor, limpiar el campo
             if (!valor) {
                 input.value = '';
                 return;
             }
             
             // Convertir a número y formatear
             let numero = parseInt(valor);
             if (isNaN(numero)) {
                 input.value = '';
                 return;
             }
             
             // Formatear con separadores de miles
             let formateado = numero.toLocaleString('es-CO');
             input.value = formateado;
         }
         
         // Función para guardar tipificación

        function guardarTipificacion() {
            const formData = new FormData(document.getElementById('tipificacionForm'));
             
            // Obtener valores de los nuevos dropdowns
            const formaContactoElement = document.getElementById('forma_contacto');
            const tipoContactoElement = document.getElementById('tipo_contacto');
            const facturaGestionarElement = document.getElementById('factura_gestionar');
            
            // Obtener información de la factura seleccionada
            const facturaGestionar = facturaGestionarElement ? facturaGestionarElement.value : '';
            const facturaSeleccionada = facturaGestionarElement ? facturaGestionarElement.selectedOptions[0] : null;
            
            // Agregar información de la factura seleccionada
            if (facturaGestionar && facturaGestionar !== 'ninguna' && facturaSeleccionada) {
                if (facturaGestionar === 'todas_las_facturas') {
                    // Manejar selección de todas las facturas
                    formData.append('obligacion_id', 'todas_las_facturas');
                    formData.append('numero_obligacion', 'TODAS LAS FACTURAS');
                    formData.append('monto_obligacion', facturaSeleccionada.dataset.saldo || '0');
                    formData.append('estado_obligacion', 'todas');
                    formData.append('facturas_ids', facturaSeleccionada.dataset.facturasIds || '');
                } else {
                    // Manejar factura individual
                    formData.append('obligacion_id', facturaGestionar);
                    formData.append('numero_obligacion', facturaSeleccionada.dataset.numero || '');
                    formData.append('monto_obligacion', facturaSeleccionada.dataset.saldo || '0');
                    formData.append('estado_obligacion', facturaSeleccionada.dataset.estado || '');
                }
            } else if (facturaGestionar === 'ninguna') {
                formData.append('obligacion_id', 'ninguna');
                formData.append('numero_obligacion', 'ninguna');
                formData.append('monto_obligacion', '0');
                formData.append('estado_obligacion', 'ninguna');
            }
            const subcategoriaHacerElement = document.getElementById('subcategoria_hacer');
            const subcategoriaRecibirElement = document.getElementById('subcategoria_recibir');
            const opcionEspecificaHacerElement = document.getElementById('opcion_especifica_hacer');
            const opcionEspecificaRecibirElement = document.getElementById('opcion_especifica_recibir');
            const subTipificacionHiddenElement = document.getElementById('sub_tipificacion_hidden');
            // Obtener canales autorizados seleccionados
            const canalesCheckboxes = document.querySelectorAll('input[name="canales_autorizados[]"]:checked');
            const canalesAutorizados = Array.from(canalesCheckboxes).map(checkbox => checkbox.value);
            
            const formaContacto = formaContactoElement ? formaContactoElement.value : '';
            const tipoContacto = tipoContactoElement ? tipoContactoElement.value : '';
            const opcionContactoExitoso = document.getElementById('opcion_contacto_exitoso') ? document.getElementById('opcion_contacto_exitoso').value : '';
            const opcionContactoTercero = document.getElementById('opcion_contacto_tercero') ? document.getElementById('opcion_contacto_tercero').value : '';
            const opcionSinContacto = document.getElementById('opcion_sin_contacto') ? document.getElementById('opcion_sin_contacto').value : '';
            const subTipificacionHidden = subTipificacionHiddenElement ? subTipificacionHiddenElement.value : '';
            const comentarios = formData.get('comentarios');
             
             // Obtener la sub-tipificación del campo hidden (razón específica seleccionada)
             let subTipificacionSeleccionada = subTipificacionHidden || '';
             
             // Obtener el resultado del contacto (segundo nivel)
             let resultadoContacto = '';
             if (tipoContacto === 'contacto_exitoso') {
                 resultadoContacto = opcionContactoExitoso;
             } else if (tipoContacto === 'contacto_tercero') {
                 resultadoContacto = opcionContactoTercero;
             } else if (tipoContacto === 'sin_contacto') {
                 resultadoContacto = opcionSinContacto;
             }

            // Validar campos obligatorios
            if (!tipoContacto) {
                alert('Por favor selecciona el tipo de contacto.');
                return;
            }
            
            if (!resultadoContacto) {
                alert('Por favor selecciona el resultado del contacto.');
                return;
            }
            
            if (!facturaGestionar) {
                alert('Por favor selecciona una factura a gestionar o "Ninguna" si el cliente no quiere pagar.');
                return;
            }
            
            if (!subTipificacionSeleccionada) {
                alert('Por favor selecciona una tipificación específica.');
                return;
            }
            
            if (!comentarios || comentarios.trim() === '') {
                alert('Por favor agrega comentarios sobre la gestión.');
                return;
            }
             
            // Agregar valores al FormData
            formData.set('forma_contacto', formaContacto);
            formData.set('factura_gestionar', facturaGestionar);
            formData.set('tipificacion', resultadoContacto);
            formData.set('sub_tipificacion', subTipificacionSeleccionada);
            formData.set('tipo_contacto_arbol', tipoContacto);
            
            // Agregar canales autorizados (pueden ser múltiples)
            canalesAutorizados.forEach((canal, index) => {
                formData.set(`canales_autorizados[${index}]`, canal);
            });
             
             // Agregar campos opcionales de información de pago si existen
             const fechaPagoEsperada = document.getElementById('fecha_pago_esperada');
             const montoPendiente = document.getElementById('monto_pendiente');
             const detallesPago = document.getElementById('detalles_pago');
             
             if (fechaPagoEsperada && fechaPagoEsperada.value) {
                 formData.set('fecha_pago_esperada', fechaPagoEsperada.value);
             }
             
             if (montoPendiente && montoPendiente.value) {
                 formData.set('monto_pendiente', montoPendiente.value);
             }
             
             if (detallesPago && detallesPago.value) {
                 formData.set('detalles_pago', detallesPago.value);
             }
             
             // Agregar campos opcionales de programar llamada si existen
             const fechaNuevaLlamada = document.getElementById('fecha_nueva_llamada');
             const motivoNuevaLlamada = document.getElementById('motivo_nueva_llamada');
             
             if (fechaNuevaLlamada && fechaNuevaLlamada.value) {
                 formData.set('fecha_nueva_llamada', fechaNuevaLlamada.value);
             }
             
            if (motivoNuevaLlamada && motivoNuevaLlamada.value) {
                formData.set('motivo_nueva_llamada', motivoNuevaLlamada.value);
            }
            
            // Teléfono con el que se contactó (desplegable de número para llamar)
            const telefonoDropdown = document.getElementById('telefonoDropdown');
            if (telefonoDropdown && telefonoDropdown.value) {
                formData.set('telefono_contacto', telefonoDropdown.value.trim());
            }
            
            // Enviar formulario
            fetch('index.php?action=guardar_tipificacion', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                
                if (data.success) {
                    alert('✅ Tipificación guardada exitosamente');
                    
            // Mostrar botones de navegación después del guardado
            console.log('Mostrando botones de navegación...');
            mostrarBotonesNavegacion();
                    
                    // Limpiar formulario
                    document.getElementById('tipificacionForm').reset();
                    
                    // Limpiar selecciones de tipificación
                    tipificacionSeleccionada = null;
                    subTipificacionSeleccionada = null;
                    
                    // Ocultar acciones específicas
                    const accionesEspecificas = document.getElementById('accionesEspecificas');
                    if (accionesEspecificas) {
                        accionesEspecificas.classList.remove('show');
                    }
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo guardar la tipificación'));
                }
            })
             .catch(error => {
                 console.error('❌ Error en fetch:', error);
                 alert('❌ Error al guardar la tipificación: ' + error.message);
             });
         }

        
        
        // Función para abrir el aplicativo de agentes
         
        // Función para Click to Call (usando el nuevo sistema)
        // La función global llamarCliente se define en click_to_call.js
        
        // Función para Click to Call (sistema original)
        function llamarDesdeVentanaAnclada(numero) {
            if (confirm(`¿Desea llamar al número ${numero}?`)) {
                // Obtener datos del usuario
                fetch('index.php?action=get_telefono_data', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.tiene_telefono) {
                            const urlLlamada = `https://estaqueue.udpsa.com/phone/phone.php?PBXCLOUD=onix.udpsa.com&extension=${data.extension}&claveWEBRTC=${data.clave}&autoanswer=1&numero=${numero}`;
                            window.open(urlLlamada, 'telefono', 'width=400,height=600,scrollbars=yes,resizable=yes,menubar=no,toolbar=no,location=no,status=no');
                        } else {
                            alert('No tiene configurada la extensión telefónica. Contacte al administrador.');
                        }
                    })
                    .catch(error => {
                        console.error('Error obteniendo datos de teléfono:', error);
                        alert('Error al obtener configuración de teléfono');
                    });
            }
        }
        
        // Reemplazar la función original
        window.llamarCliente = llamarDesdeVentanaAnclada;
        
        // Funciones para búsqueda de clientes - ELIMINADAS (botón "Buscar Cliente" removido)

        // ===== FUNCIONES PARA GESTIÓN DE FACTURAS/PRODUCTOS =====
        
        let facturasDisponibles = [];
        let facturaActual = null;
        let facturasGestionadas = [];
        // Bloquea reconfiguración de botones una vez que se muestran los de navegación
        let uiBloqueoNavegacion = false;

        let tieneTareasPendientes = <?php echo json_encode($tieneTareasPendientes ?? false); ?>;

        // #region agent log b7eaa7 gestionar_cliente.js bootstrap
        try {
            fetch('http://127.0.0.1:7559/ingest/0bcc0192-fe61-4fb0-b109-b4792228bcf7', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'b7eaa7' },
                body: JSON.stringify({
                    sessionId: 'b7eaa7',
                    runId: 'pre',
                    hypothesisId: 'NX7',
                    location: 'views/gestionar_cliente.php:bootstrap',
                    message: 'page_vars',
                    data: {
                        tieneTareasPendientes: !!tieneTareasPendientes,
                        hasBtnNav: !!document.getElementById('btnNavegacion'),
                        hasBtnNext: !!document.getElementById('btnSiguienteCliente'),
                    },
                    timestamp: Date.now()
                })
            }).catch(() => {});
        } catch (e) {}
        // #endregion

        // Inicializar gestión de facturas
        function inicializarGestionFacturas() {
            // Inicializar array de facturas disponibles (vacío por defecto)
            facturasDisponibles = [];
            
            // Como se eliminó la selección de facturas en la simplificación,
            // inicializamos con un array vacío
            console.log('Sistema de gestión de facturas inicializado (sin selección de facturas)');
            
            // Configurar botones
            configurarBotonesSegunFacturas();
        }
        
        // Manejar selección de factura (función simplificada)
        function manejarSeleccionFactura() {
            // Como se eliminó la selección de facturas en la simplificación,
            // esta función ya no es necesaria pero se mantiene para compatibilidad
            console.log('Selección de factura no disponible en modo simplificado');
            return;
        }
        
        // Configurar botones según el estado de las facturas
        function configurarBotonesSegunFacturas() {
            // Si ya mostramos los botones de navegación, no volver a ocultarlos ni reconfigurar
            if (uiBloqueoNavegacion) {
                return;
            }
            
            const btnGuardarPrincipal = document.getElementById('btnGuardarPrincipal');
            const btnNavegacion = document.getElementById('btnNavegacion');
            
            // Verificar que los elementos existen
            if (!btnGuardarPrincipal || !btnNavegacion) {
                console.warn('Botones de navegación no encontrados');
                return;
            }
            
            // Ocultar botones de navegación inicialmente
            btnNavegacion.style.display = 'none';
            
            // Mostrar botón principal
            btnGuardarPrincipal.style.display = 'inline-block';
            btnGuardarPrincipal.innerHTML = '<i class="fas fa-save"></i> Guardar Gestión';
        }

        // Mostrar botones de navegación después del guardado (todos los botones permanecen visibles)
        function mostrarBotonesNavegacion() {
            const btnNavegacion = document.getElementById('btnNavegacion');
            const btnSiguienteCliente = document.getElementById('btnSiguienteCliente');
            const btnGuardarPrincipal = document.getElementById('btnGuardarPrincipal');
            
            if (!btnNavegacion || !btnSiguienteCliente) {
                console.error('Elementos de navegación no encontrados');
                return;
            }
            
            // Marcar bloqueo de navegación para evitar reconfiguraciones posteriores
            uiBloqueoNavegacion = true;
            
            // Mostrar botones de navegación
            btnNavegacion.style.display = 'block'; 
            
            // NO ocultar el botón principal - todos los botones permanecen visibles
            if (btnGuardarPrincipal) {
                btnGuardarPrincipal.style.display = 'inline-block'; 
            }
            
            // Mostrar botón de siguiente cliente si hay tareas pendientes
            if (tieneTareasPendientes) {
                // Hay tareas pendientes - mostrar botón de siguiente cliente
                btnSiguienteCliente.style.display = 'inline-block'; 
                
                // Verificar dinámicamente si realmente hay un siguiente cliente disponible
                actualizarDisponibilidadSiguienteCliente(btnSiguienteCliente);
            } else {
                // No hay tareas pendientes - ocultar botón de siguiente cliente
                btnSiguienteCliente.style.display = 'none'; 
            }
        }

        // Verifica contra el backend si existe un siguiente cliente y ajusta visibilidad del botón
        function actualizarDisponibilidadSiguienteCliente(btnSiguienteCliente, intento = 0) {
            try {
                console.log('Verificando disponibilidad del siguiente cliente...');
                
                fetch('index.php?action=obtener_siguiente_cliente', { 
                    method: 'GET', 
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Respuesta del servidor:', data);
                    
                    if (data && data.success && data.siguiente_cliente) {
                        // Hay siguiente cliente asignado
                        btnSiguienteCliente.style.display = 'inline-block'; 
                        console.log('Siguiente cliente disponible:', data.siguiente_cliente.nombre);
                    } else {
                        // No hay siguiente cliente disponible
                        btnSiguienteCliente.style.display = 'none'; 
                        console.log('No hay siguiente cliente disponible');
                    }
                })
                .catch(error => {
                    console.error('Error verificando siguiente cliente:', error);
                    
                    // Reintento simple por posible condición de carrera de persistencia (hasta 2 intentos)
                    if (intento < 2) {
                        console.log(`Reintentando en 700ms... (intento ${intento + 1})`);
                        setTimeout(() => actualizarDisponibilidadSiguienteCliente(btnSiguienteCliente, intento + 1), 700);
                        return;
                    }
                    
                    // En caso de error persistente, ocultar el botón
                    btnSiguienteCliente.style.display = 'none'; 
                    console.log('Error persistente, ocultando botón de siguiente cliente');
                });
            } catch (error) {
                console.error('Error en actualizarDisponibilidadSiguienteCliente:', error);
                btnSiguienteCliente.style.display = 'none'; 
            }
        }
        
        // Cerrar modal de declinar todos
        function cerrarModalDeclinarTodos() {
            document.getElementById('modalDeclinarTodos').style.display = 'none';
            document.getElementById('comentarios-declinacion').value = '';
        }
        
        // Confirmar declinación de todos los productos
        function confirmarDeclinarTodos() {
            const comentarios = document.getElementById('comentarios-declinacion').value.trim();
            
            if (!comentarios) {
                mostrarError('Los comentarios son obligatorios');
                return;
            }
            
            const clienteId = <?php echo $cliente['id']; ?>;
            
            fetch('index.php?action=declinar_todos_productos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cliente_id: clienteId,
                    comentarios: comentarios
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarExito('Todos los productos han sido declinados');
                    cerrarModalDeclinarTodos();
                    cargarProductos(); // Recargar lista
                } else {
                    mostrarError('Error al declinar productos: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al declinar productos');
            });
        }
        
        // Funciones de utilidad para mostrar mensajes
        function mostrarExito(mensaje) {
            // Crear o actualizar mensaje de éxito
            let alertDiv = document.getElementById('alert-exito');
            if (!alertDiv) {
                alertDiv = document.createElement('div');
                alertDiv.id = 'alert-exito';
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.style.position = 'fixed';
                alertDiv.style.top = '20px';
                alertDiv.style.right = '20px';
                alertDiv.style.zIndex = '9999';
                document.body.appendChild(alertDiv);
            }
            
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle"></i> ${mensaje}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        function mostrarError(mensaje) {
            // Crear o actualizar mensaje de error
            let alertDiv = document.getElementById('alert-error');
            if (!alertDiv) {
                alertDiv = document.createElement('div');
                alertDiv.id = 'alert-error';
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.style.position = 'fixed';
                alertDiv.style.top = '20px';
                alertDiv.style.right = '20px';
                alertDiv.style.zIndex = '9999';
                document.body.appendChild(alertDiv);
            }
            
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i> ${mensaje}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        // ========================================
        // FUNCIONES PARA LA INFORMACIÓN DEL CLIENTE
        // ========================================
        
        /**
         * Resalta visualmente los campos de información del cliente que requieren atención
         * Se ejecuta cuando se carga la página para destacar información importante
         */
        function resaltarInformacionCliente() {
            const infoItems = document.querySelectorAll('.info-item');
            
            infoItems.forEach(item => {
                const span = item.querySelector('span');
                if (span) {
                    const texto = span.textContent.toLowerCase();
                    
                    // Resaltar saldo pendiente
                    if (texto.includes('$') && !texto.includes('$0')) {
                        item.style.borderColor = '#dc2626';
                        item.style.backgroundColor = '#fef2f2';
                    }
                    
                    // Resaltar días en mora
                    if (texto.includes('días') && !texto.includes('al día')) {
                        item.style.borderColor = '#dc2626';
                        item.style.backgroundColor = '#fef2f2';
                    }
                }
            });
        }
        
        // ===== NUEVAS FUNCIONES PARA EL SISTEMA DE TIPIFICACIONES ACTUALIZADO =====
        
        // Función para manejar selección de opción de contacto exitoso
        function seleccionarOpcionContactoExitoso(valor) {
            // Ocultar todas las sub-opciones
            const subOpciones = [
                'sub_opciones_acuerdo_pago',
                'sub_opciones_ya_pago', 
                'sub_opciones_localizado_sin_acuerdo',
                'sub_opciones_reclamo',
                'sub_opciones_volver_llamar',
                'sub_opciones_recordar_pago',
                'sub_opciones_venta_novedad'
            ];
            
            subOpciones.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.style.display = 'none';
                    // Limpiar selección
                    const select = elemento.querySelector('select');
                    if (select) select.value = '';
                }
            });
            
            // Mostrar la sub-opción correspondiente
            const subOpcionId = `sub_opciones_${valor}`;
            const subOpcionElemento = document.getElementById(subOpcionId);
            if (subOpcionElemento) {
                subOpcionElemento.style.display = 'block';
            }
            
            // Limpiar campos adicionales
            const camposAdicionales = document.getElementById('campos_adicionales');
            if (camposAdicionales) {
                camposAdicionales.innerHTML = '';
                camposAdicionales.style.display = 'none';
            }
            
            // Mostrar campos específicos según la opción
            if (valor === 'acuerdo_pago') {
                // Obtener la fecha actual para establecer como mínimo
                const hoy = new Date().toISOString().split('T')[0];

                camposAdicionales.innerHTML =
                    '<div class="form-group">' +
                        '<label for="fecha_acuerdo" class="form-label">Fecha de Pago:</label>' +
                        '<input type="date" name="fecha_acuerdo" id="fecha_acuerdo" class="form-control" min="' + hoy + '" required>' +
                        '<small class="form-help">Solo se permiten fechas futuras (hoy o posteriores)</small>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label for="monto_acuerdo" class="form-label">Monto del Acuerdo:</label>' +
                        '<div class="input-group">' +
                            '<span class="input-prefix" style="background-color: #e9ecef; border: 1px solid #ced4da; padding: 8px 12px; font-weight: bold; color: #495057;">$</span>' +
                            '<input type="text" name="monto_acuerdo" id="monto_acuerdo" class="form-control" placeholder="Ej: 150.000" required oninput="formatearPesos(this)" style="font-family: \'Courier New\', monospace; font-weight: bold; text-align: right;">' +
                        '</div>' +
                        '<small class="form-help">Ingresa el monto total adeudado o una cuota específica (en pesos colombianos)</small>' +
                    '</div>';
                camposAdicionales.style.display = 'block';
            } else if (valor === 'volver_llamar') {
                camposAdicionales.innerHTML =
                    '<div class="form-group">' +
                        '<label for="fecha_nueva_llamada" class="form-label">Fecha para Nueva Llamada:</label>' +
                        '<input type="date" name="fecha_nueva_llamada" id="fecha_nueva_llamada" class="form-control" required>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label for="motivo_nueva_llamada" class="form-label">Motivo:</label>' +
                        '<input type="text" name="motivo_nueva_llamada" id="motivo_nueva_llamada" class="form-control" required>' +
                    '</div>';
                camposAdicionales.style.display = 'block';
            }
            
            // Actualizar la sub-tipificación
            document.getElementById('sub_tipificacion_hidden').value = valor;
        }
        
        // Función para manejar selección de opción de contacto con tercero
        function seleccionarOpcionContactoTercero(valor) {
            // Ocultar todas las sub-opciones
            const subOpciones = [
                'sub_opciones_aqui_no_vive',
                'sub_opciones_mensaje_tercero',
                'sub_opciones_fallecido_otro'
            ];
            
            subOpciones.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.style.display = 'none';
                    // Limpiar selección
                    const select = elemento.querySelector('select');
                    if (select) select.value = '';
                }
            });
            
            // Mostrar la sub-opción correspondiente
            const subOpcionId = `sub_opciones_${valor}`;
            const subOpcionElemento = document.getElementById(subOpcionId);
            if (subOpcionElemento) {
                subOpcionElemento.style.display = 'block';
            }
            
            // Limpiar campos adicionales
            const camposAdicionales = document.getElementById('campos_adicionales');
            if (camposAdicionales) {
                camposAdicionales.innerHTML = '';
                camposAdicionales.style.display = 'none';
            }
            
            // Actualizar la sub-tipificación
            document.getElementById('sub_tipificacion_hidden').value = valor;
        }
        
        // Función para manejar selección de opción de sin contacto
        function seleccionarOpcionSinContacto(valor) {
            // Ocultar todas las sub-opciones
            const subOpciones = [
                'sub_opciones_no_contesta',
                'sub_opciones_buzon_mensajes',
                'sub_opciones_telefono_danado',
                'sub_opciones_fallecido_otro_sin_contacto',
                'sub_opciones_localizacion',
                'sub_opciones_envio_estado_cuenta',
                'sub_opciones_venta_novedad_analisis'
            ];
            
            subOpciones.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.style.display = 'none';
                    // Limpiar selección
                    const select = elemento.querySelector('select');
                    if (select) select.value = '';
                }
            });
            
            // Mostrar la sub-opción correspondiente
            const subOpcionId = `sub_opciones_${valor}`;
            const subOpcionElemento = document.getElementById(subOpcionId);
            if (subOpcionElemento) {
                subOpcionElemento.style.display = 'block';
            }
            
            // Limpiar campos adicionales
            const camposAdicionales = document.getElementById('campos_adicionales');
            if (camposAdicionales) {
                camposAdicionales.innerHTML = '';
                camposAdicionales.style.display = 'none';
            }
            
            // Actualizar la sub-tipificación
            document.getElementById('sub_tipificacion_hidden').value = valor;
        }
        
        // Función para manejar la selección de sub-opciones (tercer nivel)
        function seleccionarSubOpcion(valor) {
            dbglog_tip('views/gestionar_cliente.php:seleccionarSubOpcion', 'enter', {
                valor: String(valor || ''),
                hasSubHidden: !!document.getElementById('sub_tipificacion_hidden'),
                opcionContactoExitoso: (document.getElementById('opcion_contacto_exitoso') || {}).value || null
            }, 'TIPV4');
            // Actualizar la sub-tipificación con el valor específico seleccionado
            document.getElementById('sub_tipificacion_hidden').value = valor;
            
            // Verificar si el nivel 2 es "acuerdo_pago" para preservar los campos de pago
            const opcionContactoExitoso = document.getElementById('opcion_contacto_exitoso');
            const esAcuerdoPago = opcionContactoExitoso && opcionContactoExitoso.value === 'acuerdo_pago';
            
            // Mostrar campos adicionales si es necesario
            const camposAdicionales = document.getElementById('campos_adicionales');
            if (!camposAdicionales) return;
            
            // Si es acuerdo de pago, no limpiar los campos existentes
            if (esAcuerdoPago) {
                // Solo actualizar la sub-tipificación, mantener los campos de pago
                return;
            }
            
            // Limpiar campos adicionales solo si no es acuerdo de pago
            camposAdicionales.innerHTML = '';
            camposAdicionales.style.display = 'none';
            
            // Mostrar campos específicos según la sub-opción seleccionada
            if (valor === 'volver_llamar') {
                camposAdicionales.innerHTML = `
                    <div class="form-group">
                        <label for="fecha_nueva_llamada" class="form-label">Fecha para Nueva Llamada:</label>
                        <input type="date" name="fecha_nueva_llamada" id="fecha_nueva_llamada" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="motivo_nueva_llamada" class="form-label">Motivo:</label>
                        <input type="text" name="motivo_nueva_llamada" id="motivo_nueva_llamada" class="form-control" required>
                    </div>
                `;
                camposAdicionales.style.display = 'block';
            }
        }
        
        // ===== FUNCIONALIDAD DEL BUSCADOR DE CLIENTES =====
        let timeoutBuscador = null;
        let resultadosBuscadorVisible = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            const buscadorInput = document.getElementById('buscadorClienteInput');
            const resultadosDiv = document.getElementById('resultadosBuscador');
            
            if (buscadorInput && resultadosDiv) {
                // Ocultar resultados al hacer clic fuera
                document.addEventListener('click', function(e) {
                    if (!buscadorInput.contains(e.target) && !resultadosDiv.contains(e.target)) {
                        resultadosDiv.style.display = 'none';
                        resultadosBuscadorVisible = false;
                    }
                });
                
                // Buscar mientras se escribe (con debounce)
                buscadorInput.addEventListener('input', function() {
                    const termino = this.value.trim();
                    
                    // Limpiar timeout anterior
                    if (timeoutBuscador) {
                        clearTimeout(timeoutBuscador);
                    }
                    
                    // Si el término es muy corto, ocultar resultados
                    if (termino.length < 2) {
                        resultadosDiv.style.display = 'none';
                        resultadosBuscadorVisible = false;
                        return;
                    }
                    
                    // Esperar 300ms antes de buscar (debounce)
                    timeoutBuscador = setTimeout(function() {
                        buscarClientes(termino);
                    }, 300);
                });
                
                // Mostrar resultados al hacer focus si hay término
                buscadorInput.addEventListener('focus', function() {
                    const termino = this.value.trim();
                    if (termino.length >= 2 && resultadosBuscadorVisible) {
                        resultadosDiv.style.display = 'block';
                    }
                });
            }
        });
        
        function buscarClientes(termino) {
            const resultadosDiv = document.getElementById('resultadosBuscador');
            
            if (!resultadosDiv) return;
            
            // Mostrar loading
            resultadosDiv.innerHTML = '<div class="loading-buscador"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
            resultadosDiv.style.display = 'block';
            resultadosBuscadorVisible = true;
            
            // Realizar búsqueda
            fetch('index.php?action=buscar_clientes_por_termino&termino=' + encodeURIComponent(termino), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.clientes && data.clientes.length > 0) {
                    mostrarResultadosBuscador(data.clientes);
                } else {
                    resultadosDiv.innerHTML = '<div class="sin-resultados-buscador"><i class="fas fa-info-circle"></i> No se encontraron clientes</div>';
                }
            })
            .catch(error => {
                console.error('Error en búsqueda:', error);
                resultadosDiv.innerHTML = '<div class="sin-resultados-buscador"><i class="fas fa-exclamation-circle"></i> Error al buscar clientes</div>';
            });
        }
        
        function mostrarResultadosBuscador(clientes) {
            const resultadosDiv = document.getElementById('resultadosBuscador');
            
            if (!resultadosDiv) return;
            
            let html = '';
            
            // Limitar a 15 resultados para no sobrecargar
            const clientesLimitados = clientes.slice(0, 15);
            
            clientesLimitados.forEach(function(cliente) {
                html += `
                    <div class="resultado-buscador-item" onclick="seleccionarClienteBuscador(${cliente.id})">
                        <div class="resultado-buscador-nombre">
                            <i class="fas fa-user"></i> ${escapeHtml(cliente.nombre || 'Sin nombre')}
                        </div>
                        <div class="resultado-buscador-info">
                            ${cliente.cedula ? `<span><i class="fas fa-id-card"></i> ${escapeHtml(cliente.cedula)}</span>` : ''}
                            ${cliente.telefono ? `<span><i class="fas fa-phone"></i> ${escapeHtml(cliente.telefono)}</span>` : ''}
                        </div>
                    </div>
                `;
            });
            
            if (clientes.length > 15) {
                html += `<div class="sin-resultados-buscador" style="padding: 10px; font-size: 0.85rem; color: #6b7280;">
                    <i class="fas fa-info-circle"></i> Mostrando 15 de ${clientes.length} resultados
                </div>`;
            }
            
            resultadosDiv.innerHTML = html;
        }
        
        /**
         * Seleccionar cliente desde el buscador
         * Usa AJAX para cambiar de cliente SIN recargar la página
         * @param {number|string} clienteId - ID del cliente seleccionado
         */
        async function seleccionarClienteBuscador(clienteId) {
            console.log('🔍 [Buscador] Seleccionando cliente desde búsqueda:', clienteId, '(tipo:', typeof clienteId + ')');

            // Validar que el ID existe y es válido
            if (!clienteId || clienteId === 'undefined' || clienteId === 'null' || clienteId === '') {
                console.error('❌ [Buscador] ID de cliente inválido:', clienteId);
                alert('Error: ID de cliente inválido. Por favor, intenta nuevamente.');
                return;
            }

            // Convertir a número si es string
            const idNumerico = Number(clienteId);
            if (isNaN(idNumerico) || idNumerico <= 0) {
                console.error('❌ [Buscador] ID de cliente no es un número válido:', clienteId);
                alert('Error: ID de cliente no válido. Por favor, intenta nuevamente.');
                return;
            }

            console.log('✅ [Buscador] ID validado:', idNumerico);

            // Ocultar resultados
            const resultadosDiv = document.getElementById('resultadosBuscador');
            const buscadorInput = document.getElementById('buscadorClienteInput');
            
            if (resultadosDiv) {
                resultadosDiv.style.display = 'none';
                resultadosBuscadorVisible = false;
            }
            
            if (buscadorInput) {
                buscadorInput.value = '';
            }

            // Recargar la página con el nuevo cliente
            console.log('🔄 [Buscador] Recargando página con cliente:', idNumerico);
            window.location.href = `index.php?action=gestionar_cliente&id=${encodeURIComponent(idNumerico)}`;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Funcionalidad del desplegable de teléfonos
        document.addEventListener('DOMContentLoaded', function() {
            const telefonoDropdown = document.getElementById('telefonoDropdown');
            const telefonoSeleccionadoElement = document.getElementById('telefonoSeleccionado');
            
            if (telefonoDropdown && telefonoSeleccionadoElement) {
                // Función para actualizar el texto del teléfono seleccionado
                function actualizarTelefonoSeleccionado() {
                    const telefonoSeleccionado = telefonoDropdown.value;
                    if (telefonoSeleccionado) {
                        telefonoSeleccionadoElement.textContent = telefonoSeleccionado;
                        telefonoSeleccionadoElement.style.display = 'inline-block';
                    } else {
                        telefonoSeleccionadoElement.textContent = 'N/A';
                        telefonoSeleccionadoElement.style.display = 'inline-block';
                    }
                }
                
                // Agregar evento de cambio para mostrar el teléfono seleccionado
                telefonoDropdown.addEventListener('change', actualizarTelefonoSeleccionado);
                
                // Agregar evento click para llamar desde el softphone
                telefonoSeleccionadoElement.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const numero = this.textContent.trim();
                    
                    // Verificar que el número no sea 'N/A' o vacío
                    if (!numero || numero === 'N/A' || numero === '') {
                        console.warn('⚠️ [Teléfono] No hay número válido para llamar');
                        return;
                    }
                    
                    console.log('📞 [Teléfono] Clic en número:', numero);
                    
                    // Verificar si el softphone está disponible
                    if (typeof window.webrtcSoftphone !== 'undefined' && window.webrtcSoftphone) {
                        // Verificar si está conectado
                        if (window.webrtcSoftphone.status === 'connected') {
                            // Llamar usando la función llamarDesdeWebRTC
                            if (typeof llamarDesdeWebRTC === 'function') {
                                llamarDesdeWebRTC(numero);
                            } else if (typeof window.llamarCliente === 'function') {
                                window.llamarCliente(numero);
                            } else {
                                console.error('❌ [Teléfono] No se encontró función para llamar');
                                alert('Error: No se puede iniciar la llamada. El softphone no está completamente inicializado.');
                            }
                        } else {
                            console.warn('⚠️ [Teléfono] Softphone no está conectado. Estado:', window.webrtcSoftphone.status);
                            alert('El softphone no está conectado. Por favor, espera a que se conecte al PBX.');
                        }
                    } else {
                        console.warn('⚠️ [Teléfono] Softphone no está disponible');
                        alert('El softphone no está disponible. Por favor, espera a que se cargue.');
                    }
                });
                
                // Agregar título para indicar que es clickeable
                telefonoSeleccionadoElement.title = 'Clic para llamar desde el softphone';
                
                // Inicializar con el valor por defecto
                actualizarTelefonoSeleccionado();
            }
            
            // Configurar el formulario de agregar información
            const formAgregarInfo = document.getElementById('formAgregarInfo');
            if (formAgregarInfo) {
                formAgregarInfo.addEventListener('submit', function(e) {
                    e.preventDefault();
                    guardarInformacionAdicional();
                });
            }
        });
        
        // ===== FUNCIONES PARA EL MODAL DE AGREGAR INFORMACIÓN =====
        
        function mostrarModalAgregarInfo() {
            const modal = document.getElementById('modalAgregarInfo');
            if (modal) {
                modal.style.display = 'flex';
                // Limpiar formulario
                document.getElementById('formAgregarInfo').reset();
                // Reiniciar teléfonos adicionales
                reiniciarTelefonosAdicionales();
            }
        }
        
        function cerrarModalAgregarInfo() {
            const modal = document.getElementById('modalAgregarInfo');
            if (modal) {
                modal.style.display = 'none';
                // Reiniciar el formulario
                document.getElementById('formAgregarInfo').reset();
                reiniciarTelefonosAdicionales();
            }
        }
        
        // Variables para manejar teléfonos adicionales
        let contadorTelefonos = 0;
        
        // Función para agregar un campo de teléfono adicional
        function agregarTelefono() {
            contadorTelefonos++;
            const container = document.getElementById('telefonos-adicionales-container');
            
            const telefonoDiv = document.createElement('div');
            telefonoDiv.className = 'form-group telefonos-adicionales-item';
            telefonoDiv.id = `telefono-${contadorTelefonos}`;
            
            telefonoDiv.innerHTML = `
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="telefono_${contadorTelefonos}" class="form-label">Teléfono ${contadorTelefonos}:</label>
                        <input type="tel" name="telefonos_adicionales[]" id="telefono_${contadorTelefonos}" 
                               class="form-input" placeholder="Ej: 3001234567">
                    </div>
                    <div class="form-group" style="width: auto; margin-left: 10px;">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="eliminarTelefono(${contadorTelefonos})" 
                                style="margin-top: 25px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(telefonoDiv);
        }
        
        // Función para eliminar un campo de teléfono
        function eliminarTelefono(id) {
            const telefonoDiv = document.getElementById(`telefono-${id}`);
            if (telefonoDiv) {
                telefonoDiv.remove();
            }
        }
        
        // Función para reiniciar los teléfonos adicionales
        function reiniciarTelefonosAdicionales() {
            const container = document.getElementById('telefonos-adicionales-container');
            container.innerHTML = '';
            contadorTelefonos = 0;
        }
        
        // Función para actualizar el dropdown de teléfonos con nuevos teléfonos
        function actualizarDropdownTelefonos(nuevosTelefonos) {
            const dropdown = document.getElementById('telefonoDropdown');
            const telefonoSeleccionadoElement = document.getElementById('telefonoSeleccionado');
            if (!dropdown) return;
            
            // Filtrar teléfonos vacíos
            const telefonosValidos = nuevosTelefonos.filter(tel => tel.trim() !== '');
            
            if (telefonosValidos.length === 0) return;
            
            // Agregar cada teléfono al dropdown si no existe
            telefonosValidos.forEach(telefono => {
                // Verificar si el teléfono ya existe en el dropdown
                const opcionesExistentes = Array.from(dropdown.options).map(opt => opt.value);
                if (!opcionesExistentes.includes(telefono)) {
                    const nuevaOpcion = document.createElement('option');
                    nuevaOpcion.value = telefono;
                    nuevaOpcion.textContent = telefono;
                    dropdown.appendChild(nuevaOpcion);
                }
            });
            
            // Actualizar el elemento de texto con el primer teléfono nuevo si no hay selección actual
            if (telefonoSeleccionadoElement && !dropdown.value) {
                telefonoSeleccionadoElement.textContent = telefonosValidos[0];
            }
            
            // Mostrar un mensaje de confirmación
            console.log(`✅ Se agregaron ${telefonosValidos.length} teléfono(s) al dropdown`);
        }
        
        function guardarInformacionAdicional() {
            const formData = new FormData(document.getElementById('formAgregarInfo'));
            
            // Verificar que al menos un campo tenga información
            const email = formData.get('nuevo_email');
            const direccion = formData.get('nueva_direccion');
            const ciudad = formData.get('nueva_ciudad');
            const telefonosAdicionales = formData.getAll('telefonos_adicionales[]');
            
            const tieneContacto = email || direccion || ciudad || telefonosAdicionales.some(t => t.trim() !== '');
            
            if (!tieneContacto) {
                alert('Por favor completa al menos un campo para guardar información.');
                return;
            }
            
            // Mostrar loading
            const btnGuardar = document.querySelector('#formAgregarInfo button[type="submit"]');
            const textoOriginal = btnGuardar.innerHTML;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            btnGuardar.disabled = true;
            
            // Enviar datos
            fetch('index.php?action=agregar_informacion_cliente', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Construir mensaje detallado
                    let mensaje = '✅ Información guardada exitosamente';
                    let mensajeAdicional = '';
                    
                    // Información sobre teléfonos
                    if (data.telefonos_guardados !== undefined) {
                        const partes = [];
                        
                        if (data.telefonos_guardados > 0) {
                            partes.push(`${data.telefonos_guardados} teléfono(s) agregado(s) correctamente`);
                        }
                        
                        if (data.telefonos_duplicados > 0) {
                            partes.push(`${data.telefonos_duplicados} teléfono(s) ya existían y no se agregaron`);
                        }
                        
                        if (data.todas_columnas_ocupadas) {
                            partes.push('⚠️ TODAS LAS COLUMNAS DE TELÉFONO ESTÁN OCUPADAS');
                            mensajeAdicional = '\n\n📞 Por favor, contacte al administrador para agregar más números de teléfono.';
                        } else if (data.telefonos_error > 0) {
                            partes.push(`${data.telefonos_error} teléfono(s) no pudieron guardarse`);
                        }
                        
                        if (partes.length > 0) {
                            mensaje += '\n\n' + partes.join('\n');
                        }
                    }
                    
                    // Mostrar mensaje principal
                    alert(mensaje + mensajeAdicional);
                    
                    // Si todas las columnas están ocupadas, mostrar mensaje destacado
                    if (data.todas_columnas_ocupadas) {
                        const mensajeAdmin = '⚠️ ATENCIÓN: Todas las columnas de teléfono están ocupadas.\n\n' +
                                            'El cliente ya tiene números en todas las columnas disponibles:\n' +
                                            '- telefono\n' +
                                            '- celular2\n' +
                                            '- cel3 hasta cel11\n\n' +
                                            'Por favor, contacte al administrador del sistema para agregar más columnas de teléfono.';
                        alert(mensajeAdmin);
                    }
                    
                    // Actualizar dinámicamente la información del cliente en la primera sección
                    if (email) {
                        const emailElement = document.getElementById('email-value');
                        if (emailElement) {
                            emailElement.textContent = email;
                            emailElement.className = 'email-registrado';
                        }
                    }
                    if (direccion) {
                        const direccionElement = document.getElementById('direccion-value');
                        if (direccionElement) {
                            direccionElement.textContent = direccion;
                        }
                    }
                    if (ciudad) {
                        const ciudadElement = document.getElementById('ciudad-value');
                        if (ciudadElement) {
                            ciudadElement.textContent = ciudad;
                        }
                    }
                    
                    // Actualizar el dropdown de teléfonos con los nuevos teléfonos solo si se guardaron
                    if (data.telefonos_guardados > 0 && telefonosAdicionales.length > 0) {
                        // Filtrar solo los teléfonos que se guardaron exitosamente
                        const telefonosGuardados = telefonosAdicionales.filter((tel, index) => {
                            // Si hay teléfonos no guardados, excluirlos
                            if (data.telefonos_no_guardados && data.telefonos_no_guardados.includes(tel.trim())) {
                                return false;
                            }
                            return tel.trim() !== '';
                        });
                        
                        if (telefonosGuardados.length > 0) {
                            actualizarDropdownTelefonos(telefonosGuardados);
                        }
                    }
                    
                    // Si se guardó información exitosamente (aunque algunos teléfonos no), cerrar el modal
                    cerrarModalAgregarInfo();
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo guardar la información'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al guardar la información: ' + error.message);
            })
            .finally(() => {
                // Restaurar botón
                btnGuardar.innerHTML = textoOriginal;
                btnGuardar.disabled = false;
            });
        }
        
    </script>

     <!-- Modal para Búsqueda de Clientes -->

     <!-- Modal para Crear Producto -->
     <div id="modalCrearProducto" class="modal-overlay">
         <div class="modal-content">
             <div class="modal-header">
                 <h3><i class="fas fa-plus"></i> Agregar Nuevo Producto</h3>
                 <button type="button" class="modal-close" onclick="cerrarModalCrearProducto()">&times;</button>
             </div>
             <div class="modal-body">
                 <form id="formCrearProducto">
                     <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                     
                     <div class="form-group">
                         <label for="nombre-producto" class="form-label">Nombre del Producto:</label>
                         <input type="text" name="nombre_producto" id="nombre-producto" class="form-input" 
                                placeholder="Ej: Cuota mensual, Servicio adicional..." required>
                     </div>
                     
                     <div class="form-group">
                         <label for="monto-producto" class="form-label">Monto (opcional):</label>
                         <input type="number" name="monto" id="monto-producto" class="form-input" 
                                step="0.01" min="0" placeholder="0.00">
                     </div>
                     
                     <div class="form-group">
                         <label for="estado-producto" class="form-label">Estado Inicial:</label>
                         <select name="estado" id="estado-producto" class="form-select">
                             <option value="pendiente">Pendiente</option>
                             <option value="en_proceso">En Proceso</option>
                             <option value="pagado">Pagado</option>
                             <option value="rechazado">Rechazado</option>
                         </select>
                     </div>
                     
                     <div class="btn-container">
                         <button type="submit" class="btn btn-primary">
                             <i class="fas fa-save"></i> Crear Producto
                         </button>
                         <button type="button" class="btn btn-secondary" onclick="cerrarModalCrearProducto()">
                             <i class="fas fa-times"></i> Cancelar
                         </button>
                     </div>
                 </form>
             </div>
         </div>
     </div>

     <!-- Modal para Confirmar Declinación de Todos los Productos -->
     <div id="modalDeclinarTodos" class="modal-overlay">
         <div class="modal-content">
             <div class="modal-header">
                 <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Declinación</h3>
                 <button type="button" class="modal-close" onclick="cerrarModalDeclinarTodos()">&times;</button>
             </div>
             <div class="modal-body">
                 <p>¿Estás seguro de que deseas declinar todos los productos pendientes de este cliente?</p>
                 <p class="text-muted">Esta acción marcará todos los productos como "Rechazado" y no se puede deshacer.</p>
                 
                 <div class="form-group">
                     <label for="comentarios-declinacion" class="form-label">Comentarios (obligatorio):</label>
                     <textarea name="comentarios" id="comentarios-declinacion" class="form-input" 
                               rows="3" placeholder="Motivo de la declinación..." required></textarea>
                 </div>
                 
                 <div class="btn-container">
                     <button type="button" class="btn btn-danger" onclick="confirmarDeclinarTodos()">
                         <i class="fas fa-times-circle"></i> Sí, Declinar Todos
                     </button>
                     <button type="button" class="btn btn-secondary" onclick="cerrarModalDeclinarTodos()">
                         <i class="fas fa-times"></i> Cancelar
                     </button>
                 </div>
             </div>
         </div>
     </div>

     <!-- Modal para Agregar Información Adicional -->
     <div id="modalAgregarInfo" class="modal-overlay">
         <div class="modal-content" style="max-width: 800px; max-height: 90vh;">
             <div class="modal-header">
                 <h3><i class="fas fa-plus-circle"></i> Agregar Información Adicional del Cliente</h3>
                 <span class="close" onclick="cerrarModalAgregarInfo()">&times;</span>
             </div>
             <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                 <form id="formAgregarInfo">
                     <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                     
                     <!-- Información de Contacto -->
                     <div class="form-section">
                         <h4 class="form-title">
                             <i class="fas fa-address-book"></i> Información de Contacto Adicional
                         </h4>
                         
                         <div class="form-row">
                             <div class="form-group">
                                 <label for="nuevo_email" class="form-label">Correo Electrónico:</label>
                                 <input type="email" name="nuevo_email" id="nuevo_email" class="form-input" 
                                        placeholder="Ej: cliente@email.com">
                             </div>
                             
                             <div class="form-group">
                                 <label for="nueva_direccion" class="form-label">Dirección:</label>
                                 <input type="text" name="nueva_direccion" id="nueva_direccion" class="form-input" 
                                        placeholder="Ej: Calle 123 #45-67">
                             </div>
                         </div>
                         
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nueva_ciudad" class="form-label">Ciudad:</label>
                                <input type="text" name="nueva_ciudad" id="nueva_ciudad" class="form-input" 
                                       placeholder="Ej: Bogotá">
                            </div>
                        </div>
                        
                        <!-- Sección de teléfonos adicionales -->
                        <div class="form-section">
                            <h5 class="form-subtitle">
                                <i class="fas fa-phone"></i> Teléfonos Adicionales
                            </h5>
                            <div id="telefonos-adicionales-container">
                                <!-- Los teléfonos se agregarán dinámicamente aquí -->
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarTelefono()">
                                <i class="fas fa-plus"></i> Agregar otro teléfono
                            </button>
                        </div>
                     </div>
                     
                     
                     <div class="btn-container">
                         <button type="submit" class="btn btn-success">
                             <i class="fas fa-save"></i> Guardar Información
                         </button>
                         <button type="button" class="btn btn-secondary" onclick="cerrarModalAgregarInfo()">
                             <i class="fas fa-times"></i> Cancelar
                         </button>
                     </div>
                 </form>
             </div>
         </div>
     </div>
     

    <?php if ($tieneTelefono && !empty($datosTelefono['extension_telefono'])): ?>
    <!-- Scripts del Softphone WebRTC -->
    <script>
        // Configuración del softphone
        const webrtcConfig = {
            wss_server: '<?php echo $webrtcConfig['wss_server']; ?>',
            sip_domain: '<?php echo $webrtcConfig['sip_domain']; ?>',
            extension: '<?php echo htmlspecialchars($datosTelefono['extension_telefono'] ?? ''); ?>',
            password: '<?php echo htmlspecialchars($datosTelefono['clave_webrtc'] ?? ''); ?>',
            display_name: '<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Asesor'); ?>',
            iceServers: <?php echo json_encode($webrtcConfig['iceServers']); ?>,
            debug_mode: <?php echo $webrtcConfig['debug_mode'] ? 'true' : 'false'; ?>,
            base_path: '<?php echo $basePath; ?>'
        };

        // Función para inicializar el softphone
        function inicializarSoftphoneEmerCobranza() {
            // Verificar que SIP.js esté cargado
            if (typeof SIP === 'undefined') {
                console.warn('⚠️ [Softphone] SIP.js aún no está cargado, reintentando en 100ms...');
                setTimeout(inicializarSoftphoneEmerCobranza, 100);
                return;
            }

            if (typeof WebRTCSoftphone === 'undefined') {
                console.error('❌ [Softphone] WebRTCSoftphone no está cargado');
                return;
            }

            // CRÍTICO: Verificar si el softphone ya está inicializado
            // Esto evita reinicializar y perder llamadas activas al cambiar de cliente
            if (window.webrtcSoftphone) {
                console.warn('⚠️ [Softphone] Ya existe una instancia del softphone');
                return;
            }

            // Inicializar el softphone solo si no existe
            try {
                window.webrtcSoftphone = new WebRTCSoftphone(webrtcConfig);
                console.log('✅ [Softphone] Inicializado correctamente');
            } catch (error) {
                console.error('❌ [Softphone] Error al inicializar:', error);
            }
        }

        // Llamar desde WebRTC
        async function llamarDesdeWebRTC(numero) {
            if (!window.webrtcSoftphone) {
                console.error('❌ [Softphone] No está inicializado');
                alert('El softphone no está disponible. Por favor, espera a que se conecte.');
                return;
            }

            // Validar que el número no esté vacío
            if (!numero || numero.trim() === '') {
                alert('Por favor, selecciona un número de teléfono.');
                return;
            }

            // Limpiar el número (solo dígitos)
            const numeroLimpio = numero.toString().replace(/\D/g, '');

            if (numeroLimpio === '') {
                alert('El número de teléfono no es válido.');
                return;
            }

            console.log('📞 [Llamar] Iniciando llamada al número:', numeroLimpio);

            try {
                // Usar callNumber() que establece el número y luego llama automáticamente
                if (typeof window.webrtcSoftphone.callNumber === 'function') {
                    await window.webrtcSoftphone.callNumber(numeroLimpio);
                    console.log('✅ [Llamar] Llamada iniciada correctamente');
                } else if (typeof window.webrtcSoftphone.setNumber === 'function' && typeof window.webrtcSoftphone.makeCall === 'function') {
                    // Fallback: establecer número y luego llamar
                    window.webrtcSoftphone.setNumber(numeroLimpio);
                    await window.webrtcSoftphone.makeCall();
                    console.log('✅ [Llamar] Llamada iniciada usando setNumber + makeCall');
                } else {
                    // Último fallback: establecer currentNumber directamente
                    window.webrtcSoftphone.currentNumber = numeroLimpio;
                    if (typeof window.webrtcSoftphone._updateNumberDisplay === 'function') {
                        window.webrtcSoftphone._updateNumberDisplay();
                    }
                    await window.webrtcSoftphone.makeCall();
                    console.log('✅ [Llamar] Llamada iniciada usando currentNumber directo');
                }
            } catch (error) {
                console.error('❌ [Llamar] Error al iniciar llamada:', error);
                alert('Error al iniciar la llamada: ' + (error.message || 'Error desconocido'));
            }
        }

        // Esperar a que todos los scripts estén cargados
        let intentosEspera = 0;
        const maxIntentos = 50; // 5 segundos máximo (50 * 100ms)

        function esperarScriptsYInicializar() {
            // CRÍTICO: Verificar PRIMERO si el softphone ya está inicializado
            // Esto previene reinicializaciones cuando se cambia de cliente
            if (window.webrtcSoftphone && typeof window.webrtcSoftphone === 'object') {
                console.log('⚠️ [Softphone] El softphone ya está inicializado. No se reinicializará.');
                return; // Salir inmediatamente si ya está inicializado
            }

            intentosEspera++;

            // Verificar que SIP.js esté disponible
            if (typeof SIP === 'undefined') {
                if (intentosEspera < maxIntentos) {
                    if (intentosEspera % 10 === 0) {
                        console.log(`⏳ [Softphone] Esperando a que SIP.js se cargue... (intento ${intentosEspera}/${maxIntentos})`);
                    }
                    setTimeout(esperarScriptsYInicializar, 100);
                } else {
                    console.error('❌ [Softphone] Timeout: SIP.js no se cargó después de 5 segundos');
                }
                return;
            }

            // Verificar que WebRTCSoftphone esté disponible
            if (typeof WebRTCSoftphone === 'undefined') {
                if (intentosEspera < maxIntentos) {
                    if (intentosEspera % 10 === 0) {
                        console.log(`⏳ [Softphone] Esperando a que softphone-web.js se cargue... (intento ${intentosEspera}/${maxIntentos})`);
                    }
                    setTimeout(esperarScriptsYInicializar, 100);
                } else {
                    console.error('❌ [Softphone] Timeout: softphone-web.js no se cargó después de 5 segundos');
                }
                return;
            }

            // Verificar que el contenedor exista
            const container = document.getElementById('webrtc-softphone');
            if (!container) {
                if (intentosEspera < maxIntentos) {
                    setTimeout(esperarScriptsYInicializar, 100);
                } else {
                    console.error('❌ [Softphone] No se encontró el contenedor #webrtc-softphone');
                }
                return;
            }

            // CRÍTICO: Verificar NUEVAMENTE antes de inicializar (doble verificación)
            // Esto previene condiciones de carrera si se llama múltiples veces
            if (window.webrtcSoftphone && typeof window.webrtcSoftphone === 'object') {
                console.log('⚠️ [Softphone] El softphone ya está inicializado (verificación final). No se reinicializará.');
                return;
            }

            // Todo está listo, inicializar
            console.log('✅ [Softphone] Todos los scripts están cargados, inicializando...');
            inicializarSoftphoneEmerCobranza();
        }

        // Función para inicializar cuando los scripts estén listos
        window.inicializarSoftphoneCuandoListo = function () {
            // Resetear contador de intentos
            intentosEspera = 0;
            // Esperar un momento adicional para asegurar que todo esté completamente cargado
            setTimeout(function () {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', esperarScriptsYInicializar);
                } else {
                    esperarScriptsYInicializar();
                }
            }, 100);
        };

        // Cargar asesor-gestionar.js (ahora siempre recarga la página al cambiar de cliente)
        // Scripts - CARGAR PRIMERO asesor-gestionar.js para que esté disponible
        (function () {
            console.log('📦 [Carga Scripts] Iniciando carga de scripts...');

            // Paso 1: Cargar asesor-gestionar.js PRIMERO (sin dependencias)
            const asesorScript = document.createElement('script');
            asesorScript.src = 'assets/js/asesor-gestionar.js';
            asesorScript.onload = function () {
                console.log('✅ [Carga Scripts] asesor-gestionar.js cargado');

                // Paso 2: Cargar SIP.js
                const sipScript = document.createElement('script');
                sipScript.src = 'assets/js/sip.min.js';
                sipScript.onload = function () {
                    console.log('✅ [Carga Scripts] SIP.js cargado');

                    // Paso 3: Cargar softphone-web.js
                    const softphoneScript = document.createElement('script');
                    softphoneScript.src = 'assets/js/softphone-web.js?v=' + new Date().getTime();
                    softphoneScript.onload = function () {
                        console.log('✅ [Carga Scripts] softphone-web.js cargado');

                        // Paso 4: Inicializar softphone si está configurado
                        if (typeof window.inicializarSoftphoneCuandoListo === 'function') {
                            window.inicializarSoftphoneCuandoListo();
                        }
                    };
                    softphoneScript.onerror = function () {
                        console.error('❌ [Carga Scripts] Error al cargar softphone-web.js');
                    };
                    document.head.appendChild(softphoneScript);
                };
                sipScript.onerror = function () {
                    console.error('❌ [Carga Scripts] Error al cargar SIP.js, intentando CDN...');
                    const sipScriptCDN = document.createElement('script');
                    sipScriptCDN.src = 'https://cdn.jsdelivr.net/npm/sip.js@0.20.0/dist/sip.min.js';
                    sipScriptCDN.onload = function () {
                        console.log('✅ [Carga Scripts] SIP.js cargado desde CDN');
                        const softphoneScript = document.createElement('script');
                        softphoneScript.src = 'assets/js/softphone-web.js?v=' + new Date().getTime();
                        softphoneScript.onload = function () {
                            console.log('✅ [Carga Scripts] softphone-web.js cargado');
                            if (typeof window.inicializarSoftphoneCuandoListo === 'function') {
                                window.inicializarSoftphoneCuandoListo();
                            }
                        };
                        document.head.appendChild(softphoneScript);
                    };
                    sipScriptCDN.onerror = function () {
                        console.error('❌ [Carga Scripts] Error: No se pudo cargar SIP.js');
                    };
                    document.head.appendChild(sipScriptCDN);
                };
                document.head.appendChild(sipScript);
            };
            asesorScript.onerror = function () {
                console.error('❌ [Carga Scripts] ERROR CRÍTICO: No se pudo cargar asesor-gestionar.js');
                alert('Error crítico: No se pudo cargar el script necesario. Por favor, recarga la página.');
            };
            document.head.appendChild(asesorScript);
        })();

        // Integrar con los números de teléfono existentes
        // Sobrescribir la función llamarCliente si existe para usar el softphone
        if (typeof window.llamarCliente === 'function') {
            const llamarClienteOriginal = window.llamarCliente;
            window.llamarCliente = function(numero) {
                if (window.webrtcSoftphone && window.webrtcSoftphone.status === 'connected') {
                    llamarDesdeWebRTC(numero);
                } else {
                    llamarClienteOriginal(numero);
                }
            };
        } else {
            window.llamarCliente = function(numero) {
                if (window.webrtcSoftphone && window.webrtcSoftphone.status === 'connected') {
                    llamarDesdeWebRTC(numero);
                } else {
                    alert('El softphone no está disponible. Por favor, espera a que se conecte.');
                }
            };
        }
    </script>
    <?php else: ?>
    <script>
        console.warn('⚠️ [Softphone] Usuario sin teléfono configurado');
    </script>
    <?php endif; ?>

</body>
</html>

