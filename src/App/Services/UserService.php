<?php

namespace App\Services;

class UserService extends BaseService
{
    public function getAll()
    {
        return $this->db->fetchAll('
            SELECT  *
            FROM    user
        ');
    }

    public function getBy(array $data)
    {
        return $this->db->fetchAll('
            SELECT  *
            FROM    user
            WHERE   '.$data[0].' = "'.$data[1].'"
        ');
    }

    function save($user)
    {
        $this->db->insert('user', $user);

        return $this->db->lastInsertId();
    }

    function update($id, $user)
    {
        return $this->db->update('user', $user, ['id' => $id]);
    }

    function delete($id)
    {
        return $this->db->delete('user', ['id' => $id]);
    }
}
