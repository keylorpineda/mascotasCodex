<?php
namespace App\Libraries;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use App\Entities\Parametros\CorreosEntity;
class Mailer
{
    public array $mail_status = [];
    /**
     * Si smtp_keep_alive === true:
     *  se mantiene abierta la conexión SMTP
     * Si smtp_keep_alive === false:
     *  se cierra la conexión SMTP luego de cada proceso
    */
    public bool $smtp_keep_alive = false;

    public string $name = "";
    public bool $use_smtp = true;

    public PHPMailer $mail;

    private static function get_correo(?int $ID_CORREO): CorreosEntity
    {
        $CorreosModel = model("Parametros\CorreosModel")
            ->select("SMTP", "USUARIO", "CONTRASENA", "PUERTO")
            ->where("ID_CORREO", $ID_CORREO)
            ->orWhere("PREDEFINIDO", true)
            ->orderBy("PREDEFINIDO", "ASC")
            ->limit(1);
        return $CorreosModel->getFirstRow();
    }

    /**
     * Validación completa de datos de entrada
     */
    private function validateMailData(array $data): array
    {
        // Validar campos requeridos
        if (empty($data['correo'])) {
            return ["status" => false, "message" => "Email destinatario es requerido"];
        }
        
        if (empty($data['asunto'])) {
            return ["status" => false, "message" => "Asunto del correo es requerido"];
        }
        
        if (empty($data['cuerpo'])) {
            return ["status" => false, "message" => "Cuerpo del correo es requerido"];
        }

        // Validar formato de emails principales
        $emails = is_array($data['correo']) ? $data['correo'] : [$data['correo']];
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ["status" => false, "message" => "Email destinatario inválido: {$email}"];
            }
        }

        // Validar formato de reply-to
        if (isset($data['reply-to']) && !empty($data['reply-to'])) {
            if (!filter_var($data['reply-to'], FILTER_VALIDATE_EMAIL)) {
                return ["status" => false, "message" => "Email reply-to inválido: {$data['reply-to']}"];
            }
        }

        // Validar emails CC
        if (isset($data['CC'])) {
            $ccEmails = is_array($data['CC']) ? $data['CC'] : [$data['CC']];
            foreach ($ccEmails as $email) {
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return ["status" => false, "message" => "Email CC inválido: {$email}"];
                }
            }
        }

        // Validar emails BCC
        if (isset($data['BCC'])) {
            $bccEmails = is_array($data['BCC']) ? $data['BCC'] : [$data['BCC']];
            foreach ($bccEmails as $email) {
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return ["status" => false, "message" => "Email BCC inválido: {$email}"];
                }
            }
        }

        // Validar archivos adjuntos
        if (isset($data['adjunto']) && !empty($data['adjunto'])) {
            if (is_array($data['adjunto'])) {
                foreach ($data['adjunto'] as $value) {
                    if (is_array($value)) {
                        if (!isset($value['ruta']) || !file_exists($value['ruta'])) {
                            return ["status" => false, "message" => "Archivo adjunto no encontrado: " . ($value['ruta'] ?? 'ruta no especificada')];
                        }
                    } else {
                        if (!file_exists($value)) {
                            return ["status" => false, "message" => "Archivo adjunto no encontrado: {$value}"];
                        }
                    }
                }
            } else {
                if (!file_exists($data['adjunto'])) {
                    return ["status" => false, "message" => "Archivo adjunto no encontrado: {$data['adjunto']}"];
                }
            }
        }

        // Validar imágenes embebidas
        if (isset($data['img'])) {
            if (is_array($data['img']) && isset($data['img'][0])) {
                foreach ($data['img'] as $value) {
                    if (!isset($value['path']) || !isset($value['cid'])) {
                        return ["status" => false, "message" => "Imagen embebida requiere 'path' y 'cid'"];
                    }
                    if (!file_exists($value['path'])) {
                        return ["status" => false, "message" => "Imagen embebida no encontrada: {$value['path']}"];
                    }
                }
            } else {
                if (!isset($data['img']['path']) || !isset($data['img']['cid'])) {
                    return ["status" => false, "message" => "Imagen embebida requiere 'path' y 'cid'"];
                }
                if (!file_exists($data['img']['path'])) {
                    return ["status" => false, "message" => "Imagen embebida no encontrada: {$data['img']['path']}"];
                }
            }
        }

        // Validar string attachments
        if (isset($data['stratt']) && !empty($data['stratt'])) {
            if (is_array($data['stratt']) && isset($data['stratt'][0])) {
                foreach ($data['stratt'] as $value) {
                    if (!isset($value["doc"]) || !isset($value["name"])) {
                        return ["status" => false, "message" => "String attachment requiere 'doc' y 'name'"];
                    }
                    if (empty($value["doc"])) {
                        return ["status" => false, "message" => "String attachment 'doc' no puede estar vacío"];
                    }
                }
            } else if (!isset($data['stratt']["doc"]) || !isset($data['stratt']["name"])) {
                return ["status" => false, "message" => "String attachment requiere 'doc' y 'name'"];
            } else if (empty($data['stratt']["doc"])) {
                return ["status" => false, "message" => "String attachment 'doc' no puede estar vacío"];
            }
        }

        return ["status" => true, "message" => "Datos válidos"];
    }

    /**
     * Configurar SMTP o mail()
     */
    private function configureMailTransport(CorreosEntity $credenciales): void
    {
        if ($this->use_smtp === true) {
            // Usar SMTP
            $this->mail->isSMTP();
            $this->mail->Host     = $credenciales->getSMTP();
            $this->mail->Username = $credenciales->getUSUARIO();
            $this->mail->Password = $credenciales->getCONTRASENA();
            $this->mail->Port     = $credenciales->getPUERTO();
            $this->mail->SMTPAuth = TRUE;
            $this->mail->SMTPSecure = "ssl";
            $this->mail->SMTPKeepAlive = $this->smtp_keep_alive;
        } else {
            // Usar PHP mail()
            $this->mail->isMail();
        }
    }

    /**
     * Configurar propiedades básicas del mail
     */
    private function configureMailProperties(array $data, CorreosEntity $credenciales): void
    {
        $this->mail->Timeout = 30;
        $this->mail->CharSet = "UTF-8";
        $this->mail->IsHTML(true);

        $name = $data['name'] ?? "";
        $this->mail->setFrom($credenciales->getUSUARIO(), $name);
        
        $this->mail->Subject = $data['asunto'];
        $this->mail->Body = $data['cuerpo'];
    }

    /**
     * Configurar destinatarios principales
     */
    private function configureRecipients(array $data): void
    {
        if (is_array($data['correo'])) {
            foreach ($data['correo'] as $value) {
                $this->mail->AddAddress($value);
            }
        } else {
            $this->mail->AddAddress($data['correo']);
        }

        // Reply-to
        if (isset($data['reply-to']) && !empty($data['reply-to'])) {
            $this->mail->addReplyTo($data['reply-to']);
        }
    }

    /**
     * Configurar copias (CC y BCC)
     */
    private function configureCopies(array $data): void
    {
        // Copia (CC)
        if (isset($data['CC'])) {
            if(is_array($data['CC'])) {
                foreach ($data['CC'] as $value) {
                    if (!empty($value)) {
                        $this->mail->addCC($value);
                    }
                }
            } else {
                if (!empty($data['CC'])) {
                    $this->mail->addCC($data['CC']);
                }
            }
        }

        // Copia oculta (BCC)
        if (isset($data['BCC'])) {
            if(is_array($data['BCC'])) {
                foreach ($data['BCC'] as $value) {
                    if (!empty($value)) {
                        $this->mail->addBCC($value);
                    }
                }
            } else {
                if (!empty($data['BCC'])) {
                    $this->mail->addBCC($data['BCC']);
                }
            }
        }
    }

    /**
     * Configurar imágenes embebidas
     */
    private function configureEmbeddedImages(array $data): void
    {
        if (!isset($data['img'])) {
            return;
        }

        if (is_array($data['img']) && isset($data['img'][0])) {
            foreach ($data['img'] as $value) {
                if (isset($value['path']) && isset($value['cid'])) {
                    $this->mail->AddEmbeddedImage($value['path'], $value['cid']);
                }
            }
        } else {
            if (isset($data['img']['path']) && isset($data['img']['cid'])) {
                $this->mail->AddEmbeddedImage($data['img']['path'], $data['img']['cid']);
            }
        }
    }

    /**
     * Configurar archivos adjuntos
     */
    private function configureAttachments(array $data): void
    {
        if (!isset($data['adjunto']) || empty($data['adjunto'])) {
            return;
        }

        if (is_array($data['adjunto'])) {
            foreach ($data['adjunto'] as $value) {
                if (is_array($value)) {
                    if (isset($value['ruta']) && file_exists($value['ruta'])) {
                        $nombre = $value['nombre'] ?? basename($value['ruta']);
                        $this->mail->addAttachment($value['ruta'], $nombre);
                    }
                } else {
                    if (file_exists($value)) {
                        $this->mail->addAttachment($value);
                    }
                }
            }
        } else {
            if (file_exists($data['adjunto'])) {
                $this->mail->addAttachment($data['adjunto']);
            }
        }
    }

    /**
     * Configurar archivos adjuntos desde string
     */
    private function configureStringAttachments(array $data): void
    {
        if (!isset($data['stratt']) || empty($data['stratt'])) {
            return;
        }

        if (is_array($data['stratt']) && isset($data['stratt'][0])) {
            // Es array de attachments
            foreach ($data['stratt'] as $value) {
                if (isset($value["doc"]) && isset($value["name"]) && !empty($value["doc"])) {
                    $this->mail->AddStringAttachment($value["doc"], $value["name"], 'base64', $value["mime"] ?? "application/pdf");
                }
            }
        } else if (isset($data['stratt']["doc"]) && isset($data['stratt']["name"]) && !empty($data['stratt']["doc"])) {
            // Es un solo attachment
            $this->mail->AddStringAttachment($data['stratt']["doc"], $data['stratt']["name"], 'base64', $data['stratt']["mime"] ?? "application/pdf");
        }
    }

    /**
     * Ejecutar el envío y manejar resultado
     */
    private function executeSend(): array
    {
        try {
            $exito = $this->mail->send();
            if (!$exito) {
                throw new Exception($this->mail->ErrorInfo);
            }
            
            return [ 
                "status" => true, 
                "message" => "Correo enviado correctamente"
            ];
        } catch (\PHPMailer\PHPMailer\Exception | \Exception $e) {
            $errorMessage = $this->mail->ErrorInfo ?: $e->getMessage();
            
            error_log(
                "Mailer Error: {$errorMessage}".PHP_EOL,
                3,
                base_dir('writer/logs/mailer_errors.log')
            );
            
            return [ 
                "status" => false, 
                "message" => "No se pudo enviar el mensaje. Error: {$errorMessage}"
            ];
        }
    }

    /**
     * MÉTODO PRINCIPAL - Orquesta todo el proceso manteniendo compatibilidad total
     */
    public function send_mail(array $data, ?int $ID_CORREO = null): array
    {
        $this->mail ??= new PHPMailer(true);

        // Validar datos de entrada
        $validationResult = $this->validateMailData($data);
        if (!$validationResult['status']) {
            $this->mail_status = $validationResult;
            return $this->mail_status;
        }

        // Obtener credenciales
        $CREDENCIALES = self::get_correo($ID_CORREO);
        
        // Inicializar PHPMailer si no existe
        $this->mail ??= new PHPMailer(true);
        
        // Cada aspecto en su método
        $this->configureMailTransport($CREDENCIALES);
        $this->configureMailProperties($data, $CREDENCIALES);
        $this->configureRecipients($data);
        $this->configureCopies($data);
        $this->configureEmbeddedImages($data);
        $this->configureAttachments($data);
        $this->configureStringAttachments($data);
        
        // Ejecutar envío
        $this->mail_status = $this->executeSend();
        
        return $this->mail_status;
    }

    /**
     * Reset que respeta la configuración smtp_keep_alive
     */
    public function reset(): void
    {
        if (!$this->smtp_keep_alive) {
            $this->mail->smtpClose();
        }
        
        // Limpiar destinatarios y adjuntos pero mantener configuración SMTP
        $this->mail->clearAddresses();
        $this->mail->clearAttachments();
        $this->mail->clearCCs();
        $this->mail->clearBCCs();
        $this->mail->clearReplyTos();
        
        // Solo crear nueva instancia si no mantenemos conexión activa
        if (!$this->smtp_keep_alive) {
            $this->mail = new PHPMailer(true);
        }
    }
    
    /**
     * Método para cerrar conexión explícitamente cuando sea necesario
     */
    public function closeConnection(): void
    {
        if (isset($this->mail)) {
            $this->mail->smtpClose();
        }
    }
    
    /**
     * Método para verificar el estado de la conexión
     */
    public function isConnected(): bool
    {
        return isset($this->mail) && $this->mail->getSMTPInstance() !== null;
    }

    /**
     * Método para validar solo un email
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Método para obtener último error detallado
     */
    public function getLastError(): ?string
    {
        return isset($this->mail) ? $this->mail->ErrorInfo : null;
    }

    /**
     * Método para verificar si un archivo existe y es legible
     */
    public function validateAttachmentFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ["valid" => false, "error" => "Archivo no encontrado: {$filePath}"];
        }
        
        if (!is_readable($filePath)) {
            return ["valid" => false, "error" => "Archivo no legible: {$filePath}"];
        }
        
        return ["valid" => true, "error" => null];
    }
}