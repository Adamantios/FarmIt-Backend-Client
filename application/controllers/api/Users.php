<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property Users_model $Users_model
 * @property Logged_users_model $Logged_users_model
 * @property InstantPurchases_model $InstantPurchases_model
 * @property Announcements_model $Announcements_model
 * @property Addresses_model $Addresses_model
 */
class Users extends REST_Controller
{

    function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, "
            . "Content-Type, Accept, Access-Control-Request-Method");

        if ("OPTIONS" === $_SERVER['REQUEST_METHOD'])
            die();

        // Construct the parent class
        parent::__construct();
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        //$this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        //$this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        //$this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function register_post()
    {
        if ((!$this->post('email') || !$this->post('password') || !$this->post('name') ||
            !$this->post('surname') || !$this->post('tel_num'))
        ) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided complete registration data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Remove all illegal characters from email
        $email = filter_var($this->post('email'), FILTER_SANITIZE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->response([
                'status' => FALSE,
                'message' => 'Email address is invalid!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Check if phone number has anything other than digits or is longer than 15 digits
        if ((is_numeric($this->post('tel_num')) === false)
            || strlen((string)$this->post('tel_num')) > 15
        )

            $this->response([
                'status' => FALSE,
                'message' => 'Phone number is invalid!'
            ], REST_Controller::HTTP_BAD_REQUEST);

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($email);
        if ($user_id) {
            $this->response([
                'status' => FALSE,
                'message' => 'An account with this e-mail already exists!'
            ], REST_Controller::HTTP_CONFLICT);
        } else {
            $data = array();
            $data['email'] = $email;
            $data['password'] = md5($this->post('password'));
            $data['name'] = $this->post('name');
            $data['surname'] = $this->post('surname');
            $data['tel_num'] = $this->post('tel_num');

            $addedUserId = $this->Users_model->add_new_user($data);
            if (!$addedUserId) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'A registration error occurred.Please try again!'
                ], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                // Here we call a function go give the user his token. The token is also passed in the response
                $token = create_token();
                // Add user to Logged_users_model table
                $this->load->model('Logged_users_model');
                $flag = $this->Logged_users_model->add($addedUserId, $token);

                if (!$flag) {
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Registration failed'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                } else {
                    // here we have to remove the info that we don't need to send to the frontend
                    $session = $this->Users_model->get_user_session($addedUserId);
                    $this->response([
                        'status' => TRUE,
                        'user_id' => $addedUserId,
                        'session' => $session,
                        'token' => $token,
                        'message' => 'You have been successfully registered'
                    ], REST_Controller::HTTP_OK);
                }
            }
        }
    }

    function login_post()
    {
        if ((!$this->post('email') || !$this->post('password'))) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided complete login data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Remove all illegal characters from email
        $email = filter_var($this->post('email'), FILTER_SANITIZE_EMAIL);
        $pass = $this->post('password');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->response([
                'status' => FALSE,
                'message' => 'Email address is invalid!'
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        $this->load->model('Users_model');
        $db_pass = $this->Users_model->get_user_pass_by_email($email);
        if (!$db_pass) {
            $this->response([
                'status' => FALSE,
                'message' => 'No user was found with that e-mail!'
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        if (md5($pass) == $db_pass) {
            $id = $this->Users_model->get_user_id_by_email($email);
            // Here we call a function go give the user his token. The token is also passed in the responce
            $token = create_token();
            $this->load->model('Logged_users_model');
            //Add user to loggedUsers table
            $flag = $this->Logged_users_model->add($id, $token);
            if (!$flag) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Login failed'
                ], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                $session = $this->Users_model->get_user_session($id);

                if (!$session)
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Session not found.'
                    ], REST_Controller::HTTP_BAD_REQUEST);

                $this->load->model('Addresses_model');
                $numOfAddresses = $this->Addresses_model->count_addresses($id);

                $this->response([
                    'status' => TRUE,
                    'message' => 'User logged in!',
                    'session' => $session,
                    'token' => $token,
                    'user_id' => $id,
                    'addresses' => $numOfAddresses
                ], REST_Controller::HTTP_OK);
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Wrong password!'
            ], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    function delete_post()
    {
        if (!$this->post('email') || !$this->post('password') || !$this->post('token')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided complete data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id) {
            $this->response([
                'status' => FALSE,
                'message' => 'User not found'
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        $this->load->model('Logged_users_model');
        $returned_token = $this->Logged_users_model->getToken($user_id);

        if (!$returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Session not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $token = $this->post('token');

        // Check user token
        if ($token != $returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action: Invalid token.'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Remove all illegal characters from email
        $email = filter_var($this->post('email'), FILTER_SANITIZE_EMAIL);
        $pass = $this->post('password');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->response([
                'status' => FALSE,
                'message' => 'Email address is invalid!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $db_pass = $this->Users_model->get_user_pass_by_email($email);
        if (!$db_pass) {
            $this->response([
                'status' => FALSE,
                'message' => 'No user was found with that e-mail!'
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        if (md5($pass) == $db_pass) {
            // Remove user's instant purchases.
            $this->load->model('InstantPurchases_model');
            $purchases_deleted = $this->InstantPurchases_model->delete_instant_purchases($user_id);
            // Remove user's announcements.
            $this->load->model('Announcements_model');
            $announcements_deleted = $this->Announcements_model->delete_announcements($user_id);
            // Remove user from loggedUsers table
            $flag = $this->Logged_users_model->remove($user_id);

            if ($purchases_deleted && $announcements_deleted && $flag) {
                $deleted_id = $this->Users_model->delete_user($user_id, $email);
                if ($deleted_id) {
                    $this->response([
                        'status' => TRUE,
                        'message' => 'User has been deleted!'
                    ], REST_Controller::HTTP_OK);
                } else {
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Something went wrong while trying to remove instant purchases, '
                            . 'announcements and token for user with id.' . (string)$user_id
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Something went wrong.'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Wrong password!'
            ], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    function backstage_login_post()
    {
        if (!$this->post('email') || !$this->post('token')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided complete data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));
        $this->load->model('Logged_users_model');
        $token = $this->Logged_users_model->getToken($user_id);

        if ($token == $this->post('token')) {
            // Here we call a function go give the user his token. The token is also passed in the response
            $token = create_token();
            // Add user to Logged_users_model table
            $token_created = $this->Logged_users_model->add($user_id, $token);

            if (!$token_created) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Failed to create token!'
                ], REST_Controller::HTTP_EXPECTATION_FAILED);
            } else {
                $this->load->model('Addresses_model');
                $numOfAddresses = $this->Addresses_model->count_addresses($user_id);

                $this->response([
                    'status' => TRUE,
                    'message' => 'Token has been successfully created!',
                    'token' => $token,
                    'addresses' => $numOfAddresses
                ], REST_Controller::HTTP_OK);
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Failed to authenticate user!'
            ], REST_Controller::HTTP_FORBIDDEN);
        }
    }

    function delete_token_post()
    {
        if (!$this->post('email')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided complete data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));
        $this->load->model('Logged_users_model');
        $removed = $this->Logged_users_model->remove($user_id);

        if (!$removed) {
            $this->response([
                'status' => FALSE,
                'message' => 'Failed to remove token!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->response([
                'status' => TRUE,
                'message' => 'Token has been successfully removed!'
            ], REST_Controller::HTTP_OK);
        }
    }

    function get_producers_post()
    {
        $this->load->model('Users_model');
        $producers = $this->Users_model->get_producers($this->post('start'));

        if ($producers) {
            $this->response([
                'status' => TRUE,
                'message' => 'Producers fetched!',
                'data' => $producers
            ], REST_Controller::HTTP_OK);

        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Producers could not be fetched!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    function logout_post()
    {
        if ((!$this->post('email')))
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Request.'
            ], REST_Controller::HTTP_BAD_REQUEST);

        $email = $this->post('email');
        // Remove all illegal characters from email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            log_message('info', $email . " is a valid email address");
        } else {
            log_message('info', $email . ' is not a valid email address');
            $this->response([
                'status' => FALSE,
                'message' => 'Email address is not valid'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $user_id = $this->Users_model->get_user_id_by_email($email);

        if ($user_id) {
            // Remove user from loggedUsers table
            $flag = $this->Logged_users_model->remove($user_id);

            if ($flag)
                $this->response([
                    'status' => TRUE,
                    'message' => 'User logged out.'
                ], REST_Controller::HTTP_OK);

            else
                $this->response([
                    'status' => FALSE,
                    'message' => "User's token could not be removed."
                ], REST_Controller::HTTP_BAD_REQUEST);

        } else
            $this->response([
                'status' => FALSE,
                'message' => 'User not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
    }

    public function get_user_post()
    {
        if ((!$this->post('email') || !$this->post('token'))) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided all the requested data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id) {
            $this->response([
                'status' => FALSE,
                'message' => 'User not found'
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        $this->load->model('Logged_users_model');
        $returned_token = $this->Logged_users_model->getToken($user_id);

        if (!$returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Session not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $token = $this->post('token');

        // Check user's token
        if ($token != $returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action!'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->load->model('Users_model');
        $user = $this->Users_model->get_user($user_id);

        if ($user)
            $this->response([
                'status' => TRUE,
                'message' => 'User fetched.',
                'data' => $user
            ], REST_Controller::HTTP_OK);
        else
            $this->response([
                'status' => FALSE,
                'message' => 'User not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
    }

    public function update_user_post()
    {
        if ((!$this->post('data') || !$this->post('token'))) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided all the requested data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Remove all illegal characters from email
        $email = filter_var($this->post('data')['email'], FILTER_SANITIZE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->response([
                'status' => FALSE,
                'message' => 'Email address is invalid!',
                'code' => 1
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        // Check if phone number has anything other than digits or is longer than 15 digits
        if ((is_numeric($this->post('data')['tel_num']) === false)
            || strlen((string)$this->post('tel_num')) > 15
        )

            $this->response([
                'status' => FALSE,
                'message' => 'Phone number is invalid!'
            ], REST_Controller::HTTP_NOT_ACCEPTABLE);

        $this->load->model('Logged_users_model');
        $returned_token = $this->Logged_users_model->getToken($this->post('data')['id']);

        if (!$returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Session not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $token = $this->post('token');

        // Check user's token
        if ($token != $returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action!'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('data')['email']);

        if ($user_id && $user_id != $this->post('data')['id'])
            $this->response([
                'status' => FALSE,
                'message' => 'An account with this e-mail already exists!'
            ], REST_Controller::HTTP_CONFLICT);

        $user_updated = $this->Users_model->update_user($this->post('data'), $this->post('data')['id']);

        if ($user_updated)
            $this->response([
                'status' => TRUE,
                'message' => 'User was updated successfully.'
            ], REST_Controller::HTTP_OK);
        else
            $this->response([
                'status' => FALSE,
                'message' => 'User could not be updated.'
            ], REST_Controller::HTTP_BAD_REQUEST);
    }

    function change_password_post()
    {
        if (!$this->post('email') || !$this->post('token') || !$this->post('old') || !$this->post('new')) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Request.'
            ], REST_Controller::HTTP_BAD_REQUEST);
//            $message = array('success' => 'false');
//            $this->response($message, 400);
        }

        $token = $this->post('token');

        $email = $this->post('email');
        // Remove all illegal characters from email
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->response([
                'status' => FALSE,
                'message' => 'Email address is not valid'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($email);

        if (!$user_id) {
            $this->response([
                'status' => FALSE,
                'message' => 'User not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->load->model('Logged_users_model');
            // Check if user exists in loggedUsers table
            $result = $this->Logged_users_model->getToken($user_id);
            if (!$result) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Session not found.'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            // Check user token
            if ($token != $result) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Unauthorized action: Invalid token.'
                ], REST_Controller::HTTP_UNAUTHORIZED);
            }

            $db_pass = $this->Users_model->get_user_pass_by_email($email);

            if (!$db_pass) {
                $this->response([
                    'status' => FALSE,
                    'message' => "User's password could not be found!"
                ], REST_Controller::HTTP_FORBIDDEN);
            }

            if (md5($this->post('old')) != $db_pass)
                $this->response([
                    'status' => FALSE,
                    'message' => 'Wrong password!',
                ], REST_Controller::HTTP_NOT_ACCEPTABLE);

            $data['password'] = md5($this->post('new'));

            $flag = $this->Users_model->update_user($data, $user_id);

            if ($flag) {
                $this->response([
                    'status' => TRUE,
                    'message' => 'Password changed'
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Database error, try again',
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    function get_statistics_post()
    {
        if (!$this->post('email') || !$this->post('token')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided all the requested data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id) {
            $this->response([
                'status' => FALSE,
                'message' => 'User not found'
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        $this->load->model('Logged_users_model');
        $returned_token = $this->Logged_users_model->getToken($user_id);

        if (!$returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Session not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $token = $this->post('token');

        // Check user's token
        if ($token != $returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action!'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $statistics = $this->Users_model->get_statistics($user_id);

        if ($statistics)
            $this->response([
                'status' => TRUE,
                'message' => 'Statistics returned.',
                'announcements_created' => $statistics['announcements_created'],
                'offers_accepted' => $statistics['offers_accepted'],
                'offers_received' => $statistics['offers_received'],
                'instant_purchases' => $statistics['instant_purchases']
            ], REST_Controller::HTTP_OK);
        else
            $this->response([
                'status' => FALSE,
                'message' => 'Statistics not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
    }
}