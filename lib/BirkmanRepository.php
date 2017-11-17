<?php

class BirkmanRepository
{
    proteted $conn;

    public function construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function createUser(string $birkmanId, string $slackUsername)
    {
        $stmt = $this->conn->prepare('
            INSERT INTO birkman_data (birkman_id, slack_username)
            VALUES (:birkman_id, :slack_username);
        ');

        $stmt->bindParam(':birkman_id', $birkmanId);
        $stmt->bindParam(':slack_username', $slackUsername);
        $stmt->execute();
    }

    public function updateBirkmanData(string $birkmanId, string $birkmanJsonData)
    {
        $stmt = $this->conn->prepare('
            UPDATE birkman_data SET (birkman_data = :birkman_data)
            WHERE birkman_id = :birkman_id;
        ');

        $stmt->bindParam(':birkman_id', $birkmanId);
        $stmt->bindParam(':birkman_data', $birkmanJsonData);
        $stmt->execute();
    }
}
