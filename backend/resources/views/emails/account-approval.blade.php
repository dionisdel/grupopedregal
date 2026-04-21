<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuenta Aprobada</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #F5F5F5; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #FFFFFF; border-radius: 8px; overflow: hidden;">
        <div style="background-color: #333333; padding: 20px; text-align: center;">
            <h1 style="color: #FFFFFF; margin: 0; font-size: 24px;">Grupo Pedregal</h1>
        </div>
        <div style="padding: 30px;">
            <h2 style="color: #333333;">¡Tu cuenta ha sido aprobada!</h2>
            <p style="color: #555;">Hola {{ $userName }},</p>
            <p style="color: #555;">Tu cuenta en el portal de Grupo Pedregal ha sido aprobada. Ya puedes acceder al área de cliente con tus credenciales.</p>
            <p style="color: #555;">Email de acceso: <strong>{{ $userEmail }}</strong></p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}" style="background-color: #E8751A; color: #FFFFFF; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">ACCEDER AL PORTAL</a>
            </div>
            <p style="color: #999; font-size: 12px;">Si no solicitaste esta cuenta, puedes ignorar este mensaje.</p>
        </div>
        <div style="background-color: #333333; padding: 15px; text-align: center;">
            <p style="color: #999; font-size: 12px; margin: 0;">&copy; {{ date('Y') }} Grupo Pedregal. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
