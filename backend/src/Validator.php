<?php
require_once __DIR__ . '/Database.php';

class Validator
{
    public static function validateUser(array $data, PDO $pdo, $requireAddresses = true, $excludeUserId = null)
    {
        $errors = [];

        // Required fields
        foreach (['first_name', 'last_name', 'email', 'mobile_number', 'birth_date'] as $f) {
            if (!isset($data[$f]) || trim($data[$f]) === '') {
                $errors[$f] = "$f is required";
            }
        }

        if (isset($data['first_name']) && strlen($data['first_name']) < 4) {
            $errors['first_name'] = 'first_name must be at least 4 characters';
        }
        if (isset($data['last_name']) && strlen($data['last_name']) < 4) {
            $errors['last_name'] = 'last_name must be at least 4 characters';
        }

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'invalid email format';
            } else {
                // uniqueness
                $sql = 'SELECT id FROM users WHERE email = :email';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':email' => $data['email']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['id'] != $excludeUserId) {
                    $errors['email'] = 'email already exists';
                }
            }
        }

        if (isset($data['mobile_number']) && !ctype_digit($data['mobile_number'])) {
            $errors['mobile_number'] = 'mobile_number must contain only numeric characters';
        }

        if (isset($data['birth_date'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['birth_date']);
            $now = new DateTime();
            if (!$d || $d->format('Y-m-d') !== $data['birth_date']) {
                $errors['birth_date'] = 'birth_date must be YYYY-MM-DD';
            } else {
                $year = (int)$d->format('Y');
                if ($year < 1950) {
                    $errors['birth_date'] = 'birth_date year must be >= 1950';
                }
                if ($d > $now) {
                    $errors['birth_date'] = 'birth_date cannot be in the future';
                }
            }
        }

        // Addresses check
        if ($requireAddresses) {
            if (!isset($data['addresses']) || !is_array($data['addresses']) || count($data['addresses']) === 0) {
                $errors['addresses'] = 'at least one address is required';
            } else {
                foreach ($data['addresses'] as $i => $addr) {
                    $aErrors = self::validateAddress($addr, $pdo, true);
                    if (!empty($aErrors)) {
                        $errors["addresses.$i"] = $aErrors;
                    }
                }
            }
        }

        return $errors;
    }

    public static function validateAddress(array $data, PDO $pdo = null, $allowMissingUserId = false)
    {
        $errors = [];

        if (!isset($data['barangay']) || trim($data['barangay']) === '') {
            $errors['barangay'] = 'barangay is required';
        } elseif (strlen($data['barangay']) < 3) {
            $errors['barangay'] = 'barangay must be at least 3 characters';
        }

        if (!isset($data['city']) || trim($data['city']) === '') {
            $errors['city'] = 'city is required';
        } elseif (strlen($data['city']) < 3) {
            $errors['city'] = 'city must be at least 3 characters';
        }

        if (!$allowMissingUserId) {
            if (!isset($data['user_id'])) {
                $errors['user_id'] = 'user_id is required';
            } elseif ($pdo) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id');
                $stmt->execute([':id' => $data['user_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    $errors['user_id'] = 'user_id does not exist';
                }
            }
        }

        return $errors;
    }
}
