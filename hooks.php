<?php

//add a widget to the dashboard
register_dashboard_widget(array(
    'users::widgets/backend/latest',
));

//we don't need caching
register_no_cache_routes(array(
    '\Plugins\users\controllers\UsersController@login',
    '\Plugins\users\controllers\UsersController@signup',
    '\Plugins\users\controllers\UsersController@profile',
    '\Plugins\users\controllers\UsersController@logout'
));
//register the layouts to the block manager
register_layouts(array(
    array(
        'name'   =>  'users_login',
        'title'  =>  'users::strings.users_login_page',
        'routes'  =>  array(
            '\Plugins\users\controllers\UsersController@login'
        ),
    ),
    array(
        'name'   =>  'users_registration',
        'title'  =>  'users::strings.users_register_page',
        'routes'  =>  array(
            '\Plugins\users\controllers\UsersController@signup'
        ),
    ),
    array(
        'name'   =>  'users_profile',
        'title'  =>  'users::strings.users_profile_page',
        'routes'  =>  array(
            '\Plugins\users\controllers\UsersController@profile'
        ),
    ),
    array(
        'name'   =>  'users_forgot_pass',
        'title'  =>  'users::strings.forgot',
        'routes'  =>  array(
            '\Plugins\users\controllers\UsersController@forgot'
        ),
    ),
));
// Create the forms
register_content_block(array(
    'users_signup_form'  =>  array(
        'name'  =>  'users_signup_form',
        'title' =>  t('users::strings.signup_form'),
        'tpl'   =>  array(
            'users::blocks/signupform'=>'default'
        ),
        'multiple'  =>  false,
        'configurable'=> false
    ),'users_login_form'  =>  array(
        'name'  =>  'users_login_form',
        'title' =>  t('users::strings.login_form'),
        'tpl'   =>  array(
            'users::blocks/loginform'=>'default'
        ),
        'multiple'  =>  false,
        'configurable'=> false
    )
));
/**
 * @param $users
 * @return string
 */
function usersToList($users){
    ob_start();
    ?>
    <ul class="nav nav-stacked">
        <?php foreach($users as $item):?>
            <li class="clearfix"><a href="<?php echo url('users/edituser')."/". $item->id ?>"><?php echo $item->full_name?></a></li>
        <?php endforeach?>
    </ul>
    <?php
    return ob_get_clean();
}

function users_install(){
    \Settings::create(
        array(
            'namespace' => 'cms',
            'setting_name' => 'signup_activation_needed',
            'setting_value' => '0',
            'autoload' => 1,
        )
    );
    \Settings::create(
        array(
            'namespace' => 'cms',
            'setting_name' => 'reminder_mail',
            'setting_value' => 'noreply@example.com',
            'autoload' => 1,
        )
    );
}

function users_uninstall(){
    $setting = \Settings::whereNamespace('cms')->whereSettingName('signup_activation_needed')->first();
    $setting->delete();
    $setting = \Settings::whereNamespace('cms')->whereSettingName('reminder_mail')->first();
    $setting->delete();
}

register_plugin_install_handler('users','users_install');
register_plugin_uninstall_handler('users','users_uninstall');