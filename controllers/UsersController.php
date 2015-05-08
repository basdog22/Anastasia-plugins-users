<?php

namespace Plugins\users\controllers;

/**
 * Class UsersController
 * @package Plugins\users\controllers
 */
class UsersController extends \FrontendbaseController{
    /**
     * @var string
     */
    protected $layout = 'layouts.default.theme';

    /**
     * Show the login page
     */
    public function login(){
        add_breadcrumb(get_config_value('brand'), url('/'));
        add_breadcrumb(t('strings.login'), url('login'));
        \Session::forget('gridsrun');
        $this->loadLayout(false,false,'users_login');
        $this->setPageTitle(t('strings.login'));
    }



    /**
     * Show the sign up page
     */
    public function signup(){
        add_breadcrumb(get_config_value('brand'), url('/'));
        add_breadcrumb(t('users::strings.sign_up'), url('signup'));
        \Session::forget('gridsrun');
        $this->loadLayout(false,false,'users_registration');
        $this->setPageTitle(t('users::strings.register'));
    }

    public function forgot(){
        add_breadcrumb(get_config_value('brand'), url('/'));
        add_breadcrumb(t('users::strings.forgot'), url('forgot'));
        \Session::forget('gridsrun');
        $dummy = new \stdClass();
        $this->loadLayout($dummy,'users::forgot','users_forgot_pass');
        $this->setPageTitle(t('users::strings.forgot_password'));
    }

    public function resetcode(){
        add_breadcrumb(get_config_value('brand'), url('/'));
        add_breadcrumb(t('users::strings.reset_code'), url('resetcode'));
        \Session::forget('gridsrun');
        $dummy = new \stdClass();
        $this->loadLayout($dummy,'users::resetcode','users_forgot_pass');
        $this->setPageTitle(t('users::strings.reset_code'));
    }

    public function activate(){
        add_breadcrumb(get_config_value('brand'), url('/'));
        add_breadcrumb(t('users::strings.activate'), url('activate'));
        \Session::forget('gridsrun');
        $dummy = new \stdClass();
        $this->loadLayout($dummy,'users::activate','users_forgot_pass');
        $this->setPageTitle(t('users::strings.activate'));
    }

    /**
     * Show the profile page
     */
    public function profile(){
        add_breadcrumb(get_config_value('brand'), url('/'));
        add_breadcrumb(t('users::strings.profile'), url('profile'));
        \Session::forget('gridsrun');
        $this->loadLayout(false,false,'users_profile_page');
        $this->setPageTitle(t('users::strings.users_profile'));
    }

