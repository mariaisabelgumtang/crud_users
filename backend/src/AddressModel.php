<?php
class AddressModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($userId, array $data)
    {
        $sql = 'INSERT INTO addresses (user_id, street, barangay, city) VALUES (:user_id, :street, :barangay, :city)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':street' => isset($data['street']) ? $data['street'] : null,
            ':barangay' => $data['barangay'],
            ':city' => $data['city']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM addresses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, array $data)
    {
        $sql = 'UPDATE addresses SET street = :street, barangay = :barangay, city = :city WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':street' => isset($data['street']) ? $data['street'] : null,
            ':barangay' => $data['barangay'],
            ':city' => $data['city'],
            ':id' => $id
        ]);
        return $this->find($id);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM addresses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function listByUser($userId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM addresses WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
