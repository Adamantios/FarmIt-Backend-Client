<?php

/**
 * Created by PhpStorm.
 * User: Manos
 * Date: 2/3/2016
 * Time: 3:50 μμ
 */
class Addresses_Model extends CI_Model {

    /**
     * Addresses constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Inserts a new address row.
     *
     * @return int or bool: the id of the inserted row or false if not inserted.
     */
    public function insert_address($data) {
        $this->db->insert('addresses', $data);
        $insertedId = $this->db->insert_id();

        if (!$insertedId)
            return FALSE;
        else
            return $insertedId;
    }

    public function get_addresses($id) {
        $query = $this->db->get_where('addresses', array('user_id' => $id));

        if ($query->num_rows() == 0)
            return FALSE;

        else
            return $query->result_array();
    }

    /**
     * Updates an address row.
     *
     * @return true if a row was updated and false if not.
     */
    public function update_address($id, $userId, $alias, $street, $no, $area, $zip, $tel) {
        $data = array(
            'user_id' => $userId,
            'alias' => $alias,
            'street' => $street,
            'no' => $no,
            'area' => $area,
            'zip_code' => $zip,
            'tel' => $tel
        );

        $this->db->where('id', $id);
        $this->db->update('addresses', $data);

        if ($this->db->affected_rows())
            return true;
        else
            return false;
    }

    /**
     * Returns the number of the addresses.
     *
     * @return int: the number of the address rows.
     */
    public function get_number_of_addresses() {
        return $this->db->count_all('addresses');
    }

    /**
     * Returns an address by id.
     *
     * @param $id : the id of the address to return.
     * @return array containing the address.
     */
    public function get_address_by_id($id) {
        $query = $this->db->get_where('addresses', array('id' => $id));
        return $query->row_array();
    }

    /**
     * Returns an address by userId.
     *
     * @param $userId : the user with the address to return.
     * @return array containing the address.
     */
    public function get_address_by_user($userId) {
        $query = $this->db->get_where('addresses', array('user_id' => $userId));
        return $query->row_array();
    }

    /**
     * Returns address(es) by street.
     *
     * @param $street : the street of the address(es) to return.
     * @return array containing the address(es).
     */
    public function get_address_by_street($street) {
        $query = $this->db->get_where('addresses', array('street' => $street));

        $index = 0;
        $response['result'] = NULL;

        if ($query->num_rows() > 0) {
            // get data of each row
            foreach ($query->result_array() as $row) {
                $response['result'][$index] = $row;
                $index++;
            }
        }

        return $response;
    }

    /**
     * Returns address(es) by street number.
     *
     * @param $no : the street number of the address(es) to return.
     * @return array containing the address(es).
     */
    public function get_address_by_no($no) {
        $query = $this->db->get_where('addresses', array('no' => $no));

        $index = 0;
        $response['result'] = NULL;

        if ($query->num_rows() > 0) {
            // get data of each row
            foreach ($query->result_array() as $row) {
                $response['result'][$index] = $row;
                $index++;
            }
        }

        return $response;
    }

    /**
     * Returns address(es) by street and number combination.
     *
     * @param $street : the street of the address(es) to return.
     * @param $no : the street number of the address(es) to return.
     * @return array containing the address(es).
     */
    public function get_address_by_streetNo($street, $no) {
        $query = $this->db->get_where('addresses', array('street' => $street, 'no' => $no));

        $index = 0;
        $response['result'] = NULL;

        if ($query->num_rows() > 0) {
            // get data of each row
            foreach ($query->result_array() as $row) {
                $response['result'][$index] = $row;
                $index++;
            }
        }

        return $response;
    }

    /**
     * Returns address(es) by area.
     *
     * @param $area : the area of the address(es) to return.
     * @return array containing the address(es).
     */
    public function get_address_by_area($area) {
        $query = $this->db->get_where('addresses', array('area' => $area));

        $index = 0;
        $response['result'] = NULL;

        if ($query->num_rows() > 0) {
            // get data of each row
            foreach ($query->result_array() as $row) {
                $response['result'][$index] = $row;
                $index++;
            }
        }

        return $response;
    }

    /**
     * Returns address(es) by zip code.
     *
     * @param $zip : the zip code of the address(es) to return.
     * @return array containing the address(es).
     */
    public function get_address_by_zip($zip) {
        $query = $this->db->get_where('addresses', array('zip_code' => $zip));

        $index = 0;
        $response['result'] = NULL;

        if ($query->num_rows() > 0) {
            // get data of each row
            foreach ($query->result_array() as $row) {
                $response['result'][$index] = $row;
                $index++;
            }
        }

        return $response;
    }

    /**
     * Returns address(es) by telephone number.
     *
     * @param $tel : the telephone number of the address(es) to return.
     * @return array containing the address(es).
     */
    public function get_address_by_tel($tel) {
        $query = $this->db->get_where('addresses', array('tel' => $tel));

        $index = 0;
        $response['result'] = NULL;

        if ($query->num_rows() > 0) {
            // get data of each row
            foreach ($query->result_array() as $row) {
                $response['result'][$index] = $row;
                $index++;
            }
        }

        return $response;
    }

    public function edit_address($data, $user_id)
    {
        $this->db->where('id', $data['id']);
        unset($data['id']);
        $this->db->where('user_id', $user_id);
        $this->db->update('addresses', $data);

        if ($this->db->affected_rows())
            return true;
        else
            return false;
    }

    /**
     * Deletes an address by id.
     *
     * @return true if a row was deleted and false if not.
     */
    public function delete_address_by_id($id, $user_id) {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        $this->db->delete('addresses');

        if ($this->db->affected_rows())
            return true;
        else
            return false;
    }

    /**
     * Deletes an address by user.
     *
     * @return true if a row was deleted and false if not.
     */
    public function delete_address_by_user($userId) {
        $this->db->where('user_id', $userId);
        $this->db->delete('addresses');

        if ($this->db->affected_rows())
            return true;
        else
            return false;
    }

    public function count_addresses($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->from('addresses');

        return $this->db->count_all_results();
    }
}