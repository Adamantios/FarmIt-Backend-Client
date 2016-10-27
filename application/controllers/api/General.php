<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property General_model $General_model
 */
class General extends REST_Controller
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
    }

    public function get_server_date_time_post()
    {
        $this->load->model('General_model');
        $time = $this->General_model->get_DB_Time();

        $this->response([
            'status' => TRUE,
            'dateTime' => $time
        ], REST_Controller::HTTP_OK);
    }
}