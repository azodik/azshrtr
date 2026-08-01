<?php

return [
    'verify_email' => [
        'subject' => 'Une petite étape — vérifiez votre e-mail',
        'heading' => 'Ravis de vous accueillir',
        'hi' => 'Bonjour :name,',
        'body' => 'Merci d’avoir rejoint azshrtr. Confirmez votre e-mail avec le code ci-dessous ou le bouton — puis créez et partagez vos liens en toute liberté.',
        'cta' => 'Vérifier l’e-mail',
        'muted' => 'Ce lien et ce code expirent dans 24 heures. Si vous ne vous êtes pas inscrit, ignorez ce message en toute confiance.',
        'thanks' => 'Bienvenue,',
    ],

    'email_otp' => [
        'subject' => 'Votre code de connexion azshrtr',
        'heading' => 'Voici votre code',
        'hi' => 'Bonjour :name,',
        'body' => 'Utilisez ce code à usage unique pour vous connecter. Content de vous revoir.',
        'muted' => 'Ce code expire dans 10 minutes. Si vous ne l’avez pas demandé, ignorez cet e-mail — votre compte reste en sécurité.',
        'thanks' => 'À tout de suite,',
    ],

    'password_reset' => [
        'subject' => 'Revenons ensemble dans azshrtr',
        'heading' => 'Réinitialisez votre mot de passe',
        'hi' => 'Bonjour :name,',
        'body' => 'Pas d’inquiétude — ça arrive. Nous avons reçu une demande de réinitialisation. Choisissez un nouveau mot de passe ci-dessous et vous serez de retour en un instant.',
        'cta' => 'Choisir un nouveau mot de passe',
        'muted' => 'Ce lien expire dans :minutes minutes. Si vous n’avez pas demandé de réinitialisation, votre mot de passe reste inchangé.',
        'thanks' => 'Nous sommes là si besoin,',
    ],

    'welcome' => [
        'subject' => 'Bienvenue sur azshrtr — nous sommes ravis',
        'heading' => 'Bienvenue à bord',
        'hi' => 'Bonjour :name,',
        'body' => 'Votre compte est prêt. Créez des liens courts, des QR codes et partagez-les depuis votre espace — hâte de voir ce que vous allez créer.',
        'cta' => 'Ouvrir la console',
        'muted' => 'Si vous n’avez pas créé ce compte, ignorez cet e-mail.',
        'thanks' => 'Content de vous avoir,',
    ],

    'sign_in_activity' => [
        'subject' => 'Petit contrôle — nouvelle connexion',
        'heading' => 'Nous avons détecté une nouvelle connexion',
        'hi' => 'Bonjour :name,',
        'body' => 'Quelqu’un s’est connecté à votre compte azshrtr. Si c’était vous, tout va bien. Sinon, sécurisons-le ensemble.',
        'time' => 'Quand : :time',
        'ip' => 'Adresse IP : :ip',
        'device' => 'Appareil : :device',
        'muted' => 'Si cela vous semble inhabituel, réinitialisez votre mot de passe tout de suite — nous vous aidons à protéger votre espace.',
        'cta' => 'Sécuriser mon compte',
        'thanks' => 'On veille sur vous,',
        'unknown_device' => 'Appareil inconnu',
        'unknown_ip' => 'Inconnue',
    ],

    'usage_warning' => [
        'subject' => 'Petit rappel — vous êtes à :threshold% de votre limite de :metric',
        'heading' => 'Vous approchez de la limite',
        'hi' => 'Bonjour :name,',
        'body' => '**:organization** a utilisé **:used** sur **:limit** :metric (:percent%) avec le forfait :plan. Pas de stress — juste un clin d’œil pour éviter les surprises.',
        'threshold' => 'Vous avez franchi le seuil de **:threshold%**.',
        'cta' => 'Voir les offres',
        'muted' => 'Pro vous donne plus d’espace dès que vous êtes prêt — ou attendez le prochain renouvellement mensuel.',
        'thanks' => 'On vous encourage,',
        'metric_links' => 'liens',
        'metric_qr' => 'QR codes',
    ],

    'usage_limit' => [
        'subject' => 'Vous avez atteint votre limite de :metric — on est là',
        'heading' => 'Vous avez atteint une limite',
        'hi' => 'Bonjour :name,',
        'body' => '**:organization** a utilisé tous les :metric disponibles avec le forfait :plan (**:used** / **:limit**). C’est souvent bon signe — Pro peut vous offrir un espace illimité quand vous voulez.',
        'cta' => 'Passer à Pro',
        'muted' => 'Passez à Pro quand vous voulez, ou attendez le prochain renouvellement. Dans tous les cas, merci de grandir avec nous.',
        'thanks' => 'Merci de grandir avec nous,',
        'metric_links' => 'liens',
        'metric_qr' => 'QR codes',
    ],

    'subscription_upgraded' => [
        'subject' => 'Bienvenue dans Pro — nous sommes ravis',
        'heading' => 'Vous êtes en Pro',
        'hi' => 'Bonjour :name,',
        'body' => 'Excellente nouvelle — **:organization** est en Pro. Profitez de liens et QR illimités, de domaines personnalisés et de liens protégés. Hâte de grandir avec vous.',
        'cta' => 'Ouvrir votre espace',
        'muted' => 'Facturation annuelle. Gérez votre offre à tout moment depuis Facturation.',
        'thanks' => 'Merci de croire en nous,',
    ],

    'subscription_downgrade_scheduled' => [
        'subject' => 'Vous allez nous manquer en Pro — :organization',
        'heading' => 'Nous sommes désolés de vous voir partir',
        'hi' => 'Bonjour :name,',
        'body' => 'Vous avez demandé de passer **:organization** en Free. C’est tout à fait ok — vous gardez tous les avantages Pro jusqu’à la fin de la période en cours.',
        'effective' => 'Free commence le **:date**.',
        'cta' => 'Rester en Pro',
        'muted' => 'Vous avez changé d’avis ? Gardez Pro en un clic avant cette date — nous serions vraiment ravis que vous restiez.',
        'thanks' => 'Avec gratitude,',
    ],

    'subscription_downgraded' => [
        'subject' => 'Nous sommes désolés de vous voir partir — :organization',
        'heading' => 'Nous sommes désolés de vous voir partir',
        'hi' => 'Bonjour :name,',
        'body' => '**:organization** est maintenant en Free. Merci d’avoir essayé Pro ; la porte reste ouverte — vous pouvez toujours créer des liens et QR dans les limites Free, et Pro est là dès que vous voulez plus d’espace.',
        'cta' => 'Revenir à Pro',
        'muted' => 'Quand vous serez prêt, liens illimités, domaines personnalisés et liens protégés sont à un clic. Nous serions ravis de vous accueillir à nouveau.',
        'thanks' => 'Merci d’avoir été avec nous,',
    ],

    'billing_payment_succeeded' => [
        'subject' => 'Merci — Pro est actif pour :organization',
        'heading' => 'Tout est prêt — merci',
        'hi' => 'Bonjour :name,',
        'body' => 'Votre paiement est passé, et **:organization** est en Pro. Merci pour votre soutien — hâte de voir ce que vous allez créer.',
        'amount' => 'Montant débité : **:amount**.',
        'cta' => 'Ouvrir Facturation',
        'muted' => 'Consultez vos reçus à tout moment depuis Facturation.',
        'thanks' => 'Merci de soutenir azshrtr,',
    ],

    'billing_payment_failed' => [
        'subject' => 'Terminons votre passage à Pro — :organization',
        'heading' => 'Ce paiement n’a pas abouti',
        'hi' => 'Bonjour :name,',
        'body' => 'Nous n’avons pas pu finaliser le paiement pour **:organization**. Rien n’a été débité et votre espace est inchangé — aucun stress. Réessayez quand vous voulez ; on est là.',
        'amount' => 'Montant tenté : **:amount**.',
        'cta' => 'Réessayer Pro',
        'muted' => 'Une autre carte ou méthode aide souvent. Prenez votre temps — on sera prêts quand vous l’êtes.',
        'thanks' => 'On croit en vous,',
    ],

    'billing_checkout_abandoned' => [
        'subject' => 'Vous pensez encore à Pro ? On est là — :organization',
        'heading' => 'Votre passage à Pro vous attend',
        'hi' => 'Bonjour :name,',
        'body' => 'Vous avez commencé à passer **:organization** en Pro sans terminer. Aucun souci — rien n’a été débité. Reprenez quand vous voulez, exactement où vous vous êtes arrêté.',
        'cta' => 'Continuer vers Pro',
        'muted' => 'Si vous ne souhaitiez pas passer à Pro, ignorez cet e-mail. Aucune pression.',
        'thanks' => 'On est de votre côté,',
    ],

    'billing_refund_initiated' => [
        'subject' => 'Votre remboursement est en cours — :organization',
        'heading' => 'Nous avons lancé votre remboursement',
        'hi' => 'Bonjour :name,',
        'body' => 'Nous avons lancé un remboursement pour **:organization**. L’argent compte — les banques mettent généralement quelques jours ouvrés pour l’afficher.',
        'amount' => 'Montant du remboursement : **:amount**.',
        'cta' => 'Ouvrir Facturation',
        'muted' => 'Vous recevrez un autre e-mail de notre part lorsque le remboursement sera terminé.',
        'thanks' => 'Merci pour votre patience,',
    ],

    'billing_refund_succeeded' => [
        'subject' => 'Votre remboursement est terminé — :organization',
        'heading' => 'Votre remboursement est terminé',
        'hi' => 'Bonjour :name,',
        'body' => 'Bonne nouvelle — le remboursement pour **:organization** est terminé. Il devrait bientôt apparaître sur votre moyen de paiement d’origine. Nous sommes désolés si les choses n’ont pas été comme vous l’espériez.',
        'amount' => 'Remboursé : **:amount**.',
        'cta' => 'Ouvrir Facturation',
        'muted' => 'Si un jour vous souhaitez revenir, Pro sera prêt. Gérez la facturation à tout moment depuis votre espace.',
        'thanks' => 'Tous nos vœux,',
    ],

    'invitation_role' => [
        'owner' => 'Propriétaire',
        'admin' => 'Admin',
        'member' => 'Membre',
    ],

    'invitation_invited' => [
        'subject' => 'Vous êtes invité(e) à rejoindre :organization',
        'heading' => 'Vous avez été invité(e)',
        'hi' => 'Bonjour :name,',
        'body' => '**:inviter** vous a invité(e) à rejoindre **:organization** en tant que **:role**. Nous serions ravis de vous accueillir — acceptez ci-dessous pour commencer.',
        'expires' => 'Cette invitation expire le **:date**.',
        'cta' => 'Accepter l’invitation',
        'muted' => 'Connectez-vous avec cet e-mail pour rejoindre. Si vous n’attendiez pas ceci, ignorez le message.',
        'thanks' => 'Au plaisir de vous accueillir,',
    ],

    'invitation_resent' => [
        'subject' => 'Rappel — invitation à :organization',
        'heading' => 'Votre invitation vous attend',
        'hi' => 'Bonjour :name,',
        'body' => 'Petit rappel amical — **:inviter** vous a invité(e) à rejoindre **:organization** en tant que **:role**. Voici un nouveau lien dès que vous êtes prêt(e).',
        'expires' => 'Cette invitation expire le **:date**.',
        'cta' => 'Accepter l’invitation',
        'muted' => 'Connectez-vous avec cet e-mail pour rejoindre. Si vous ne souhaitez pas rejoindre, ignorez cet e-mail.',
        'thanks' => 'Au plaisir de vous voir bientôt,',
    ],

    'invitation_accepted' => [
        'subject' => 'Bienvenue dans :organization',
        'heading' => 'Vous y êtes — bienvenue',
        'hi' => 'Bonjour :name,',
        'body' => 'Vous faites maintenant partie de **:organization** en tant que **:role**. Content de vous avoir — ouvrez l’espace et dites bonjour à l’équipe.',
        'cta' => 'Ouvrir l’espace',
        'muted' => 'Si quelque chose cloche, contactez la personne qui vous a invité(e).',
        'thanks' => 'Bienvenue à bord,',
    ],

    'invitation_accepted_admin' => [
        'subject' => ':member a rejoint :organization',
        'heading' => 'Quelqu’un de nouveau a rejoint l’équipe',
        'hi' => 'Bonjour :name,',
        'body' => '**:member** a accepté l’invitation et a rejoint **:organization** en tant que **:role**. Belle croissance d’équipe.',
        'cta' => 'Voir les membres',
        'muted' => 'Vous pouvez gérer les rôles à tout moment depuis Membres.',
        'thanks' => 'Bonne collaboration,',
    ],

    'invitation_revoked' => [
        'subject' => 'Invitation à :organization retirée',
        'heading' => 'Cette invitation a été retirée',
        'hi' => 'Bonjour :name,',
        'body' => '**:inviter** a retiré l’invitation à rejoindre **:organization** en tant que **:role**. Aucune action n’est nécessaire de votre côté.',
        'cta' => 'Visiter azshrtr',
        'muted' => 'Si vous pensez que c’est une erreur, demandez au propriétaire ou admin un nouvel invite.',
        'thanks' => 'Prenez soin de vous,',
    ],
];
