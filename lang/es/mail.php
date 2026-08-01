<?php

return [
    'verify_email' => [
        'subject' => 'Un paso rápido — verifica tu correo',
        'heading' => 'Nos alegra que estés aquí',
        'hi' => 'Hola :name,',
        'body' => 'Gracias por unirte a azshrtr. Confirma tu correo con el código de abajo o el botón — y empieza a crear y compartir enlaces.',
        'cta' => 'Verificar correo',
        'muted' => 'Este enlace y código caducan en 24 horas. Si no te registraste, puedes ignorar este mensaje con tranquilidad.',
        'thanks' => 'Bienvenido/a,',
    ],

    'email_otp' => [
        'subject' => 'Tu código para entrar en azshrtr',
        'heading' => 'Aquí tienes tu código',
        'hi' => 'Hola :name,',
        'body' => 'Usa este código de un solo uso para iniciar sesión. Nos alegra verte de nuevo.',
        'muted' => 'Este código caduca en 10 minutos. Si no lo pediste, ignora este correo — tu cuenta sigue segura.',
        'thanks' => 'Nos vemos dentro,',
    ],

    'password_reset' => [
        'subject' => 'Vamos a devolverte el acceso a azshrtr',
        'heading' => 'Restablece tu contraseña',
        'hi' => 'Hola :name,',
        'body' => 'No pasa nada — nos pasa a todos. Recibimos una solicitud para restablecer tu contraseña. Elige una nueva abajo y estarás dentro en un momento.',
        'cta' => 'Elegir una nueva contraseña',
        'muted' => 'Este enlace caduca en :minutes minutos. Si no pediste un restablecimiento, tu contraseña no cambia.',
        'thanks' => 'Estamos aquí si nos necesitas,',
    ],

    'welcome' => [
        'subject' => 'Bienvenido/a a azshrtr — nos alegra tenerte',
        'heading' => 'Bienvenido/a a bordo',
        'hi' => 'Hola :name,',
        'body' => 'Tu cuenta está lista. Crea enlaces cortos, códigos QR y compártelos desde tu espacio — tenemos ganas de ver lo que creas.',
        'cta' => 'Abrir la consola',
        'muted' => 'Si no creaste esta cuenta, puedes ignorar este correo.',
        'thanks' => 'Qué gusto tenerte,',
    ],

    'sign_in_activity' => [
        'subject' => 'Solo comprobando — nuevo inicio de sesión',
        'heading' => 'Detectamos un nuevo inicio de sesión',
        'hi' => 'Hola :name,',
        'body' => 'Alguien inició sesión en tu cuenta de azshrtr. Si fuiste tú, todo bien. Si no, vamos a protegerla juntos.',
        'time' => 'Cuándo: :time',
        'ip' => 'Dirección IP: :ip',
        'device' => 'Dispositivo: :device',
        'muted' => 'Si no te suena, restablece tu contraseña ahora — te ayudamos a mantener tu espacio seguro.',
        'cta' => 'Proteger mi cuenta',
        'thanks' => 'Cuidando de ti,',
        'unknown_device' => 'Dispositivo desconocido',
        'unknown_ip' => 'Desconocida',
    ],

    'usage_warning' => [
        'subject' => 'Aviso amable — vas por el :threshold% de tu límite de :metric',
        'heading' => 'Te estás acercando',
        'hi' => 'Hola :name,',
        'body' => '**:organization** ha usado **:used** de **:limit** :metric (:percent%) en el plan :plan. Sin prisa — solo un recordatorio para que nada te sorprenda.',
        'threshold' => 'Cruzaste el umbral del **:threshold%**.',
        'cta' => 'Ver planes',
        'muted' => 'Pro te da más espacio cuando quieras — o puedes esperar al próximo reinicio mensual.',
        'thanks' => 'Ánimo contigo,',
        'metric_links' => 'enlaces',
        'metric_qr' => 'códigos QR',
    ],

    'usage_limit' => [
        'subject' => 'Llegaste al límite de :metric — estamos contigo',
        'heading' => 'Has llegado a un límite del plan',
        'hi' => 'Hola :name,',
        'body' => '**:organization** ha usado todos los :metric del plan :plan (**:used** / **:limit**). Eso suele ser buena señal — Pro te da espacio ilimitado cuando quieras.',
        'cta' => 'Mejorar a Pro',
        'muted' => 'Puedes mejorar cuando quieras, o esperar al reinicio mensual. En cualquier caso, nos alegra que crezcas con nosotros.',
        'thanks' => 'Gracias por crecer con nosotros,',
        'metric_links' => 'enlaces',
        'metric_qr' => 'códigos QR',
    ],

    'subscription_upgraded' => [
        'subject' => 'Bienvenido/a a Pro — nos hace mucha ilusión',
        'heading' => 'Ya estás en Pro',
        'hi' => 'Hola :name,',
        'body' => 'Qué buena noticia — **:organization** está en Pro. Disfruta de enlaces y QR ilimitados, dominios personalizados y enlaces con contraseña. Estamos emocionados de crecer contigo.',
        'cta' => 'Abrir tu espacio',
        'muted' => 'Se factura una vez al año. Puedes gestionar tu plan cuando quieras desde Facturación.',
        'thanks' => 'Gracias por confiar en nosotros,',
    ],

    'subscription_downgrade_scheduled' => [
        'subject' => 'Te echaremos de menos en Pro — :organization',
        'heading' => 'Lamentamos verte partir',
        'hi' => 'Hola :name,',
        'body' => 'Has pedido pasar **:organization** a Free. Está bien — seguirás con todo lo de Pro hasta el final del periodo de facturación actual.',
        'effective' => 'Free empieza el **:date**.',
        'cta' => 'Quedarme en Pro',
        'muted' => '¿Cambiaste de opinión? Puedes quedarte en Pro con un clic antes de esa fecha — de verdad nos encantaría que te quedes.',
        'thanks' => 'Con cariño,',
    ],

    'subscription_downgraded' => [
        'subject' => 'Lamentamos verte partir — :organization',
        'heading' => 'Lamentamos verte partir',
        'hi' => 'Hola :name,',
        'body' => '**:organization** está ahora en Free. Gracias por haber probado Pro; la puerta sigue abierta — puedes crear enlaces y QR dentro de los límites Free, y Pro estará aquí cuando quieras espacio ilimitado otra vez.',
        'cta' => 'Volver a Pro',
        'muted' => 'Cuando quieras, enlaces ilimitados, dominios personalizados y enlaces con contraseña están a un clic. Nos encantaría darte la bienvenida de nuevo.',
        'thanks' => 'Gracias por haber estado con nosotros,',
    ],

    'billing_payment_succeeded' => [
        'subject' => 'Gracias — Pro está activo en :organization',
        'heading' => 'Todo listo — gracias',
        'hi' => 'Hola :name,',
        'body' => 'Tu pago se completó y **:organization** está en Pro. Gracias por tu apoyo; tenemos ganas de ver lo que creas.',
        'amount' => 'Importe cobrado: **:amount**.',
        'cta' => 'Abrir Facturación',
        'muted' => 'Puedes ver los recibos cuando quieras desde Facturación.',
        'thanks' => 'Gracias por apoyar azshrtr,',
    ],

    'billing_payment_failed' => [
        'subject' => 'Terminemos tu mejora a Pro — :organization',
        'heading' => 'Ese pago no se completó',
        'hi' => 'Hola :name,',
        'body' => 'No pudimos completar el pago de **:organization**. No se cobró nada y tu espacio no cambió — sin estrés. Inténtalo de nuevo cuando quieras; estamos contigo.',
        'amount' => 'Importe intentado: **:amount**.',
        'cta' => 'Probar Pro de nuevo',
        'muted' => 'Otra tarjeta o método suele ayudar. Tómate tu tiempo — estaremos listos cuando tú lo estés.',
        'thanks' => 'Ánimo,',
    ],

    'billing_checkout_abandoned' => [
        'subject' => '¿Sigues pensando en Pro? Estamos aquí — :organization',
        'heading' => 'Tu mejora a Pro te espera',
        'hi' => 'Hola :name,',
        'body' => 'Empezaste a mejorar **:organization** a Pro pero no terminaste. Totalmente bien — no se cobró nada. Cuando quieras, continúa donde lo dejaste.',
        'cta' => 'Seguir con Pro',
        'muted' => 'Si no querías mejorar, ignora este correo. Sin ninguna presión.',
        'thanks' => 'Estamos de tu lado,',
    ],

    'billing_refund_initiated' => [
        'subject' => 'Tu reembolso está en camino — :organization',
        'heading' => 'Hemos iniciado tu reembolso',
        'hi' => 'Hola :name,',
        'body' => 'Hemos iniciado un reembolso para **:organization**. Sabemos que el dinero importa — los bancos suelen tardar unos días hábiles en mostrarlo.',
        'amount' => 'Importe del reembolso: **:amount**.',
        'cta' => 'Abrir Facturación',
        'muted' => 'Te enviaremos otro correo cuando el reembolso se complete.',
        'thanks' => 'Gracias por tu paciencia,',
    ],

    'billing_refund_succeeded' => [
        'subject' => 'Tu reembolso está completo — :organization',
        'heading' => 'Tu reembolso está completo',
        'hi' => 'Hola :name,',
        'body' => 'Buenas noticias — el reembolso de **:organization** se ha completado. Debería aparecer pronto en tu método de pago original. Sentimos que las cosas no salieran como esperabas.',
        'amount' => 'Reembolsado: **:amount**.',
        'cta' => 'Abrir Facturación',
        'muted' => 'Si algún día quieres volver, Pro estará listo. Puedes gestionar la facturación cuando quieras desde tu espacio.',
        'thanks' => 'Te deseamos lo mejor,',
    ],

    'invitation_role' => [
        'owner' => 'Propietario',
        'admin' => 'Admin',
        'member' => 'Miembro',
    ],

    'invitation_invited' => [
        'subject' => 'Te invitaron a unirte a :organization',
        'heading' => 'Has sido invitado/a',
        'hi' => 'Hola :name,',
        'body' => '**:inviter** te invitó a unirte a **:organization** como **:role**. Nos encantaría tenerte en el equipo — acepta abajo para empezar.',
        'expires' => 'Esta invitación caduca el **:date**.',
        'cta' => 'Aceptar invitación',
        'muted' => 'Debes iniciar sesión con este correo para unirte. Si no esperabas esto, puedes ignorar el mensaje.',
        'thanks' => 'Con ganas de tenerte,',
    ],

    'invitation_resent' => [
        'subject' => 'Recordatorio — te invitaron a :organization',
        'heading' => 'Tu invitación te espera',
        'hi' => 'Hola :name,',
        'body' => 'Solo un recordatorio amable — **:inviter** te invitó a **:organization** como **:role**. Aquí tienes un enlace nuevo cuando estés listo/a.',
        'expires' => 'Esta invitación caduca el **:date**.',
        'cta' => 'Aceptar invitación',
        'muted' => 'Inicia sesión con este correo para unirte. Si no quieres unirte, ignora este correo.',
        'thanks' => 'Esperamos verte pronto,',
    ],

    'invitation_accepted' => [
        'subject' => 'Bienvenido/a a :organization',
        'heading' => 'Ya estás dentro — bienvenido/a',
        'hi' => 'Hola :name,',
        'body' => 'Ya formas parte de **:organization** como **:role**. Nos alegra tenerte — abre el espacio y saluda al equipo.',
        'cta' => 'Abrir espacio',
        'muted' => 'Si algo no cuadra, habla con quien te invitó.',
        'thanks' => 'Bienvenido/a a bordo,',
    ],

    'invitation_accepted_admin' => [
        'subject' => ':member se unió a :organization',
        'heading' => 'Alguien nuevo se unió al equipo',
        'hi' => 'Hola :name,',
        'body' => '**:member** aceptó la invitación y se unió a **:organization** como **:role**. Qué bueno ver crecer al equipo.',
        'cta' => 'Ver miembros',
        'muted' => 'Puedes gestionar roles cuando quieras desde Miembros.',
        'thanks' => 'Feliz colaboración,',
    ],

    'invitation_revoked' => [
        'subject' => 'Se retiró la invitación a :organization',
        'heading' => 'Esa invitación fue retirada',
        'hi' => 'Hola :name,',
        'body' => '**:inviter** retiró la invitación para que te unieras a **:organization** como **:role**. No necesitas hacer nada.',
        'cta' => 'Visitar azshrtr',
        'muted' => 'Si crees que fue un error, pide al propietario o admin un nuevo invite.',
        'thanks' => 'Cuídate,',
    ],
];
