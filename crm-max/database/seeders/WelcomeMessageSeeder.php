<?php

namespace Database\Seeders;

use App\Models\WelcomeMessage;
use Illuminate\Database\Seeder;

class WelcomeMessageSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'     => 'Standard Welcome',
                'language' => 'English',
                'subject'  => 'Welcome to {{site_name}}. Your Account Is Ready',
                'body'     => '<h2>Hi {{first_name}},</h2>
<p>Your journey to becoming profitable starts here.</p>
<p>Your account is live and the markets are waiting. Everything you need to fund, analyze, and execute smarter trades is now at your fingertips.</p>
<p>
  <strong>Username:</strong> {{email}}<br>
  <strong>Password:</strong> {{password}}
</p>
<br>
<a href="{{login_url}}"><strong>Start Trading &rarr;</strong></a>
<br><br>
<p>&copy; 2026 {{site_name}}. All rights reserved. Trading involves risk. Past performance is not indicative of future results. Please trade responsibly. <a href="mailto:{{support_email}}">{{support_email}}</a></p>',
            ],
            [
                'name'     => 'Bienvenue Standard',
                'language' => 'French',
                'subject'  => 'Bienvenue chez {{site_name}}. Votre compte est pr&ecirc;t',
                'body'     => '<h2>Bonjour {{first_name}},</h2>
<p>Votre voyage vers la rentabilit&eacute; commence ici.</p>
<p>Votre compte est actif et les march&eacute;s vous attendent. Tout ce dont vous avez besoin pour financer, analyser et ex&eacute;cuter des transactions plus intelligentes est d&eacute;sormais &agrave; port&eacute;e de main.</p>
<p>
  <strong>Nom d\'utilisateur :</strong> {{email}}<br>
  <strong>Mot de passe :</strong> {{password}}
</p>
<br>
<a href="{{login_url}}"><strong>Commencer &agrave; trader &rarr;</strong></a>
<br><br>
<p>&copy; 2026 {{site_name}}. Tous droits r&eacute;serv&eacute;s. Le trading comporte des risques. Les performances pass&eacute;es ne sont pas indicatives des r&eacute;sultats futurs. Veuillez trader de mani&egrave;re responsable. <a href="mailto:{{support_email}}">{{support_email}}</a></p>',
            ],
        ];

        foreach ($templates as $template) {
            WelcomeMessage::firstOrCreate(
                ['name' => $template['name'], 'language' => $template['language']],
                $template
            );
        }
    }
}