    /**
     * Logout the user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(){
        \Event::fire('users.user.before_logout', array(\Auth::user()));
        \Auth::logout();
        \Event::fire('users.user.after_logout');
        return \Redirect::to('/')->withMessage(t('messages.you_logged_out'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doforgot(){
        $fields = requested();
        \Event::fire('recover.form.submited',$fields);

        try {
            // Find the user using the user email address
            $user = \Sentry::findUserByLogin($fields['email']);

            // Get the password reset code
            $resetCode = $user->getResetPasswordCode();
            \Mail::send('users::emails.forgot', array('code' => $resetCode), function ($message) use ($user) {
                $message->from(get_config_value('reminder_mail'), get_config_value('brand'));
                $message->to($user->email)->subject(t('users::strings.password_reset'));
            });

            return \Redirect::to('resetcode')->withMessage(t('users::messages.reset_code_sent'));
        } catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
            return \Redirect::to('forgot')->withMessage(t('users::messages.user_not_found'));
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doreset(){
        try {
            $user = \Sentry::findUserByResetPasswordCode(requested('code'));
            if (requested('password') === requested('password2')) {
                $pass = requested('password');
                if ($user->attemptResetPassword(requested('code'), $pass)) {
                    return \Redirect::to('login')->with('message', t('users::strings.password_reset_passed'));
                } else {
                    return \Redirect::to('login')->with('message', t('users::strings.password_reset_failed'));
                }
            } else {

                return \Redirect::to('resetcode')->with('message', t('users::strings.passwords_must_match'));
            }

        } catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
            return \Redirect::to('resetcode')->with('message', t('users::strings.reset_code_is_wrong'));
        }
    }

    /**
     * Attempt to sign in the user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dologin(){
        $fields = requested();
        \Event::fire('login.form.submited',$fields);
        if (\Auth::attempt(array('email' => $fields['email'], 'password' => $fields['password']), true)) {
            $user = \Auth::user();
            $user->last_login = new \DateTime();
            if(!$user->save()){
                return \Redirect::intended('login')->withMessage(t('messages.error_occured'));
            }
            return \Redirect::intended('/');
        } else {
            return \Redirect::to('login')
                ->withMessage(t('messages.wrong_pass'))
                ->withInput();
        }
    }

    /**
     * Attempt to sign up the user
     *
     * @return  \Illuminate\Http\RedirectResponse
     */
    public function dosignup(){
        $fields = requested();
        \Event::fire('registration.form.submited',$fields);

        if(!trim($fields['email']) || !trim($fields['password']) || !trim($fields['password2'])){
            return \Redirect::back()->withMessage(t('messages.required_fields_empty'))->withInput();
        }

        if($fields['password'] !== $fields['password2']){
            return \Redirect::back()->withMessage($this->notifyView(t('messages.password_missmatch'),'danger'))->withInput();
        }
        if(get_config_value('signup_activation_needed')){
            return $this->signuptwofactor($fields);
        }else{
            return $this->signupsimple($fields);
        }
    }

    /**
     * @param $fields
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signupsimple($fields){

        $user = new \User;
        $user->email = $fields['email'];
        $user->password = \Hash::make($fields['password']);
        \Event::fire('frontend.users.before_add', array($user));
        if(!$user->save()){
            return \Redirect::back()->withMessage(t('messages.error_occured'))->withInput();
        }
        $groupid = 5;
        $group = \Sentry::findGroupById($groupid);
        if(is_null($group)){
            return \Redirect::back()->withMessage(t('messages.user_group_not_found'));
        }
        $user = \Sentry::findUserById($user->id);
        $user->addGroup($group);
        return \Redirect::to('profile')->withMessage(t('messages.user_created'));
    }

    /**
     * @param $fields
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signuptwofactor($fields){
        try {
            $user = \Sentry::register(array(
                'email' => $fields['email'],
                'password' => $fields['password'],
            ));
            $activationCode = $user->getActivationCode();

        } catch (Cartalyst\Sentry\Users\LoginRequiredException $e) {
            return \Redirect::to('signup')->with('message', t('users::strings.email_field_required'));
        } catch (Cartalyst\Sentry\Users\PasswordRequiredException $e) {
            return \Redirect::to('signup')->with('message', t('users::strings.pass_field_required'));
        } catch (Cartalyst\Sentry\Users\UserExistsException $e) {
            return \Redirect::to('signup')->with('message', t('users::strings.user_exists'));

        }

        \Mail::send('users::emails.welcome', array('user' => $user, 'code' => $activationCode), function ($message) use ($user) {
            $message->from(get_config_value('reminder_mail'),get_config_value('brand'));
            $message->to($user->email)->subject(t('users::strings.welcome'));
        });
        return \Redirect::to('activate')->withMessage(t('users::strings.user_created_needs_activation'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doactivate()
    {
        $user = \Sentry::findUserByActivationCode(requested('code'));

        if(is_null($user)){
            return \Redirect::to('activate')->withMessage(t('users::strings.user_created_needs_activation'));
        }
        // Attempt to activate the user
        if ($user->attemptActivation(requested('code'))) {

            return \Redirect::to('/')->with('message', t('users::strings.activation_success'));
        } else {
            return \Redirect::to('activate')->withMessage(t('users::strings.activation_code_wrong'));
        }
    }


}