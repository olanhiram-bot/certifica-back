<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recupera tu cuenta de Certifica</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #d9e2ef;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(110deg,#234b78 0%,#1f5fc8 70%,#2f66de 100%);padding:28px 32px;color:#ffffff;">
                            <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.72);">Certifica</div>
                            <h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;font-weight:800;">Recupera tu cuenta</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.55;">Recibimos una solicitud para cambiar la contraseña de tu cuenta.</p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.55;color:#526173;">Para continuar, usa el siguiente botón. El enlace estará disponible durante 60 minutos.</p>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="border-radius:8px;background:#2563eb;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:13px 22px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;">Crear nueva contraseña</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#667085;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                            <p style="margin:0 0 24px;font-size:12px;line-height:1.5;word-break:break-all;color:#2563eb;">{{ $resetUrl }}</p>

                            <div style="background:#f8fafc;border:1px solid #e5eaf2;border-radius:8px;padding:14px 16px;">
                                <p style="margin:0;font-size:13px;line-height:1.5;color:#526173;">Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña actual seguirá funcionando.</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;background:#f8fafc;border-top:1px solid #e5eaf2;color:#667085;font-size:12px;line-height:1.5;">
                            Este mensaje fue enviado automáticamente por Certifica. No respondas a este correo.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
