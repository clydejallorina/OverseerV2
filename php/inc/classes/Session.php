<?php

namespace Overseer;

use Exception;
use Overseer\DB\DB;
use Overseer\DTO\SessionDatabaseDTO;

/**
 * Overseer v2 PHP Class: Session
 * 
 * Class specifically made for handling Session-related
 * operations and transactions
 * 
 * PHP Version 8.2
 * 
 * @category Overseer
 * @package  Overseer
 * @author   gristCollector <clydejallorina@gmail.com>
 * @license  http://overseer2.com/license.txt Fail License 2015
 * @link     http://overseer2.com/ Project Site
 */
final class Session
{
    public int $id;
    public string $name;
    public string $creator;
    /** @var list<int> List of IDs of characters in the session */
    public array $members;
    /** @var string bcrypt-encoded password string */
    public string $password;
    public int $battlefieldPower;
    public Atheneum $atheneum;
    /** @var int Character ID of the Grist Exchange's land location */
    public int $exchange;

    private DB $db;

    public function __construct(
        ?DB $db = null,
    ) {
        $this->db = $db ?? new DB();
    }

    /**
     * Generates a new Session in the database
     * 
     * @return Session
     */
    public function generateSession(
        string $sessionName,
        string $sessionPassword,
        string $creatorUsername,
    ): self {
        // Validate that these session details are valid first
        // Will bubble up the exceptions to be caught by whatever page is handling this
        $name = trim($sessionName);
        $password = password_hash(password: $sessionPassword, algo: PASSWORD_BCRYPT);
        $this->validateSessionCreationDetails($name);

        $sessionId = $this->db->insert(
            sqlQuery: <<<'SQL'
                INSERT INTO Sessions (name, password, creator)
                VALUES (?, ?, ?)
                SQL,
            values: [
                $name,
                $password,
                $creatorUsername,
            ],
        );

        return $this->loadSession($sessionId);
    }

    /**
     * Validates session details passed down to generateSession
     * 
     * @throws Exception Throws whenever the creation details are invalid
     */
    public function validateSessionCreationDetails(
        string $sessionName,
    ): void {
        if ($sessionName === '') {
            throw new Exception("Your session's name cannot be blank!");
        }

        $sessionIdsWithSameName = $this->db->fetchFirst(
            sqlQuery: <<<'SQL'
                SELECT * FROM Sessions s
                WHERE s.name = ?
                LIMIT 1
                SQL,
            values: [$sessionName],
            returnClass: SessionDatabaseDTO::class,
        );
        if ($sessionIdsWithSameName !== null) {
            throw new Exception('Sorry, that session name is already taken.');
        }

        return;
    }

    /**
     * Load session details into this object
     * 
     * @throws Exception Thrown when we fail to get the details for this session
     */
    public function loadSession(int $sessionId): self {
        $session = $this->db->fetchFirst(
            sqlQuery: <<<'SQL'
                SELECT * FROM Sessions s
                WHERE s.ID = ?
                LIMIT 1
                SQL,
            values: [$sessionId],
            returnClass: SessionDatabaseDTO::class,
        );

        if ($session === null) {
            throw new Exception("Could not get details for session ID {$sessionId}");
        }

        return $this->loadSessionByObject($session);
    }

    public function loadSessionByObject(SessionDatabaseDTO $session): self {
        $this->id = $session->id;
        $this->name = $session->name;
        $this->creator = $session->creator;
        $this->members = array_map(
            callback: fn (string $member): int => (int)$member,
            array: array_filter(
                array: explode('|', $session->members),
            ),
        );
        $this->password = $session->password;
        $this->battlefieldPower = $session->battlefieldPower;
        $this->atheneum = new Atheneum($session->atheneum);
        $this->exchange = $session->exchange;

        return $this;
    }

    /**
     * Load session details for a character
     * 
     * @throws Exception Thrown when we fail to get the details for this session
     */
    public function loadSessionForCharacterId(int $characterId): self {
        $session = $this->db->fetchFirst(
            sqlQuery: <<<'SQL'
                SELECT s.* FROM `Sessions` s
                JOIN `Characters` c ON c.`session` = s.`ID`
                WHERE c.`ID` = ?
                LIMIT 1
                SQL,
            values: [$characterId],
            returnClass: SessionDatabaseDTO::class,
        );

        if ($session === null) {
            throw new Exception("Could not find session details for character ID {$characterId}");
        }

        return $this->loadSessionByObject($session);
    }

    public function loadSessionBySessionName(string $sessionName): self {
        $session = $this->db->fetchFirst(
            sqlQuery: <<<'SQL'
                SELECT * FROM `Sessions`
                WHERE `name` = ?
                LIMIT 1
                SQL,
            values: [$sessionName],
            returnClass: SessionDatabaseDTO::class,
        );

        if ($session === null) {
            throw new Exception("Could not find session details for session name '{$sessionName}'");
        }

        return $this->loadSessionByObject($session);
    }

    /**
     * Commit the current data contained
     * in this Session object to the database.
     */
    public function commitChanges(): void {}

    // Character-related functions
    /**
     * Add a new character to the session.
     * 
     * THIS ASSUMES THAT THE CHARACTER HAS ALREADY BEEN CREATED
     * AND WILL NOT CHECK IF THE CHARACTER ACTUALLY EXISTS
     */
    public function addCharacter(int $characterId, bool $commitChanges = true): self {
        if (in_array($characterId, $this->members)) {
            // Character is already in the list, take this as a no-op.
            return $this;
        }

        $this->members[] = $characterId;
        
        if ($commitChanges) {
            $this->db->update(
                sqlQuery: <<<'SQL'
                    UPDATE `Sessions` SET members = ? WHERE ID = ?
                    SQL,
                values: [$this->serializeMemberList(), $this->id],
            );
        }

        return $this;
    }

    /**
     * Removes a character from this session.
     * 
     * @throws Exception Throws this exception if the character
     *                   does not exist in this session already
     */
    public function removeCharacter(int $characterId, bool $commitChanges = true): self {
        if (!in_array($characterId, $this->members)) {
            // Character is already not in the list, take this as a no-op.
            return $this;
        }

        $this->members = array_diff($this->members, [$characterId]);
        
        if ($commitChanges) {
            $this->db->update(
                sqlQuery: <<<'SQL'
                    UPDATE `Sessions` SET members = ? WHERE ID = ?
                    SQL,
                values: [$this->serializeMemberList(), $this->id],
            );
        }

        return $this;
    }

    /**
     * Serializes the member list for saving to the database
     */
    public function serializeMemberList(): string {
        return implode('|', $this->members) . '|';
    }

    // Atheneum-related functions
    /**
     * Adds a new item to the atheneum for this session
     */
    public function addItemToAtheneum(AtheneumItem $item, bool $commitChanges = true): self {
        if (isset($this->atheneum[$item->itemId])) {
            // Item is already in the atheneum, treat this as a no-op
            return $this;
        }

        $this->atheneum[$item->itemId] = $item;

        if ($commitChanges) {
            $this->db->update(
                sqlQuery: <<<'SQL'
                    UPDATE `Sessions` SET atheneum = ? WHERE ID = ?
                    SQL,
                values: [$this->atheneum->serialize(), $this->id],
            );
        }

        return $this;
    }

    public function removeItemFromAtheneum(AtheneumItem $item, bool $commitChanges = true): self {
        return $this;
    }

    public function removeItemFromAtheneumByItemId(int $itemId, bool $commitChanges = true): self {
        return $this;
    }
}
