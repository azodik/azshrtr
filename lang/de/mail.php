<?php

return [
    'verify_email' => [
        'subject' => 'Ein kurzer Schritt — bestätige deine E-Mail',
        'heading' => 'Schön, dass du da bist',
        'hi' => 'Hallo :name,',
        'body' => 'Danke, dass du bei azshrtr bist. Bestätige deine E-Mail mit dem Code unten oder über die Schaltfläche — dann kannst du Links erstellen und teilen.',
        'cta' => 'E-Mail bestätigen',
        'muted' => 'Link und Code laufen in 24 Stunden ab. Wenn du dich nicht registriert hast, kannst du diese Nachricht ruhig ignorieren.',
        'thanks' => 'Willkommen,',
    ],

    'email_otp' => [
        'subject' => 'Dein Anmeldecode für azshrtr',
        'heading' => 'Hier ist dein Code',
        'hi' => 'Hallo :name,',
        'body' => 'Nutze diesen einmaligen Code zur Anmeldung. Schön, dich wiederzusehen.',
        'muted' => 'Dieser Code läuft in 10 Minuten ab. Wenn du ihn nicht angefordert hast, ignoriere diese E-Mail — dein Konto bleibt sicher.',
        'thanks' => 'Bis gleich,',
    ],

    'password_reset' => [
        'subject' => 'Lass uns dich zurück zu azshrtr bringen',
        'heading' => 'Passwort zurücksetzen',
        'hi' => 'Hallo :name,',
        'body' => 'Kein Stress — passiert den Besten. Wir haben eine Anfrage zum Zurücksetzen deines Passworts erhalten. Wähle unten ein neues, und du bist gleich wieder drin.',
        'cta' => 'Neues Passwort wählen',
        'muted' => 'Dieser Link läuft in :minutes Minuten ab. Wenn du keinen Reset angefordert hast, bleibt dein Passwort unverändert.',
        'thanks' => 'Wir sind für dich da,',
    ],

    'welcome' => [
        'subject' => 'Willkommen bei azshrtr — schön, dass du da bist',
        'heading' => 'Willkommen an Bord',
        'hi' => 'Hallo :name,',
        'body' => 'Dein Konto ist bereit. Erstelle Kurzlinks und QR-Codes und teile sie aus deinem Workspace — wir freuen uns darauf, was du baust.',
        'cta' => 'Konsole öffnen',
        'muted' => 'Wenn du dieses Konto nicht erstellt hast, ignoriere diese E-Mail.',
        'thanks' => 'Schön, dich dabei zu haben,',
    ],

    'sign_in_activity' => [
        'subject' => 'Nur zur Sicherheit — neue Anmeldung',
        'heading' => 'Wir haben eine neue Anmeldung gesehen',
        'hi' => 'Hallo :name,',
        'body' => 'Jemand hat sich bei deinem azshrtr-Konto angemeldet. Wenn du das warst, ist alles gut. Wenn nicht, sichern wir es gemeinsam ab.',
        'time' => 'Wann: :time',
        'ip' => 'IP-Adresse: :ip',
        'device' => 'Gerät: :device',
        'muted' => 'Wenn dir das fremd vorkommt, setze sofort dein Passwort zurück — wir helfen dir, deinen Workspace zu schützen.',
        'cta' => 'Konto absichern',
        'thanks' => 'Wir achten auf dich,',
        'unknown_device' => 'Unbekanntes Gerät',
        'unknown_ip' => 'Unbekannt',
    ],

    'usage_warning' => [
        'subject' => 'Kurzer Hinweis — du bist bei :threshold% deines :metric-Limits',
        'heading' => 'Du näherst dich dem Limit',
        'hi' => 'Hallo :name,',
        'body' => '**:organization** hat **:used** von **:limit** :metric (:percent%) im :plan-Plan verbraucht. Kein Stress — nur ein freundlicher Reminder, damit nichts überrascht.',
        'threshold' => 'Du hast die **:threshold%**-Marke überschritten.',
        'cta' => 'Pläne ansehen',
        'muted' => 'Pro gibt dir mehr Raum, wann immer du bereit bist — oder warte auf den nächsten monatlichen Reset.',
        'thanks' => 'Wir drücken die Daumen,',
        'metric_links' => 'Links',
        'metric_qr' => 'QR-Codes',
    ],

    'usage_limit' => [
        'subject' => 'Du hast dein :metric-Limit erreicht — wir sind für dich da',
        'heading' => 'Du hast ein Planlimit erreicht',
        'hi' => 'Hallo :name,',
        'body' => '**:organization** hat alle verfügbaren :metric im :plan-Plan genutzt (**:used** / **:limit**). Das ist oft ein gutes Zeichen — Pro gibt dir unbegrenzt Raum, wenn du soweit bist.',
        'cta' => 'Auf Pro upgraden',
        'muted' => 'Du kannst jederzeit upgraden oder auf den monatlichen Reset warten. So oder so: schön, dass du mit uns wächst.',
        'thanks' => 'Danke, dass du mit uns wächst,',
        'metric_links' => 'Links',
        'metric_qr' => 'QR-Codes',
    ],

    'subscription_upgraded' => [
        'subject' => 'Willkommen bei Pro — wir freuen uns riesig',
        'heading' => 'Du nutzt Pro',
        'hi' => 'Hallo :name,',
        'body' => 'Tolle Neuigkeiten — **:organization** ist auf Pro. Genieße unbegrenzte Links & QR-Codes, eigene Domains und passwortgeschützte Links. Wir freuen uns, mit dir zu wachsen.',
        'cta' => 'Workspace öffnen',
        'muted' => 'Du wirst einmal im Jahr abgerechnet. Deinen Tarif kannst du jederzeit unter Abrechnung verwalten.',
        'thanks' => 'Danke für dein Vertrauen,',
    ],

    'subscription_downgrade_scheduled' => [
        'subject' => 'Du wirst uns auf Pro fehlen — :organization',
        'heading' => 'Es tut uns leid, dich gehen zu sehen',
        'hi' => 'Hallo :name,',
        'body' => 'Du möchtest **:organization** auf Free umstellen. Das ist völlig in Ordnung — bis zum Ende der aktuellen Abrechnungsperiode behältst du alle Pro-Vorteile.',
        'effective' => 'Free beginnt am **:date**.',
        'cta' => 'Bei Pro bleiben',
        'muted' => 'Anders überlegt? Du kannst Pro vor diesem Datum mit einem Klick behalten — wir würden uns wirklich freuen, wenn du bleibst.',
        'thanks' => 'Mit Wertschätzung,',
    ],

    'subscription_downgraded' => [
        'subject' => 'Es tut uns leid, dich gehen zu sehen — :organization',
        'heading' => 'Es tut uns leid, dich gehen zu sehen',
        'hi' => 'Hallo :name,',
        'body' => '**:organization** ist jetzt auf Free. Danke, dass du Pro ausprobiert hast — die Tür bleibt offen. Du kannst weiterhin Links und QR-Codes innerhalb der Free-Limits erstellen, und Pro wartet, wenn du wieder mehr Raum brauchst.',
        'cta' => 'Zurück zu Pro',
        'muted' => 'Wann immer du bereit bist: unbegrenzte Links, eigene Domains und Passwort-Links sind einen Klick entfernt. Wir würden dich gern wieder begrüßen.',
        'thanks' => 'Danke, dass du bei uns warst,',
    ],

    'billing_payment_succeeded' => [
        'subject' => 'Danke — Pro ist aktiv für :organization',
        'heading' => 'Alles erledigt — danke',
        'hi' => 'Hallo :name,',
        'body' => 'Deine Zahlung ist durch, und **:organization** ist auf Pro. Danke für deine Unterstützung — wir freuen uns auf das, was du erstellst.',
        'amount' => 'Betrag: **:amount**.',
        'cta' => 'Abrechnung öffnen',
        'muted' => 'Belege findest du jederzeit unter Abrechnung.',
        'thanks' => 'Danke, dass du azshrtr unterstützt,',
    ],

    'billing_payment_failed' => [
        'subject' => 'Lass uns dein Pro-Upgrade abschließen — :organization',
        'heading' => 'Diese Zahlung hat nicht geklappt',
        'hi' => 'Hallo :name,',
        'body' => 'Wir konnten die Zahlung für **:organization** nicht abschließen. Es wurde nichts berechnet und dein Workspace ist unverändert — kein Stress. Versuch es gern erneut; wir sind für dich da.',
        'amount' => 'Versuchter Betrag: **:amount**.',
        'cta' => 'Pro erneut versuchen',
        'muted' => 'Eine andere Karte oder Zahlungsmethode hilft oft. Nimm dir Zeit — wir sind bereit, wenn du es bist.',
        'thanks' => 'Wir drücken die Daumen,',
    ],

    'billing_checkout_abandoned' => [
        'subject' => 'Noch an Pro interessiert? Wir sind da — :organization',
        'heading' => 'Dein Pro-Upgrade wartet',
        'hi' => 'Hallo :name,',
        'body' => 'Du hast das Upgrade von **:organization** auf Pro begonnen, aber nicht abgeschlossen. Alles gut — es wurde nichts berechnet. Mach einfach weiter, wenn du soweit bist.',
        'cta' => 'Mit Pro fortfahren',
        'muted' => 'Wenn du nicht upgraden wolltest, ignoriere diese E-Mail. Kein Druck.',
        'thanks' => 'Wir sind auf deiner Seite,',
    ],

    'billing_refund_initiated' => [
        'subject' => 'Deine Rückerstattung ist unterwegs — :organization',
        'heading' => 'Wir haben deine Rückerstattung gestartet',
        'hi' => 'Hallo :name,',
        'body' => 'Wir haben eine Rückerstattung für **:organization** gestartet. Geld ist wichtig — Banken brauchen meist ein paar Werktage, bis sie auf dem Kontoauszug erscheint.',
        'amount' => 'Rückerstattungsbetrag: **:amount**.',
        'cta' => 'Abrechnung öffnen',
        'muted' => 'Du erhältst eine weitere E-Mail von uns, wenn die Rückerstattung abgeschlossen ist.',
        'thanks' => 'Danke für deine Geduld,',
    ],

    'billing_refund_succeeded' => [
        'subject' => 'Deine Rückerstattung ist abgeschlossen — :organization',
        'heading' => 'Deine Rückerstattung ist abgeschlossen',
        'hi' => 'Hallo :name,',
        'body' => 'Gute Nachrichten — die Rückerstattung für **:organization** ist abgeschlossen. Sie sollte bald auf deiner ursprünglichen Zahlungsmethode erscheinen. Es tut uns leid, wenn es nicht so gelaufen ist, wie du gehofft hast.',
        'amount' => 'Erstattet: **:amount**.',
        'cta' => 'Abrechnung öffnen',
        'muted' => 'Wenn du irgendwann zurückkommen möchtest, ist Pro bereit. Die Abrechnung kannst du jederzeit im Workspace verwalten.',
        'thanks' => 'Alles Gute,',
    ],

    'invitation_role' => [
        'owner' => 'Inhaber',
        'admin' => 'Admin',
        'member' => 'Mitglied',
    ],

    'invitation_invited' => [
        'subject' => 'Du bist eingeladen, :organization beizutreten',
        'heading' => 'Du wurdest eingeladen',
        'hi' => 'Hallo :name,',
        'body' => '**:inviter** hat dich eingeladen, **:organization** als **:role** beizutreten. Wir würden uns freuen, dich im Team zu haben — nimm unten an, um zu starten.',
        'expires' => 'Diese Einladung läuft am **:date** ab.',
        'cta' => 'Einladung annehmen',
        'muted' => 'Melde dich mit dieser E-Mail an, um beizutreten. Wenn du das nicht erwartet hast, kannst du die E-Mail ignorieren.',
        'thanks' => 'Wir freuen uns auf dich,',
    ],

    'invitation_resent' => [
        'subject' => 'Erinnerung — Einladung zu :organization',
        'heading' => 'Deine Einladung wartet',
        'hi' => 'Hallo :name,',
        'body' => 'Nur ein freundlicher Reminder — **:inviter** hat dich eingeladen, **:organization** als **:role** beizutreten. Hier ist ein neuer Link, wann immer du bereit bist.',
        'expires' => 'Diese Einladung läuft am **:date** ab.',
        'cta' => 'Einladung annehmen',
        'muted' => 'Melde dich mit dieser E-Mail an. Wenn du nicht beitreten möchtest, ignoriere diese E-Mail.',
        'thanks' => 'Hoffentlich bis bald,',
    ],

    'invitation_accepted' => [
        'subject' => 'Willkommen bei :organization',
        'heading' => 'Du bist dabei — willkommen',
        'hi' => 'Hallo :name,',
        'body' => 'Du bist jetzt Teil von **:organization** als **:role**. Schön, dass du da bist — öffne den Workspace und begrüße das Team.',
        'cta' => 'Workspace öffnen',
        'muted' => 'Wenn etwas nicht stimmt, melde dich bei der Person, die dich eingeladen hat.',
        'thanks' => 'Willkommen an Bord,',
    ],

    'invitation_accepted_admin' => [
        'subject' => ':member ist :organization beigetreten',
        'heading' => 'Jemand Neues ist im Team',
        'hi' => 'Hallo :name,',
        'body' => '**:member** hat die Einladung angenommen und ist **:organization** als **:role** beigetreten. Schön, dass das Team wächst.',
        'cta' => 'Mitglieder ansehen',
        'muted' => 'Rollen kannst du jederzeit unter Mitglieder verwalten.',
        'thanks' => 'Viel Freude beim Zusammenarbeiten,',
    ],

    'invitation_revoked' => [
        'subject' => 'Einladung zu :organization wurde zurückgezogen',
        'heading' => 'Diese Einladung wurde zurückgezogen',
        'hi' => 'Hallo :name,',
        'body' => '**:inviter** hat die Einladung für dich, **:organization** als **:role** beizutreten, zurückgezogen. Du musst nichts tun.',
        'cta' => 'azshrtr besuchen',
        'muted' => 'Falls das ein Versehen war, bitte den Workspace-Inhaber oder Admin um eine neue Einladung.',
        'thanks' => 'Alles Gute,',
    ],
];
