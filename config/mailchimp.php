<?php

return [

    'api_key' => env('MAILCHIMP_APIKEY'),

    /*
     * If you want to add to your mailchimp audience when a user registers, set this to `true`
     */
    'add_new_users' => false,

    'users' => [
        'audience_id' => null,
        'disable_opt_in' => false,
        'check_consent' => false,
        'consent_field' => null,
        'merge_fields' => [
            [
                'tag' => 'EMAIL',
                'field_name' => null,
            ],
        ],
        'tag' => null,
        'interests_field' => null,
        'id' => 'xH6KMQK5',
    ],

    /*
     * The form submissions to add to your Mailchimp Audiences
     */
    'forms' => [
        [
            'audience_id' => '9164c325e8',
            'disable_opt_in' => false,
            'check_consent' => false,
            'consent_field' => null,
            'form' => 'contact',
            'merge_fields' => [
                [
                    'tag' => 'EMAIL',
                    'field_name' => 'email',
                ],
            ],
            'tag' => null,
            'interests_field' => null,
        ],
    ],
];
