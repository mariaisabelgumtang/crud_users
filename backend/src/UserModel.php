<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AddressModel.php';

class UserModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->query('SELECT * FROM users ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return null;
        $stmt = $this->pdo->prepare('SELECT * FROM addresses WHERE user_id = :uid');
        $stmt->execute([':uid' => $id]);
        $user['addresses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $user;
    }

    public function create(array $data)
    {
        $this->pdo->beginTransaction();
        try {
            $sql = 'INSERT INTO users (email, first_name, last_name, mobile_number, birth_date) VALUES (:email, :first_name, :last_name, :mobile_number, :birth_date)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':email' => $data['email'],
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':mobile_number' => $data['mobile_number'],
                ':birth_date' => $data['birth_date']
            ]);
            $userId = (int)$this->pdo->lastInsertId();

            // addresses
            if (!empty($data['addresses']) && is_array($data['addresses'])) {
                $addrModel = new AddressModel($this->pdo);
                foreach ($data['addresses'] as $addr) {
                    $addrModel->create($userId, $addr);
                }
            }

            $this->pdo->commit();
            return $this->find($userId);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        $this->pdo->beginTransaction();
        try {
            $sql = 'UPDATE users SET email = :email, first_name = :first_name, last_name = :last_name, mobile_number = :mobile_number, birth_date = :birth_date WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':email' => $data['email'],
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':mobile_number' => $data['mobile_number'],
                ':birth_date' => $data['birth_date'],
                ':id' => $id
            ]);

            // If addresses provided, remove existing and re-add
            if (isset($data['addresses']) && is_array($data['addresses'])) {
                $this->pdo->prepare('DELETE FROM addresses WHERE user_id = :uid')->execute([':uid' => $id]);
                $addrModel = new AddressModel($this->pdo);
                foreach ($data['addresses'] as $addr) {
                    $addrModel->create($id, $addr);
                }
            }

            $this->pdo->commit();
            return $this->find($id);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
