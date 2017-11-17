<?php

namespace Repository;

class RepositoryStub
{
    /**
     * Find someone's birkman user id by his slack user name.
     * @return int birkman user id
     * @throws RecordNotFoundException
     */
    public function findBirkmanUserIdBySlackUsername($slackUsername)
    {
        return substr(md5($slackUsername), 0, 5);
        // if no record found throw new RecordNotFoundException("Record not found. Slack username " . $slackUsername);
    }
}
