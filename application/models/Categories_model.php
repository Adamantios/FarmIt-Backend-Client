<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Categories_model extends CI_Model
{
    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
        $this->load->database();
    }

    public function getAllCategories()
    {
        $query = $this->db->get('categories');

        if ($query->num_rows() < 1)
            return FALSE;

        else
            return $query->result();
    }

    /**
     * @param $category_id : the category's id.
     * @return bool
     */
    public function getSubcategories($category_id)
    {
        $this->db->select('id, name');
        $query = $this->db->get_where('subcategories', array('category_id' => $category_id));

        if ($query->num_rows() < 1)
            return FALSE;

        else
            return $query->result();
    }
}
