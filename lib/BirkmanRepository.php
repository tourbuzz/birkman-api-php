<?php

class BirkmanRepository
{
    /** @var \PDO */
    protected $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param string $birkmanId
     * @param string $slackUsername
     */
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

    /**
     * @param string $birkmanId
     * @param string $birkmanJsonData
     */
    public function updateBirkmanData(string $birkmanId, string $birkmanJsonData)
    {
        $stmt = $this->conn->prepare('
            UPDATE birkman_data SET birkman_data = :birkman_data
            WHERE birkman_id = :birkman_id;
        ');

        $stmt->bindParam(':birkman_id', $birkmanId);
        $stmt->bindParam(':birkman_data', $birkmanJsonData);
        $stmt->execute();
    }

    /**
     * @param string $slackUsername
     * @return NULL|array
     */
    public function fetchByBirkmanId(string $birkmanId)
    {
        $stmt = $this->conn->prepare('
            SELECT * FROM birkman_data WHERE birkman_id = :birkman_id LIMIT 1;
        ');
        $stmt->bindParam(':birkman_id', $birkmanId);
        $stmt->execute();

        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return NULL;
        }

        $record['birkman_data'] = json_decode($record['birkman_data'], true);


        return $record;
    }

    /**
     * @param string $slackUsername
     * @return NULL|array
     */
    public function fetchBySlackUsername(string $slackUsername)
    {
        $stmt = $this->conn->prepare('
            SELECT * FROM birkman_data WHERE slack_username = :slack_username LIMIT 1;
        ');
        $stmt->bindParam(':slack_username', $slackUsername);
        $stmt->execute();

        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return NULL;
        }

        $record['birkman_data'] = json_decode($record['birkman_data'], true);

        return $record;
    }

    public function fetchAll()
    {
        $stmt = $this->conn->prepare('
            SELECT * FROM birkman_data;
        ');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(string $birkmanId)
    {
        $stmt = $this->conn->prepare('
            DELETE FROM birkman_data
            WHERE birkman_id = :birkman_id;
        ');

        $stmt->bindParam(':birkman_id', $birkmanId);
        $stmt->execute();
    }
}
