<?php
/**
 * We want all post requests to be passed through the honeypot filter
 */
Route::group(array('before' => 'honeypot'), function(){
    Route::post('login','\Plugins\users\controllers\UsersController@dologin');
    Route::post('signup','\Plugins\users\controllers\UsersController@dosignup');
    Route::post('forgot','\Plugins\users\controllers\UsersController@doforgot');
    Route::post('resetcode','\Plugins\users\controllers\UsersController@doreset');
    Route::post('activate','\Plugins\users\controllers\UsersController@doactivate');

});

Route::get('login','\Plugins\users\controllers\UsersController@login');
Route::get('forgot','\Plugins\users\controllers\UsersController@forgot');
Route::get('resetcode','\Plugins\users\controllers\UsersController@resetcode');
Route::get('activate','\Plugins\users\controllers\UsersController@activate');
Route::get('signup','\Plugins\users\controllers\UsersController@signup');
Route::get('logout','\Plugins\users\controllers\UsersController@logout');
Route::get('profile','\Plugins\users\controllers\UsersController@profile');