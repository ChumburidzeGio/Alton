<?php

return [
    'settings' => [
        //        'url' => 'https://ia-test.paston.nl/rest/',
        'url'                               => 'https://ia.paston.nl/rest/',
        'apikey'                            => 'eUZw-$v!1rMjg1JUmJeHUPXi%$IAv?Lun8SH*#@nVQqAIrgAHL27SG$+n^grMA!lx5nnBoxWRFeCWnUP0@GjgdDv7VD7wlLj%s4a?0qyP0oXydWYK|DV6sX+!URks$aQ4|Xj99Mo62|s=?_wN3TCskRO#IweZ*JTvAzjHypE4Ms31*H6!%TXf2UkEPS%p|la*6gT@d#pl^uFPreLh?V6aj3AcLvRpn@i5snjWCZ_sT6yhNu2NT@JlUwIke=uDA+D',
        'premium_error_notification_emails' => false,
        'meeus_ccs_form_url'                => ((app()->environment() == 'prod') ? 'https://eforms.meeus.com/Meeus/servletcontroller' : 'https://eforms-acc.meeus.com/Meeus/servletcontroller'),
        'add_roadside_assistance_nl'        => true,
    ]
];

